<?php


// Fetch data from entry_tracker
$query = "SELECT label, noun, verb, conditions FROM entry_tracker WHERE panel = 1";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Entry Tracker</title>

</head>
<body>

<table>
   
        <tr>
            <td>Label</td><td>&nbsp;&nbsp;</td>
            <td>Noun</td><td>&nbsp;&nbsp;</td>
            <td>Verb</td><td>&nbsp;&nbsp;</td>
            <td>Conditions</td>
        </tr>
   
    <tbody>
        <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?php echo htmlspecialchars($row['label']); ?></td><td></td>
                <td><?php echo htmlspecialchars($row['noun']); ?></td><td></td>
                <td><?php echo htmlspecialchars($row['verb']); ?></td><td></td>
                <td><?php echo htmlspecialchars($row['conditions']); ?></td>
            </tr>
        <?php endwhile; ?>
    </tbody>
</table>

</body>
</html>


