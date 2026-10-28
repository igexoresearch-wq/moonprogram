<?php
if (!isset($pageTitle)) { $pageTitle = 'Moon Program'; }
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= h($pageTitle) ?></title>
  <link rel="stylesheet" href="<?= h($basePath ?? '') ?>assets/style.css">
</head>
<body>
<div class="shell">
  <header class="topbar">
    <div class="brand">Moon Program</div>
    <nav class="nav">
      <a href="<?= h($basePath ?? '') ?>index.php">Command Center</a>
      <a href="<?= h($basePath ?? '') ?>admin/object_definitions.php">Objects</a>
      <a href="<?= h($basePath ?? '') ?>admin/engines.php">Engines</a>
      <a href="<?= h($basePath ?? '') ?>admin/missions.php">Missions</a>
      <a href="<?= h($basePath ?? '') ?>admin/live_objects.php">Live Universe</a>
      <a href="<?= h($basePath ?? '') ?>admin/panels.php">Panels</a>
    </nav>
  </header>
