<?php
// Helper function to calculate shortest difference in degrees
function calculateShortestDifference($target, $current) {
    $difference = ($target - $current + 360) % 360;
    if ($difference > 180) $difference -= 360;
    return $difference;
}
// Update real-world rates and deduct fuel for changes
function updateRealWorldRate(&$realRate, $internalRate, &$fuelDeduction) {
    $change = abs($realRate - $internalRate);
    $fuelDeduction += floor($change / 0.5); // Deduct 1 RCS per 0.5-degree change
    $realRate = $internalRate; // Update real-world rate to match internal rate
}

?>