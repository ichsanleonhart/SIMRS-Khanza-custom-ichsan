<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
include 'koneksi.php';
date_default_timezone_set('Asia/Jakarta');

// Cek apakah user sudah login
if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit;
}

// Cek apakah form dikirim dengan tombol "simpan"
if (isset($_POST['simpan'])) {
  // Validasi isian form
  if (
    empty($_POST['nik']) || empty($_POST['nama']) || empty($_POST['jabatan']) ||
    empty($_POST['unit_kerja']) || empty($_POST['kategori']) || empty($_POST['keterangan'])
  ) {
    echo "<script>alert('Harap lengkapi semua field!'); window.history.back();</script>";
    exit;
  }

  // Ambil data dari POST
  $nik        = $_POST['nik'];
  $nama       = $_POST['nama'];
  $jabatan    = $_POST['jabatan'];
  $unit_kerja = $_POST['unit_kerja'];
  $kategori   = $_POST['kategori'];
  $petugas    = $_POST['petugas'] ?? '-';
  $keterangan = mysqli_real_escape_string($conn, $_POST['keterangan']);
  $tanggal    = date('Y-m-d H:i:s');

  // Generate Nomor Tiket
  $bulan = date('m');
  $tahun = date('Y');

  $cek_jumlah = mysqli_query($conn, "SELECT COUNT(*) as total FROM laporan_off_duty WHERE DATE(tanggal) = CURDATE()");
  $data_jumlah = mysqli_fetch_assoc($cek_jumlah);
  $urutan = str_pad($data_jumlah['total'] + 1, 4, '0', STR_PAD_LEFT);

  $no_tiket = "TKT{$urutan}/IT-OFFDUTY/{$bulan}/{$tahun}";

  // Ambil nama input dari session user
  $user_id = $_SESSION['user_id'];
  $query_user = mysqli_query($conn, "SELECT nama FROM users WHERE id = $user_id");
  $data_user  = mysqli_fetch_assoc($query_user);
  $nama_input = $data_user['nama'] ?? 'Tidak Diketahui';

  // Query simpan ke database
  $query = "INSERT INTO laporan_off_duty 
    (no_tiket, nik, nama, jabatan, unit_kerja, kategori, petugas, keterangan, tanggal, user_id, nama_input)
    VALUES 
    ('$no_tiket', '$nik', '$nama', '$jabatan', '$unit_kerja', '$kategori', '$petugas', '$keterangan', '$tanggal', '$user_id', '$nama_input')";

  if (mysqli_query($conn, $query)) {
    // --- Kirim ke Telegram ---
    $token_row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT nilai FROM setting WHERE nama = 'telegram_bot_token' LIMIT 1"));
    $chatid_row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT nilai FROM setting WHERE nama = 'telegram_chat_id' LIMIT 1"));

    $token = $token_row['nilai'] ?? '';
    $chat_id = $chatid_row['nilai'] ?? '';

    if ($token && $chat_id) {
      $pesan  = "<b>📢 LAPORAN OFF DUTY (LUAR JAM KERJA)</b>\n\n";
      $pesan .= "🎫 <b>No Tiket:</b> $no_tiket\n";
      $pesan .= "👤 <b>Nama:</b> $nama\n";
      $pesan .= "🆔 <b>NIK:</b> $nik\n";
      $pesan .= "💼 <b>Jabatan:</b> $jabatan\n";
      $pesan .= "🏢 <b>Unit:</b> $unit_kerja\n";
      $pesan .= "📂 <b>Kategori:</b> $kategori\n";
      $pesan .= "🛠️ <b>Petugas dipilih:</b> $petugas\n";
      $pesan .= "📝 <b>Keterangan:</b>\n<pre>$keterangan</pre>\n";
      $pesan .= "📅 <b>Waktu:</b> $tanggal\n";

      $url = "https://api.telegram.org/bot$token/sendMessage";
      $data = [
        'chat_id' => $chat_id,
        'text' => $pesan,
        'parse_mode' => 'HTML'
      ];

      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $url);
      curl_setopt($ch, CURLOPT_POST, true);
      curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      $response = curl_exec($ch);

      // Cek jika terjadi error saat kirim ke Telegram
      if ($response === false) {
        $curl_error = addslashes(curl_error($ch));
        echo "<script>alert('Laporan tersimpan, tapi gagal kirim Telegram: $curl_error'); window.location.href = 'off_duty.php';</script>";
        curl_close($ch);
        exit;
      }

      curl_close($ch);
    } else {
      echo "<script>alert('Laporan tersimpan, tapi token/chat_id Telegram tidak ditemukan.'); window.location.href = 'off_duty.php';</script>";
      exit;
    }

    // Berhasil semua
    echo "<script>
      alert('Laporan Off-Duty berhasil disimpan dan dikirim ke Telegram.');
      window.location.href = 'off_duty.php';
    </script>";
  } else {
    $error = addslashes(mysqli_error($conn));
    echo "<script>
      alert('Gagal menyimpan laporan: $error');
      window.history.back();
    </script>";
  }
}
?>
