<?php
declare(strict_types=1);
require_once __DIR__.'/radio_specialist_facts.php';
require_once __DIR__.'/earth_return_corridor.php';
require_once __DIR__.'/return_abort_budget.php';
function moonSimRadioCalculation(mysqli $db,string $role,string $spoken):array{
 $facts=moonSimSpecialistFacts($db,$role);
 $request=mb_strtolower($spoken);
 if(in_array($role,['EECOM','GNC'],true)&&preg_match('/(?:burn|delta.v|fuel|reserve)/',$request)){
  $fuel=$facts['csm_fuel_kg']??null;
  $facts['fuel_reserve_15pct_kg']=$fuel===null?null:round($fuel*0.15,2);
  $facts['usable_fuel_after_reserve_kg']=$fuel===null?null:round($fuel*0.85,2);
  if($fuel!==null && preg_match('/(\\d+(?:\\.\\d+)?)\\s*(?:m\\/s|meters per second)/i',$spoken,$match)){
   require __DIR__.'/physical_constants.php';
   $dv=(float)$match[1];$wet=$command_module_empty_mass+$service_module_empty_mass+$fuel;
   $used=$wet*(1-exp(-$dv/(314.0*9.80665)));
   $facts['requested_delta_v_m_s']=$dv;
   $facts['predicted_sps_fuel_for_delta_v_kg']=round($used,2);
   $facts['predicted_sps_burn_seconds']=round($used*314.0*9.80665/$service_module_thrust,2);
   $facts['preserves_15pct_reserve']=$used<=$fuel*0.85;
   $facts['burn_reference']='rocket equation, service module thrust and Isp; approximate fuel-only calculation';
  }
 }
 if($role==='TRAJECTORY'||$role==='RETRO'){
  if(preg_match('/(?:return|correction|burn solution|course)/',$request)){
   require __DIR__.'/physical_constants.php';
   $read=static function($table)use($db){$x=[];$q=$db->query("SELECT attribute,value FROM ".$table);if($q)while($row=$q->fetch_assoc())$x[$row['attribute']]=(float)$row['value'];return $x;};
   $cm=$read('command_module');$moon=$read('moon');
   $vector=static fn($x,$v='')=>[(float)$x[$v.'x'],(float)$x[$v.'y'],(float)$x[$v.'z']];
   try{
    $solution=moonSimReturnCourseSearch($vector($cm),$vector($cm,'delta_'),
      [$earth_center_x,$earth_center_y,$earth_center_z],$vector($moon),$vector($moon,'delta_'),
      $gravitational_constant*$earth_mass,$gravitational_constant*$moon_mass,
      $earth_radius,$moon_radius);
    $candidate=$solution['candidate']??null;
    $facts['earth_return_correction']=$candidate===null?'No computed corridor solution in current search bounds':[
      'delta_v_xyz_m_s'=>$candidate['delta_v_xyz'],
      'delta_v_m_s'=>$candidate['delta_v_m_s'],
      'predicted_earth_altitude_m'=>$candidate['return_preview']['predicted_closest_earth_altitude_m'],
      'source'=>'shared/earth_return_corridor.php:moonSimReturnCourseSearch'];
   }catch(Throwable $e){$facts['earth_return_correction']='No valid computed solution';}
  }
 }
 return $facts;
}
