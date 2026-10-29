<?php
$prog = 25;

//#############Initiation
include 'programshift.php';

$conn->query("UPDATE CMguidance SET delta_V = $orbitvel WHERE id = 2");
$conn->query("UPDATE CMguidance SET delta_V = $prog WHERE id = 3");

?>