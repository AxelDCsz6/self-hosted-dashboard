// js/script.js
document.addEventListener('DOMContentLoaded', function() {
    console.log('Monitor system starting...');
    loadStats();
    setInterval(loadStats, 3000); // Actualizar cada 3 segundos
});

function loadStats() {
    fetch('api_stats.php?t=' + Date.now()) // Cache buster
        .then(response => {
            if (response.status === 401) {
                window.location.href = 'login.php';
                return;
            }
            if (!response.ok) {
                throw new Error('HTTP ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            if (data.error) {
                console.error('API Error:', data.error);
                showError('Error: ' + data.error);
                return;
            }

            updateCPU(data);
            updateRAM(data);
            updateDisk(data);
            updateUptime(data);
            updatePartitions(data);
        })
        .catch(error => {
            console.error('Fetch Error:', error);
            showError('Cannot connect to server');
        });
}

function updateCPU(data) {
    const cpuVal = document.getElementById('cpu-val');
    const cpuBar = document.getElementById('cpu-bar');
    
    if (data.cpu !== undefined && !isNaN(data.cpu)) {
        cpuVal.textContent = data.cpu.toFixed(1) + '%';
        cpuBar.style.width = Math.min(data.cpu, 100) + '%';
        cpuBar.style.backgroundColor = getColorForPercent(data.cpu);
    } else {
        cpuVal.textContent = 'N/A';
        cpuBar.style.width = '0%';
    }
}

function updateRAM(data) {
    const ramVal = document.getElementById('ram-val');
    const ramText = document.getElementById('ram-text');
    const ramBar = document.getElementById('ram-bar');
    
    if (data.ram_percent !== undefined && !isNaN(data.ram_percent)) {
        ramVal.textContent = data.ram_percent.toFixed(1) + '%';
        ramText.textContent = data.ram_text || '-- / --';
        ramBar.style.width = Math.min(data.ram_percent, 100) + '%';
        ramBar.style.backgroundColor = getColorForPercent(data.ram_percent);
    } else {
        ramVal.textContent = 'N/A';
        ramText.textContent = '-- / --';
        ramBar.style.width = '0%';
    }
}

function updateDisk(data) {
    const diskVal = document.getElementById('disk-val');
    const diskText = document.getElementById('disk-text');
    const diskBar = document.getElementById('disk-bar');
    
    if (data.disk_percent !== undefined && !isNaN(data.disk_percent)) {
        diskVal.textContent = data.disk_percent.toFixed(1) + '%';
        diskText.textContent = data.disk_text || '-- / --';
        diskBar.style.width = Math.min(data.disk_percent, 100) + '%';
        diskBar.style.backgroundColor = getColorForPercent(data.disk_percent);
    } else {
        diskVal.textContent = 'N/A';
        diskText.textContent = '-- / --';
        diskBar.style.width = '0%';
    }
}

function updateUptime(data) {
    const uptimeVal = document.getElementById('uptime-val');
    if (data.uptime) {
        uptimeVal.textContent = data.uptime;
        uptimeVal.style.fontSize = '1.8rem';
    } else {
        uptimeVal.textContent = 'N/A';
    }
}

function updatePartitions(data) {
    const container = document.getElementById('partitions');
    if (!data.partitions || !Array.isArray(data.partitions)) {
        container.innerHTML = '<div class="no-data">No partition data available</div>';
        return;
    }

    container.innerHTML = data.partitions.map(part => `
        <div class="partition-item">
            <div class="partition-header">
                <span class="partition-mount">${part.mount}</span>
                <span class="partition-percent">${part.pct}%</span>
            </div>
            <div class="partition-stats">
                ${part.used} GB / ${part.total} GB
            </div>
            <div class="progress-bg">
                <div class="progress-fill" style="width: ${part.pct}%; background-color: ${getColorForPercent(part.pct)}"></div>
            </div>
        </div>
    `).join('');
}

function getColorForPercent(percent) {
    if (percent > 90) return '#e74c3c';
    if (percent > 75) return '#f39c12';
    if (percent > 60) return '#f1c40f';
    return '#2ecc71';
}

function showError(message) {
    // Opcional: mostrar mensaje de error en la interfaz
    console.error('Monitor Error:', message);
}
