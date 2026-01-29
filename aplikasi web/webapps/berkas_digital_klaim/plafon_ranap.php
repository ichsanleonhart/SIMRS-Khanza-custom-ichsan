<?php
/*
 * File: plafon_ranap.php
 * Fungsi: Input Plafon BPJS & Monitoring Profit/Loss
 */
session_start();
if (!isset($_SESSION['casemix_login']) || $_SESSION['casemix_login'] !== true) { header("Location: index.php"); exit; }
require_once('../conf/conf.php');
$koneksi = bukakoneksi();

// Data Instansi & User (Sama)
$q_set = mysqli_query($koneksi, "SELECT nama_instansi, logo FROM setting LIMIT 1");
$r_set = mysqli_fetch_assoc($q_set);
$nama_instansi = $r_set['nama_instansi'];
$logo_b64 = isset($r_set['logo']) ? 'data:image/jpeg;base64,' . base64_encode($r_set['logo']) : 'logo.php';

$user_id = $_SESSION['casemix_user'];
$nama_user_login = $user_id; 
$q_pegawai = mysqli_query($koneksi, "SELECT nama FROM pegawai WHERE nik = '$user_id'");
if(mysqli_num_rows($q_pegawai) > 0){ $nama_user_login = mysqli_fetch_assoc($q_pegawai)['nama']; } 
else { $q_dok = mysqli_query($koneksi, "SELECT nm_dokter FROM dokter WHERE kd_dokter = '$user_id'"); if(mysqli_num_rows($q_dok) > 0) $nama_user_login = mysqli_fetch_assoc($q_dok)['nm_dokter']; }

// Filter Tanggal
$tgl_awal  = isset($_GET['tgl_awal']) ? validTeks4($_GET['tgl_awal'], 10) : date('Y-m-d');
$tgl_akhir = isset($_GET['tgl_akhir']) ? validTeks4($_GET['tgl_akhir'], 10) : date('Y-m-d');

// SQL COST LOGIC (KHANZA LOGIC - RAW)
$khanza_cost_logic = "
    COALESCE(reg_periksa.biaya_reg, 0) +
    COALESCE(biaya_lab.total_lab, 0) +
    COALESCE(detail_biaya_lab.detail_total_lab, 0) +
    COALESCE(biaya_radiologi.total_radiologi, 0) +
    COALESCE(biaya_operasi.total_operasi, 0) +
    COALESCE(biaya_beri_obat.total_beri_obat, 0) +
    COALESCE(biaya_tagihan_obat_langsung.total_tagihan_obat_langsung, 0) +
    COALESCE(biaya_rawat_inap_dr.total_rawat_inap_dr, 0) +
    COALESCE(biaya_rawat_inap_drpr.total_rawat_inap_drpr, 0) +
    COALESCE(biaya_rawat_inap_pr.total_rawat_inap_pr, 0) +
    COALESCE(biaya_rawat_jl_dr.total_rawat_jl_dr, 0) +
    COALESCE(biaya_rawat_jl_drpr.total_rawat_jl_drpr, 0) +
    COALESCE(biaya_rawat_jl_pr.total_rawat_jl_pr, 0) +
    COALESCE(biaya_tambahan_biaya.total_tambahan_biaya, 0) -
    COALESCE(biaya_pengurangan_biaya.total_pengurangan_biaya, 0) +
    COALESCE(biaya_kamar_inap.total_kamar_inap, 0) +
    COALESCE(biaya_harian_kamar_inap.total_biaya_harian_kamar_inap, 0) -
    COALESCE(biaya_detreturjual.total_detreturjual, 0) +
    COALESCE(biaya_resep_pulang.total_resep_pulang, 0)
";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Input Plafon - <?= $nama_instansi ?></title>
    <link rel="icon" href="logo.php" type="image/png">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />

    <style>
        body { overflow-x: hidden; background-color: #f4f6f9; font-family: 'Segoe UI', sans-serif; font-size: 0.9rem; }
        
        /* Copy Style Sidebar dari Dashboard */
        #sidebar-wrapper { min-height: 100vh; width: 250px; margin-left: -250px; position: fixed; top: 0; left: 0; bottom: 0; z-index: 1000; transition: margin .25s ease-out; background: linear-gradient(180deg, #1e3c72 0%, #2a5298 100%); color: #fff; box-shadow: 4px 0 15px rgba(0,0,0,0.1); }
        #sidebar-wrapper .sidebar-heading { padding: 1.2rem 1rem; font-size: 1.1rem; border-bottom: 1px solid rgba(255,255,255,0.15); }
        #sidebar-wrapper .list-group { width: 250px; }
        #sidebar-wrapper .list-group-item { background: transparent; color: rgba(255,255,255,0.85); border: none; padding: 12px 20px; }
        #sidebar-wrapper .list-group-item:hover { background: rgba(255,255,255,0.15); color: #fff; border-left: 4px solid #fff; }
        #sidebar-wrapper .list-group-item.active { background: rgba(255,255,255,0.2); color: #fff; font-weight: bold; border-left: 4px solid #4cd137; }
        
        #page-content-wrapper { width: 100%; transition: margin .25s ease-out; }
        body.sb-sidenav-toggled #sidebar-wrapper { margin-left: 0; }
        @media (min-width: 768px) {
            #sidebar-wrapper { margin-left: 0; }
            #page-content-wrapper { margin-left: 250px; }
            body.sb-sidenav-toggled #sidebar-wrapper { margin-left: -250px; }
            body.sb-sidenav-toggled #page-content-wrapper { margin-left: 0; }
        }
        #overlay { display: none; position: fixed; width: 100vw; height: 100vh; background: rgba(0,0,0,0.5); z-index: 900; }
        body.sb-sidenav-toggled #overlay { display: block; }
        @media (min-width: 768px) { body.sb-sidenav-toggled #overlay { display: none; } }
        
        .top-navbar { background: #fff; box-shadow: 0 2px 5px rgba(0,0,0,0.05); padding: 10px 20px; }
        .table th { white-space: nowrap; text-align: center; vertical-align: middle; background-color: #f8f9fa; font-size: 0.85rem; }
        .table td { vertical-align: middle; font-size: 0.85rem; }
        
        /* Select2 Adjustment */
        .select2-container { width: 100% !important; }
    </style>
</head>
<body>

<div class="d-flex" id="wrapper">
    <div id="overlay" onclick="toggleMenu()"></div>
    <?php include 'sidebar.php'; ?>

    <div id="page-content-wrapper">
        <nav class="top-navbar d-flex justify-content-between align-items-center sticky-top">
            <button class="btn btn-outline-secondary border-0" id="menu-toggle"><i class="fas fa-bars fa-lg"></i></button>
            <div class="d-flex align-items-center">
                <div class="text-end me-3 d-none d-md-block line-height-sm">
                    <div class="fw-bold text-dark small"><?= $nama_user_login ?></div>
                    <small class="text-muted" style="font-size:0.75rem">Petugas Casemix</small>
                </div>
                <img src="https://ui-avatars.com/api/?name=<?= urlencode($nama_user_login) ?>&background=random" class="rounded-circle border" width="35">
            </div>
        </nav>

        <div class="container-fluid px-4 py-4">
            
            <div class="card shadow-sm mb-4 border-0">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold text-primary"><i class="fas fa-hand-holding-usd me-2"></i>Monitoring Profit/Loss (BPJS Ranap)</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="tablePlafon" class="table table-striped table-hover w-100">
                            <thead class="bg-light">
                                <tr>
                                    <th>No. Rawat</th>
                                    <th>No. RM</th>
                                    <th>Nama Pasien</th>
                                    <th width="25%">Kode ICD / Grouper</th>
                                    <th class="text-end">Billing Real</th>
                                    <th class="text-end">Tarif INACBG</th>
                                    <th class="text-end">Selisih</th>
                                    <th>DPJP</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $sql_plafon = "SELECT 
                                    reg_periksa.no_rawat, 
                                    reg_periksa.no_rkm_medis, 
                                    pasien.nm_pasien, 
                                    perkiraan_biaya_ranap.kd_penyakit, 
                                    ({$khanza_cost_logic}) AS billing_sementara, 
                                    perkiraan_biaya_ranap.tarif AS tarif_INACBG,
                                    (perkiraan_biaya_ranap.tarif - ({$khanza_cost_logic})) as selisih_raw,
                                    pegawai.nama as dpjp_nama
                                FROM reg_periksa
                                LEFT JOIN pasien ON reg_periksa.no_rkm_medis = pasien.no_rkm_medis
                                LEFT JOIN (SELECT no_rawat, Sum(biaya) AS total_lab FROM periksa_lab GROUP BY no_rawat) as biaya_lab ON reg_periksa.no_rawat = biaya_lab.no_rawat
                                LEFT JOIN (SELECT no_rawat, Sum(biaya_item) AS detail_total_lab FROM detail_periksa_lab GROUP BY no_rawat) as detail_biaya_lab ON reg_periksa.no_rawat = detail_biaya_lab.no_rawat
                                LEFT JOIN (SELECT no_rawat, Sum(biaya) AS total_radiologi FROM periksa_radiologi GROUP BY no_rawat) as biaya_radiologi ON reg_periksa.no_rawat = biaya_radiologi.no_rawat
                                LEFT JOIN (SELECT no_rawat, sum(biayaoperator1+biayaoperator2+biayaoperator3+biayaasisten_operator1+biayaasisten_operator2+biayaasisten_operator3+biayainstrumen+biayadokter_anak+biayaperawaat_resusitas+biayadokter_anestesi+biayaasisten_anestesi+biayaasisten_anestesi2+biayabidan+biayabidan2+biayabidan3+biayaperawat_luar+biayaalat+biayasewaok+akomodasi+bagian_rs+biaya_omloop+biaya_omloop2+biaya_omloop3+biaya_omloop4+biaya_omloop5+biayasarpras+biaya_dokter_pjanak+biaya_dokter_umum) AS total_operasi FROM operasi GROUP BY no_rawat) as biaya_operasi ON reg_periksa.no_rawat = biaya_operasi.no_rawat
                                LEFT JOIN (SELECT no_rawat, Sum(total) AS total_beri_obat FROM detail_pemberian_obat GROUP BY no_rawat) as biaya_beri_obat ON reg_periksa.no_rawat = biaya_beri_obat.no_rawat
                                LEFT JOIN (SELECT no_rawat, Sum(besar_tagihan) AS total_tagihan_obat_langsung FROM tagihan_obat_langsung GROUP BY no_rawat) as biaya_tagihan_obat_langsung ON reg_periksa.no_rawat = biaya_tagihan_obat_langsung.no_rawat
                                LEFT JOIN (SELECT no_rawat, sum(biaya_rawat) AS total_rawat_inap_dr FROM rawat_inap_dr GROUP BY no_rawat) as biaya_rawat_inap_dr ON reg_periksa.no_rawat = biaya_rawat_inap_dr.no_rawat
                                LEFT JOIN (SELECT no_rawat, sum(biaya_rawat) AS total_rawat_inap_drpr FROM rawat_inap_drpr GROUP BY no_rawat) as biaya_rawat_inap_drpr ON reg_periksa.no_rawat = biaya_rawat_inap_drpr.no_rawat
                                LEFT JOIN (SELECT no_rawat, sum(biaya_rawat) AS total_rawat_inap_pr FROM rawat_inap_pr GROUP BY no_rawat) as biaya_rawat_inap_pr ON reg_periksa.no_rawat = biaya_rawat_inap_pr.no_rawat
                                LEFT JOIN (SELECT no_rawat, sum(biaya_rawat) AS total_rawat_jl_dr FROM rawat_jl_dr GROUP BY no_rawat) as biaya_rawat_jl_dr ON reg_periksa.no_rawat = biaya_rawat_jl_dr.no_rawat
                                LEFT JOIN (SELECT no_rawat, sum(biaya_rawat) AS total_rawat_jl_drpr FROM rawat_jl_drpr GROUP BY no_rawat) as biaya_rawat_jl_drpr ON reg_periksa.no_rawat = biaya_rawat_jl_drpr.no_rawat
                                LEFT JOIN (SELECT no_rawat, sum(biaya_rawat) AS total_rawat_jl_pr FROM rawat_jl_pr GROUP BY no_rawat) as biaya_rawat_jl_pr ON reg_periksa.no_rawat = biaya_rawat_jl_pr.no_rawat
                                LEFT JOIN (SELECT no_rawat, sum(besar_biaya) AS total_tambahan_biaya FROM tambahan_biaya GROUP BY no_rawat) as biaya_tambahan_biaya ON reg_periksa.no_rawat = biaya_tambahan_biaya.no_rawat
                                LEFT JOIN (SELECT no_rawat, sum(besar_pengurangan) AS total_pengurangan_biaya FROM pengurangan_biaya GROUP BY no_rawat) as biaya_pengurangan_biaya ON reg_periksa.no_rawat = biaya_pengurangan_biaya.no_rawat
                                LEFT JOIN (SELECT no_rawat, sum(ttl_biaya) AS total_kamar_inap FROM kamar_inap GROUP BY no_rawat) as biaya_kamar_inap ON reg_periksa.no_rawat = biaya_kamar_inap.no_rawat
                                LEFT JOIN (SELECT ki.no_rawat, sum(bh.jml*bh.besar_biaya*ki.lama) AS total_biaya_harian_kamar_inap FROM kamar_inap ki inner join biaya_harian bh on ki.kd_kamar=bh.kd_kamar GROUP BY ki.no_rawat) as biaya_harian_kamar_inap ON reg_periksa.no_rawat = biaya_harian_kamar_inap.no_rawat
                                LEFT JOIN (SELECT left(no_retur_jual, 17) as no_rawat_retur, sum(subtotal) AS total_detreturjual FROM detreturjual GROUP BY no_rawat_retur) as biaya_detreturjual ON reg_periksa.no_rawat = biaya_detreturjual.no_rawat_retur
                                LEFT JOIN (SELECT no_rawat, sum(total) AS total_resep_pulang FROM resep_pulang GROUP BY no_rawat) as biaya_resep_pulang ON reg_periksa.no_rawat = biaya_resep_pulang.no_rawat
                                LEFT JOIN perkiraan_biaya_ranap ON reg_periksa.no_rawat = perkiraan_biaya_ranap.no_rawat
                                LEFT JOIN dpjp_ranap ON reg_periksa.no_rawat = dpjp_ranap.no_rawat
                                LEFT JOIN pegawai ON dpjp_ranap.kd_dokter = pegawai.nik
                                INNER JOIN kamar_inap ON reg_periksa.no_rawat = kamar_inap.no_rawat
                                WHERE reg_periksa.status_bayar = 'Belum Bayar' 
                                    AND reg_periksa.status_lanjut = 'Ranap'
                                    AND kamar_inap.stts_pulang = '-'
                                    AND reg_periksa.kd_pj = 'BPJ'
                                GROUP BY reg_periksa.no_rawat
                                ORDER BY selisih_raw ASC"; // Urutkan dari yang paling RUGI

                                $hasil = mysqli_query($koneksi, $sql_plafon);
                                while ($row = mysqli_fetch_assoc($hasil)) {
                                    $selisih = $row['selisih_raw'];
                                    $warna_selisih = ($selisih < 0) ? 'text-danger fw-bold' : 'text-success fw-bold';
                                    $id_select = str_replace(['/','\\'], '-', $row['no_rawat']); // ID aman untuk JS
                                ?>
                                <tr>
                                    <td><?= $row['no_rawat'] ?></td>
                                    <td><?= $row['no_rkm_medis'] ?></td>
                                    <td><?= $row['nm_pasien'] ?></td>
                                    <td>
                                        <div class="input-group input-group-sm">
                                            <select class="form-select select2-grouper" 
                                                    data-rawat="<?= $row['no_rawat'] ?>" 
                                                    id="grouper_<?= $id_select ?>">
                                                <?php if(!empty($row['kd_penyakit'])): ?>
                                                    <option value="<?= $row['tarif_INACBG'] ?>:<?= $row['kd_penyakit'] ?>" selected>
                                                        <?= $row['kd_penyakit'] ?> (Rp <?= number_format($row['tarif_INACBG'],0,',','.') ?>)
                                                    </option>
                                                <?php endif; ?>
                                            </select>
                                            <button class="btn btn-primary btn-sm btn-save" data-id="<?= $id_select ?>" data-rawat="<?= $row['no_rawat'] ?>">
                                                <i class="fas fa-save"></i>
                                            </button>
                                        </div>
                                    </td>
                                    <td class="text-end"><?= number_format($row['billing_sementara'], 0, ',', '.') ?></td>
                                    <td class="text-end"><?= number_format($row['tarif_INACBG'], 0, ',', '.') ?></td>
                                    <td class="text-end <?= $warna_selisih ?>"><?= number_format($selisih, 0, ',', '.') ?></td>
                                    <td><small><?= $row['dpjp_nama'] ?></small></td>
                                </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    // Sidebar Toggle
    document.getElementById("menu-toggle").onclick = function () { document.body.classList.toggle("sb-sidenav-toggled"); };

    $(document).ready(function() {
        // Init Datatable
        $('#tablePlafon').DataTable({ dom: 'Bfrtip', pageLength: 15 });

        // Init Select2 AJAX
        $('.select2-grouper').select2({
    theme: 'bootstrap-5',
    placeholder: 'Ketik nominal atau kode...',
    allowClear: true,
    minimumInputLength: 1, 
    tags: true, // Biarkan user input manual jika perlu
    ajax: {
        url: 'api/get_grouper.php', // Path relatif sudah benar karena dipanggil dari plafon_ranap.php
        dataType: 'json',
        delay: 250,
        data: function (params) { return { search: params.term }; },
        processResults: function (data) { return { results: data.results }; },
        cache: true,
        error: function(jqXHR, textStatus, errorThrown) {
            console.error("Select2 Error:", textStatus, errorThrown);
            // Ini akan membantu kita debug di Console Browser (F12) jika masih error
        }
    },
    templateResult: function(data) {
        if (!data.id) return data.text;
        if(data.text.includes('Manual:')) {
            return $('<span class="fw-bold text-success"><i class="fas fa-edit me-1"></i> ' + data.text + '</span>');
        }
        return data.text;
    }
});

        // Handle Save Button
        $(document).on('click', '.btn-save', function() {
            var rawat = $(this).data('rawat');
            var id_el = $(this).data('id');
            var val = $('#grouper_' + id_el).val();

            if(!val) { Swal.fire('Error', 'Pilih grouper/tarif dulu!', 'warning'); return; }

            Swal.fire({
                title: 'Simpan Tarif?',
                text: "Data perkiraan biaya akan diperbarui.",
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Ya, Simpan'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: 'api/save_grouper.php',
                        method: 'POST',
                        data: { case: rawat, grouper: val },
                        dataType: 'json',
                        success: function(resp) {
                            if(resp.status === 'success') {
                                Swal.fire('Berhasil!', resp.message, 'success').then(() => location.reload());
                            } else {
                                Swal.fire('Gagal!', resp.message, 'error');
                            }
                        },
                        error: function() { Swal.fire('Error', 'Gagal menghubungi server.', 'error'); }
                    });
                }
            });
        });
    });
</script>

</body>
</html>
<?php mysqli_close($koneksi); ?>