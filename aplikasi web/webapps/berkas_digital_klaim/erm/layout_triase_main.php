<style>
    /* Reset & Base Style */
    * { box-sizing: border-box; }
    body { font-family: Tahoma, Arial, sans-serif; font-size: 11px; margin: 0; }
    
    /* Table Styling agar mirip Jasper Report */
    table.main-table { width: 100%; border-collapse: collapse; margin-bottom: 5px; page-break-inside: avoid; }
    table.main-table td, table.main-table th { border: 1px solid #000; padding: 4px; vertical-align: top; }
    
    /* Header Specific */
    .header-table { width: 100%; border-collapse: collapse; border: 1px solid #000; margin-bottom: 0; }
    .header-table td { padding: 4px; vertical-align: middle; }
    
    /* Color Classes */
    .bg-dark-red { background-color: #990000; color: #fff; font-weight: bold; text-align: center; padding: 5px; }
    .bg-cream { background-color: #F5F5DC; font-weight: bold; } /* Warna krem untuk header section */
    .text-center { text-align: center; }
    .text-bold { font-weight: bold; }
    .v-middle { vertical-align: middle; }
    
    /* Font Sizes */
    .fs-10 { font-size: 10px; }
    .fs-12 { font-size: 12px; }
    .fs-14 { font-size: 14px; }
</style>

<table class="header-table">
    <tr>
        <td width="15%" class="text-center" style="border-right: 1px solid #000;">
            <img src="<?= $logo_b64; ?>" style="width: 60px; height: 60px;">
        </td>
        
        <td width="45%" class="text-center" style="border-right: 1px solid #000;">
            <span class="fs-14 text-bold"><?= strtoupper($setting['nama_instansi']); ?></span><br>
            <span class="fs-10"><?= $setting['alamat_instansi']; ?>, <?= $setting['kabupaten']; ?></span><br>
            <span class="fs-10"><?= $setting['kontak']; ?> | E-mail: <?= $setting['email']; ?></span>
        </td>
        
        <td width="40%" style="vertical-align: top; padding: 2px 5px;">
            <table width="100%" style="border: none;">
                <tr>
                    <td width="30%" style="border: none; font-size: 10px;">Nomor RM</td>
                    <td style="border: none; font-size: 10px;">: <?= $d_umum['no_rkm_medis']; ?></td>
                </tr>
                <tr>
                    <td style="border: none; font-size: 10px;">Nama</td>
                    <td style="border: none; font-size: 10px;">: <?= $d_umum['nm_pasien']; ?></td>
                </tr>
                <tr>
                    <td style="border: none; font-size: 10px;">Tanggal Lahir</td>
                    <td style="border: none; font-size: 10px;">: <?= formatTgl($d_umum['tgl_lahir']); ?></td>
                </tr>
                <tr>
                    <td style="border: none; font-size: 10px;">Jenis Kelamin</td>
                    <td style="border: none; font-size: 10px;">: <?= $d_umum['jk'] == 'L' ? 'Laki-Laki' : 'Perempuan'; ?></td>
                </tr>
            </table>
        </td>
    </tr>
</table>

<table style="width: 100%; border-collapse: collapse; border: 1px solid #000; border-top: none;">
    <tr>
        <td class="bg-dark-red">TRIASE PASIEN GAWAT DARURAT</td>
    </tr>
    <tr>
        <td class="text-center" style="border-bottom: 1px solid #000; padding: 3px; font-size: 10px;">
            Triase dilakukan segera setelah pasien datang dan sebelum pasien/ keluarga mendaftar di TPP IGD
        </td>
    </tr>
</table>

<table class="main-table" style="border-top: none;">
    <tr>
        <td width="50%" style="border-bottom: 1px solid #000;">
            Tanggal Kunjungan : <?= formatTgl($d_umum['tgl_registrasi']); ?>
        </td>
        <td width="50%" style="border-bottom: 1px solid #000;">
            Pukul : <?= formatJam($d_umum['tgl_registrasi']); ?>
        </td>
    </tr>
    
    <tr>
        <td width="20%">Cara Datang</td>
        <td width="80%"><?= $d_khusus['caradatang'] ?? '-'; ?></td> </tr>
    <tr>
        <td>Macam Kasus</td>
        <td><?= $d_umum['macam_kasus'] ?? '-'; ?></td>
    </tr>

    <tr class="bg-cream">
        <td class="text-center">KETERANGAN</td>
        <td class="text-center">TRIASE <?= $tipe_triase; ?></td>
    </tr>

    <tr>
        <td style="height: 50px;">KELUHAN UTAMA</td>
        <td><?= $d_khusus['keluhanutama'] ?? '-'; ?></td>
    </tr>

    <tr>
        <td>TANDA VITAL</td>
        <td>
            Suhu (C) : <?= $d_khusus['suhu'] ?? '-'; ?>, 
            Nyeri : <?= $d_khusus['nyeri'] ?? '-'; ?>, 
            Tensi : <?= $d_khusus['tensi'] ?? '-'; ?>, 
            Nadi(/menit) : <?= $d_khusus['nadi'] ?? '-'; ?>, 
            Saturasi O2(%) : <?= $d_khusus['saturasi'] ?? '-'; ?>, 
            Respirasi(/menit) : <?= $d_khusus['rr'] ?? '-'; ?>
        </td>
    </tr>

    <tr>
        <td>KEBUTUHAN KHUSUS</td>
        <td><?= $d_khusus['kebutuhan_khusus'] ?? '-'; ?></td>
    </tr>

    <tr>
        <td class="bg-cream text-center">PEMERIKSAAN</td>
        <td style="background-color: <?= $config['warna_bg']; ?>; color: <?= $config['warna_txt']; ?>; font-weight: bold; text-align: center;">
            <?= $config['sub_judul']; ?>
        </td>
    </tr>

    <?php if(!empty($checklist_data)): ?>
        <?php foreach($checklist_data as $check): ?>
        <tr>
            <td><?= strtoupper($check['kategori']); ?></td>
            <td style="background-color: <?= $config['warna_bg']; ?>; color: <?= $config['warna_txt']; ?>;">
                <?= $check['nilai']; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    <?php else: ?>
        <tr>
            <td>PEMERIKSAAN FISIK</td>
            <td style="background-color: <?= $config['warna_bg']; ?>; color: <?= $config['warna_txt']; ?>;">-</td>
        </tr>
    <?php endif; ?>

    <tr>
        <td>PLAN</td>
        <td style="background-color: <?= $config['warna_bg']; ?>; color: <?= $config['warna_txt']; ?>;">
            <?= $d_khusus['plan'] ?? $d_khusus['keterangan'] ?? '-'; ?>
        </td>
    </tr>

    <tr class="bg-cream">
        <td></td>
        <td class="text-center">Petugas Triase <?= ucfirst(strtolower($tipe_triase)); ?></td>
    </tr>
	
	 <tr>
        <td>Tanggal & Jam</td>
        <td><?= formatTgl($d_khusus['tanggaltriase'] ?? $d_umum['tgl_registrasi']); ?> </td>
    </tr>
	
	 <tr>
        <td>Catatan</td>
        <td><?= $d_khusus['keterangan'] ?? '-'; ?></td>
    </tr>

    <tr>
        <td> Dokter/Petugas Jaga IGD</td>
        <td class="text-center">
            <?php if(!empty($qr_b64)): ?>
            <img src="<?= $qr_b64; ?>" style="width: 100px; height: 100px;">
            <?php endif; ?>
			<br>
			<?= $nama_perawat; ?>
        </td>
    </tr>
</table>