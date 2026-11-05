<?php
declare(strict_types=1);
require_once __DIR__.'/../shared/orbital_mechanics.php';
require_once __DIR__.'/../shared/lm_ascent_targets.php';
require_once __DIR__.'/../shared/docking_closure_guidance.php';
require_once __DIR__.'/../physics/contact.php';
function check(bool $ok,string $label):void {if(!$ok)throw new RuntimeException($label);echo "PASS: $label\n";}
$mu=6.67430e-11*7.342e22;$r=1737400.0;
$v=sqrt($mu/($r+100000.0));
$orbit=moonSimOrbitalElements([$r+100000.0,0,0],[0,$v,0],[0,0,0],$mu);
check(abs($orbit['periapsis_radius']-($r+100000.0))<0.1,'LM circular insertion orbital elements');
$target=moonSimLunarAscentTargets([$r+100000.0,0,0],[0,$v,0],[0,0,0],[0,0,0],$mu,$r,5500,2000,15600,311,100000);
check($target['cutoff_reference_met']===true,'LM ascent target detects achieved safe orbit');
$dock=moonSimDockingClosureReference([0,0,0],[0,0,0],[5,0,0],[-0.1,0,0]);
check($dock['in_proximity_hold_zone']===true,'Docking close slow approach allowed');
$unsafe=moonSimDockingClosureReference([0,0,0],[0,0,0],[20,0,0],[-4,0,0]);
check($unsafe['unsafe_fast_closure']===true,'Docking unsafe closure inhibited');
check(moonSimClassifySurfaceImpact('CM','EARTH',6,5,1,0,0,1)==='LANDED','CM gentle Earth impact classifies safe');
check(moonSimClassifySurfaceImpact('CM','EARTH',200,100,5,0,0,1)==='CRASHED','CM high speed impact classifies crash');
check(moonSimClassifySurfaceImpact('LM','MOON',2,1,1,1,1,0)==='LANDED','LM lunar touchdown envelope');
