<?php
declare(strict_types=1);
require_once __DIR__.'/nbody_gravity.php';
require_once __DIR__.'/verlet_kernel.php';
require_once __DIR__.'/orbital_mechanics.php';

/**
 * Finite burn in an Earth-fixed frame. Coast to the requested departure time,
 * integrate real thrust acceleration using changing vehicle mass, then coast
 * toward the moving Moon. This is a targeting calculation, never a command.
 */
function moonSimFiniteTliPreview(
 array $craft,array $velocity,array $earth,array $moon,array $moonVelocity,
 array $burnDirection,float $departureDelay,float $burnSeconds,
 float $initialMass,float $fuelMass,float $thrust,float $isp,
 float $earthMu,float $moonMu,float $moonRadius,float $earthRadius,
 float $horizon=500000.0,float $step=60.0
):array{
 foreach([$departureDelay,$burnSeconds,$initialMass,$fuelMass,$thrust,$isp,$earthMu,$moonMu,$moonRadius,$earthRadius,$horizon,$step] as $v)
  if(!is_finite($v))throw new InvalidArgumentException('Nonfinite finite-burn input');
 if($departureDelay<0||$burnSeconds<=0||$step<=0||$horizon<=$departureDelay+$burnSeconds+7200
    ||$initialMass<=0||$fuelMass<=0||$fuelMass>=$initialMass||$thrust<=0||$isp<=0
    ||$earthMu<=0||$moonMu<=0||$moonRadius<=0||$earthRadius<=0)
    throw new InvalidArgumentException('Invalid finite-burn or propulsion inputs');
 $norm=moonSimVectorMagnitude($burnDirection);
 if($norm<1e-9||!is_finite($norm))throw new InvalidArgumentException('Missing burn direction');
 $dir=array_map(static fn($x)=>$x/$norm,$burnDirection);
 $flow=$thrust/($isp*9.80665);
 $requiredFuel=$flow*$burnSeconds;
 if($requiredFuel>$fuelMass+1e-7)throw new RuntimeException('Requested burn exceeds onboard propellant');
 $mass=$initialMass;$spent=0.0;
 $initialR=moonSimRelativeVector($craft,$earth);
 $initialNormal=moonSimVectorCross($initialR,$velocity);
 $normalLength=moonSimVectorMagnitude($initialNormal);
 if($normalLength<1e-9)throw new InvalidArgumentException('Undefined departure orbital plane');
 $initialNormal=array_map(static fn($x)=>$x/$normalLength,$initialNormal);
 $initialUp=array_map(static fn($x)=>$x/moonSimVectorMagnitude($initialR),$initialR);
 $initialTang=moonSimVectorCross($initialNormal,$initialUp);
 $radialComponent=moonSimVectorDot($dir,$initialUp);
 $tangentComponent=moonSimVectorDot($dir,$initialTang);
 $normalComponent=moonSimVectorDot($dir,$initialNormal);
 $orientationSet=false;
 $closest=INF;$closestAt=null;$relativeSpeed=null;$earthImpact=false;$moonImpact=false;
 $atEnd=false;$time=0.0;$closestRelativePosition=null;$closestRelativeVelocity=null;
 $maxSteps=(int)ceil($horizon/min($step,30.0));
 if($maxSteps>250000)throw new InvalidArgumentException('Preview too many integration steps');
 while($time<$horizon-1e-8){
  $dt=min($step,$horizon-$time);
  if($time<$departureDelay)$dt=min($dt,$departureDelay-$time);
  elseif($time<$departureDelay+$burnSeconds)$dt=min($dt,$departureDelay+$burnSeconds-$time,10.0);
  if($dt<1e-7)throw new RuntimeException('Finite burn timestep collapsed');
  $burn=$time>=$departureDelay-1e-8 && $time<$departureDelay+$burnSeconds-1e-8;
  if($burn && !$orientationSet){
   $up=moonSimRelativeVector($craft,$earth);$uMag=moonSimVectorMagnitude($up);
   $up=array_map(static fn($x)=>$x/$uMag,$up);
   $tang=moonSimVectorCross($initialNormal,$up);
   $tMag=moonSimVectorMagnitude($tang);
   if($tMag<1e-9)throw new RuntimeException('Undefined coasting prograde');
   $tang=array_map(static fn($x)=>$x/$tMag,$tang);
   for($j=0;$j<3;$j++)$dir[$j]=$up[$j]*$radialComponent+$tang[$j]*$tangentComponent+$initialNormal[$j]*$normalComponent;
   $dMag=moonSimVectorMagnitude($dir);
   $dir=array_map(static fn($x)=>$x/$dMag,$dir);
   $orientationSet=true;
  }
  $a0=moonSimGravityAcceleration($craft,$earth,$moon,$earthMu,$moonMu);
  $am0=moonSimMoonAcceleration($moon,$earth,$earthMu,$moonMu);
  if($burn){
   $accel=$thrust/$mass;
   for($j=0;$j<3;$j++)$a0[$j]+=$accel*$dir[$j];
  }
  $next=moonSimVerletDrift($craft,$velocity,$a0,$dt);
  $moonNext=moonSimVerletDrift($moon,$moonVelocity,$am0,$dt);
  if($burn){$spent+=$flow*$dt;$mass=$initialMass-$spent;}
  $a1=moonSimGravityAcceleration($next,$earth,$moonNext,$earthMu,$moonMu);
  $am1=moonSimMoonAcceleration($moonNext,$earth,$earthMu,$moonMu);
  if($burn){
   $accel=$thrust/$mass;
   for($j=0;$j<3;$j++)$a1[$j]+=$accel*$dir[$j];
  }
  $velocity=moonSimVerletKick($velocity,$a0,$a1,$dt);
  $moonVelocity=moonSimVerletKick($moonVelocity,$am0,$am1,$dt);
  $craft=$next;$moon=$moonNext;$time+=$dt;
  $earthDistance=moonSimVectorMagnitude(moonSimRelativeVector($craft,$earth));
  if($earthDistance<=$earthRadius)$earthImpact=true;
  $range=moonSimVectorMagnitude(moonSimRelativeVector($craft,$moon));
  if($time>=$departureDelay+$burnSeconds){
   if($range<$closest){
    $closest=$range;$closestAt=$time;
    $closestRelativePosition=moonSimRelativeVector($craft,$moon);
    $closestRelativeVelocity=moonSimRelativeVector($velocity,$moonVelocity);
    $relativeSpeed=moonSimVectorMagnitude($closestRelativeVelocity);
    $atEnd=true;
   }else{$atEnd=false;}
   if($range<=$moonRadius)$moonImpact=true;
  }
  if($earthImpact||$moonImpact)break;
 }
 return [
  'departure_delay_seconds'=>$departureDelay,
  'burn_duration_seconds'=>$burnSeconds,
  'burn_direction_inertial_xyz'=>$dir,
  'ignition_direction_inertial_xyz'=>$orientationSet?$dir:null,
  'propellant_used_kg'=>$spent,
  'propellant_remaining_kg'=>$fuelMass-$spent,
  'closest_approach_km'=>$closest/1000.0,
  'closest_surface_altitude_km'=>($closest-$moonRadius)/1000.0,
  'closest_approach_time_seconds'=>$closestAt,
  'relative_speed_at_approach_m_s'=>$relativeSpeed,
  'relative_position_at_approach_m'=>$closestRelativePosition,
  'relative_velocity_at_approach_m_s'=>$closestRelativeVelocity,
  'earth_impact_detected'=>$earthImpact,
  'moon_impact_detected'=>$moonImpact,
  'closest_approach_at_horizon'=>$atEnd,
  'horizon_seconds'=>$horizon,
  'propellant_feasible'=>$spent<=$fuelMass+1e-6,
 ];
}

/** Search finite-burn duration and departure phase, with a fuel ceiling. */
function moonSimFiniteTliSearch(
 array $craft,array $velocity,array $earth,array $moon,array $moonVelocity,
 array $referenceDirection,float $referenceDv,float $initialMass,float $fuelMass,
 float $thrust,float $isp,float $earthMu,float $moonMu,float $moonRadius,
 float $earthRadius,float $horizon=500000.0
):array{
 $dirMagnitude=moonSimVectorMagnitude($referenceDirection);
 if($dirMagnitude<1e-9||!is_finite($dirMagnitude)||$referenceDv<=0)
  throw new InvalidArgumentException('Invalid TLI seed direction or delta-V');
 $r=moonSimRelativeVector($craft,$earth);
 $rmag=moonSimVectorMagnitude($r);
 if($rmag<=$earthRadius)throw new RuntimeException('Vehicle below Earth launch orbit');
 $up=array_map(static fn($x)=>$x/$rmag,$r);
 $forward=array_map(static fn($x)=>$x/$dirMagnitude,$referenceDirection);
 $idealFuel=$initialMass*(1-exp(-$referenceDv/($isp*9.80665)));
 $idealDuration=$idealFuel*$isp*9.80665/$thrust;
 $best=null;$evaluated=0;$fuelRejected=0;$collisionRejected=0;
 // Earth orbit departure phase must be searched, not presumed from current position.
 foreach([0,1800,3600,5400,7200,10800,14400,18000] as $delay){
  foreach([-12,-6,0,6,12] as $tilt){
   $angle=deg2rad((float)$tilt);
   $direction=[];
   for($i=0;$i<3;$i++)$direction[$i]=$forward[$i]*cos($angle)+$up[$i]*sin($angle);
   foreach([0.92,0.97,1.0,1.03,1.08] as $factor){
    $duration=$idealDuration*$factor;
    if($duration*$thrust/($isp*9.80665)>$fuelMass){$fuelRejected++;continue;}
    $preview=moonSimFiniteTliPreview($craft,$velocity,$earth,$moon,$moonVelocity,$direction,
     (float)$delay,$duration,$initialMass,$fuelMass,$thrust,$isp,
     $earthMu,$moonMu,$moonRadius,$earthRadius,$horizon);
    $evaluated++;
    if($preview['earth_impact_detected']||$preview['moon_impact_detected']||
       $preview['closest_approach_at_horizon']){$collisionRejected++;continue;}
    if($best===null||$preview['closest_surface_altitude_km']<$best['closest_surface_altitude_km']){
     $best=$preview;$best['candidate_departure_offset_deg']=$tilt;
    }
   }
  }
 }
 if($best===null)throw new RuntimeException('No resolved safe finite-thrust lunar encounter within search grid');
 // Local refinement around the best coarse candidate. Search the missing
 // out-of-plane dimension plus phase and burn duration; never bypass the
 // propellant or collision constraints.
 $initialBest=$best;
 $planeNormal=moonSimVectorCross($r,$velocity);
 $planeLength=moonSimVectorMagnitude($planeNormal);
 $planeNormal=array_map(static fn($x)=>$x/$planeLength,$planeNormal);
 $bestDirection=$best['burn_direction_inertial_xyz'];
 $bestDelay=$best['departure_delay_seconds'];
 $bestDuration=$best['burn_duration_seconds'];
 $refined=0;
 foreach([-600.0,0.0,600.0] as $delayOffset){
  $delay=max(0.0,$bestDelay+$delayOffset);
  foreach([-3.0,0.0,3.0] as $normalDegrees){
   $angle=deg2rad($normalDegrees);$direction=[];
   for($j=0;$j<3;$j++)$direction[$j]=$bestDirection[$j]*cos($angle)+$planeNormal[$j]*sin($angle);
   foreach([0.975,1.0,1.025] as $durationFactor){
    $duration=$bestDuration*$durationFactor;
    if($duration*$thrust/($isp*9.80665)>$fuelMass){$fuelRejected++;continue;}
    $candidate=moonSimFiniteTliPreview($craft,$velocity,$earth,$moon,$moonVelocity,$direction,
     $delay,$duration,$initialMass,$fuelMass,$thrust,$isp,
     $earthMu,$moonMu,$moonRadius,$earthRadius,$horizon,30.0);
    $evaluated++;$refined++;
    if($candidate['earth_impact_detected']||$candidate['moon_impact_detected']||
       $candidate['closest_approach_at_horizon']){$collisionRejected++;continue;}
    if($candidate['closest_surface_altitude_km']<$best['closest_surface_altitude_km']){
     $best=$candidate;
     $best['candidate_departure_offset_deg']=$initialBest['candidate_departure_offset_deg'];
     $best['candidate_normal_offset_deg']=$normalDegrees;
    }
   }
  }
 }
 $best['refinement_candidates_evaluated']=$refined;
 if($best['relative_position_at_approach_m']!==null && $best['relative_velocity_at_approach_m_s']!==null){
  $arrival=moonSimOrbitalElements($best['relative_position_at_approach_m'],$best['relative_velocity_at_approach_m_s'],[0,0,0],$moonMu);
  $best['arrival_specific_energy_j_kg']=$arrival['specific_energy'];
  $best['arrival_eccentricity']=$arrival['eccentricity'];
  $best['arrival_periapsis_altitude_km']=$arrival['periapsis_radius']===null?null:($arrival['periapsis_radius']-$moonRadius)/1000;
 }
 $best['candidate_evaluated']=$evaluated;
 $best['candidate_rejected_fuel']=$fuelRejected;
 $best['candidate_rejected_impact_or_horizon']=$collisionRejected;
 $best['targeting_quality']=$best['closest_surface_altitude_km']<=100.0?'NEAR_LUNAR_PASS':($best['closest_surface_altitude_km']<=10000.0?'FAR_LUNAR_ENCOUNTER':'NO_LUNAR_ENCOUNTER');
 $best['usable_lunar_encounter']=$best['closest_surface_altitude_km']>=0.0 && $best['closest_surface_altitude_km']<=1000.0;
 $best['burn_start_seconds_from_now']=$best['departure_delay_seconds'];
 $best['burn_cutoff_seconds_from_now']=$best['departure_delay_seconds']+$best['burn_duration_seconds'];
 $best['warning']='Coarse finite-thrust search, fixed inertial engine orientation, simplified Moon ephemeris. Human verification and refinement required; no automatic ignition.';
 return $best;
}
