<?php 
$result = $conn->query("SELECT status FROM CMswitches WHERE name = '$switch_name'");
$status = ($result && $row = $result->fetch_assoc()) ? $row['status'] : 0;
?>
<div class="switch-containerw">
        <div class="switch-labelw"><?php echo str_replace('_', '<br>', $switch_name); ?></div>
        <form method="POST">
            <table>
                <tr>
                    <td>Manual</td>
                    <td>
                        <button class="switchw" name="toggle_switch" value="<?php echo $switch_name; ?>">
                            <div style="left: <?php echo ($status == 1) ? 'calc(100% - 28px)' : '2px'; ?>" class="switch-circlew"></div>
                        </button>
                    </td>
                    <td>Auto</td>
                </tr>
            </table>
        </form>
    </div>
    