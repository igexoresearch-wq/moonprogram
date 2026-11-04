<?php
/* LM descent navigation display derived from live telemetry, not a camera. */
require_once __DIR__.'/shared/lm_landing_estimate.php';
require_once __DIR__.'/shared/physical_constants.php';
$read=function(string $table)use($conn):array{
 $res=$conn->query("SELECT attribute,value FROM $table");$out=[];
 if($res)while($row=$res->fetch_assoc())$out[$row['attribute']]=(float)$row['value'];
 return $out;
};
$lmNav=$read('telemetry');$moonNav=$read('moon');$v=[];
foreach(['x','y','z'] as $axis){
 $v[]=(float)($lmNav[$axis]??0)-(float)($moonNav[$axis]??0);
}
$range=sqrt(array_sum(array_map(static fn($x)=>$x*$x,$v)));
$unit=$range>1?array_map(static fn($x)=>$x/$range,$v):[1,0,0];
$relative=[];
foreach(['delta_x','delta_y','delta_z'] as $key)$relative[]=(float)($lmNav[$key]??0)-(float)($moonNav[$key]??0);
$radial=array_sum(array_map(static fn($a,$b)=>$a*$b,$unit,$relative));
$hor=sqrt(max(0,array_sum(array_map(static fn($x)=>$x*$x,$relative))-$radial*$radial));
$landingNav=moonSimLmLandingEstimate($range-$moon_radius,$radial,$hor,$gravitational_constant*$moon_mass,$moon_radius);
?>
<div style="background:#0c150c;color:#c6eab7;border:2px solid #859b74;padding:15px;font:17px monospace">
<strong>LM DSKY / LANDING NAV</strong><br>
R1 ALT: <?=htmlspecialchars(number_format($landingNav['altitude_m'],1))?> M<br>
R2 VERT: <?=htmlspecialchars(number_format($radial,2))?> M/S<br>
R3 HORIZ: <?=htmlspecialchars(number_format($hor,2))?> M/S<br>
LANDING ENVELOPE: <?=!empty($landingNav['soft_landing_envelope'])?'WITHIN LIMITS':'NOT YET'?><br>
<small>READOUT ONLY — true landing is decided by physical surface-contact physics.</small>
</div>
