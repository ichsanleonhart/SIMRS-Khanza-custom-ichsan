<?php
// config.php
// Konfigurasi Pusat

// 1. DATABASE KHANZA
define('DB_HOST', '192.168.1.2');
define('DB_USER', 'client');
define('DB_PASS', 'epotoransu');
define('DB_NAME', 'sik_master');

// 2. ORTHANC
define('ORTHANC_URL', 'http://localhost:8042');
define('ORTHANC_USER', 'ichsan');
define('ORTHANC_PASS', 'epotoransu');

// 3. SATUSEHAT API
define('SS_BASE_URL', 'https://api-satusehat.kemkes.go.id'); // Ganti URL Production jika sudah live
define('SS_CLIENT_ID', 'jw4tmbbymU8WmoYAX4ET5pvKpoM4gvaklPG5hYM9W6NMVpeL');
define('SS_CLIENT_SECRET', 'VN8QAlIlKGGT9pDIz4GRjHL9dqprR3r61KNtQsWDzuAAVwksgtGxuj8cpAxLwBvq');
define('SS_ORG_ID', '100027196'); 

// 4. KUNCI ENKRIPSI LOGIN (Sesuai Khanza)
define('AES_KEY_USER', 'nur');
define('AES_KEY_PASS', 'windi');

// PATH PENTING (Double Backslash untuk Windows)
// Pastikan folder ini benar-benar ada di D:
define('DCMTK_BIN', 'D:\\dcmtk\\bin\\dump2dcm.exe');
define('WL_OUTPUT_DIR', 'D:\\orthanc_worklists\\');

// PREFIX UNIK (Untuk UID DICOM)
define('ORG_ROOT_UID', '1.2.840.10008.5.1.4.1.1.1.99.');
?>