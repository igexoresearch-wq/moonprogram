<?php

/**
 * Central aerodynamic / reentry model.
 *
 * Outputs signed acceleration vectors:
 *   $drag_acc_x/y/z
 *   $lift_acc_x/y/z
 *
 * These are added directly to propulsion acceleration by physics/step.php.
 */

function moonSimAtmosphere(float $altitude): array
{
    if ($altitude < 0.0) {
        $altitude = 0.0;
    }

    if ($altitude >= 120000.0) {
        return ['density'=>0.0, 'temperature'=>360.0, 'speed_of_sound'=>380.0];
    }

    // Density/temperature reference points approximating the U.S. Standard
    // Atmosphere through the entry region. Density is log-interpolated.
    $table = [
        [0.0,      1.2250000, 288.15],
        [10000.0,  0.4135100, 223.15],
        [20000.0,  0.0889100, 216.65],
        [30000.0,  0.0184100, 226.65],
        [40000.0,  0.0039960, 250.35],
        [50000.0,  0.0010270, 270.65],
        [60000.0,  0.0003097, 247.00],
        [70000.0,  0.00008283,219.60],
        [80000.0,  0.00001846,198.60],
        [90000.0,  0.000003416,186.90],
        [100000.0, 0.000000560,195.00],
        [110000.0, 0.000000097,240.00],
        [120000.0, 0.000000022,360.00],
    ];

    for ($i=0; $i<count($table)-1; $i++) {
        [$h0,$rho0,$t0] = $table[$i];
        [$h1,$rho1,$t1] = $table[$i+1];

        if ($altitude >= $h0 && $altitude <= $h1) {
            $f = ($altitude-$h0)/($h1-$h0);
            $logRho = log($rho0) + (log($rho1)-log($rho0))*$f;
            $temperature = $t0 + ($t1-$t0)*$f;
            $speedOfSound = sqrt(1.4 * 287.05287 * $temperature);

            return [
                'density'=>exp($logRho),
                'temperature'=>$temperature,
                'speed_of_sound'=>$speedOfSound,
            ];
        }
    }

    return ['density'=>0.0, 'temperature'=>360.0, 'speed_of_sound'=>380.0];
}

function moonSimApolloAeroCoefficients(float $mach): array
{
    // Apollo 11 CM trim aerodynamic coefficients. Between tabulated Mach
    // points, linear interpolation is adequate for this simulator fidelity.
    $table = [
        [0.4,  0.24465, 0.85300],
        [0.7,  0.26325, 0.98542],
        [0.9,  0.32074, 1.10652],
        [1.1,  0.49373, 1.16970],
        [1.2,  0.47853, 1.15600],
        [1.35, 0.56282, 1.27880],
        [1.65, 0.55002, 1.26570],
        [2.0,  0.53247, 1.27210],
        [2.4,  0.50740, 1.24120],
        [3.0,  0.47883, 1.21670],
        [4.0,  0.44147, 1.21480],
        [10.0, 0.42856, 1.22460],
        [29.5, 0.38773, 1.28910],
    ];

    if ($mach <= $table[0][0]) {
        return ['cl'=>$table[0][1], 'cd'=>$table[0][2]];
    }

    for ($i=0; $i<count($table)-1; $i++) {
        [$m0,$cl0,$cd0] = $table[$i];
        [$m1,$cl1,$cd1] = $table[$i+1];

        if ($mach >= $m0 && $mach <= $m1) {
            $f = ($mach-$m0)/($m1-$m0);
            return [
                'cl'=>$cl0+($cl1-$cl0)*$f,
                'cd'=>$cd0+($cd1-$cd0)*$f,
            ];
        }
    }

    $last = $table[count($table)-1];
    return ['cl'=>$last[1], 'cd'=>$last[2]];
}

function moonSimCross(array $a, array $b): array
{
    return [
        $a[1]*$b[2]-$a[2]*$b[1],
        $a[2]*$b[0]-$a[0]*$b[2],
        $a[0]*$b[1]-$a[1]*$b[0],
    ];
}

function moonSimDot(array $a, array $b): float
{
    return $a[0]*$b[0]+$a[1]*$b[1]+$a[2]*$b[2];
}

function moonSimUnit(array $v): array
{
    $n = sqrt(moonSimDot($v,$v));
    return $n > 1.0e-12
        ? [$v[0]/$n,$v[1]/$n,$v[2]/$n]
        : [0.0,0.0,0.0];
}

function moonSimEnsureReentryState(mysqli $conn): void
{
    $conn->query(
        "CREATE TABLE IF NOT EXISTS reentry_state (
            id TINYINT UNSIGNED NOT NULL,
            chute_state ENUM('STOWED','DROGUE','MAIN_REEFED','MAIN','FAILED')
                NOT NULL DEFAULT 'STOWED',
            chute_timer_seconds DOUBLE NOT NULL DEFAULT 0,
            density_kg_m3 DOUBLE NOT NULL DEFAULT 0,
            mach DOUBLE NOT NULL DEFAULT 0,
            dynamic_pressure_pa DOUBLE NOT NULL DEFAULT 0,
            heat_flux_w_m2 DOUBLE NOT NULL DEFAULT 0,
            heat_load_j_m2 DOUBLE NOT NULL DEFAULT 0,
            aero_deceleration_g DOUBLE NOT NULL DEFAULT 0,
            updated_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
            PRIMARY KEY (id)
        ) ENGINE=InnoDB"
    );

    $conn->query(
        "INSERT INTO reentry_state (id)
         VALUES (1)
         ON DUPLICATE KEY UPDATE id=id"
    );
}

moonSimEnsureReentryState($conn);

$drag_acc_x = 0.0;
$drag_acc_y = 0.0;
$drag_acc_z = 0.0;
$lift_acc_x = 0.0;
$lift_acc_y = 0.0;
$lift_acc_z = 0.0;
$drag_acc_net = 0.0;

$velocity = [
    (float)$delta_xCM,
    (float)$delta_yCM,
    (float)$delta_zCM,
];
$speed = sqrt(moonSimDot($velocity,$velocity));

$atmosphere = moonSimAtmosphere((float)$altitude);
$air_density = (float)$atmosphere['density'];
$speed_of_sound = max(1.0, (float)$atmosphere['speed_of_sound']);
$mach = $speed/$speed_of_sound;
$dynamicPressure = 0.5*$air_density*$speed*$speed;

$mass = max(1.0,(float)$totalSaturnV);
$vHat = moonSimUnit($velocity);

$area = 0.0;
$cd = 0.0;
$cl = 0.0;

// Launch vehicle / CSM / entry capsule configurations.
if ((int)$stage1CM === 1 || (int)$stage2CM === 1 || (int)$stage3CM === 1) {
    $area = M_PI*(10.1/2.0)**2; // Saturn V diameter
    $cd = 0.40;
} elseif ((int)$service_moduleCM === 1) {
    $area = M_PI*(3.91/2.0)**2;
    $cd = 0.75;
} elseif ((int)$escapeCM === 0) {
    $area = M_PI*(3.91/2.0)**2;
    $coeff = moonSimApolloAeroCoefficients($mach);
    $cd = $coeff['cd'];
    $cl = $coeff['cl'];
}

$dragMagnitude = 0.0;
$liftMagnitude = 0.0;

if ($air_density > 0.0 && $speed > 0.01 && $area > 0.0) {
    $dragForce = $dynamicPressure*$cd*$area;
    $liftForce = $dynamicPressure*$cl*$area;

    $dragMagnitude = $dragForce/$mass;
    $liftMagnitude = $liftForce/$mass;

    $drag_acc_x = -$vHat[0]*$dragMagnitude;
    $drag_acc_y = -$vHat[1]*$dragMagnitude;
    $drag_acc_z = -$vHat[2]*$dragMagnitude;

    // Apollo CM lifting entry: offset CG creates a fixed-magnitude lift vector;
    // roll/bank rotates that vector around the relative-wind velocity vector.
    if ((int)$service_moduleCM === 0 && (int)$escapeCM === 0 && $cl > 0.0) {
        $radial = moonSimUnit([
            (float)$xCM-(float)$earth_center_x,
            (float)$yCM-(float)$earth_center_y,
            (float)$zCM-(float)$earth_center_z,
        ]);

        // Project local-up into the plane perpendicular to velocity.
        $projection = moonSimDot($radial,$vHat);
        $liftUp = moonSimUnit([
            $radial[0]-$projection*$vHat[0],
            $radial[1]-$projection*$vHat[1],
            $radial[2]-$projection*$vHat[2],
        ]);

        $side = moonSimUnit(moonSimCross($vHat,$liftUp));
        $bank = deg2rad((float)$rollCM);

        $liftDir = [
            cos($bank)*$liftUp[0] + sin($bank)*$side[0],
            cos($bank)*$liftUp[1] + sin($bank)*$side[1],
            cos($bank)*$liftUp[2] + sin($bank)*$side[2],
        ];

        $lift_acc_x = $liftDir[0]*$liftMagnitude;
        $lift_acc_y = $liftDir[1]*$liftMagnitude;
        $lift_acc_z = $liftDir[2]*$liftMagnitude;
    }
}

// Earth landing system state.
$stateResult = $conn->query(
    "SELECT chute_state, chute_timer_seconds, heat_load_j_m2
     FROM reentry_state
     WHERE id=1"
);
$stateRow = $stateResult ? $stateResult->fetch_assoc() : null;

$chuteState = $stateRow['chute_state'] ?? 'STOWED';
$chuteTimer = (float)($stateRow['chute_timer_seconds'] ?? 0.0);
$heatLoad = (float)($stateRow['heat_load_j_m2'] ?? 0.0);

$nextChuteState = $chuteState;
$nextChuteTimer = $chuteTimer + 1.0;

$entryCapsule =
    (int)$service_moduleCM === 0
    && (int)$escapeCM === 0;

if (!$entryCapsule) {
    $nextChuteState = 'STOWED';
    $nextChuteTimer = 0.0;
} elseif ((int)$parachutes_deployedCM === 1) {
    if ($chuteState === 'STOWED' && (float)$altitude <= 7315.0) {
        if ($speed > 300.0) {
            $nextChuteState = 'FAILED';
            $nextChuteTimer = 0.0;
        } elseif ($speed <= 200.0) {
            $nextChuteState = 'DROGUE';
            $nextChuteTimer = 0.0;
        }
    }

    if (
        $chuteState === 'DROGUE'
        && (float)$altitude <= 3350.0
        && $speed <= 90.0
    ) {
        $nextChuteState = 'MAIN_REEFED';
        $nextChuteTimer = 0.0;
    }

    if ($chuteState === 'MAIN_REEFED' && $chuteTimer >= 10.0) {
        $nextChuteState = 'MAIN';
        $nextChuteTimer = 0.0;
    }
}

// Add parachute drag as real aerodynamic force instead of multiplying velocity.
$chuteArea = 0.0;
$chuteCd = 1.5;

if ($nextChuteState === 'DROGUE') {
    $diameter = 16.5*0.3048;
    $chuteArea = 2.0*M_PI*($diameter/2.0)**2;
} elseif ($nextChuteState === 'MAIN_REEFED') {
    $diameter = 83.5*0.3048;
    $fullArea = 3.0*M_PI*($diameter/2.0)**2;
    $chuteArea = 0.25*$fullArea;
} elseif ($nextChuteState === 'MAIN') {
    $diameter = 83.5*0.3048;
    $chuteArea = 3.0*M_PI*($diameter/2.0)**2;
}

if ($chuteArea > 0.0 && $air_density > 0.0 && $speed > 0.01) {
    $chuteDrag = $dynamicPressure*$chuteCd*$chuteArea/$mass;

    $drag_acc_x += -$vHat[0]*$chuteDrag;
    $drag_acc_y += -$vHat[1]*$chuteDrag;
    $drag_acc_z += -$vHat[2]*$chuteDrag;

    $dragMagnitude += $chuteDrag;
}

// Sutton-Graves-style convective heating approximation used as a central
// heat-flux/heat-load signal. The heat-shield failure model can be calibrated
// later without changing trajectory physics.
$heatFlux = 0.0;
if ($entryCapsule && $air_density > 0.0 && $speed > 0.0) {
    $heatFlux = 1.83e-4*sqrt($air_density)*($speed**3);
}
$heatLoad += $heatFlux;

$sigma = 5.670374419e-8;
$emissivity = 0.90;
$temp = $heatFlux > 0.0
    ? (($heatFlux/($sigma*$emissivity))**0.25)
    : 0.0;

$aeroMagnitude = sqrt(
    $drag_acc_x**2+$drag_acc_y**2+$drag_acc_z**2
    +$lift_acc_x**2+$lift_acc_y**2+$lift_acc_z**2
);
$aeroG = $aeroMagnitude/9.80665;

$drag_acc_net = sqrt(
    $drag_acc_x**2+$drag_acc_y**2+$drag_acc_z**2
);

$stmt = $conn->prepare(
    "UPDATE reentry_state
     SET chute_state=?,
         chute_timer_seconds=?,
         density_kg_m3=?,
         mach=?,
         dynamic_pressure_pa=?,
         heat_flux_w_m2=?,
         heat_load_j_m2=?,
         aero_deceleration_g=?,
         updated_at=NOW(6)
     WHERE id=1"
);
$stmt->bind_param(
    'sddddddd',
    $nextChuteState,
    $nextChuteTimer,
    $air_density,
    $mach,
    $dynamicPressure,
    $heatFlux,
    $heatLoad,
    $aeroG
);
$stmt->execute();

?>
