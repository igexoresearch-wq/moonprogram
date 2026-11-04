<?php
declare(strict_types=1);
require_once __DIR__.'/lm_finite_ascent_profile.php';
/** Development-only invariant checks: does not need DB or server. */
function moonSimAscentValidationCase(array $p,array $v,array $moonP,array $moonV,
 float $mu,float $radius,float $wet,float $fuel,float $thrust,float $isp):array{
 $profile=moonSimLunarAscentProfileSearch($p,$v,$moonP,$moonV,$mu,$radius,$wet,$fuel,$thrust,$isp);
 $problems=[];
 if($profile['fuel_consumed_kg']>$fuel+1e-5)$problems[]='APS fuel overdraw';
 if($profile['lunar_impact'])$problems[]='Lunar contact';
 if(!$profile['lunar_bound']||$profile['final_perilune_altitude_m']<30000)$problems[]='Unsafe insertion';
 if($profile['final_apolune_altitude_m']===null)$problems[]='Undefined apolune';
 return ['valid_source_profile'=>count($problems)===0,'violations'=>$problems,'profile'=>$profile,
 'stage_expected_at_cutoff'=>['descent'=>0,'fuel_ascent_kg'=>$fuel-$profile['fuel_consumed_kg']],
 'note'=>'Deterministic isolated model verification; integration with database stages is a separate assertion.'];
}

/** Verify staged flight telemetry snapshot and flight event after a cutoff.
 * Pure input arrays allow local checks without contacting production DB. */
function moonSimAscentStagePersistenceCheck(array $telemetry,array $cm,array $event,
 float $minimumPeriluneM,float $moonRadius):array{
 $fail=[];
 if((int)($telemetry['descent']??1)!==0)$fail[]='Descent stage still attached';
 if((int)($cm['dock']??1)!==0)$fail[]='LM still docked';
 if((int)($cm['stage3']??1)!==0)$fail[]='Saturn stage still attached';
 if((float)($telemetry['fuel_ascent']??-1)<0)$fail[]='Negative APS fuel';
 if((float)($telemetry['thrust']??1)>0)$fail[]='APS thrust not cut off';
 if((string)($event['event_name']??'')!=='ORBIT_CUTOFF')$fail[]='Missing insertion event';
 if((int)($event['descent_attached']??1)!==0)$fail[]='Recorded stage attached';
 if(abs((float)($event['fuel_kg']??-999)-(float)($telemetry['fuel_ascent']??0))>1.0)
  $fail[]='Logged APS fuel mismatch';
 if($minimumPeriluneM<$moonRadius+30000)$fail[]='Unsafe lunar perilune';
 return ['pass'=>$fail===[],'violations'=>$fail];
}
