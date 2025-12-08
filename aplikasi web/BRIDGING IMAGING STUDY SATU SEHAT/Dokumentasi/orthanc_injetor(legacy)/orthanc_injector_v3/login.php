<?php
session_start();
require_once 'config.php';

if (isset($_POST['login'])) {
    $u = $_POST['username'];
    $p = $_POST['password'];

    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    // Query Validasi Admin Khanza (AES Decrypt)
    $sql = "SELECT AES_DECRYPT(usere, '".AES_KEY_USER."') as usere, 
                   AES_DECRYPT(passworde, '".AES_KEY_PASS."') as passworde 
            FROM admin 
            WHERE AES_DECRYPT(usere, '".AES_KEY_USER."') = ?";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $u);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows > 0) {
        $row = $res->fetch_assoc();
        if ($row['passworde'] === $p) {
            // Login Sukses
            $_SESSION['user_admin'] = $row['usere'];
            header("Location: admin_panel.php"); // Masuk ke Cockpit
            exit();
        }
    }
    $error = "Username atau Password Salah!";
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Login Admin Radiologi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>body { background: #121212; color: #eee; height: 100vh; display: flex; align-items: center; justify-content: center; }</style>
</head>
<body>
    <div class="card bg-dark border-secondary" style="width: 350px;">
        <div class="card-header border-secondary fw-bold text-info">🔐 RESTRICTED AREA</div>
        <div class="card-body">
            <?php if(isset($error)) echo "<div class='alert alert-danger py-1'>$error</div>"; ?>
            <form method="POST">
                <div class="mb-3">
                    <label>Username</label>
                    <input type="text" name="username" class="form-control bg-secondary text-white border-0" required autofocus>
                </div>
                <div class="mb-3">
                    <label>Password</label>
                    <input type="password" name="password" class="form-control bg-secondary text-white border-0" required>
                </div>
                <button type="submit" name="login" class="btn btn-info w-100 fw-bold">MASUK</button>
            </form>
            <div class="mt-3 text-center">
                <a href="index.php" class="text-decoration-none text-muted small">&larr; Kembali ke Monitor Publik</a>
            </div>
        </div>
    </div>
</body>
</html>