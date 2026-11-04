<?php
declare(strict_types=1);
require_once __DIR__.'/midcourse_tracking.php';
/** Read-only finite-burn Earth-return search with moving-Moon coast. */
function moonSimTeiReference(array $craft,array $velocity,array $earth,array $moon,array $moonVelocity,
 float $earthMu,float $moonMu,float $earthRadius,float $moonRadius,
 float $wet,float $fuel,float $thrust,float $isp):array{
 if($wet<=$fuel||$fuel<=0||$thrust<=0||$isp<=0)throw new InvalidArgumentException('SPS unavailable');
 $rv=moonSimRelativeVector($velocity,$moonVelocity);$speed=moonSimVectorMagnitude($rv);
 if($speed<1)throw new RuntimeException('Undefined departure heading');
 $outward=moonSimRelativeVector($craft,$moon);$rr=moonSimVectorMagnitude($outward);
 if($rr<=$moonRadius+1000)throw new RuntimeException('Invalid departure altitude');
 $dirs=[$rv,$outward,moonSimVectorCross($rv,$outward)];
 $best=null;$tested=0;$flow=$thrust/($isp*9.80665);
 foreach($dirs as $raw){
  $mag=moonSimVectorMagnitude($raw);if($mag<1)continue;
  foreach([-1,1] as $sgn){
   $d=array_map(static fn($x)=>$sgn*$x/$mag,$raw);
   foreach([30.0,60.0,120.0,240.0,360.0] as $seconds){
    $spent=$seconds*$flow;
    if($spent>0.9*$fuel)continue;
    // Rocket-equation finite burn reference, with midpoint burn direction.
    $dv=$isp*9.80665*log($wet/($wet-$spent));
    $kick=array_map(static fn($x)=>$x*$dv,$d);
    $coast=moonSimMidcoursePropagatedPreview($craft,$velocity,$earth,$moon,$moonVelocity,
      $earthMu,$moonMu,$earthRadius,$moonRadius,1036800.0,$kick,180.0);
    $tested++;
    $arrival=$coast['post_lunar_earth_min_altitude_m'];
    if($coast['moon_impact']||$arrival===null||$arrival<60000||$arrival>300000)continue;
    $score=abs($arrival-120000);
    if($best===null||$score<$best['entry_altitude_target_error_m'])
     $best=['direction_xyz'=>$d,'duration_seconds'=>$seconds,'fuel_used_kg'=>$spent,
       'ideal_delta_v_m_s'=>$dv,'entry_altitude_target_error_m'=>$score,
       'predicted_return'=>$coast];
   }
  }
 }
 return ['candidate'=>$best,'candidates_tested'=>$tested,
 'warning'=>'Finite fuel accounting with effective impulsive midpoint departure and coupled coast; refine through true continuous-burn dynamics before execution.'];
}
