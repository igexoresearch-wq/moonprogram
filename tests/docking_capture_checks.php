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
 ['already_docked',false,[$p,$v,$l,$slow,$q,$q,0,1]]
];
foreach($cases as [$name,$expected,$args]){
 $actual=moonSimDockCaptureEligible(...$args);
 if($actual!==$expected)throw new RuntimeException($name);
 echo "PASS $name\n";
}
