<?php

define('CLIFTON_DEBUG', true); // در پروDUCTION بگذار false

/* ===== 0) CONFIG ===== */
const DB_HOST = 'localhost';
const DB_NAME = '<DB_NAME>';
const DB_USER = '<DB_NAME>';
const DB_PASS = '<DB_PASSWORD>';
const TABLE_PREFIX = '';
const ADMIN_NUMBERS = ['<ADMIN_NUMBER_1>', '<ADMIN_NUMBER_2>', '<ADMIN_NUMBER_3>'];
// const BASE_URL = 'https://elm-angize.ir/personality/clifton' // مسیر پایه اپت TEMP;
const BASE_URL = null;
const KAVENEGAR_API_KEY = '<API_KEY>';
const TOKEN_SECRET = 'PUT_A_LONG_RANDOM_SECRET_HERE_32+CHARS';
const TOKEN_TTL = 60 * 60 * 24 * 30;

/* ===== 1) DEBUG / HEADERS ===== */
ini_set('display_errors', CLIFTON_DEBUG ? '1' : '0');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/clifton_api_error.log');

header('Content-Type: application/json; charset=utf-8');
$origin = $_SERVER['HTTP_ORIGIN'] ?? '*';
header('Access-Control-Allow-Origin: ' . $origin);
header('Vary: Origin');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

/* ===== 2) RESP HELPERS ===== */
function json_success($data = null, $code = 200)
{
    http_response_code($code);
    echo json_encode(['success' => true, 'data' => $data], JSON_UNESCAPED_UNICODE);
    exit;
}
function json_error($msg = 'خطای نامشخص', $code = 400, $meta = null)
{
    http_response_code($code);
    $out = ['success' => false, 'data' => $msg];
    if (CLIFTON_DEBUG && $meta)
        $out['debug'] = $meta;
    echo json_encode($out, JSON_UNESCAPED_UNICODE);
    exit;
}

/* ===== 3) UTIL ===== */
function b64url_enc(string $bin): string
{
    return rtrim(strtr(base64_encode($bin), '+/', '-_'), '=');
}
function b64url_dec(string $s): string|false
{
    $pad = strlen($s) % 4;
    if ($pad)
        $s .= str_repeat('=', 4 - $pad);
    return base64_decode(strtr($s, '-_', '+/'), true);
}

function issue_token_for_mobile(string $mobile, int $ttl = TOKEN_TTL): string
{
    $payload = json_encode(['m' => $mobile, 'exp' => time() + $ttl, 'rnd' => bin2hex(random_bytes(8))], JSON_UNESCAPED_UNICODE);
    $iv = random_bytes(12);
    $key = hash('sha256', TOKEN_SECRET, true);
    $tag = '';
    $ct = openssl_encrypt($payload, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag, '', 16);
    return b64url_enc($iv . $tag . $ct);
}
function decode_token_mobile(string $t): array
{
    $raw = b64url_dec($t);
    if ($raw === false || strlen($raw) < 29)
        return [null, 'توکن نامعتبر است.'];
    $iv = substr($raw, 0, 12);
    $tag = substr($raw, 12, 16);
    $ct = substr($raw, 28);
    $key = hash('sha256', TOKEN_SECRET, true);
    $pl = openssl_decrypt($ct, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
    if ($pl === false)
        return [null, 'توکن نامعتبر/دستکاری‌شده است.'];
    $j = json_decode($pl, true);
    if (!$j || empty($j['m']))
        return [null, 'توکن خالی است.'];
    if (!empty($j['exp']) && time() > $j['exp'])
        return [null, 'انقضای توکن گذشته است.'];
    return [$j['m'], null];
}

function require_env_or_fail()
{
    if (!extension_loaded('pdo_mysql'))
        json_error('افزونه PDO MySQL فعال نیست.', 500);
    foreach (['DB_HOST' => DB_HOST, 'DB_NAME' => DB_NAME, 'DB_USER' => DB_USER] as $k => $v) {
        if ($v === '')
            json_error("کانفیگ دیتابیس ($k) تنظیم نشده.", 500);
    }
}
function guess_base_url(): string
{
    if (BASE_URL)
        return BASE_URL;
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $script = $_SERVER['SCRIPT_NAME'] ?? '';
    $base = preg_replace('#/api/[^/]+$#', '', $script);
    return $scheme . '://' . $host . rtrim(dirname($base), '/');
}
function get_pdo(): PDO
{
    static $pdo = null;
    if ($pdo)
        return $pdo;
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    } catch (Throwable $e) {
        error_log('DB connect error: ' . $e->getMessage());
        json_error('اتصال به دیتابیس ناموفق بود.', 500, ['db_error' => $e->getMessage()]);
    }
    return $pdo;
}
function table_results(): string
{
    return TABLE_PREFIX . 'clifton_results';
}

function table_themes() : string
{
    return TABLE_PREFIX . 'clifton_themes';
}

function table_domains() : string 
{
    return TABLE_PREFIX . 'clifton_domains';
}

function table_questions() : string 
{
    return TABLE_PREFIX . 'clifton_questions';
}

function ensure_tables()
{
    $pdo = get_pdo();
    $tbl = table_results();
    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `$tbl`(
              `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
              `user_id` INT UNSIGNED NOT NULL DEFAULT 0,
              `mobile_number` VARCHAR(15) NOT NULL,
              `degrees` LONGTEXT,
              `user_info` MEDIUMTEXT,
              `test_option` VARCHAR(32) DEFAULT 'free',
              `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
              PRIMARY KEY(`id`),
              KEY `user_mobile`(`user_id`,`mobile_number`),
              KEY `mobile_only`(`mobile_number`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
    } catch (Throwable $e) {
        error_log('ensure_tables error: ' . $e->getMessage());
        json_error('ایجاد/بروزرسانی جدول نتوانست انجام شود.', 500, ['sql' => $e->getMessage()]);
    }
}
function read_input(): array
{
    $isJson = isset($_SERVER['CONTENT_TYPE']) && stripos($_SERVER['CONTENT_TYPE'], 'application/json') !== false;
    $payload = [];
    if ($isJson) {
        $raw = file_get_contents('php://input') ?: '';
        if ($raw !== '') {
            $dec = json_decode($raw, true);
            if (is_array($dec))
                $payload = $dec;
        }
    }
    return array_merge($_GET ?? [], $_POST ?? [], $payload);
}
function normalize_mobile($m): string
{
    $m = trim((string) $m);
    $m = preg_replace('/^(\+98|0)/', '', $m);
    return preg_replace('/\D+/', '', $m);
}
function is_valid_mobile($m): bool
{
    return (bool) preg_match('/^[0-9]{10,11}$/', $m);
}
function ensure_json_string($v): string
{
    if (is_array($v) || is_object($v))
        return json_encode($v, JSON_UNESCAPED_UNICODE);
    $s = (string) $v;
    if ($s === '')
        return '{}';
    json_decode($s);
    return (json_last_error() === JSON_ERROR_NONE) ? $s : json_encode($s, JSON_UNESCAPED_UNICODE);
}
function json_try_decode($s)
{
    if ($s === null || $s === '')
        return null;
    $d = json_decode($s, true);
    return (json_last_error() === JSON_ERROR_NONE) ? $d : $s;
}
function send_sms_kavenegar($number, $share_url): bool
{
    if (!KAVENEGAR_API_KEY)
        return true;
    $sender = '10005000600044';
    $url = "https://api.kavenegar.com/v1/" . KAVENEGAR_API_KEY . "/sms/send.json";
    $data = [
        'sender' => $sender,
        'receptor' => $number,
        'message' => "برای مشاهده نتایج آزمون کلیفتون روی لینک زیر بزنید:\n\n{$share_url}\n\nآکادمی ساحل براتی\nhttps://sahelbaratii.com\nپشتیبانی: 05191771402"
    ];
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_POST => 1, CURLOPT_POSTFIELDS => http_build_query($data), CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 15, CURLOPT_SSL_VERIFYPEER => false]);
    $resp = curl_exec($ch);
    $err = curl_error($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($err || $code !== 200) {
        error_log('SMS error: ' . $err . ' code=' . $code . ' resp=' . $resp);
        return false;
    }
    $j = json_decode($resp, true);
    return isset($j['return']['status']) && (int) $j['return']['status'] === 200;
}

/* ===== 4) QUESTIONS (Clifton) ===== */

function clifton_questions(): array
{
    $pdo = get_pdo();
    $tbl = table_questions();
    $stmt = $pdo->query("SELECT * FROM $tbl");
    $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $questions_formatted = [];
    foreach ($questions as $question) {
        $formatted = [
            [
                "text" => $question["phrase_a_text"],
                "themes" => json_decode($question["phrase_a_themes"])
            ],
            [
                "text" => $question["phrase_b_text"],
                "themes" => json_decode($question["phrase_b_themes"])
            ]
        ];
        $questions_formatted[] = $formatted;
    }

    return array_slice($questions_formatted, 0, 10);  // TEMP

}


/* ===== 5) ACTIONS ===== */
function api_save_clifton_results($in)
{
    $pdo = get_pdo();
    $tbl = table_results();
    $mobile = normalize_mobile($in['mobile_number'] ?? '');
    if (!is_valid_mobile($mobile))
        json_error('لطفاً شماره موبایل معتبر وارد کنید.');

    $degrees = isset($in['degrees']) ? ensure_json_string($in['degrees']) : '{}';
    $user_info = isset($in['user_info']) ? ensure_json_string($in['user_info']) : '{}';
    $test_option = trim($in['test_option'] ?? 'free');
    $user_id = (int) ($in['user_id'] ?? 0);

    try {
        $pdo->prepare("DELETE FROM `$tbl` WHERE `mobile_number`=?")->execute([$mobile]);
        $stmt = $pdo->prepare("INSERT INTO `$tbl`
          (`user_id`,`mobile_number`, `degrees`, `user_info`,`test_option`,`created_at`)
          VALUES(:uid,:m,:d,:ui,:to,NOW())");
        $stmt->execute([
            ':uid' => $user_id,
            ':m' => $mobile,
            ':d' => $degrees,
            ':ui' => $user_info,
            ':to' => $test_option
        ]);
    } catch (Throwable $e) {
        error_log('save_clifton error: ' . $e->getMessage());
        json_error('خطا در ذخیره نتایج.', 500, ['sql' => $e->getMessage()]);
    }

    $token = issue_token_for_mobile($mobile);
    $share = guess_base_url() . '/results.php?m=' . $mobile;
    send_sms_kavenegar($mobile, $share);

    json_success([
        'message' => 'نتایج با موفقیت ذخیره شد.',
        'mobile_number' => $mobile,
        'token' => $token,
        'share_url' => $share
    ]);
}
function api_get_clifton_results($in)
{
    $is_test = isset($in['mobile_number']) ? $in['mobile_number'] == 'test' : false;

    if (!$is_test){
        $pdo = get_pdo();
        $tbl = table_results();
        $t = trim($in['t'] ?? '');
        $ridEnc = trim($in['rid'] ?? '');
        $mobile = normalize_mobile($in['mobile_number'] ?? '');
    
        try {
            if ($t !== '') {
                [$m, $err] = decode_token_mobile($t);
                if ($err)
                    json_error($err);
                $mobile = $m;
                $stmt = $pdo->prepare("SELECT * FROM `$tbl` WHERE `mobile_number`=? ORDER BY `created_at` DESC LIMIT 1");
                $stmt->execute([$mobile]);
            } elseif ($ridEnc !== '') {
                $id = base64_decode($ridEnc, true);
                if ($id === false || !ctype_digit((string) $id))
                    json_error('شناسه نتیجه نامعتبر است.');
                $stmt = $pdo->prepare("SELECT * FROM `$tbl` WHERE `id`=? LIMIT 1");
                $stmt->execute([$id]);
            } elseif ($mobile !== '' && is_valid_mobile($mobile)) {
                $stmt = $pdo->prepare("SELECT * FROM `$tbl` WHERE `mobile_number`=? ORDER BY `created_at` DESC LIMIT 1");
                $stmt->execute([$mobile]);
            } else {
                json_error('شماره موبایل یا توکن (t) الزامی است.');
            }
            $row = $stmt->fetch();
        } catch (Throwable $e) {
            error_log('get_clifton error: ' . $e->getMessage());
            json_error('خطای پایگاه داده.', 500, ['sql' => $e->getMessage()]);
        }
        if (!$row)
            json_error('نتایج یافت نشد.', 404);
    } else {
        $mock_degrees = [
            "achiever"          => 20,
            "activator"         => 15,
            "adaptability"      => 13,
            "analytical"        => 12,
            "arranger"          => 10,
            "belief"            => 8,
            "command"           => 6,
            "communication"     => 5,
            "competition"       => 3,
            "connectedness"     => 1,
        
            // Added full set of remaining themes
            "consistency"       => 14,
            "deliberative"      => 7,
            "discipline"        => 11,
            "focus"             => 18,
            "responsibility"    => 19,
            "restorative"       => 9,
        
            "empathy"           => 16,
            "harmony"           => 4,
            "includer"          => 6,
            "individualization" => 17,
            "developer"         => 12,
            "positivity"        => 15,
            "relator"           => 13,
        
            "futuristic"        => 18,
            "ideation"          => 11,
            "input"             => 10,
            "intellection"      => 16,
            "learner"           => 19,
            "strategic"         => 17,
            "context"           => 14,
        
            "self_assurance"    => 7,
            "significance"      => 9,
            "woo"               => 5,
            "maximizer"         => 20
        ];
        $mock_user_info = [
            "birthYear" => 1380,
            "gender" => "مرد",
            "date" => "1402/01/01"
        ];
    }

    $resp = [
        'input' => [
            'degrees' => !$is_test ? json_try_decode($row['degrees']) : $mock_degrees ,
            'user_info' => !$is_test ? json_try_decode($row['user_info']) : $mock_user_info,
            'test_option' => !$is_test ? $row['test_option'] : 'free',
            'created_at' => !$is_test ?  $row['created_at'] : '2024-01-01 12:00:00'
        ]
    ];

    $resp = array_merge(get_results_content(in: $resp, json: false), $resp);

    json_success(['results' => $resp]);
}
function api_fetch_clifton_results_by_mobile($in)
{
    $pdo = get_pdo();
    $tbl = table_results();
    $mobile = normalize_mobile($in['mobile_number'] ?? '');
    $admin = normalize_mobile($in['admin_number'] ?? '');
    if (!is_valid_mobile($mobile))
        json_error('شماره موبایل معتبر نیست.');
    if (!in_array($admin, ADMIN_NUMBERS, true))
        json_error('دسترسی غیرمجاز.', 403);
    try {
        $stmt = $pdo->prepare("SELECT * FROM `$tbl` WHERE `mobile_number`=? ORDER BY `created_at` DESC LIMIT 1");
        $stmt->execute([$mobile]);
        $row = $stmt->fetch();
    } catch (Throwable $e) {
        error_log('fetch_clifton error: ' . $e->getMessage());
        json_error('خطای پایگاه داده.', 500, ['sql' => $e->getMessage()]);
    }
    if (!$row)
        json_error('نتایجی یافت نشد.', 404);
    $resp = [
        'degrees' => json_try_decode(($row['degrees'])),
        'user_info' => json_try_decode($row['user_info']),
        'test_option' => $row['test_option'],
        'created_at' => $row['created_at']
    ];
    json_success($resp);
}
function api_clear_clifton_results($in)
{
    $pdo = get_pdo();
    $tbl = table_results();
    $mobile = normalize_mobile($in['mobile_number'] ?? '');
    $admin = normalize_mobile($in['admin_number'] ?? '');
    if (!is_valid_mobile($mobile))
        json_error('شماره موبایل معتبر نیست.');
    if (!in_array($admin, ADMIN_NUMBERS, true))
        json_error('دسترسی غیرمجاز.', 403);
    try {
        $pdo->prepare("DELETE FROM `$tbl` WHERE `mobile_number`=?")->execute([$mobile]);
    } catch (Throwable $e) {
        error_log('clear_clifton error: ' . $e->getMessage());
        json_error('خطا در پاک کردن نتایج.', 500, ['sql' => $e->getMessage()]);
    }
    json_success('نتایج با موفقیت پاک شد.');
}

function get_results_content($in, $json) {
    if ($json)
    {
        $json_string = file_get_contents('./resources/results-content.json');
    
        $data = json_decode($json_string, true);
    
        if (json_last_error() !== JSON_ERROR_NONE) {
            http_response_code(400);
            json_error('error while fetching results content');
        }
    
        return $data;
    }

    $pdo = get_pdo();
    $themes_tbl = table_themes();
    $stmt = $pdo->query("SELECT * FROM $themes_tbl");
    $themes_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $input_degrees = $in['input']['degrees'];

    arsort($input_degrees);

    $themes_lookup = [];
    foreach ($themes_data as $theme) {
        $themes_lookup[$theme['themeKey']] = $theme;
    }

    $themes = [];
    $index = 0;

    foreach ($input_degrees as $themeKey => $degree) {
        if (!isset($themes_lookup[$themeKey])) continue;

        $data = $themes_lookup[$themeKey];

        if ($index < 5) {
            $themes[$themeKey] = $data;

            $themes[$themeKey]["positiveIdentifiers"] = json_decode($data["positiveIdentifiers"]);
            $themes[$themeKey]["negativeIdentifiers"] = json_decode($data["negativeIdentifiers"]);
            $themes[$themeKey]["howThemeHelpsYou"] = json_decode($data["howThemeHelpsYou"]);
            $themes[$themeKey]["behavioralProfile"] = json_decode($data["behavioralProfile"]);
            $themes[$themeKey]["workplace"] = json_decode($data["workplace"]);
            $themes[$themeKey]["improvementMethods"] = json_decode($data["improvementMethods"]);
            $themes[$themeKey]["books"] = json_decode($data["books"]);
            $themes[$themeKey]["onWeaknessObstacles"] = json_decode($data["onWeaknessObstacles"]);
            $themes[$themeKey]["onWeaknessOpportunities"] = json_decode($data["onWeaknessOpportunities"]);

            unset(
                $themes[$themeKey]['onWeaknessDescription'],
                $themes[$themeKey]['onWeaknessObstacles'],
                $themes[$themeKey]['onWeaknessOpportunities']
            );
        } elseif ($index < 10) {
            $themes[$themeKey] = [
                'domain' => $data['domain'],
                'name' => $data['name'],
                'icon' => $data['icon'],
                'phrase' => $data['phrase'],
                'onDominanceDescription' => $data['onDominanceDescription'],
                'howThemeHelpsYou' => json_decode($data['howThemeHelpsYou']),
                'picture' => $data['picture'],
                'maxDegree' => $data['maxDegree'],
            ];
        } elseif ($index >= 29) {
            $themes[$themeKey] = [
                'domain' => $data['domain'],
                'name' => $data['name'],
                'icon' => $data['icon'],
                'phrase' => $data['phrase'],
                'onWeaknessDescription' => $data['onWeaknessDescription'],
                'onWeaknessObstacles' => json_decode($data['onWeaknessObstacles']),
                'onWeaknessOpportunities' => json_decode($data['onWeaknessOpportunities']),
                'picture' => $data['picture'],
                'maxDegree' => $data['maxDegree'],
            ];
        } else {
            $themes[$themeKey] = [
                'domain' => $data['domain'],
                'icon' => $data['icon'],
                'name' => $data['name'],
                'picture' => $data['picture'],
                'maxDegree' => $data['maxDegree'],
            ];
        }

        $index++;
    }

    $domains_tbl = table_domains();
    $stmt = $pdo->query("SELECT * FROM $domains_tbl");
    $domains_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $domains = [];

    foreach ($domains_data as $domain) {
        $domain_formatted = [
            "name" => $domain["name"],
            "icon" => $domain["icon"],
            "maxDegree" => $domain["maxDegree"],
            "descriptions" => [
                "low" => $domain["descriptionLow"],
                "average" => $domain["descriptionAverage"],
                "high" => $domain["descriptionHigh"]
            ],
            "colors" => [
                "primary" => $domain["colorPrimary"],
                "bg" => $domain["colorBg"],
                "sep" => $domain["colorSep"]
            ]
        ];

        $domains[$domain["domainKey"]] = $domain_formatted;
    }

    return [
        'themes' => $themes,
        'domains'=> $domains
    ];
}

/* ===== 6) ROUTER ===== */
try {
    require_env_or_fail();
    ensure_tables();
    $in = read_input();
    $action = $in['action'] ?? '';

    if ($action === 'ping')
        json_success(['message' => 'pong']);

    // clifton actions
    if ($action === 'get_clifton_questions')
        json_success(['questions' => clifton_questions()]);
    if ($action === 'save_clifton_results')
        api_save_clifton_results($in);
    if ($action === 'get_clifton_results')
        api_get_clifton_results($in);
    if ($action === 'fetch_clifton_results_by_mobile')
        api_fetch_clifton_results_by_mobile($in);
    if ($action === 'clear_clifton_results')
        api_clear_clifton_results($in);

    // Backward-compat (اگر فرانت قدیمی صدا زد)
    if ($action === 'get_clifton_questions')
        json_success(['questions' => clifton_questions()]);
    if ($action === 'save_clifton_results')
        api_save_clifton_results($in);
    if ($action === 'get_clifton_results')
        api_get_clifton_results($in);
    if ($action === 'fetch_clifton_results_by_mobile')
        api_fetch_clifton_results_by_mobile($in);
    if ($action === 'clear_clifton_results')
        api_clear_clifton_results($in);

    if ($action === 'check_user_login')
        json_success(['is_logged_in' => false]);
    if ($action === 'get_user_mobile')
        json_error('در نسخه استندالون پشتیبانی نمی‌شود.', 400);

    json_error('اکشن نامعتبر یا خالی است.', 404);
} catch (Throwable $e) {
    error_log('FATAL: ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
    json_error('Server error.', 500, ['fatal' => $e->getMessage()]);
}



