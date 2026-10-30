<?php
declare(strict_types=1);

require __DIR__ . '/../shared/physical_constants.php';
require __DIR__ . '/../shared/orbital_mechanics.php';
require __DIR__ . '/../physics/integrator.php';

$earth = [$earth_center_x, $earth_center_y, $earth_center_z];
$mu = $gravitational_constant * $earth_mass;
$dt = 0.1;

function advanceEarthOnlyVerlet(
    array $position,
    array $velocity,
    array $earth,
    float $mu,
    float $dt
): array {
    $a0 = moonSimGravityAcceleration(
        $position,
        $earth,
        [0.0,0.0,0.0],
        $mu,
        0.0
    );

    $newPosition = [
        $position[0] + $velocity[0]*$dt + 0.5*$a0[0]*$dt*$dt,
        $position[1] + $velocity[1]*$dt + 0.5*$a0[1]*$dt*$dt,
        $position[2] + $velocity[2]*$dt + 0.5*$a0[2]*$dt*$dt,
    ];

    $a1 = moonSimGravityAcceleration(
        $newPosition,
        $earth,
        [0.0,0.0,0.0],
        $mu,
        0.0
    );

    $newVelocity = [
        $velocity[0] + 0.5*($a0[0]+$a1[0])*$dt,
        $velocity[1] + 0.5*($a0[1]+$a1[1])*$dt,
        $velocity[2] + 0.5*($a0[2]+$a1[2])*$dt,
    ];

    return [$newPosition,$newVelocity];
}

function relativeDrift(float $value, float $baseline): float
{
    return abs($value-$baseline)/max(abs($baseline),1.0e-15);
}

function runOrbitRegression(
    string $label,
    array $position,
    array $velocity,
    array $earth,
    float $mu,
    float $dt,
    float $duration
): array {
    $initial=moonSimOrbitalElements($position,$velocity,$earth,$mu);
    $max=[
        'energy'=>0.0,
        'h'=>0.0,
        'e'=>0.0,
        'a'=>0.0,
        'rp'=>0.0,
        'ra'=>0.0,
    ];

    $steps=(int)ceil($duration/$dt);

    for($i=0;$i<$steps;$i++){
        $h=min($dt,$duration-$i*$dt);
        if($h<=0.0){break;}

        [$position,$velocity]=advanceEarthOnlyVerlet(
            $position,$velocity,$earth,$mu,$h
        );

        if(($i%100)===0 || $i===$steps-1){
            $el=moonSimOrbitalElements($position,$velocity,$earth,$mu);

            $max['energy']=max(
                $max['energy'],
                relativeDrift($el['specific_energy'],$initial['specific_energy'])
            );
            $max['h']=max(
                $max['h'],
                relativeDrift($el['angular_momentum'],$initial['angular_momentum'])
            );
            $max['e']=max(
                $max['e'],
                abs($el['eccentricity']-$initial['eccentricity'])
            );
            $max['a']=max(
                $max['a'],
                relativeDrift($el['semi_major_axis'],$initial['semi_major_axis'])
            );

            if($initial['periapsis_radius']!==null && $el['periapsis_radius']!==null){
                $max['rp']=max(
                    $max['rp'],
                    relativeDrift($el['periapsis_radius'],$initial['periapsis_radius'])
                );
            }
            if($initial['apoapsis_radius']!==null && $el['apoapsis_radius']!==null){
                $max['ra']=max(
                    $max['ra'],
                    relativeDrift($el['apoapsis_radius'],$initial['apoapsis_radius'])
                );
            }
        }
    }

    printf(
        "%s\n  radius %.3f km | speed %.3f m/s | e %.9f | a %.3f km\n",
        $label,
        $initial['radius']/1000.0,
        $initial['speed'],
        $initial['eccentricity'],
        $initial['semi_major_axis']/1000.0
    );
    printf(
        "  max drift energy %.3e | h %.3e | e(abs) %.3e | a %.3e | rp %.3e | ra %.3e\n",
        $max['energy'],
        $max['h'],
        $max['e'],
        $max['a'],
        $max['rp'],
        $max['ra']
    );

    return $max;
}

// Circular 185 km LEO.
$rCircular=$earth_radius+185000.0;
$posCircular=[$earth_center_x+$rCircular,$earth_center_y,$earth_center_z];
$velCircular=[0.0,sqrt($mu/$rCircular),0.0];
$periodCircular=2.0*M_PI*sqrt(($rCircular**3)/$mu);

$circular=runOrbitRegression(
    'Earth-only circular 185 km orbit',
    $posCircular,
    $velCircular,
    $earth,
    $mu,
    $dt,
    3.0*$periodCircular
);

// Elliptical 185 x 1000 km orbit, initialized at perigee.
$rp=$earth_radius+185000.0;
$ra=$earth_radius+1000000.0;
$a=0.5*($rp+$ra);
$posElliptical=[$earth_center_x+$rp,$earth_center_y,$earth_center_z];
$velElliptical=[0.0,moonSimVisVivaSpeed($mu,$rp,$a),0.0];
$periodElliptical=2.0*M_PI*sqrt(($a**3)/$mu);

$elliptical=runOrbitRegression(
    'Earth-only elliptical 185 x 1000 km orbit',
    $posElliptical,
    $velElliptical,
    $earth,
    $mu,
    $dt,
    3.0*$periodElliptical
);

$relativeTolerance=1.0e-8;
$eccentricityAbsoluteTolerance=1.0e-8;
$failed=false;

foreach([$circular,$elliptical] as $metrics){
    if(
        $metrics['energy']>$relativeTolerance
        || $metrics['h']>$relativeTolerance
        || $metrics['a']>$relativeTolerance
        || $metrics['rp']>$relativeTolerance
        || $metrics['ra']>$relativeTolerance
        || $metrics['e']>$eccentricityAbsoluteTolerance
    ){
        $failed=true;
    }
}

if($failed){
    fwrite(STDERR,"FAIL: orbital conservation regression exceeded tolerance.\n");
    exit(1);
}

echo "PASS: circular and elliptical orbital invariants stayed within tolerance.\n";
?>