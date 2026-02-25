<?php
// File: modules/mpp/index.php
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../helpers/auth_helper.php';

cekLogin();
if (!cekAkses('mpp_skrining')) {
    header("Location: " . $base_url . "modules/dashboard/index.php");
    exit;
}

require_once '../../layout/header.php';
require_once '../../layout/sidebar.php';
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="h5 mb-0 fw-bold text-gray-800">Dashboard Manajer Pelayanan Pasien</h4>
            <small class="text-muted">Overview Kinerja & Profil Risiko Pasien</small>
        </div>
        <div>
            <a href="master_masalah.php" class="btn btn-outline-secondary btn-sm me-2"><i class="fas fa-database me-1"></i> Master Masalah</a>
            <a href="../../modules/ranap/index.php" class="btn btn-primary btn-sm shadow-sm"><i class="fas fa-search me-1"></i> Cari Pasien untuk Skrining</a>
        </div>
    </div>

    <div class="row g-3 mb-4">
        
        <div class="col-lg-6">
            <div class="card shadow-sm h-100 border-0">
                <div class="card-header bg-white py-3 border-bottom-0">
                    <h6 class="m-0 fw-bold text-primary"><i class="fas fa-chart-bar me-2"></i>5 Alasan Skrining Terbanyak</h6>
                </div>
                <div class="card-body">
                    <div style="position: relative; height: 300px; width: 100%;">
                        <canvas id="chartAlasan"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3">
            <div class="card shadow-sm h-100 border-0">
                <div class="card-header bg-white py-3 border-bottom-0">
                    <h6 class="m-0 fw-bold text-info"><i class="fas fa-chart-pie me-2"></i>Profil Risiko Pasien</h6>
                </div>
                <div class="card-body">
                    <div style="position: relative; height: 200px; width: 100%;">
                        <canvas id="chartRisiko"></canvas>
                    </div>
                    <div class="mt-3 text-center small text-muted">
                        *Tinggi: >3 Parameter Terpenuhi
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3">
            <div class="card shadow-sm h-100 border-0">
                <div class="card-header bg-white py-3 border-bottom-0">
                    <h6 class="m-0 fw-bold text-success"><i class="fas fa-user-nurse me-2"></i>Top Performance</h6>
                </div>
                <div class="card-body p-0" style="max-height: 350px; overflow-y: auto;">
                    <ul class="list-group list-group-flush" id="listPetugas">
                        <li class="list-group-item text-center text-muted py-4">Memuat data...</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3 border-bottom-0">
                    <h6 class="m-0 fw-bold text-warning"><i class="fas fa-exclamation-triangle me-2"></i>Masalah Paling Sering Muncul (Evaluasi Form A)</h6>
                </div>
                <div class="card-body">
                    <div style="position: relative; height: 250px; width: 100%;">
                        <canvas id="chartMasalah"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<?php require_once '../../layout/footer.php'; ?>

<script>
$(document).ready(function() {
    
    // Load Data Analytics
    $.ajax({
        url: 'data_analytics.php',
        type: 'GET',
        dataType: 'json',
        success: function(res) {
            if(res.error) {
                console.error(res.error);
                return;
            }
            initChartAlasan(res.chart1);
            initChartRisiko(res.chart2);
            initChartMasalah(res.chart3);
            renderListPetugas(res.chart4);
        },
        error: function(e) {
            console.log('Error fetching analytics:', e);
        }
    });

    function initChartAlasan(data) {
        if(!data) return;
        new Chart(document.getElementById('chartAlasan'), {
            type: 'bar',
            data: {
                labels: data.labels,
                datasets: [{
                    label: 'Jumlah Pasien',
                    data: data.values,
                    backgroundColor: '#4e73df',
                    borderRadius: 5
                }]
            },
            options: {
                indexAxis: 'y', 
                responsive: true,
                maintainAspectRatio: false, // PENTING
                plugins: { legend: { display: false } }
            }
        });
    }

    function initChartRisiko(data) {
        if(!data) return;
        new Chart(document.getElementById('chartRisiko'), {
            type: 'doughnut',
            data: {
                labels: data.labels,
                datasets: [{
                    data: data.values,
                    backgroundColor: ['#1cc88a', '#f6c23e', '#e74a3b'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false, // PENTING
                cutout: '70%',
                plugins: { 
                    legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 10 } } } 
                }
            }
        });
    }

    function initChartMasalah(data) {
        if(!data) return;
        new Chart(document.getElementById('chartMasalah'), {
            type: 'bar',
            data: {
                labels: data.labels,
                datasets: [{
                    label: 'Frekuensi Masalah',
                    data: data.values,
                    backgroundColor: 'rgba(255, 159, 64, 0.7)',
                    borderColor: 'rgba(255, 159, 64, 1)',
                    borderWidth: 1,
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false, // PENTING
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true } }
            }
        });
    }

    function renderListPetugas(data) {
        let html = '';
        if(data && data.length > 0) {
            data.forEach((item, index) => {
                let badgeColor = index === 0 ? 'bg-warning text-dark' : 'bg-light text-secondary';
                let icon = index === 0 ? '<i class="fas fa-crown text-warning me-2"></i>' : '';
                
                html += `
                <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                    <div class="d-flex align-items-center">
                        <div class="fw-bold small text-truncate" style="max-width: 150px;">${icon}${item.nama}</div>
                    </div>
                    <span class="badge ${badgeColor} rounded-pill">${item.total} Px</span>
                </li>`;
            });
        } else {
            html = '<li class="list-group-item text-center text-muted">Belum ada data kinerja.</li>';
        }
        $('#listPetugas').html(html);
    }

});
</script>