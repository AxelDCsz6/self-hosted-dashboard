document.addEventListener('DOMContentLoaded', function() {
    // Detectar si estamos en la página de monitor
    if(document.getElementById('monitor-view')) {
        updateStats();
        setInterval(updateStats, 2000); // Actualizar cada 2 segundos
    }
});

function updateStats() {
    fetch('includes/api_stats.php')
    .then(response => response.json())
    .then(data => {
        if(data.error) {
            console.error('API Error:', data.error);
            return;
        }

        // Helper para actualizar elementos con seguridad
        const setTxt = (id, txt) => { let el = document.getElementById(id); if(el) el.innerText = txt; };
        const setWidth = (id, pct, color) => { 
            let el = document.getElementById(id); 
            if(el) {
                el.style.width = pct + '%';
                if(color) el.style.backgroundColor = color;
            }
        };

        // CPU
        setTxt('cpu-val', data.cpu + '%');
        setWidth('cpu-bar', data.cpu, getColor(data.cpu));

        // RAM
        setTxt('ram-val', data.ram_percent + '%');
        setTxt('ram-text', data.ram_text);
        setWidth('ram-bar', data.ram_percent, getColor(data.ram_percent));

        // DISCO
        setTxt('disk-val', data.disk_percent + '%');
        setTxt('disk-text', data.disk_text);
        setWidth('disk-bar', data.disk_percent, getColor(data.disk_percent));

        // UPTIME
        setTxt('uptime-val', formatUptime(data.uptime));

        // PARTICIONES
        const partContainer = document.getElementById('partitions');
        if(partContainer && data.partitions) {
            let html = '';
            data.partitions.forEach(p => {
                let color = getColor(p.pct);
                html += `
                <div style="background:rgba(255,255,255,0.05); padding:10px; border-radius:6px; margin-bottom:10px;">
                    <div style="display:flex; justify-content:space-between; margin-bottom:5px; font-size:0.9rem;">
                        <span><i class="fas fa-hdd"></i> ${p.mount}</span>
                        <span>${p.pct}%</span>
                    </div>
                    <div class="progress-track" style="height:4px;">
                        <div class="progress-fill" style="width:${p.pct}%; background:${color}"></div>
                    </div>
                    <div style="font-size:0.75rem; color:#888; margin-top:4px; text-align:right;">
                        ${p.used} GB / ${p.total} GB
                    </div>
                </div>`;
            });
            partContainer.innerHTML = html;
        }
    })
    .catch(err => console.error('Error fetching stats:', err));
}

function getColor(pct) {
    if(pct > 90) return '#cf6679'; // Rojo
    if(pct > 70) return '#ffd600'; // Amarillo
    return '#03dac6'; // Teal/Verde
}

function formatUptime(seconds) {
    const d = Math.floor(seconds / (3600*24));
    const h = Math.floor(seconds % (3600*24) / 3600);
    const m = Math.floor(seconds % 3600 / 60);
    return `${d}d ${h}h ${m}m`;
}
