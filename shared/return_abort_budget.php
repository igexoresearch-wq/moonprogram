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
