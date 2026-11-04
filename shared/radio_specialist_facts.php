<?php
declare(strict_types=1);
require_once __DIR__.'/orbital_mechanics.php';
require_once __DIR__.'/physical_constants.php';
/** Read-only instrument snapshot and deterministic calculations. */
function moonSimSpecialistFacts(mysqli $db,string $role):array{
 $read=static function(string $table)use($db):array{
  $rows=[];$q=$db->query("SELECT attribute,value FROM ".$table);
  if($q)while($x=$q->fetch_assoc())$rows[$x['attribute']]=(float)$x['value'];
  return $rows;
 };
 $cm=$read('command_module');$lm=$read('telemetry');$moon=$read('moon');
 $earth=[$earth_center_x,$earth_center_y,$earth_center_z];
 $facts=['source'=>'live database; calculations from shared/orbital_mechanics.php'];
 if(in_array($role,['TRAJECTORY','GUIDO','RETRO'],true)){
  $pos=[(float)($cm['x']??0),(float)($cm['y']??0),(float)($cm['z']??0)];
  $vel=[(float)($cm['delta_x']??0),(float)($cm['delta_y']??0),(float)($cm['delta_z']??0)];
  if(moonSimVectorMagnitude(moonSimRelativeVector($pos,$earth))>1){
   $orbit=moonSimOrbitalElements($pos,$vel,$earth,$gravitational_constant*$earth_mass);
   $facts['cm_earth_altitude_m']=$orbit['radius']-$earth_radius;
   $facts['cm_earth_periapsis_m']=$orbit['periapsis_radius']===null?null:$orbit['periapsis_radius']-$earth_radius;
   $facts['cm_earth_apoapsis_m']=$orbit['apoapsis_radius']===null?null:$orbit['apoapsis_radius']-$earth_radius;
   $facts['cm_earth_relative_speed_m_s']=$orbit['speed'];
  }
  $facts['navigation_data_source']='command_module: x,y,z,delta_x,delta_y,delta_z';
 }
 if(in_array($role,['EECOM','GNC','RETRO'],true)){
  $facts['csm_fuel_kg']=$cm['fuel']??null;
  $facts['service_module_attached']=$cm['service_module']??null;
  $facts['sps_thrust_n']=$service_module_thrust;
  $facts['csm_fuel_source']='command_module.fuel, command_module.service_module, shared/physical_constants.php';
 }
 if($role==='TELMU'){
  $facts['lm_ascent_fuel_kg']=$lm['fuel_ascent']??null;
  $facts['lm_descent_fuel_kg']=$lm['fuel']??null;
  $facts['lm_descent_attached']=$lm['descent']??null;
  $facts['lm_source']='telemetry.fuel_ascent, telemetry.fuel, telemetry.descent';
 }
 if($role==='CAPCOM')$facts=['duty'=>'Relay only. Do not calculate, invent approvals, or speak for FIDO.'];
 return $facts;
}
