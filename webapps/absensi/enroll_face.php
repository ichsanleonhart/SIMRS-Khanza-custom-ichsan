<?php
// AJAX handler: enroll new face images
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Clear any previous output
if (ob_get_level()) {
    ob_end_clean();
}

try {
    $labelRaw = isset($_POST['label']) ? $_POST['label'] : '';
    $images = isset($_POST['images']) ? $_POST['images'] : [];
    
    // Debug: Log the request
    error_log('Enrollment request received: ' . print_r($_POST, true));
    
    // Process the enrollment
    $label = preg_replace('/[^a-zA-Z0-9_-]/','_', trim($labelRaw));
    
    // Handle both array and single image formats
    if(!is_array($images)){
        $images = [$images];
    }
    
    $saved = [];
    if($label === '' || empty($images)){
        echo json_encode(['ok'=>false,'message'=>'Label atau gambar tidak valid']);
        exit;
    }
    
    // Create faces directory if it doesn't exist
    $facesDir = __DIR__ . DIRECTORY_SEPARATOR . 'faces';
    if(!is_dir($facesDir)){
        if(!@mkdir($facesDir, 0775, true)){
            echo json_encode(['ok'=>false,'message'=>'Gagal membuat folder faces: ' . $facesDir]);
            exit;
        }
    }
    
    $baseDir = $facesDir . DIRECTORY_SEPARATOR . $label;
    if(!is_dir($baseDir)){
        if(!@mkdir($baseDir, 0775, true)){
            echo json_encode(['ok'=>false,'message'=>'Gagal membuat folder label: ' . $baseDir]);
            exit;
        }
    }
    
    $idx = 1;
    $debugInfo = [];
    foreach($images as $dataUri){
        if(!is_string($dataUri)) {
            $debugInfo[] = 'Image ' . $idx . ': Not a string';
            continue;
        }
        if(strpos($dataUri, ';base64,') === false) {
            $debugInfo[] = 'Image ' . $idx . ': No base64 marker';
            continue;
        }
        
        $parts = explode(';base64,', $dataUri);
        if(count($parts) !== 2) {
            $debugInfo[] = 'Image ' . $idx . ': Invalid base64 format';
            continue;
        }
        
        $mime = $parts[0];
        $ext = 'jpeg';
        if(stripos($mime,'png')!==false){ $ext = 'png'; }
        
        $bin = base64_decode($parts[1]);
        if($bin === false) {
            $debugInfo[] = 'Image ' . $idx . ': Base64 decode failed';
            continue;
        }
        
        $filename = date('Ymd_His') . '_' . $idx . '.' . $ext;
        $target = $baseDir . DIRECTORY_SEPARATOR . $filename;
        
        $result = @file_put_contents($target, $bin);
        if($result !== false){
            $saved[] = 'faces/'.$label.'/'.$filename;
            $debugInfo[] = 'Image ' . $idx . ': Saved successfully (' . $result . ' bytes)';
            $idx++;
        } else {
            $debugInfo[] = 'Image ' . $idx . ': Failed to save to ' . $target;
        }
    }
    
    if(count($saved) > 0){
        echo json_encode([
            'ok'=>true,
            'saved'=>$saved,
            'message'=>'Berhasil menyimpan ' . count($saved) . ' foto',
            'folder' => $baseDir,
            'files' => $saved,
            'debug' => $debugInfo
        ]);
    } else {
        echo json_encode([
            'ok'=>false,
            'message'=>'Gagal menyimpan foto',
            'debug' => $debugInfo,
            'folder' => $baseDir,
            'imageCount' => count($images)
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode(['ok'=>false,'message'=>'Error: ' . $e->getMessage()]);
}
?>
