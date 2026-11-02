<?php
declare(strict_types=1);
/**
 * LM ascent/rendezvous flight computer: read-only advisory calculations.
 * Never alters positions, velocities, propellant, or engine commands.
 */
function moonSimLmFlightSolution(array $lm, array $cm, array $moon, float $moonRadius, float $moonMu): array {
    $p=[$lm['x']-$moon['x'],$lm['y']-$moon['y'],$lm['z']-$moon['z']];
    $v=[$lm['delta_x']-$moon['delta_x'],$lm['delta_y']-$moon['delta_y'],$lm['delta_z']-$moon['delta_z']];
    $r=sqrt($p[0]**2+$p[1]**2+$p[2]**2);
    if ($r<1.0) throw new RuntimeException('LM position is invalid.');
    $n=[$p[0]/$r,$p[1]/$r,$p[2]/$r];
    $radial=$v[0]*$n[0]+$v[1]*$n[1]+$v[2]*$n[2];
    $v2=$v[0]**2+$v[1]**2+$v[2]**2;
    $tangential=sqrt(max(0.0,$v2-$radial**2));
    $c=[$cm['x']-$lm['x'],$cm['y']-$lm['y'],$cm['z']-$lm['z']];
    $dv=[$cm['delta_x']-$lm['delta_x'],$cm['delta_y']-$lm['delta_y'],$cm['delta_z']-$lm['delta_z']];
    $distance=sqrt($c[0]**2+$c[1]**2+$c[2]**2);
    $relativeSpeed=sqrt($dv[0]**2+$dv[1]**2+$dv[2]**2);
    $closing=$distance>0.0?-($c[0]*$dv[0]+$c[1]*$dv[1]+$c[2]*$dv[2])/$distance:0.0;
    $circularSpeed=sqrt($moonMu/$r);
    $specificEnergy=0.5*$v2-$moonMu/$r;
    $semimajor=$specificEnergy<0.0?-$moonMu/(2.0*$specificEnergy):null;
    return [
        'altitude_m'=>$r-$moonRadius,
        'radial_speed_m_s'=>$radial,
        'tangential_speed_m_s'=>$tangential,
        'circular_speed_m_s'=>$circularSpeed,
        'circular_speed_gap_m_s'=>$circularSpeed-$tangential,
        'semimajor_axis_m'=>$semimajor,
        'target_range_m'=>$distance,
        'target_relative_speed_m_s'=>$relativeSpeed,
        'target_closing_speed_m_s'=>$closing,
        'target_vector_m'=>$c,
        'relative_velocity_m_s'=>$dv,
    ];
}
