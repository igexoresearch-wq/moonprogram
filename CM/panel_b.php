<?php
require_once __DIR__ . '/../shared/docking_commands.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['docking_release'])) {
        moonSimQueueDockingCommand($conn, 'UNDOCK');
        $needs_redirect = true;
    }

    if (isset($_POST['sm_release'])) {
        // Service-module separation is still represented by the original
        // service_module state. Reentry physics will make this a full event.
        $conn->query("UPDATE command_module SET value = 0 WHERE attribute = 'service_module'");
        $conn->query("UPDATE command_module SET value = 0 WHERE attribute = 'thrust'");
        $conn->query(
            "UPDATE EngineStatus
             SET status = 'OFF'
             WHERE engine_stage = 'Service Module'"
        );
        $needs_redirect = true;
    }

    if (isset($_POST['st3_release'])) {
        moonSimQueueDockingCommand($conn, 'SIVB_SEPARATE');
        $needs_redirect = true;
    }
}

$parachute_class = $parachutes_deployedCM == 1 ? 'indicator-on' : 'indicator-off';
?>

<table class="control-table">
    <tr>
        <td class="control-cell">
            <div class="indicator <?php echo $parachute_class; ?>">DEPLOYED</div>
        </td>
        <td class="spacer"></td>

        <td class="control-cell">
            <form method="POST">
                <button type="submit" name="docking_release" class="control-button"></button>
            </form>
        </td>
        <td class="spacer"></td>

        <td class="control-cell">
            <form method="POST">
                <button
                    type="submit"
                    name="sm_release"
                    class="control-button"
                    onclick="return confirm('Separate the Service Module?');"
                ></button>
            </form>
        </td>
        <td class="spacer"></td>

        <td class="control-cell">
            <form method="POST">
                <button
                    type="submit"
                    name="st3_release"
                    class="control-button"
                    onclick="return confirm('Separate CSM from S-IVB for transposition/docking?');"
                ></button>
            </form>
        </td>
    </tr>

    <tr>
        <td class="label-cell">PARACHUTE DEPLOYED</td>
        <td class="spacer"></td>
        <td class="label-cell">DOCKING RELEASE</td>
        <td class="spacer"></td>
        <td class="label-cell">SM RELEASE</td>
        <td class="spacer"></td>
        <td class="label-cell">S-IVB RELEASE</td>
    </tr>
</table>

<style>
.control-table {width:100%;border-spacing:15px;text-align:center}
.control-cell {width:20%}
.spacer {width:10px}
.indicator {width:120px;height:40px;line-height:40px;font-family:Arial,sans-serif;font-size:1rem;font-weight:bold;text-align:center;color:#000;border-radius:5px;box-shadow:inset 0 2px 5px rgba(0,0,0,.5)}
.indicator-on {background:#ffff00}
.indicator-off {background:#d3d3d3}
.control-button {width:150px;height:50px;background:linear-gradient(to bottom,#d3d3d3,#a9a9a9);border:2px solid #555;border-radius:8px;box-shadow:0 4px 8px rgba(0,0,0,.3);cursor:pointer}
.control-button:active {background:linear-gradient(to bottom,#a9a9a9,#d3d3d3);box-shadow:inset 0 2px 4px rgba(0,0,0,.5)}
.label-cell {font-family:Arial,sans-serif;font-size:1.2rem;font-weight:bold;color:#fff}
</style>
