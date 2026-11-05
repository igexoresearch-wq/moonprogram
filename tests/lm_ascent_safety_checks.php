<?php
declare(strict_types=1);
require_once __DIR__.'/../shared/lm_ascent_safety.php';
function checkAscentSafety(bool $actual,bool $expected,string $label):void {
 if($actual!==$expected)throw new RuntimeException($label);
 echo "PASS: $label\n";
}
checkAscentSafety(moonSimLmAscentMustInhibitForLowThrust([
 'altitude_m'=>0,'initial_thrust_to_weight'=>0.95]),true,'low thrust on surface inhibits launch');
checkAscentSafety(moonSimLmAscentMustInhibitForLowThrust([
 'altitude_m'=>0,'initial_thrust_to_weight'=>1.5,'estimated_fuel_reserve_kg'=>-100]),false,
 'advisory negative fuel estimate does not cancel viable launch');
checkAscentSafety(moonSimLmAscentMustInhibitForLowThrust([
 'altitude_m'=>30000,'initial_thrust_to_weight'=>0.7,'estimated_fuel_reserve_kg'=>-1]),false,
 'in-flight low thrust-weight ratio and advisory fuel estimate are not automatic shutdown');
checkAscentSafety(moonSimLmAscentMustInhibitForLowThrust([
 'altitude_m'=>INF,'initial_thrust_to_weight'=>1.5]),true,'invalid altitude fails closed');
checkAscentSafety(moonSimLmAscentMustInhibitForLowThrust([
 'altitude_m'=>50,'initial_thrust_to_weight'=>NAN]),true,'invalid thrust fails closed');
