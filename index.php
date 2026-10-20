<?php

declare(strict_types=1);
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Moon Program</title>
<style>
:root {
    color-scheme: dark;
    font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
    background: #090d14;
    color: #e8edf5;
}
* { box-sizing: border-box; }
body {
    margin: 0;
    background:
        radial-gradient(circle at 20% 0%, rgba(77,113,180,.12), transparent 30rem),
        #090d14;
}
main {
    width: min(1500px, calc(100% - 32px));
    margin: 0 auto;
    padding: 28px 0 48px;
}
h1 { margin: 0; font-size: 28px; }
.subtitle { color: #91a0b6; margin: 6px 0 24px; }
.grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0,1fr));
    gap: 12px;
    margin-bottom: 16px;
}
.card, .panel {
    background: rgba(17,24,36,.94);
    border: 1px solid #263247;
    border-radius: 12px;
}
.card { padding: 14px 16px; }
.card .label {
    color: #91a0b6;
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: .08em;
}
.card .value {
    margin-top: 7px;
    font-size: 21px;
    font-variant-numeric: tabular-nums;
}
.panel {
    padding: 16px;
    margin-top: 16px;
}
.controls {
    display: flex;
    flex-wrap: wrap;
    gap: 9px;
    align-items: center;
}
input, button {
    border: 1px solid #34445f;
    background: #111a29;
    color: #eef4ff;
    border-radius: 8px;
    padding: 9px 12px;
}
input { min-width: 260px; }
button { cursor: pointer; }
button:hover { background: #17243a; }
button.danger { border-color: #70404a; }
.status {
    margin-left: auto;
    color: #91a0b6;
    min-height: 20px;
}
.table-wrap {
    overflow-x: auto;
}
table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
    font-variant-numeric: tabular-nums;
}
th, td {
    padding: 10px 9px;
    border-bottom: 1px solid #202b3e;
    text-align: right;
    white-space: nowrap;
}
th {
    color: #91a0b6;
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: .05em;
    position: sticky;
    top: 0;
    background: #111824;
}
th:first-child, td:first-child,
th:nth-child(2), td:nth-child(2) {
    text-align: left;
}
.name {
    font-weight: 650;
}
.meta {
    color: #7e8ca2;
    font-size: 11px;
    display: block;
    margin-top: 2px;
}
.root-row {
    background: rgba(77,113,180,.055);
}
.badge {
    display: inline-block;
    padding: 2px 6px;
    border-radius: 999px;
    border: 1px solid #34445f;
    color: #b8c5d9;
    font-size: 10px;
    margin-left: 6px;
}
.running { color: #78d9a4; }
.paused { color: #e8c36c; }
@media (max-width: 900px) {
    .grid { grid-template-columns: repeat(2, minmax(0,1fr)); }
    .status { width: 100%; margin-left: 0; }
}
</style>
</head>
<body>
<main>
    <h1>Moon Program</h1>
    <div class="subtitle">Earth–Moon physics and Apollo/Saturn V stack</div>

    <section class="grid">
        <div class="card">
            <div class="label">Simulation</div>
            <div class="value" id="runState">—</div>
        </div>
        <div class="card">
            <div class="label">Simulation Time</div>
            <div class="value" id="simTime">—</div>
        </div>
        <div class="card">
            <div class="label">Physics Step</div>
            <div class="value" id="tickSize">—</div>
        </div>
        <div class="card">
            <div class="label">Last DB Tick</div>
            <div class="value" id="lastTick" style="font-size:15px">—</div>
        </div>
    </section>

    <section class="panel">
        <div class="controls">
            <input id="controlKey" type="password" autocomplete="off"
                   placeholder="Control key (MOON_CONTROL_KEY)">
            <button data-action="start">Start</button>
            <button data-action="pause">Pause</button>
            <button data-action="step">+1 second</button>
            <button data-action="reset" class="danger">Reset launch state</button>
            <span class="status" id="controlStatus"></span>
        </div>
    </section>

    <section class="panel">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Object</th>
                        <th>Attached to</th>
                        <th>X rel Earth km</th>
                        <th>Y rel Earth km</th>
                        <th>Z rel Earth km</th>
                        <th>Speed m/s</th>
                        <th>Current mass kg</th>
                        <th>Fuel kg</th>
                        <th>Pitch</th>
                        <th>Yaw</th>
                        <th>Roll</th>
                    </tr>
                </thead>
                <tbody id="objectsBody"></tbody>
            </table>
        </div>
    </section>
</main>

<script>
const els = {
    runState: document.getElementById('runState'),
    simTime: document.getElementById('simTime'),
    tickSize: document.getElementById('tickSize'),
    lastTick: document.getElementById('lastTick'),
    body: document.getElementById('objectsBody'),
    key: document.getElementById('controlKey'),
    status: document.getElementById('controlStatus')
};

els.key.value = sessionStorage.getItem('moonControlKey') || '';
els.key.addEventListener('input', () => {
    sessionStorage.setItem('moonControlKey', els.key.value);
});

function number(value, digits = 3) {
    if (value === null || value === undefined) return '—';
    return Number(value).toLocaleString(undefined, {
        minimumFractionDigits: digits,
        maximumFractionDigits: digits
    });
}

function angle(value) {
    return value === null ? '—' : number(value, 3) + '°';
}

async function loadState() {
    const response = await fetch('api.php', { cache: 'no-store' });
    const data = await response.json();

    if (!response.ok || !data.success) {
        throw new Error(data.message || 'Failed to load state');
    }

    const state = data.state;
    els.runState.textContent = state.running ? 'RUNNING' : 'PAUSED';
    els.runState.className = 'value ' + (state.running ? 'running' : 'paused');
    els.simTime.textContent = number(state.simulated_time_seconds, 3) + ' s';
    els.tickSize.textContent =
        number(state.tick_seconds, 3) + ' s × ' + number(state.time_scale, 3);
    els.lastTick.textContent = state.last_tick_at || '—';

    els.body.innerHTML = '';

    for (const object of data.objects) {
        const tr = document.createElement('tr');

        if (object.root_id === object.id) {
            tr.classList.add('root-row');
        }

        const indent = '&nbsp;'.repeat(Math.min(object.depth, 8) * 4);
        const rootBadge = object.root_id === object.id
            ? '<span class="badge">physics root</span>'
            : '';

        const fuelText = object.fuel_capacity_kg > 0
            ? number(object.fuel_remaining_kg, 1)
              + ' / ' + number(object.fuel_capacity_kg, 1)
            : '—';

        tr.innerHTML = `
            <td>
                <span class="name">${indent}${escapeHtml(object.name)}${rootBadge}</span>
                <span class="meta">${escapeHtml(object.component_type)}</span>
            </td>
            <td>${object.parent_name ? escapeHtml(object.parent_name) : '—'}</td>
            <td>${number(object.x_km_earth_relative, 3)}</td>
            <td>${number(object.y_km_earth_relative, 3)}</td>
            <td>${number(object.z_km_earth_relative, 3)}</td>
            <td>${number(object.speed_m_s, 3)}</td>
            <td>${number(object.current_mass_kg, 1)}</td>
            <td>${fuelText}</td>
            <td>${angle(object.pitch_deg)}</td>
            <td>${angle(object.yaw_deg)}</td>
            <td>${angle(object.roll_deg)}</td>
        `;

        els.body.appendChild(tr);
    }
}

function escapeHtml(value) {
    return String(value)
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}

async function control(action) {
    if (action === 'reset' && !confirm('Reset the entire simulation to launch state?')) {
        return;
    }

    els.status.textContent = 'Working…';

    const response = await fetch('api.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Control-Key': els.key.value
        },
        body: JSON.stringify({ action })
    });

    const data = await response.json();

    if (!response.ok || !data.success) {
        throw new Error(data.message || 'Control request failed');
    }

    els.status.textContent = data.message || 'Done';
    await loadState();
}

document.querySelectorAll('button[data-action]').forEach(button => {
    button.addEventListener('click', async () => {
        try {
            await control(button.dataset.action);
        } catch (error) {
            els.status.textContent = error.message;
        }
    });
});

async function refresh() {
    try {
        await loadState();
    } catch (error) {
        els.status.textContent = error.message;
    }
}

refresh();
setInterval(refresh, 1000);
</script>
</body>
</html>
