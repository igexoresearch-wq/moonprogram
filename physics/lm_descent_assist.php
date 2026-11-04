<?php
declare(strict_types=1);
/**
 * Optional LM descent-assist throttle controller.
 * The pilot retains attitude control. This controller only changes throttle;
 * it cannot steer the LM, stage the vehicle, or teleport its position.
 */
function moonSimEnsureLmAssist(mysqli $conn): void {
    static $ready = false;
    if ($ready) return;
    $conn->query("CREATE TABLE IF NOT EXISTS lm_descent_assist (
        id TINYINT UNSIGNED NOT NULL PRIMARY KEY,
        enabled TINYINT(1) NOT NULL DEFAULT 0,
        attitude_assist TINYINT(1) NOT NULL DEFAULT 0,
        status VARCHAR(100) NOT NULL DEFAULT 'MANUAL',
        altitude_m DOUBLE NOT NULL DEFAULT 0,
        radial_speed_m_s DOUBLE NOT NULL DEFAULT 0,
        tangential_speed_m_s DOUBLE NOT NULL DEFAULT 0,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB");
    $conn->query("INSERT IGNORE INTO lm_descent_assist(id) VALUES (1)");
    $column=$conn->query("SHOW COLUMNS FROM lm_descent_assist LIKE 'attitude_assist'");
    if($column && $column->num_rows===0)$conn->query("ALTER TABLE lm_descent_assist ADD COLUMN attitude_assist TINYINT(1) NOT NULL DEFAULT 0");
    $ready = true;
}

function moonSimLmDescentAssistStep(mysqli $conn): void {
    moonSimEnsureLmAssist($conn);
    $result = $conn->query("SELECT enabled,attitude_assist FROM lm_descent_assist WHERE id=1");
    $control = $result?$result->fetch_assoc():null;
    $enabled = $control && (int)($control['enabled'] ?? 0) === 1;
    $attitudeAssist = $control && (int)($control['attitude_assist'] ?? 0) === 1;
    if (!$enabled) return;

    $read = static function (string $table, array $keys) use ($conn): array {
        $out = [];
        $keysSql = implode(',', array_map(
            static fn(string $key): string => "'" . $conn->real_escape_string($key) . "'",
            $keys
        ));
        $result = $conn->query("SELECT attribute,value FROM $table WHERE attribute IN ($keysSql)");
        if ($result) while ($row=$result->fetch_assoc()) $out[$row['attribute']] = (float)$row['value'];
        return $out;
    };
    $lm=$read('telemetry',['x','y','z','delta_x','delta_y','delta_z','descent','fuel','crash']);
    $moon=$read('moon',['x','y','z','delta_x','delta_y','delta_z']);
    if (count($lm)<9 || count($moon)<6) return;

    $dx=$lm['x']-$moon['x']; $dy=$lm['y']-$moon['y']; $dz=$lm['z']-$moon['z'];
    $r=sqrt($dx*$dx+$dy*$dy+$dz*$dz);
    if ($r<1.0) return;
    // Load constants in this function scope; include_once would not rebind globals.
    include __DIR__.'/../shared/physical_constants.php';
    $alt=$r-$moon_radius;
    $normal=[$dx/$r,$dy/$r,$dz/$r];
    $relative=[$lm['delta_x']-$moon['delta_x'],$lm['delta_y']-$moon['delta_y'],$lm['delta_z']-$moon['delta_z']];
    $radial=$relative[0]*$normal[0]+$relative[1]*$normal[1]+$relative[2]*$normal[2];
    $tangential=sqrt(max(0.0,array_sum(array_map(static fn($v)=>$v*$v,$relative))-$radial*$radial));
    $status='DESCENT ASSIST';
    $throttle=0.0;
    if ((int)$lm['crash']===1 || $lm['fuel']<=0 || (int)$lm['descent']!==1) {
        $status='INHIBITED: VEHICLE/STAGE/FUEL';
        $enabled=false;
    } elseif ($alt<0.0) {
        $status='SURFACE CONTACT';
        $enabled=false;
    } else {
        // Pilot must point the descent engine near local-up. Do not apply
        // thrust in the wrong direction based on radial speed alone.
        require_once __DIR__.'/attitude.php';
        $q=moonSimLoadVehicleQuaternion($conn,'LM');
        $forward=moonSimRotateBodyVector($q,[1.0,0.0,0.0]);
        $alignment=$forward[0]*$normal[0]+$forward[1]*$normal[1]+$forward[2]*$normal[2];
        if($attitudeAssist){
            require_once __DIR__.'/../shared/ascent_guidance_math.php';
            $horizontal=[$relative[0]-$radial*$normal[0],$relative[1]-$radial*$normal[1],$relative[2]-$radial*$normal[2]];
            $horizontalMagnitude=sqrt(array_sum(array_map(static fn($x)=>$x*$x,$horizontal)));
            $desired=$normal;
            if($horizontalMagnitude>0.3){
                $brakeFraction=max(0.0,min(0.65,$horizontalMagnitude/100.0));
                for($i=0;$i<3;$i++)$desired[$i]=$normal[$i]*(1-$brakeFraction)-$horizontal[$i]/$horizontalMagnitude*$brakeFraction;
            }
            $rates=moonSimAscentBodyRates($q,$desired,2.0);
            $pr=sprintf('%.6f',$rates['pitch_rate']);$yr=sprintf('%.6f',$rates['yaw_rate']);
            $conn->query("UPDATE telemetry SET value=CASE attribute WHEN 'pitch_rate' THEN $pr WHEN 'yaw_rate' THEN $yr ELSE 0 END WHERE attribute IN ('pitch_rate','yaw_rate','roll_rate')");
        }
        if ($alignment<0.85) {
            $status='HOLD: POINT ENGINE UP (<32 DEG ERROR)';
            $throttle=0.0;
        } elseif ($tangential>12.0 && $alt<500.0) {
            $status='WARNING: EXCESSIVE LATERAL SPEED';
            $throttle=0.0;
        } else {
            // Height-dependent descent target, then a radial-speed PD command.
            $target=-min(25.0,max(0.8,sqrt(max(0.0,$alt))*0.36));
            $gravity=$gravitational_constant*$moon_mass/($r*$r);
            $desiredAcc=$gravity+max(-3.0,min(3.0,0.6*($target-$radial)));
            // Based on the existing DPS design-command scale. Actual
            // minimum/fixed-full throttle is handled by physics/thrust.php.
            $massApprox=$lander_ascent_empty_mass+$lander_descent_empty_mass
                +$lm_rcs_initial_propellant+max(0.0,$lm['fuel']);
            $throttle=max(0.0,min(60.0,100.0*$desiredAcc*$massApprox/$lander_descent_design_thrust));
            if ($alt<6.0 && $radial>-1.6 && $tangential<2.0) {
                $throttle=0.0;
                $status='CONTACT APPROACH: ENGINE IDLE';
            }
        }
    }

    $stmt=$conn->prepare("UPDATE lm_descent_assist SET enabled=?, status=?, altitude_m=?, radial_speed_m_s=?, tangential_speed_m_s=? WHERE id=1");
    $on=$enabled?1:0;
    $stmt->bind_param('isddd',$on,$status,$alt,$radial,$tangential);
    $stmt->execute();
    $command=$enabled?$throttle:0.0;
    $stmt=$conn->prepare("UPDATE telemetry SET value=? WHERE attribute='thrust'");
    $stmt->bind_param('d',$command);
    $stmt->execute();
}
