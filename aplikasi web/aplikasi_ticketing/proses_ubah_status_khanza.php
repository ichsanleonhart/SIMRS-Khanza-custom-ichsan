<?php
session_start();
require 'koneksi.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';
require 'PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $id      = $_POST['id'];
    $status  = $_POST['status'];
    $catatan = $_POST['catatan'] ?? '';

    // Update status dan catatan
    $stmt = $conn->prepare("UPDATE akses_khanza SET status = ?, catatan_admin = ? WHERE id = ?");
    $stmt->bind_param("ssi", $status, $catatan, $id);
    $stmt->execute();

    // Kirim notifikasi email jika selesai
    if ($status === 'selesai') {
        // Ambil data user peminta akses
        $stmtUser = $conn->prepare("SELECT u.email, u.nama, a.subjek, a.deskripsi, a.tanggal 
                                    FROM akses_khanza a 
                                    JOIN users u ON a.user_id = u.id 
                                    WHERE a.id = ?");
        $stmtUser->bind_param("i", $id);
        $stmtUser->execute();
        $data = $stmtUser->get_result()->fetch_assoc();

        // Ambil setting email dari database
        $getMail = $conn->query("SELECT * FROM mail_settings LIMIT 1");
        $mailSet = $getMail->fetch_assoc();

        if ($data && $mailSet && filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host       = $mailSet['mail_host'];
                $mail->SMTPAuth   = true;
                $mail->Username   = $mailSet['mail_username'];
                $mail->Password   = $mailSet['mail_password'];
                $mail->SMTPSecure = 'tls';
                $mail->Port       = $mailSet['mail_port'];

                $mail->setFrom($mailSet['mail_from_email'], $mailSet['mail_from_name']);
                $mail->addAddress($data['email'], $data['nama']);

                $mail->isHTML(true);
                $mail->Subject = 'Permintaan Akses Anda Telah Diselesaikan';
                $mail->Body = "
                    Assalamualaikum wr. wb,<br><br>
                    Berikut persetujuan permintaan buka akses Khanza atas nama:<br>
                    <strong>" . htmlspecialchars($data['nama']) . "</strong><br><br>
                    <strong>Catatan Admin:</strong><br>
                    <em>" . nl2br(htmlspecialchars($catatan)) . "</em><br><br>
                    Terima kasih.<br>
                    <strong>FixPoint Tim</strong>
                ";

                $mail->send();
            } catch (Exception $e) {
                error_log("Email gagal dikirim: {$mail->ErrorInfo}");
            }
        }
    }

    echo "<script>alert('Status berhasil diperbarui.'); window.location = 'data_akses_khanza.php';</script>";
}
?>
