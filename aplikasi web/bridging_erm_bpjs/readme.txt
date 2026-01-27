BIKIN DULU TABLE BARU

CREATE TABLE IF NOT EXISTS `bridging_erm_status` (
  `no_sep` varchar(40) NOT NULL,
  `no_rawat` varchar(20) DEFAULT NULL,
  `status_kirim` enum('Belum','Sudah','Gagal') DEFAULT 'Belum',
  `waktu_kirim` datetime DEFAULT NULL,
  `respon_bpjs` text,
  `keterangan` text,
  PRIMARY KEY (`no_sep`),
  KEY `idx_status` (`status_kirim`),
  KEY `idx_rawat` (`no_rawat`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;



Cara Pemasangan
Buat folder baru di htdocs, misal: /erm_bridge.

Taruh ke-4 file di atas (erm_config.php, ErmBridging.php, erm_worker.php, index.php).

Jalankan SQL CREATE TABLE... di database.

Buka browser: http://localhost/erm_bridge/.

Klik START SERVICE.

Catatan Kritis
Looping: Di index.php, saya set interval 5 detik (const delay = 5000;). Jika server kuat, bisa dipercepat jadi 1000 (1 detik).

Data Limit: Di erm_worker.php, saya set $limit = 1. Artinya setiap 5 detik dia kirim 1 data. Ini sengaja agar efek terminalnya terlihat dan tidak membuat server timeout jika data banyak. Jika ingin lebih ngebut, ubah $limit jadi 5 atau 10.

Tabel Baru: Dengan adanya LEFT JOIN bridging_erm_status, sistem tidak akan mengirim ulang data yang sudah berstatus 'Sudah', kecuali Kamerad pakai fitur Manual Resend (karena di worker saya pakai INSERT ... ON DUPLICATE KEY UPDATE).