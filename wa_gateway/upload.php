<?php
header('Content-Type: application/json'); // Ensure response is in JSON format

// Check if a file is uploaded
if (!isset($_FILES['file']['name']) || empty($_FILES['file']['name'])) {
    echo json_encode(["status" => "error", "message" => "No file uploaded."]);
    exit;
}

$name = basename($_FILES['file']['name']); // Get file name safely
$size = $_FILES['file']['size'];
$type = $_FILES['file']['type'];
$tmp_name = $_FILES['file']['tmp_name'];
$error = $_FILES['file']['error'];
$maxsize = 50 * 1024 * 1024; // Limit file size to 50MB
$upload_dir = __DIR__ . "/media/"; // Save files in 'media' folder

// Ensure upload directory exists
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

$destination = $upload_dir . $name;

// Validate file type (allow only PDFs)
if ($type !== "application/pdf") {
    echo json_encode(["status" => "error", "message" => "Only PDF files are allowed."]);
    exit;
}

// Validate file size
if ($size > $maxsize) {
    echo json_encode(["status" => "error", "message" => "File size exceeds the 50MB limit."]);
    exit;
}

// Move file to the media folder
if (move_uploaded_file($tmp_name, $destination)) {
    echo json_encode(["status" => "success", "message" => "File uploaded successfully.", "file_path" => "media/$name"]);
} else {
    echo json_encode(["status" => "error", "message" => "Failed to move uploaded file."]);
}
?>
