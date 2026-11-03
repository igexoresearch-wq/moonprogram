<?php
declare(strict_types=1);

/**
 * CLI: php tests/orbital_propagation_checks.php
 * Uses the production gravity and Verlet kernels, without a database.
 * The primary tests are zero-thrust two-body invariants. The moving-Moon
 * test explicitly avoids treating spacecraft two-body energy as conserved
 * in the time-dependent three-body field.
 */
require_once __DIR__.'/../shared/nbody_gravity.php';
require_once __DIR__.'/../shared/verlet_kernel.php';
require_once __DIR__.'/../shared/orbital_mechanics.php';
require_once __DIR__.'/../shared/ascent_guidance_math.php';

function verifyOrbit(bool $ok,string $label):void {
    if(!$ok)throw new RuntimeException('FAIL: '.$label);
    echo 'PASS '.$label.PHP_EOL;
}
function relativeError(float $value,float $expected):float {
    return abs($value-$expected)/max(1.0,abs($expected));
}
function integrateCraft(array $p,array $v,array $earth,array $moon,
    float $earthMu,float $moonMu,float $dt):array {
    $a0=moonSimGravityAcceleration($p,$earth,$moon,$earthMu,$moonMu);
    $pn=moonSimVerletDrift($p,$v,$a0,$dt);
    $a1=moonSimGravityAcceleration($pn,$earth,$moon,$earthMu,$moonMu);
    return [$pn,moonSimVerletKick($v,$a0,$a1,$dt)];
}
$earth=[10000000000.0,10000000000.0,10000000000.0];
$earthRadius=6371000.0;
$mu=3.986004418e14;$moonMu=4.9048695e12;
$moon=[10384400000.0,10000000000.0,10000000000.0];
$zero=[0.0,0.0,0.0];
$radius=$earthRadius+180000.0;
$vCircular=sqrt($mu/$radius);
$p0=[$earth[0]+$radius,$earth[1],$earth[2]];
$v0=[0.0,$vCircular,0.0];
$period=2.0*M_PI*sqrt($radius**3/$mu);
$initial=moonSimOrbitalElements($p0,$v0,$earth,$mu);
verifyOrbit($initial['periapsis_radius']!==null
    && relativeError($initial['periapsis_radius'],$radius)<1e-12,
    'Analytical circular Earth periapsis');
verifyOrbit($initial['specific_energy']<0,'Earth orbit has negative specific energy');
verifyOrbit(moonSimParkingOrbitReached($p0,$v0,$earth,$mu,$earthRadius,
    160000.0,170000.0,200.0),'Initial parking orbit accepted');
verifyOrbit(!moonSimParkingOrbitReached($p0,[0.0,$vCircular*0.5,0.0],
    $earth,$mu,$earthRadius,160000.0,170000.0,200.0),
    'Suborbital trajectory rejected as parking orbit');

$p=$p0;$v=$v0;$maxEnergyDrift=0.0;$maxAngularDrift=0.0;$maxRadiusDrift=0.0;
$h0=$initial['angular_momentum'];
$n=(int)round(2.0*$period/0.1);
for($i=0;$i<$n;$i++) {
    [$p,$v]=integrateCraft($p,$v,$earth,$moon,$mu,0.0,0.1);
    if($i%100===0 || $i===$n-1) {
        $e=moonSimOrbitalElements($p,$v,$earth,$mu);
        $maxEnergyDrift=max($maxEnergyDrift,relativeError($e['specific_energy'],$initial['specific_energy']));
        $maxAngularDrift=max($maxAngularDrift,relativeError($e['angular_momentum'],$h0));
        $maxRadiusDrift=max($maxRadiusDrift,abs($e['radius']-$radius));
    }
}
verifyOrbit($maxEnergyDrift<1e-8,'Earth two-body specific orbital energy drift < 1e-8');
verifyOrbit($maxAngularDrift<1e-8,'Earth two-body angular momentum drift < 1e-8');
verifyOrbit($maxRadiusDrift<100.0,'180-km circular orbit radial drift < 100 m over two revolutions');
verifyOrbit(moonSimParkingOrbitReached($p,$v,$earth,$mu,$earthRadius,
    160000.0,170000.0,200.0),'Parking orbit persists through two revolutions');
echo 'MEASURE circular_max_energy_relative_drift='.$maxEnergyDrift.PHP_EOL;
echo 'MEASURE circular_max_angular_relative_drift='.$maxAngularDrift.PHP_EOL;
echo 'MEASURE circular_max_radius_drift_m='.$maxRadiusDrift.PHP_EOL;

// An eccentric orbit must retain its energy, angular momentum and turning radii.
$rp=$earthRadius+160000.0;$ra=$earthRadius+320000.0;
$a=0.5*($rp+$ra);
$vp=sqrt($mu*(2.0/$rp-1.0/$a));
$p=[$earth[0]+$rp,$earth[1],$earth[2]];$v=[0.0,$vp,0.0];
$start=moonSimOrbitalElements($p,$v,$earth,$mu);
verifyOrbit(relativeError($start['apoapsis_radius'],$ra)<1e-10,
    'Elliptic osculating apoapsis matches vis-viva reference');
$minAltitude=INF;$maxAltitude=0.0;
$n=(int)round((2.0*M_PI*sqrt($a**3/$mu))/0.5);
for($i=0;$i<$n;$i++) {
    [$p,$v]=integrateCraft($p,$v,$earth,$moon,$mu,0.0,0.5);
    $r=moonSimVectorMagnitude(moonSimRelativeVector($p,$earth));
    $minAltitude=min($minAltitude,$r-$earthRadius);
    $maxAltitude=max($maxAltitude,$r-$earthRadius);
}
$finish=moonSimOrbitalElements($p,$v,$earth,$mu);
verifyOrbit(abs($minAltitude-160000.0)<100.0,'Elliptic perigee stays within 100 m');
verifyOrbit(abs($maxAltitude-320000.0)<100.0,'Elliptic apogee stays within 100 m');
verifyOrbit(relativeError($finish['specific_energy'],$start['specific_energy'])<1e-7,
    'Elliptic orbital energy remains bounded');
echo 'MEASURE ellipse_min_altitude_m='.$minAltitude.PHP_EOL;
echo 'MEASURE ellipse_max_altitude_m='.$maxAltitude.PHP_EOL;

// Moon: moving natural body under Earth gravity. Verify its independent
// Earth-relative energy and orbital radius across a full day.
$moonP=$moon;$moonV=[0.0,sqrt($mu/384400000.0),0.0];
$initialMoon=moonSimOrbitalElements($moonP,$moonV,$earth,$mu);
$moonEnergyDrift=0.0;$moonRadiusDrift=0.0;
for($i=0;$i<86400;$i++) {
    $a0=moonSimMoonAcceleration($moonP,$earth,$mu);
    $moonNext=moonSimVerletDrift($moonP,$moonV,$a0,1.0);
    $a1=moonSimMoonAcceleration($moonNext,$earth,$mu);
    $moonV=moonSimVerletKick($moonV,$a0,$a1,1.0);
    $moonP=$moonNext;
    if($i%1000===0 || $i===86399) {
        $m=moonSimOrbitalElements($moonP,$moonV,$earth,$mu);
        $moonEnergyDrift=max($moonEnergyDrift,relativeError($m['specific_energy'],$initialMoon['specific_energy']));
        $moonRadiusDrift=max($moonRadiusDrift,abs($m['radius']-384400000.0));
    }
}
verifyOrbit($moonEnergyDrift<1e-8,'Moon Earth-centered orbital energy stable for 24 h');
verifyOrbit($moonRadiusDrift<100.0,'Moon circular-orbit radius stable to 100 m for 24 h');
echo 'MEASURE moon_max_energy_relative_drift='.$moonEnergyDrift.PHP_EOL;
echo 'MEASURE moon_max_radius_drift_m='.$moonRadiusDrift.PHP_EOL;

// Zero-thrust Earth+Moon craft propagation: compare Earth-only and lunar-
// perturbed orbits over one orbit. In a moving field, Earth-only spacecraft
// energy need not be constant, so only finite-state and boundedness are tested.
$pEarth=$p0;$vEarth=$v0;$pBoth=$p0;$vBoth=$v0;
$moonP=$moon;$moonV=[0.0,sqrt($mu/384400000.0),0.0];
$steps=(int)round($period);
for($i=0;$i<$steps;$i++) {
    [$pEarth,$vEarth]=integrateCraft($pEarth,$vEarth,$earth,$moon,$mu,0.0,1.0);
    [$pBoth,$vBoth]=integrateCraft($pBoth,$vBoth,$earth,$moonP,$mu,$moonMu,1.0);
    $moonA0=moonSimMoonAcceleration($moonP,$earth,$mu);
    $moonNext=moonSimVerletDrift($moonP,$moonV,$moonA0,1.0);
    $moonA1=moonSimMoonAcceleration($moonNext,$earth,$mu);
    $moonV=moonSimVerletKick($moonV,$moonA0,$moonA1,1.0);
    $moonP=$moonNext;
}
$sep=moonSimVectorMagnitude(moonSimRelativeVector($pBoth,$pEarth));
$end=moonSimOrbitalElements($pBoth,$vBoth,$earth,$mu);
verifyOrbit(is_finite($sep)&&$sep>0.0,'Moving Moon perturbs a real Earth orbit');
verifyOrbit($sep<100000.0,'Moon perturbation remains bounded during a single LEO orbit');
verifyOrbit($end['specific_energy']<0.0,'Earth-Moon-propagated LEO spacecraft remains Earth-bound');
echo 'MEASURE lunar_perturbation_separation_m='.$sep.PHP_EOL;
echo 'PASS orbital propagation, energy and parking-orbit analytical checks'.PHP_EOL;
