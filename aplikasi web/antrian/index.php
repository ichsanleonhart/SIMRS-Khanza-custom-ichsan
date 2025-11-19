<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tampilan Antrian Gabungan - SIMKES</title>
    <style>
        body, html {
            margin: 0;
            padding: 0;
            width: 100%; /* Pastikan body mengambil 100% lebar halaman */
            height: 100%;
            overflow: hidden; 
            display: flex; /* Menggunakan Flexbox untuk penempatan berdampingan */
            flex-direction: row; /* Pastikan penempatan berbaris horizontal */
        }
        .iframe-container {
            /* Menggunakan flex-grow: 1 untuk memastikan kedua elemen tumbuh sama rata */
            flex-grow: 1; 
            /* width: 50%; bisa dihilangkan, tapi kita simpan sebagai fallback/eksplisit */
            width: 50%; 
            height: 100vh; /* Tinggi penuh viewport */
            box-sizing: border-box; 
            border: 5px solid #000; 
        }
        iframe {
            width: 100%;
            height: 100%;
            border: none; 
            /* Opsional: Jika halaman target juga menggunakan viewport, ini membantu */
            /* transform: scale(1); 
            transform-origin: 0 0; */
        }
    </style>
    </head>
<body>

    <div class="iframe-container">
        <iframe src="http://192.168.1.2/antrian-apotek/" title="Antrian Apotek"></iframe>
    </div>

    <div class="iframe-container">
        <iframe src="http://192.168.1.2/webapps/antrian-poli/" title="Antrian Poli"></iframe>
    </div>

</body>
</html>