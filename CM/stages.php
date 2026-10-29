<table class="stage-table">
    <tr>
        <td id="stage1">Stage 1</td>
    </tr>
    <tr>
        <td id="stage2">Stage 2</td>
    </tr>
    <tr>
        <td id="stage3">Stage 3</td>
    </tr>
</table>

<style>
    .stage-table {
        width: 120px;
        border-collapse: separate;
        border-spacing: 0;
        margin: auto;
    }

    .stage-table td {
        height: 60px;
        text-align: center;
        font-family: Arial, sans-serif;
        font-size: 1.2rem;
        font-weight: bold;
        color: #000;
        border: 2px solid #888;
        border-radius: 5px;
        background-color: #d3d3d3;
        box-shadow: 2px 2px 5px rgba(0, 0, 0, 0.5);
    }

    .stage-active {
        background: repeating-linear-gradient(
            45deg,
            #000000,
            #000000 10px,
            #ffffff 10px,
            #ffffff 20px
        );
        color: #ffffff;
    }
</style>

<script>
    // Replace these values with dynamic data from your backend
    const stage1Status = <?php echo $stage1CM; ?>;
    const stage2Status = <?php echo $stage2CM; ?>;
    const stage3Status = <?php echo $stage3CM; ?>;

    // Apply styles dynamically based on stage status
    if (stage1Status === 0) {
        document.getElementById('stage1').classList.add('stage-active');
    }
    if (stage2Status === 0) {
        document.getElementById('stage2').classList.add('stage-active');
    }
    if (stage3Status === 0) {
        document.getElementById('stage3').classList.add('stage-active');
    }
</script>
