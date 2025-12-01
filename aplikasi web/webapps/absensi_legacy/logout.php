<?php	
	session_start();
	unset($id);
	unset($nama);
	session_destroy();
	require_once "conf/command.php";
	if (cekSessiAdmin())
	{
		session_unregister("ses_admin");
	}
?>
<!DOCTYPE html>
<html lang="id">
<head>
	<meta charset="UTF-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<title>Keluar</title>
	<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-900 text-slate-100 flex items-center justify-center p-4">
	<div class="max-w-md w-full text-center">
		<div class="bg-slate-800/60 border border-slate-700 rounded-2xl shadow-xl p-8">
			<h1 class="text-2xl font-extrabold">Anda telah keluar</h1>
			<p class="text-slate-400 mt-2">Mengalihkan kembali ke halaman utama...</p>
		</div>
		<meta http-equiv="refresh" content="1;URL=index.php" />
	</div>
</body>
</html>