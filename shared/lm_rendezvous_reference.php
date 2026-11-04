<?php
declare(strict_types=1);
require_once __DIR__.'/orbital_mechanics.php';
/** Two-body near-circular lunar rendezvous phasing reference (not a command). */
function moonSimLunarRendezvousReference(array $lmP,array $lmV,array $cmP,array $cmV,
 array $moonP,array $moonV,float $mu,float $moonRadius):array{
 if($mu<=0||$moonRadius<=0)throw new InvalidArgumentException('Invalid lunar parameter');
 $lr=moonSimRelativeVector($lmP,$moonP);$cr=moonSimRelativeVector($cmP,$moonP);
 $lv=moonSimRelativeVector($lmV,$moonV);$cv=moonSimRelativeVector($cmV,$moonV);
 $lmag=moonSimVectorMagnitude($lr);$cmag=moonSimVectorMagnitude($cr);
 if(min($lmag,$cmag)<=$moonRadius)throw new RuntimeException('Rendezvous requires two orbiting vehicles');
 $l=moonSimOrbitalElements($lr,$lv,[0,0,0],$mu);
 $c=moonSimOrbitalElements($cr,$cv,[0,0,0],$mu);
 if($l['specific_energy']>=0||$c['specific_energy']>=0)throw new RuntimeException('Rendezvous requires lunar-bound spacecraft');
 $dot=moonSimVectorDot($lr,$cr)/($lmag*$cmag);
 $phase=rad2deg(acos(max(-1,min(1,$dot))));
 $relative=moonSimRelativeVector($lmP,$cmP);
 $dv=moonSimRelativeVector($lmV,$cmV);
 $closing=-moonSimVectorDot($relative,$dv)/max(1,moonSimVectorMagnitude($relative));
 $averageRadius=($lmag+$cmag)/2;
 $meanMotion=sqrt($mu/($averageRadius**3));
 $period=2*M_PI/$meanMotion;
 $radiusError=$cmag-$lmag;
 $circularTargetSpeed=sqrt($mu/$cmag);
 $referenceDv=$circularTargetSpeed-sqrt($mu/$lmag);
 return ['range_m'=>moonSimVectorMagnitude($relative),'closing_speed_m_s'=>$closing,
 'angular_separation_deg'=>$phase,'average_orbital_period_s'=>$period,
 'radial_separation_m'=>$radiusError,'reference_circularization_dv_m_s'=>$referenceDv,
 'reference_phasing_rate_rad_s'=>$meanMotion,'lm_specific_energy'=>$l['specific_energy'],
 'cm_specific_energy'=>$c['specific_energy'],
 'warning'=>'Orbital phasing reference only. This is not a collision-safe rendezvous burn or docking trajectory.'];
}
