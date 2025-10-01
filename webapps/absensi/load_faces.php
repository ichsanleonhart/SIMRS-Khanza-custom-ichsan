<?php
// Lazy loading endpoint for face descriptors
// This allows loading faces on-demand instead of all at once

if (ob_get_level()) { ob_end_clean(); }
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

try {
    $facesBaseDir = __DIR__ . DIRECTORY_SEPARATOR . 'faces';
    $knownFaces = [];
    
    if (is_dir($facesBaseDir)) {
        $labels = array_diff(scandir($facesBaseDir), ['.', '..']);
        
        // Return all images for each label so lazy loading has complete data
        foreach ($labels as $label) {
            $labelDir = $facesBaseDir . DIRECTORY_SEPARATOR . $label;
            if (is_dir($labelDir)) {
                $images = [];
                foreach (scandir($labelDir) as $img) {
                    if ($img === '.' || $img === '..') { continue; }
                    if (preg_match('/\.(jpe?g|png)$/i', $img)) {
                        $images[] = 'faces/'. rawurlencode($label) .'/'. rawurlencode($img);
                    }
                }
                if (!empty($images)) {
                    $knownFaces[] = ['label' => $label, 'images' => $images];
                }
            }
        }
    }
    
    echo json_encode([
        'ok' => true,
        'faces' => $knownFaces,
        'count' => count($knownFaces),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'ok' => false,
        'error' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}
?>
