<?php
// Fetch guidance data for IDs 1-10
$guidance_data = [];
$result = $conn->query("SELECT id, noun, verb FROM guidance WHERE id BETWEEN 1 AND 10");
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $guidance_data[] = $row;
    }
}

// Handle Abort button action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['abort_id'])) {
    $abort_id = intval($_POST['abort_id']);
    $conn->query("UPDATE guidance SET verb = '0' WHERE id = $abort_id");
}
?>

<div class="guidance-table-container">
    <table class="guidance-table">
        <?php foreach (array_chunk($guidance_data, ceil(count($guidance_data) / 2)) as $row): ?>
            <tr>
                <?php foreach ($row as $data): ?>
                    <td>
                        <div class="guidance-cell">
                            <div class="guidance-noun"><?php echo htmlspecialchars(ucfirst($data['noun'])); ?></div>
                            <div class="guidance-verb">
                                <div class="guidance-digital-display"><?php echo htmlspecialchars($data['verb']); ?></div>
                            </div>
                            <div class="guidance-actions">
                                <?php if ($data['verb'] === '1'): ?>
                                    <div class="guidance-status guidance-active">Active</div>
                                    <form method="POST" style="display:inline;">
                                        <button type="submit" name="abort_id" value="<?php echo $data['id']; ?>" class="guidance-abort-button">Abort</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </td>
                <?php endforeach; ?>
                <?php if (count($row) < ceil(count($guidance_data) / 2)): ?>
                    <td></td> <!-- Empty cell for uneven rows -->
                <?php endif; ?>
            </tr>
        <?php endforeach; ?>
    </table>
</div>

<style>
    /* Guidance Table Styling */
    .guidance-table-container {
        padding: 20px;
        background-color: #3a3a3a;
        border-radius: 8px;
    }

    .guidance-table {
        width: 100%;
        border-spacing: 15px;
    }

    .guidance-cell {
        display: flex;
        flex-direction: column;
        align-items: center;
        background-color: #2e2e2e;
        padding: 10px;
        border-radius: 8px;
        border: 1px solid #1e1e1e;
    }

    .guidance-noun {
        font-family: Arial, sans-serif;
        font-size: 1.2rem;
        color: #ffffff;
        margin-bottom: 5px;
    }

    .guidance-verb {
        margin-bottom: 10px;
    }

    .guidance-digital-display {
        font-family: 'DigitTech', monospace;
        font-size: 1.5rem;
        font-weight: bold;
        color: #FFFF00;
        background-color: #000000;
        padding: 5px;
        border-radius: 5px;
        width: 7ch;
        text-align: right;
    }

    .guidance-actions {
        text-align: center;
    }

    .guidance-status {
        font-size: 1rem;
        font-weight: bold;
        padding: 5px 10px;
        border-radius: 5px;
        margin-bottom: 5px;
    }

    .guidance-active {
        background-color: #00ff00;
        color: #000000;
    }

    .guidance-abort-button {
        padding: 5px 10px;
        font-size: 1rem;
        border-radius: 5px;
        border: none;
        cursor: pointer;
        background-color: #ff0000;
        color: #ffffff;
    }

    .guidance-abort-button:hover {
        background-color: #cc0000;
    }

    .guidance-abort-button:active {
        background-color: #990000;
    }
</style>

