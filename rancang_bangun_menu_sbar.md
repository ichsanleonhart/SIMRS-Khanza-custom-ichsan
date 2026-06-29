# Rancang Bangun Implementasi Menu SBAR (SIMKES Khanza)

Dokumen ini berisi rancangan teknis yang disesuaikan untuk menambahkan fitur **SBAR (Situation, Background, Assessment, Recommendation)** ke dalam aplikasi desktop SIMKES Khanza berdasarkan pilihan desain berikut:
1. **Integrasi UI**: SBAR diintegrasikan sebagai tab tambahan di layar Rawat Inap ([DlgRawatInap.java](file:///C:/GITHUB/SIMRS-Khanza-custom-ichsan/src/simrskhanza/DlgRawatInap.java)) dan Rawat Jalan ([DlgRawatJalan.java](file:///C:/GITHUB/SIMRS-Khanza-custom-ichsan/src/simrskhanza/DlgRawatJalan.java)).
2. **Penyimpanan Database**: Menggunakan tabel terstruktur baru bernama `rekammedis_sbar` dengan kolom S, B, A, R terpisah.

---

## 🗄️ 1. Skema Database (`rekammedis_sbar`)

Tabel baru akan dibuat untuk menyimpan data secara terstruktur dengan relasi ke tabel registrasi periksa (`reg_periksa`):

```sql
CREATE TABLE `rekammedis_sbar` (
  `no_rawat` varchar(17) NOT NULL,
  `tanggal` date NOT NULL,
  `jam` time NOT NULL,
  `situation` text NOT NULL,
  `background` text NOT NULL,
  `assessment` text NOT NULL,
  `recommendation` text NOT NULL,
  `nip_pemberi` varchar(20) NOT NULL,        -- NIP perawat yang melapor
  `nip_penerima` varchar(20) DEFAULT NULL,    -- NIP dokter yang menerima laporan/instruksi
  `tbak` enum('Belum', 'Sudah') DEFAULT 'Belum', -- Status Tulis Baca Konfirmasi (TBAK)
  `waktu_konfirmasi` datetime DEFAULT NULL,   -- Kapan dokter mengonfirmasi verbal
  PRIMARY KEY (`no_rawat`, `tanggal`, `jam`),
  KEY `nip_pemberi` (`nip_pemberi`),
  KEY `nip_penerima` (`nip_penerima`),
  CONSTRAINT `rekammedis_sbar_ibfk_1` FOREIGN KEY (`no_rawat`) REFERENCES `reg_periksa` (`no_rawat`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
```

---

## 🎨 2. Rancangan UI Modular: `PanelSBAR`

Untuk mencegah bertambahnya kompleksitas dan ukuran file [DlgRawatInap.java](file:///C:/GITHUB/SIMRS-Khanza-custom-ichsan/src/simrskhanza/DlgRawatInap.java) (9.000+ baris) & [DlgRawatJalan.java](file:///C:/GITHUB/SIMRS-Khanza-custom-ichsan/src/simrskhanza/DlgRawatJalan.java) (10.000+ baris), kita akan menerapkan konsep **Modular GUI Panel**.

Kita akan membuat satu kelas panel kustom yang bisa dipakai ulang (reusable panel):
* **Nama Kelas**: `PanelSBAR` (berupa `javax.swing.JPanel` kustom)
* **Lokasi Berkas**: [PanelSBAR.java](file:///C:/GITHUB/SIMRS-Khanza-custom-ichsan/src/rekammedis/PanelSBAR.java) & [PanelSBAR.form](file:///C:/GITHUB/SIMRS-Khanza-custom-ichsan/src/rekammedis/PanelSBAR.form) di dalam package `rekammedis`.

### Tampilan Desain `PanelSBAR`:
* **Input Area**: 
  - `JTextArea` untuk **Situation (S)**, **Background (B)**, **Assessment (A)**, dan **Recommendation (R)**.
  - `JTextField` untuk **NIP Pemberi** (Perawat) dan **NIP Penerima** (Dokter) disertai tombol pencarian dialog petugas/dokter.
  - `JComboBox` untuk status **TBAK** (`Belum`/`Sudah`).
* **Table History**:
  - `JTable` di bagian bawah untuk melihat daftar histori catatan SBAR dari pasien bersangkutan.
* **Control Buttons**:
  - Tombol **Simpan, Baru, Hapus, Ganti, Keluar, Cetak**.

---

## 🔗 3. Alur Integrasi pada Layar Rawat Inap & Rawat Jalan

Metode modular ini diintegrasikan ke layar Rawat Inap/Jalan dengan langkah berikut:

### Langkah A: Inisialisasi Panel di DlgRawatInap & DlgRawatJalan
1. Tambahkan variabel instansi `PanelSBAR` di bagian atas berkas:
   ```java
   private rekammedis.PanelSBAR panelSBAR = new rekammedis.PanelSBAR();
   ```
2. Tambahkan panel tersebut ke dalam `TabRawat` (JTabbedPane) saat inisialisasi komponen:
   ```java
   TabRawat.addTab("Catatan SBAR", panelSBAR);
   ```

### Langkah B: Sinkronisasi Data Pasien Aktif
Ketika pengguna memilih pasien di tabel utama rawat inap atau rawat jalan, method pencatat data (biasanya `getData()` atau event klik tabel) akan memicu pembaruan data pada `PanelSBAR`:
```java
private void getData() {
    if (tbKamIn.getSelectedRow() != -1) {
        TNoRw.setText(tbKamIn.getValueAt(tbKamIn.getSelectedRow(), 0).toString());
        // ... (Kode bawaan lainnya)
        
        // Kirim no_rawat aktif ke Panel SBAR dan muat datanya
        panelSBAR.setNoRawat(TNoRw.getText());
        panelSBAR.tampil();
    }
}
```

---

## 🔄 4. Logika Validasi TBAK (Tulis Baca Konfirmasi)

Sistem akan memvalidasi status konfirmasi verbal:
* Jika status **TBAK** diubah dari `Belum` ke `Sudah`, field `waktu_konfirmasi` diisi otomatis dengan waktu server saat ini (`NOW()`).
* NIP Penerima harus berupa data Dokter (`dokter.kd_dokter` / `dokter.nm_dokter`), sedangkan NIP Pemberi dapat berupa Perawat/Petugas.

---

## 🖨️ 5. Desain Output Cetak SBAR

* Laporan SBAR akan dibangun menggunakan JasperReports (`.jrxml` file).
* Berkas laporan [rptSBAR.jrxml](file:///C:/GITHUB/SIMRS-Khanza-custom-ichsan/report/rptSBAR.jrxml) diletakkan di folder `report/`.
* Data dilewatkan menggunakan kueri SQL berdasarkan parameter `no_rawat`, `tanggal`, dan `jam` catatan SBAR terpilih.
