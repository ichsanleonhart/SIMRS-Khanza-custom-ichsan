<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login Dashboard Keuangan</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
        }
        .login-card {
            max-width: 400px;
            width: 100%;
        }
    </style>
</head>
<body>

<div class="card login-card shadow-sm">
    <div class="card-body p-4">
        <h3 class="card-title text-center mb-4">Dashboard Keuangan RS</h3>
        
        <form action="core/login_process.php" method="POST">
            
            <?php
            // Komentar: Menampilkan pesan error jika login gagal
            if (isset($_GET['error'])) {
                echo '<div class="alert alert-danger" role="alert">Username atau Password salah!</div>';
            }
            ?>

            <div class="mb-3">
                <label for="username" class="form-label">Username</label>
                <input type="text" class="form-control" id="username" name="username" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <div class="d-grid">
                <button type="submit" class="btn btn-primary">Login</button>
            </div>
        </form>
    </div>
</div>

<script src="assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>