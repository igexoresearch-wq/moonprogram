<?php
declare(strict_types=1);
require_once __DIR__.'/orbital_mechanics.php';
/** Midcourse correction references, computed from independent tracking state. */
function moonSimMidcourseCorrection(array $craft,array $velocity,array $moon,array $moonVelocity,
 float $moonMu,float $moonRadius,float $coastSeconds):array{
 if($moonMu<=0||$moonRadius<=0||$coastSeconds<=0)throw new InvalidArgumentException('Invalid midcourse geometry');
 $rel=moonSimRelativeVector($craft,$moon);
 $rv=moonSimRelativeVector($velocity,$moonVelocity);
 $predict=[];
 for($i=0;$i<3;$i++)$predict[$i]=$rel[$i]+$rv[$i]*$coastSeconds;
 $range=moonSimVectorMagnitude($predict);
 $relativeSpeed=moonSimVectorMagnitude($rv);
 $unit=$range>1?array_map(static fn($v)=>$v/$range,$predict):[0,0,0];
 // A zero-displacement ballistic correction would target the lunar center,
 // which is unsafe. Return the uncorrected track and lateral reference only.
 $lateral=moonSimVectorCross($rv,$rel);$m=moonSimVectorMagnitude($lateral);
 $safeDirection=$m>1e-5?array_map(static fn($v)=>$v/$m,$lateral):[0,0,0];
 return ['linearized_coast_seconds'=>$coastSeconds,
 'uncorrected_relative_range_m'=>$range,
 'relative_speed_m_s'=>$relativeSpeed,
 'lateral_unit_xyz'=>$safeDirection,
 'minimum_safe_lunar_range_m'=>$moonRadius+100000,
 'correction_required'=>$range>$moonRadius+100000,
 'warning'=>'Linearly extrapolated reference only. Requires full Earth/Moon propagator and verified correction targeting; do not burn from this alone.'];
}
/** Earth-centered Earth-return safety reference: whether the conic can avoid entry. */
function moonSimEarthReturnClassification(array $position,array $velocity,array $earth,
 float $earthMu,float $earthRadius):array{
 $orbit=moonSimOrbitalElements($position,$velocity,$earth,$earthMu);
 $peri=$orbit['periapsis_radius'];
 return ['earth_periapsis_altitude_m'=>$peri===null?null:$peri-$earthRadius,
 'potential_earth_entry'=>$peri!==null&&$peri<=$earthRadius+120000,
 'earth_bound'=>$orbit['specific_energy']<0,
 'note'=>'A free-return trajectory requires full Earth-Moon propagation and a valid lunar flyby; this conic alone does not establish one.'];
}
