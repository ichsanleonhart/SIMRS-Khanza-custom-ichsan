/**
 * -----------------------------------------------------------------------------
 * DISCLAIMER:
 * -----------------------------------------------------------------------------
 * File ini adalah hasil pengembangan oleh [MUHAMMAD NURYAHYA] dan hanya boleh 
 * digunakan oleh pihak yang telah mendapatkan izin langsung dari saya. Setiap 
 * penggunaan file ini tanpa izin yang sah akan mengharuskan pengguna untuk 
 * mendapatkan persetujuan terlebih dahulu. Penggunaan file ini harus sesuai 
 * dengan ketentuan dan persyaratan yang telah ditetapkan.
 * 
 * **Peringatan**: Dilarang menghapus, memodifikasi, atau mengubah bagian 
 * disclaimer ini dalam bentuk apapun. Jika file ini digunakan, bagian 
 * disclaimer ini harus tetap utuh dan tidak boleh dihapus atau diubah.
 * 
 * Silakan kembangkan sesuai kebutuhan Anda. Jika ada modifikasi, Anda 
 * dipersilakan untuk mengirimkan hasilnya melalui email ke 
 * arrayazman@gmail.com agar kita bisa saling mengembangkan, 
 * 
 * Tidak boleh dijual atau dikomersilkan.
 * -----------------------------------------------------------------------------
 * DONASI:
 * -----------------------------------------------------------------------------
 * Jika Anda ingin mendukung pengembangan lebih lanjut dari proyek ini, 
 * Anda dapat melakukan donasi melalui rekening bank saya sebagai berikut:
 * 
 * BANK  : BCA
 * a/n   : MUHAMMAD NURYAHYA 
 * REK   : 6695446563
 * WA    : 0851-6299-3322
 * Tele  : https://t.me/arrayazman
 * Email : arrayazman@gmail.com
 * 
 * Terima kasih atas dukungan Anda!
 * -----------------------------------------------------------------------------
 */

# Antrian-Poli
Antrian Poli Klinik
Sudah menggunakan text to speech menggunakan librari dari responsivevoice.js
data yang ditampilkan sudah sesuai jam praktek dokter
jalankan Query SQL berikut untuk update enum agar suara tidak looping

ALTER TABLE `antripoli` CHANGE `status` `status` ENUM('0','1','2','3') CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL;

CREATE TABLE `antriadmisi`  (
  `kd_loket` char(3) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `jenis` enum('kasir','loket') CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `status` enum('0','1','2','3') CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `norm` varchar(17) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `waktu` datetime NULL DEFAULT NULL
) ENGINE = MyISAM CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = DYNAMIC;

