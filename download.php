<?php
/**
 * Secure File Download Handler
 */
require_once 'db.php';

// Validate ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$id = (int)$_GET['id'];

// Fetch book from database
$stmt = $pdo->prepare("SELECT * FROM books WHERE id = ?");
$stmt->execute([$id]);
$book = $stmt->fetch();

// Validate book and file exist
if (!$book || empty($book['file_path'])) {
    header('Location: index.php?error=File not found');
    exit;
}

// Resolve to absolute path
$uploadDir = realpath(__DIR__ . '/uploads');
$filePath = realpath($book['file_path']);

// Security: ensure file is inside uploads directory
if (!$filePath || strpos($filePath, $uploadDir) !== 0 || !is_file($filePath) || !is_readable($filePath)) {
    header('Location: index.php?error=Invalid file path');
    exit;
}

// Get file info
$fileName = basename($filePath);
$fileSize = filesize($filePath);

// Detect MIME type properly
$mimeType = 'application/octet-stream';
if (function_exists('finfo_open')) {
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $detected = finfo_file($finfo, $filePath);
    if ($detected) {
        $mimeType = $detected;
    }
    finfo_close($finfo);
} elseif (function_exists('mime_content_type')) {
    $detected = mime_content_type($filePath);
    if ($detected) {
        $mimeType = $detected;
    }
}

// Clean any output buffering
while (ob_get_level()) {
    ob_end_clean();
}

// Disable compression
@ini_set('zlib.output_compression', '0');

// Set headers for forced download
header('Content-Description: File Transfer');
header('Content-Type: ' . $mimeType);
header('Content-Disposition: attachment; filename="' . $fileName . '"');
header('Content-Transfer-Encoding: binary');
header('Expires: 0');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Pragma: public');
header('Content-Length: ' . $fileSize);
header('X-Content-Type-Options: nosniff');

// Stream file in chunks (more reliable than readfile for large files)
$handle = fopen($filePath, 'rb');
if ($handle) {
    while (!feof($handle)) {
        echo fread($handle, 8192);
        flush();
    }
    fclose($handle);
} else {
    http_response_code(500);
    exit('Error reading file');
}

exit;
?>
