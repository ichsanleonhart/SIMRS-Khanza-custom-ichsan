# AI Progress & To-Do Tracker (Antigravity)

File ini digunakan untuk sinkronisasi konteks AI di berbagai *device* (Komputer Rumah -> Laptop Kerja).

## 🟢 Status Saat Ini (25 Februari 2026)
- **Modul**: `modules/edokter/ranap/index.php`
- **Pencapaian Terakhir**: Memperbaiki sistem pencarian dan tampilan DPJP di tabel pasien rawat inap E-Dokter. Mengubah query menjadi `LEFT JOIN` ke tabel `dokter` sehingga nama DPJP muncul bila sudah diset.

## 🟡 Rencana Selanjutnya (Next Action)

### 1. Rombak UI/UX Tombol Aksi (ERM)
- **Masalah Saat Ini**: Form ERM (E-Rekam Medis) menggunakan satu modal raksasa dengan banyak tab (CPPT, Resume, Resep, Lab, Rad, dll). Berpotensi membingungkan user jika menu bertambah banyak.
- **Solusi**: 
  - Ubah tombol tunggal [ ERM ] di kolom aksi menjadi **Tombol Dropdown**.
  - Kategorikan menu (*Medis, Penunjang, Lainnya*, dll).
  - Setiap menu memanggil modul/modal yang super ringan dan fokus hanya pada satu *form* (misal: klik CPPT hanya memunculkan form CPPT).

### 2. Integrasi Form Medis dengan Logic SIMKES Khanza (Java -> PHP)
- **Tujuan**: Menerjemahkan logika simpan/update/hapus dari kode `*.java` aplikasi *desktop* Khanza menjadi PHP.
- **Workflow**:
  1. *User* menyediakan *path* ke file java referensi (atau *paste* di obrolan).
  2. *AI* mempelajari alur query dan logika penyimpanan.
  3. *AI* merombak kode PHP aplikasi `mpp` agar *insert* ke *database* persisten dan kompatibel dengan SIMKES Khanza.

---
**Instruksi untuk AI (Saat pindah komputer):**
Jika user memerintahkan untuk membaca file ini, baca poin **Rencana Selanjutnya** dan tanyakan apakah kita akan memulai langkah pertama (Merombak UI Tombol Aksi ERM) sekarang.
