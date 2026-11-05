<?php
require_once __DIR__.'/../physics/attitude.php';
require_once __DIR__.'/../physics/docking.php';
$q=[1,0,0,0];$p=[0,0,0];$v=[0,0,0];$l=[1,0,0];$slow=[-0.1,0,0];
$cases=[
 ['capture',true,[$p,$v,$l,$slow,$q,$q,0,0]],
 ['overspeed',false,[$p,$v,$l,[1,0,0],$q,$q,0,0]],
 ['too_far',false,[$p,$v,[5,0,0],$slow,$q,$q,0,0]],
 ['bad_orientation',false,[$p,$v,$l,$slow,$q,moonSimQuaternionFromEuler(40,0,0),0,0]],
 ['stage_attached',false,[$p,$v,$l,$slow,$q,$q,1,0]],
 ['already_docked',false,[$p,$v,$l,$slow,$q,$q,0,1]],
 ['redock_inhibited',false,[$p,$v,$l,$slow,$q,$q,0,0,true]],
 ['capture_range_boundary',true,[$p,$v,[2,0,0],$slow,$q,$q,0,0]],
 ['beyond_capture_boundary',false,[$p,$v,[2.001,0,0],$slow,$q,$q,0,0]],
 ['speed_at_limit',true,[$p,$v,$l,[-0.30,0,0],$q,$q,0,0]],
 ['speed_above_limit',false,[$p,$v,$l,[-0.301,0,0],$q,$q,0,0]],
 ['separating_fast',false,[$p,$v,$l,[0.06,0,0],$q,$q,0,0]],
 ['non_finite_position',false,[$p,$v,[INF,0,0],$slow,$q,$q,0,0]],
 ['non_finite_speed',false,[$p,$v,$l,[NAN,0,0],$q,$q,0,0]]
];
foreach($cases as [$name,$expected,$args]){
 $actual=moonSimDockCaptureEligible(...$args);
 if($actual!==$expected)throw new RuntimeException($name);
 echo "PASS $name\n";
}
