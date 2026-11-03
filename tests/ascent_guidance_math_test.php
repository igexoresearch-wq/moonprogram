<?php
declare(strict_types=1);
require_once __DIR__.'/../shared/ascent_guidance_math.php';
require_once __DIR__.'/../physics/attitude.php';

function ascentCheck(bool $ok,string $label):void {
    if(!$ok)throw new RuntimeException('FAIL: '.$label);
    echo 'PASS '.$label.PHP_EOL;
}
$radius=6371000.0;$mu=3.986004418e14;
$origin=[1.0e10,1.0e10,1.0e10];
$pad=[$origin[0]+$radius,$origin[1],$origin[2]];
$launch=moonSimAscentDirection($pad,[0,0,0],$origin,$radius,170000.0);
ascentCheck(abs($launch['direction'][0]-1)<1e-8,'launch-pad thrust is radial upward');
ascentCheck(abs($launch['elevation_above_local_horizon_deg']-90)<1e-8,'vertical initial ascent');
$up=[$origin[0]+$radius+75000.0,$origin[1],$origin[2]];
$turn=moonSimAscentDirection($up,[200,1500,0],$origin,$radius,170000.0);
ascentCheck($turn['direction'][1]>0.0 && $turn['direction'][0]>0.0,'gravity turn has east and radial thrust');
ascentCheck(abs(sqrt(array_sum(array_map(static fn($x)=>$x*$x,$turn['direction'])))-1)<1e-9,'unit thrust vector');
$rates=moonSimAscentBodyRates([1,0,0,0],[0,1,0],3.0);
ascentCheck($rates['yaw_rate']<0 && abs($rates['yaw_rate'])<=3.0,'yaw sign and rate cap toward east');
$aligned=moonSimAscentBodyRates([1,0,0,0],[1,0,0],3.0);
ascentCheck(abs($aligned['pitch_rate'])<1e-9 && abs($aligned['yaw_rate'])<1e-9,'aligned vehicle holds inertial attitude');
$north=moonSimAscentDirection(
    [$origin[0],$origin[1],$origin[2]+$radius],
    [0,0,0],$origin,$radius,170000.0);
ascentCheck(abs($north['direction'][2]-1)<1e-8,'polar launch has safe tangent fallback');
$orbitR=$radius+180000.0;
$circ=sqrt($mu/$orbitR);
$orbitP=[$origin[0]+$orbitR,$origin[1],$origin[2]];
ascentCheck(moonSimParkingOrbitReached($orbitP,[0,$circ,0],$origin,$mu,$radius,
    160000.0,170000.0,200.0),'bound circular parking orbit accepted');
ascentCheck(!moonSimParkingOrbitReached($orbitP,[0,0,0],$origin,$mu,$radius,
    160000.0,170000.0,200.0),'nonorbital stationary craft rejected');
ascentCheck(!moonSimParkingOrbitReached($orbitP,[0,1.6*$circ,0],$origin,$mu,$radius,
    160000.0,170000.0,200.0),'unbound escape trajectory rejected');
ascentCheck(!moonSimParkingOrbitReached($orbitP,[500,$circ,0],$origin,$mu,$radius,
    160000.0,170000.0,200.0),'large radial climb not parking orbit');
ascentCheck(!moonSimParkingOrbitReached($orbitP,[0,$circ*1.07,0],$origin,$mu,$radius,
    160000.0,170000.0,200.0),'excessive high apoapsis rejected');
echo 'All ascent guidance analytical checks passed; deployment trajectory not yet flown.'.PHP_EOL;
