# Rancang Bangun Implementasi Menu SBAR (SIMKES Khanza)

Dokumen ini berisi rancangan teknis yang disesuaikan untuk menambahkan fitur **SBAR (Situation, Background, Assessment, Recommendation)** ke dalam aplikasi desktop SIMKES Khanza berdasarkan pilihan desain berikut:
1. **Integrasi UI**: SBAR diintegrasikan sebagai tab tambahan di layar Rawat Inap ([DlgRawatInap.java](file:///C:/GITHUB/SIMRS-Khanza-custom-ichsan/src/simrskhanza/DlgRawatInap.java)) dan Rawat Jalan ([DlgRawatJalan.java](file:///C:/GITHUB/SIMRS-Khanza-custom-ichsan/src/simrskhanza/DlgRawatJalan.java)).
2. **Penyimpanan Database**: Menggunakan tabel terstruktur baru bernama `rekammedis_sbar` dengan kolom S, B, A, R terpisah.
3. **Riwayat Lengkap**: Menyajikan catatan SBAR pada tampilan riwayat perawatan pasien ([RMRiwayatPerawatan.java](file:///C:/GITHUB/SIMRS-Khanza-custom-ichsan/src/rekammedis/RMRiwayatPerawatan.java)).

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

## 📋 4. Integrasi ke Riwayat Perawatan Lengkap (`RMRiwayatPerawatan`)

Agar dokter dan perawat dapat meninjau catatan SBAR di dalam rekam medis terintegrasi penuh:
1. **Tambahkan Checkbox Kontrol**:
   - Di [RMRiwayatPerawatan.java](file:///C:/GITHUB/SIMRS-Khanza-custom-ichsan/src/rekammedis/RMRiwayatPerawatan.java) and [RMRiwayatPerawatan.form](file:///C:/GITHUB/SIMRS-Khanza-custom-ichsan/src/rekammedis/RMRiwayatPerawatan.form), kita tambahkan komponen checkbox `chkSBAR` (tipe `widget.CekBox`) ke panel filter menu agar pengguna bisa memilih apakah ingin memuat data SBAR ke HTML atau tidak.
2. **Kueri Data SBAR**:
   Mengambil data SBAR beserta informasi nama perawat (pemberi) dan nama dokter (penerima):
   ```sql
   select rekammedis_sbar.tanggal, rekammedis_sbar.jam, rekammedis_sbar.situation, 
          rekammedis_sbar.background, rekammedis_sbar.assessment, rekammedis_sbar.recommendation,
          rekammedis_sbar.nip_pemberi, pemberi.nama as nama_pemberi,
          rekammedis_sbar.nip_penerima, penerima.nm_dokter as nama_penerima,
          rekammedis_sbar.tbak, rekammedis_sbar.waktu_konfirmasi
   from rekammedis_sbar 
   inner join petugas as pemberi on rekammedis_sbar.nip_pemberi=pemberi.nip 
   left join dokter as penerima on rekammedis_sbar.nip_penerima=penerima.kd_dokter 
   where rekammedis_sbar.no_rawat=? 
   order by rekammedis_sbar.tanggal, rekammedis_sbar.jam
   ```
3. **Method Rendering HTML**:
   Kita buat method baru `menampilkanSBAR(String norawat)` untuk memformat kueri di atas menjadi tabel HTML yang disisipkan ke variabel `htmlContent`:
   ```java
   private void menampilkanSBAR(String norawat) {
       try {
           if(chkSBAR.isSelected()==true){
               try {
                   rs2=koneksi.prepareStatement(
                       "select rekammedis_sbar.tanggal, rekammedis_sbar.jam, rekammedis_sbar.situation, " +
                       "rekammedis_sbar.background, rekammedis_sbar.assessment, rekammedis_sbar.recommendation, " +
                       "rekammedis_sbar.nip_pemberi, pemberi.nama as nama_pemberi, " +
                       "rekammedis_sbar.nip_penerima, penerima.nm_dokter as nama_penerima, " +
                       "rekammedis_sbar.tbak, rekammedis_sbar.waktu_konfirmasi " +
                       "from rekammedis_sbar " +
                       "inner join petugas as pemberi on rekammedis_sbar.nip_pemberi=pemberi.nip " +
                       "left join dokter as penerima on rekammedis_sbar.nip_penerima=penerima.kd_dokter " +
                       "where rekammedis_sbar.no_rawat='"+norawat+"' order by rekammedis_sbar.tanggal, rekammedis_sbar.jam").executeQuery();
                   if(rs2.next()){
                       htmlContent.append("<tr class='isi'>").
                                       append("<td valign='top' width='2%'></td>").
                                       append("<td valign='top' width='18%'>Catatan SBAR & TBAK</td>").
                                       append("<td valign='top' width='1%' align='center'>:</td>").
                                       append("<td valign='top' width='79%'>").
                                       append("<table width='100%' border='0' align='center' cellpadding='3px' cellspacing='0' class='tbl_form'>");
                       do{
                           htmlContent.append("<tr>").
                                           append("<td valign='top'>").
                                               append("<table width='100%' border='0' align='center' cellpadding='3px' cellspacing='0px' class='tbl_form'>").
                                                   append("<tr>").
                                                       append("<td width='50%'><b>Waktu Laporan:</b> ").append(rs2.getString("tanggal")).append(" ").append(rs2.getString("jam")).append("</td>").
                                                       append("<td width='50%'><b>Pemberi Laporan (NIP):</b> ").append(rs2.getString("nama_pemberi")).append(" (").append(rs2.getString("nip_pemberi")).append(")</td>").
                                                   append("</tr>").
                                                   append("<tr>").
                                                       append("<td width='50%'><b>Penerima Laporan:</b> ").append(rs2.getString("nama_penerima") == null ? "-" : rs2.getString("nama_penerima")).append("</td>").
                                                       append("<td width='50%'><b>Status TBAK (Verifikasi Verbal):</b> ").append(rs2.getString("tbak")).append(" (Konfirmasi: ").append(rs2.getString("waktu_konfirmasi") == null ? "-" : rs2.getString("waktu_konfirmasi")).append(")</td>").
                                                   append("</tr>").
                                                   append("<tr>").
                                                       append("<td width='100%' colspan='2'><b>Situation (S):</b><br>").append(rs2.getString("situation").replaceAll("(\r\n|\r|\n|\n\r)","<br>")).append("</td>").
                                                   append("</tr>").
                                                   append("<tr>").
                                                       append("<td width='100%' colspan='2'><b>Background (B):</b><br>").append(rs2.getString("background").replaceAll("(\r\n|\r|\n|\n\r)","<br>")).append("</td>").
                                                   append("</tr>").
                                                   append("<tr>").
                                                       append("<td width='100%' colspan='2'><b>Assessment (A):</b><br>").append(rs2.getString("assessment").replaceAll("(\r\n|\r|\n|\n\r)","<br>")).append("</td>").
                                                   append("</tr>").
                                                   append("<tr>").
                                                       append("<td width='100%' colspan='2'><b>Recommendation (R):</b><br>").append(rs2.getString("recommendation").replaceAll("(\r\n|\r|\n|\n\r)","<br>")).append("</td>").
                                                   append("</tr>").
                                               append("</table>").
                                           append("</td>").
                                       append("</tr>");
                       }while(rs2.next());
                       htmlContent.append("</table>").
                                   append("</td>").
                                   append("</tr>");
                   }
               } catch (Exception e) {
                   System.out.println("Notifikasi : "+e);
               } finally{
                   if(rs2!=null){
                       rs2.close();
                   }
               }
           }
       } catch (Exception e) {
           System.out.println("Notif SBAR : "+e);
       }
   }
   ```
4. **Pemanggilan Method**:
   Kita panggil `menampilkanSBAR(rs.getString("no_rawat"));` di dalam loop rekam medis utama `RMRiwayatPerawatan.java` agar secara otomatis dirender saat checkbox `chkSBAR` dicentang.

---

## 🔄 5. Alur Validasi TBAK (Tulis Baca Konfirmasi)

Sistem akan memvalidasi status konfirmasi verbal:
* Jika status **TBAK** diubah dari `Belum` ke `Sudah`, field `waktu_konfirmasi` diisi otomatis dengan waktu server saat ini (`NOW()`).
* NIP Penerima harus berupa data Dokter (`dokter.kd_dokter` / `dokter.nm_dokter`), sedangkan NIP Pemberi dapat berupa Perawat/Petugas.

---

## 🖨️ 6. Desain Output Cetak SBAR

* Laporan SBAR akan dibangun menggunakan JasperReports (`.jrxml` file).
* Berkas laporan [rptSBAR.jrxml](file:///C:/GITHUB/SIMRS-Khanza-custom-ichsan/report/rptSBAR.jrxml) diletakkan di folder `report/`.
* Data dilewatkan menggunakan kueri SQL berdasarkan parameter `no_rawat`, `tanggal`, dan `jam` catatan SBAR terpilih.
