<?php
declare(strict_types=1);
/** Manual abort/return fuel budget for a candidate SPS maneuver. */
function moonSimReturnAbortBudget(float $wetMass,float $spsFuel,float $spsThrust,
 float $isp,float $plannedBurnSeconds,float $reserveFraction=0.15):array{
 if($wetMass<=0||$spsFuel<=0||$spsFuel>=$wetMass||$spsThrust<=0||
  $isp<=0||$plannedBurnSeconds<0||$reserveFraction<0||$reserveFraction>=1)
  throw new InvalidArgumentException('Invalid return contingency data');
 $flow=$spsThrust/($isp*9.80665);
 $spent=$flow*$plannedBurnSeconds;
 $reserve=$spsFuel*$reserveFraction;
 $remaining=$spsFuel-$spent;
 $available=max(0,$spsFuel-$reserve);
 $possible=$spent<=$available;
 $idealDv=$possible?$isp*9.80665*log($wetMass/($wetMass-$spent)):null;
 return ['candidate_burn_seconds'=>$plannedBurnSeconds,
  'fuel_needed_kg'=>$spent,'fuel_remaining_kg'=>$remaining,
  'protected_reserve_kg'=>$reserve,'candidate_fuel_feasible'=>$possible,
  'ideal_delta_v_m_s'=>$idealDv,
  'remaining_reserved_burn_time_s'=>$reserve/$flow,
  'warning'=>'Budget only. No safe Earth-return trajectory or survival guarantee.'];
}

/** Abort planning gate: corridor and propellant are necessary, not sufficient,
 * for a recoverable CM. Returns explicit pre-entry action requirements. */
function moonSimReturnRecoveryGate(array $burn,array $coast,array $cm):array{
 $errors=[];
 if(empty($burn['candidate_fuel_feasible']))$errors[]='SPS fuel reserve not met';
 $alt=$coast['post_lunar_earth_min_altitude_m']??null;
 if($alt===null||$alt<65000||$alt>140000)$errors[]='Return misses entry corridor';
 if(!empty($coast['moon_impact']))$errors[]='Lunar collision predicted';
 if((int)($cm['ABORT']??0)!==0)$errors[]='Abort flag still active';
 if((int)($cm['stage3']??0)!==0)$errors[]='S-IVB remains attached';
 if((int)($cm['service_module']??0)!==1)$errors[]='SPS unavailable for departure';
 return ['departure_candidate_allowed'=>$errors===[],
  'blocking_conditions'=>$errors,
  'preentry_actions'=>['Complete planned SPS burn','Perform midcourse correction if required',
    'Separate service module before entry','Confirm launch escape tower absent',
    'Arm and sequence drogue/main parachutes within permitted flight conditions'],
  'recovery_win_confirmed'=>false,
  'warning'=>'Departure authorization only. CM splashdown, heat load and parachutes are resolved by physical reentry.'];
}
