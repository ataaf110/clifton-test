<?php
// =======================
// Clifton Chat API (Standalone)
// =======================

// ✅ تنظیمات
define("GILA_API_URL", "https://api.gilas.io/v1/chat/completions");
define("GILA_API_KEY", "<API_KEY>"); // کلید خودت را اینجا بگذار

// ✅ CORS و هدرها
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json; charset=UTF-8");
if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") { http_response_code(200); exit; }

// ✅ فقط POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Method Not Allowed"]);
    exit;
}

// ✅ دریافت ورودی‌ها از فرانت Clifton
$action              = $_POST["action"]                ?? ""; // اختیاری
$message             = $_POST["message"]               ?? "";
$themes              = json_decode($_POST["themes"] ?? "{}", true);
$user_info           = json_decode($_POST["user_info"]   ?? "{}", true);
$selected_test_option= $_POST["selected_test_option"]  ?? "free";

// ✅ اعتبارسنجی اولیه
if (trim($message) === "") {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "پیام خالی است."]);
    exit;
}

if (!is_array($themes))      $themes      = [];
if (!is_array($user_info))   $user_info   = [];

// ✅ ساخت پرامپت Clifton
function build_clifton_prompt($themes, $user_info, $selected_test_option) {
    $t_json = json_encode(array_combine(range(1, count($themes)), $themes), JSON_UNESCAPED_UNICODE);
    $u_json = json_encode($user_info, JSON_UNESCAPED_UNICODE);

    // راهنمای نگارشی پاسخ (کوتاه، فارسی، کاربردی)
    $guidelines = "قوانین پاسخ‌دهی:
- خروجی کاملاً فارسی و قابل‌اجرا باشد.
- از تیترهای کوتاه و بولت‌های کاربردی استفاده کن.
- پاسخ‌های دقیق، شخصی‌سازی‌شده و بر اساس ترکیب تم‌ها بده.
- تم‌ها را به صورت ترکیبی تحلیل کن، نه جداگانه.
- از عبارات عمومی و کلیشه‌ای دوری کن.
- محتوا را برای سن 18–25 سال جذاب، قابل‌فهم و کاربردی ارائه کن.
- محتوای پیچیده نده و اصطلاحات تخصصی را خیلی ساده توضیح بده.
- ضعف‌ها را هم توضیح بده ولی لحن باید تشویقی باشد.
 - جمله‌های تخریبی یا قضاوتی ممنوع است.";

    // معرفی داده‌ها
    $context = "داده‌های تست کلیفتون کاربر:
ترتیب 34 استعداد کاربر به صورت نزولی (از برجسته ترین به ضعیف ترین): {$t_json}
- اطلاعات کاربر: {$u_json}
- پلن/گزینه آزمون: {$selected_test_option}";

    // نقش سیستم
    $role = "تو یک دستیار متخصص در تحلیل تست CliftonStrengths هستی که وظیفه‌ات کمک به کاربران برای درک نتایج، شناخت نقاط قوت، مدیریت ضعف‌ها، و دریافت پیشنهادهای شغلی، تحصیلی و توسعه فردی است.
پاسخ‌ها باید کوتاه، قابل‌فهم، صمیمی و کاربردی باشند.";

    return $role . "\n\n" . $guidelines . "\n\n" . $context;
}

$system_prompt = build_clifton_prompt(
    $themes, $user_info, $selected_test_option
);

// ✅ بدنه درخواست به گیلَس
$body = json_encode([
    "model" => "gpt-4o-mini",
    "messages" => [
        ["role" => "system", "content" => $system_prompt],
        ["role" => "user",   "content" => $message]
    ]
], JSON_UNESCAPED_UNICODE);

// ✅ فراخوانی API
$ch = curl_init(GILA_API_URL);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json",
    "Authorization: Bearer " . GILA_API_KEY
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$response = curl_exec($ch);
$error    = curl_error($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// ✅ هندل خطاهای اتصال
if ($error) {
    http_response_code(502);
    echo json_encode(["success" => false, "message" => "خطا در اتصال به API: $error"], JSON_UNESCAPED_UNICODE);
    exit;
}

// ✅ پارس پاسخ
$data = json_decode($response, true);

// اگر API خودش خطا داده
if ($httpCode < 200 || $httpCode >= 300) {
    $errMsg = $data["error"]["message"] ?? "پاسخی از API دریافت نشد.";
    http_response_code($httpCode);
    echo json_encode(["success" => false, "message" => "خطا از API: " . $errMsg], JSON_UNESCAPED_UNICODE);
    exit;
}

// ✅ خروجی موفق
if (isset($data["choices"][0]["message"]["content"])) {
    echo json_encode([
        "success" => true,
        "message" => $data["choices"][0]["message"]["content"]
    ], JSON_UNESCAPED_UNICODE);
} else {
    echo json_encode([
        "success" => false,
        "message" => "پاسخ معتبری از API دریافت نشد."
    ], JSON_UNESCAPED_UNICODE);
}
