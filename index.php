<?php
// ===== CLIFTON Standalone: index.php =====
header('Content-Type: text/html; charset=utf-8');
if (!session_id())
    session_start();

ini_set('display_errors', 1);
error_reporting(E_ALL);

// لاگین تستی: ?login=09921234567
if (!empty($_GET['login']))
    $_SESSION['user_login'] = $_GET['login'];

$clean = function ($s) {
    $s = preg_replace('/^(\+98|0)/', '', (string) $s);
    return preg_replace('/\D+/', '', $s);
};
$current_login = !empty($_SESSION['user_login']) ? $clean($_SESSION['user_login']) : '';

$admin_numbers = ['9928532946', '9338837627', '9151179905'];
if (!empty($_SESSION['clifton_temp_admin_number'])) {
    $tmp = $clean($_SESSION['clifton_temp_admin_number']);
    if ($tmp && !in_array($tmp, $admin_numbers, true))
        $admin_numbers[] = $tmp;
}
$is_admin = $current_login && in_array($current_login, $admin_numbers, true);

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$baseUri = rtrim(string: dirname($_SERVER['SCRIPT_NAME']), characters: '/');
$baseUrl = $scheme . '://' . $_SERVER['HTTP_HOST'] . ($baseUri ?: '');

$API_URL = $baseUrl . '/api/api.php';
$TEST_URL = $baseUrl . '/index.php';
$RESULTS_URL = $baseUrl . '/results.php';

$CLIFTON_CONFIG = [
    'API' => $API_URL,
    'TEST_URL' => $TEST_URL,
    'RESULTS_URL' => $RESULTS_URL,
    'ADMIN_NUMBERS' => array_values($admin_numbers),
    'CURRENT_USER_LOGIN' => (string) $current_login
];
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>تست استعدادیابی کیلفتون (CSTA)</title>

    <script src="https://elm-angize.ir/wp-content/themes/ahura/assets/css/tailwind.css"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { 'vazir': ['Vazir', 'sans-serif'] },
                    colors: {
                        primary: { 50: '#f0f9ff', 100: '#e0f2fe', 200: '#bae6fd', 300: '#7dd3fc', 400: '#38bdf8', 500: '#0ea5e9', 600: '#0284c7', 700: '#0369a1', 800: '#075985', 900: '#0c4a6e' },
                        secondary: { 50: '#f5f3ff', 100: '#ede9fe', 200: '#ddd6fe', 300: '#c4b5fd', 400: '#a78bfa', 500: '#8b5cf6', 600: '#7c3aed', 700: '#6d28d9', 800: '#5b21b6', 900: '#4c1d95' }
                    }
                }
            }
        }
    </script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/vazir-font@29.1.0/dist/font-face.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= htmlspecialchars($baseUrl) ?>/assets/css/clifton-style.css">

    <script> window.CLIFTON = <?= json_encode($CLIFTON_CONFIG, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>; </script>

    <script src="<?= htmlspecialchars($baseUrl) ?>/assets/js/clifton-bridge.js" defer></script>
    <script src="<?= htmlspecialchars($baseUrl) ?>/assets/js/main.js" defer></script>
</head>

<body class="min-h-screen relative overflow-x-hidden bg-pic">

    <!-- صفحه شروع -->
    <div id="start-page" class="container mx-auto px-4 py-8 animate__animated animate__fadeIn">
        <div class="max-w-4xl mx-auto glass-card card-hover overflow-hidden relative">
            <div
                class="bg-gradient-to-r from-primary-600 to-secondary-600 py-8 px-10 text-center relative overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-r from-primary-500/20 to-secondary-500/20"></div>
                <h1 class="text-3xl md:text-4xl font-bold text-white relative z-10 neon-text">
                    آزمون استعدادیابی کلیفتون (CSTA)
                </h1>
                <p class="text-primary-100 mt-3 text-lg relative z-10">
                    با این تست کشف می‌کنی در چه چیزهایی واقعاً بهترین هستی و چطور می‌توانی همان استعدادهای طبیعی‌ات را به موفقیت‌های واقعی تبدیل کنی.
                </p>
                <div class="absolute bottom-0 left-0 right-0 h-1 bg-white/20"></div>
            </div>

            <div class="p-8 relative bg-littlewhite">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-10">
                    <div class="border-2 border-primary-200 rounded-xl p-6 text-center cursor-pointer hover:bg-primary-50/50 transition-all duration-300 transform hover:scale-[1.02] glass-card"
                        onclick="selectOption('free')">
                        <div
                            class="w-20 h-20 bg-primary-100 rounded-full flex items-center justify-center mx-auto mb-4 shadow-md">
                            <i class="fas fa-gift text-4xl text-primary-600"></i>
                        </div>
                        <h3 class="text-2xl font-bold text-gray-800 mb-3">گزارش رایگان</h3>
                        <p class="text-gray-600 text-sm mb-4">نمایش و معرفی کلی استعداد های برتر</p>
                        <div class="mt-4"><span
                                class="bg-green-100 text-green-800 text-sm font-medium px-3 py-1 rounded-full shadow-sm">رایگان</span>
                        </div>
                    </div>

                    <div class="border-2 border-secondary-200 rounded-xl p-6 text-center cursor-pointer hover:bg-secondary-50/50 transition-all duration-300 transform hover:scale-[1.02] glass-card"
                        onclick="selectOption('premium')">
                        <div
                            class="w-20 h-20 bg-secondary-100 rounded-full flex items-center justify-center mx-auto mb-4 shadow-md">
                            <i class="fas fa-crown text-4xl text-secondary-600"></i>
                        </div>
                        <h3 class="text-2xl font-bold text-gray-800 mb-3">گزارش پیشرفته</h3>
                        <p class="text-gray-600 text-sm mb-4">تفسیر کامل استعداد ها</p>
                        <div class="mt-4"><span
                                class="bg-yellow-100 text-yellow-800 text-sm font-medium px-3 py-1 rounded-full shadow-sm">پرمیوم</span>
                        </div>
                    </div>
                </div>

                <div class="space-y-6 max-w-md mx-auto">
                    <div class="glass-card p-6 rounded-xl">
                        <label for="birth-year" class="block text-sm font-medium text-gray-700 mb-3">سال تولد</label>
                        <select id="birth-year"
                            class="w-full px-4 py-1 border border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500 glass-card">
                            <option selected disabled>انتخاب کنید</option>
                        </select>
                    </div>

                    <div class="glass-card p-6 rounded-xl">
                        <label class="block text-sm font-medium text-gray-700 mb-3">جنسیت</label>
                        <div class="flex space-x-6 space-x-reverse justify-center">
                            <label class="inline-flex items-center"><input type="radio" name="gender" value="male"
                                    class="h-5 w-5 text-primary-600 focus:ring-primary-500"><span
                                    class="mr-2 text-gray-700 font-medium">مرد</span></label>
                            <label class="inline-flex items-center"><input type="radio" name="gender" value="female"
                                    class="h-5 w-5 text-primary-600 focus:ring-primary-500"><span
                                    class="mr-2 text-gray-700 font-medium">زن</span></label>
                        </div>
                    </div>
                </div>

                <div class="mt-10 text-center">
                    <button id="start-btn"
                        class="bg-gradient-to-r from-primary-600 to-secondary-600 hover:from-primary-700 hover:to-secondary-700 text-white font-bold py-4 px-8 rounded-xl transition duration-300 flex items-center justify-center mx-auto glow-on-hover"
                        onclick="startTest()">
                        <i class="fas fa-play ml-3 text-lg"></i> شروع آزمون
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- صفحه آزمون -->
    <div id="test-page" class="container mx-auto px-4 py-8 hidden animate__animated animate__fadeIn" dir="rtl">
        <div class="max-w-3xl mx-auto flex flex-col gap-10">
            <div class="glass-card p-6 rounded-xl bg-white/70">
                <div class="flex justify-between items-center mb-3">
                    <span class="text-sm font-medium text-gray-700" id="progress-text">سوال ۱</span>
                    <span class="text-sm font-medium text-primary-600" id="progress-percent">۰%</span>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill" id="progress-fill" style="width:0%"></div>
                </div>
            </div>
            <div id="question-card"
                class="question-card glass-card rounded-2xl shadow-xl overflow-hidden p-8 mb-8 hover:shadow-lg bg-white/70 flex flex-col gap-10">
                <div class="flex flex-col sm:flex-row items-center justify-center gap-6">
                    <div class="flex flex-col items-center gap-6 w-full max-w-sm p-6 rounded-xl border border-gray-200  bg-[#EEF2FF]">
                        <p id="question-text-1" class="text-gray-900  text-center text-lg font-bold leading-tight tracking-[-0.015em]">
                            ضعف‌های خودم را بهتر از نقاط قوتم می‌شناسم.
                        </p>

                        <div class="flex flex-col sm:flex-row flex-wrap gap-3 pt-2 justify-center">
                            <button 
                                class="option-btn text-sm font-medium leading-normal flex items-center justify-center rounded-lg border border-primary/20  bg-white  px-4 h-11 text-gray-700 relative cursor-pointer hover:bg-gray-100  transition-all duration-200"
                                onclick="selectAnswer(event, 1)"
                                >
                                مرا توصیف می کند
                            </button>

                            <button 
                            class="option-btn text-sm font-medium leading-normal flex items-center justify-center rounded-lg border border-primary/20  bg-white  px-4 h-11 text-gray-700 relative cursor-pointer hover:bg-gray-100  transition-all duration-200"
                            onclick="selectAnswer(event, 2)">
                                مرا به خوبی توصیف میکند
                            </button>
                        </div>
                    </div>

                    <div class="flex px-4 py-3 justify-center">
                        <button class="cursor-pointer option-btn text-sm font-medium leading-normal flex items-center justify-center rounded-lg border border-secondary/20  bg-white  px-4 h-11 text-gray-700 relative cursor-pointer hover:bg-gray-100 transition-all duration-200" onclick="selectAnswer(event, 3)">
            
                            خنثی
                        </button>
                    </div>

                    <div class="flex flex-col items-center gap-6 w-full max-w-sm p-6 rounded-xl border border-gray-200 bg-[#FDF2F8] ">
                        <p id="question-text-2" class="text-gray-900  text-center text-lg font-bold leading-tight tracking-[-0.015em]">
                            نقاط قوت خودم را بهتر از ضعف‌هایم می‌شناسم.
                        </p>

                        <div class="flex flex-col sm:flex-row flex-wrap gap-3 pt-2 justify-center">
                            <button 
                            class="option-btn text-sm font-medium leading-normal flex items-center justify-center rounded-lg border border-secondary/20  bg-white  px-4 h-11 text-gray-700 relative cursor-pointer hover:bg-gray-100 transition-all duration-200"
                            onclick="selectAnswer(event, 4)">
                                مرا توصیف میکند
                            </button>

                            <button 
                                class="option-btn text-sm font-medium leading-normal flex items-center justify-center rounded-lg border border-secondary/20  bg-white  px-4 h-11 text-gray-700 relative cursor-pointer hover:bg-gray-100  transition-all duration-200"
                                onclick="selectAnswer(event, 5)">
                                مرا به خوبی توصیف میکند
                            </button>
                        </div>
                    </div>

                </div>
                <div class="flex justify-between glass-card p-4 rounded-xl bg-white/70">
                    <button id="prev-btn"
                        class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-medium py-3 px-6 rounded-xl transition opacity-50 cursor-not-allowed"
                        disabled onclick="prevQuestion()">
                        <i class="fas fa-arrow-right ml-2"></i>سوال قبلی
                    </button>
                    <button id="next-btn"
                        class="bg-gradient-to-r from-primary-600 to-secondary-600 hover:from-primary-700 hover:to-secondary-700 text-white font-medium py-3 px-6 rounded-xl transition opacity-50 cursor-not-allowed"
                        disabled onclick="nextQuestion()">
                        سوال بعدی <i class="fas fa-arrow-left mr-2"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- صفحه آماده‌سازی -->
    <div id="preparing-page" class="container mx-auto px-4 py-8 hidden animate__animated animate__fadeIn" dir="rtl">
        <div class="max-w-3xl mx-auto glass-card rounded-2xl shadow-xl overflow-hidden p-8 text-center bg-white/70">
            <div class="flex justify-center mb-8">
                <div class="relative">
                    <div
                        class="w-40 h-40 rounded-full bg-gradient-to-r from-primary-100 to-secondary-100 flex items-center justify-center shadow-lg animate-spin-slow">
                        <div class="w-32 h-32 rounded-full bg-white/50 flex items-center justify-center"><i
                                class="fas fa-cog text-5xl"></i></div>
                    </div>
                    <div
                        class="absolute -inset-2 rounded-full bg-gradient-to-r from-primary-300 to-secondary-300 opacity-20 blur-md animate-pulse">
                    </div>
                </div>
            </div>

            <h2 class="text-3xl font-bold text-gray-800 mb-4 gradient-text">در حال آماده‌سازی نتایج شما</h2>
            <p class="text-gray-600 mb-8 text-lg text-center">لطفا صبر کنید، در حال تحلیل و محاسبه نتایج تست کیلفتون شما هستیم...</p>

            <div class="w-full bg-gray-200 rounded-full h-3 mb-8 max-w-md mx-auto">
                <div class="bg-gradient-to-r from-primary-500 to-secondary-500 h-3 rounded-full animate-pulse"
                    style="width:100%"></div>
            </div>

            <div class="grid grid-cols-3 gap-4 max-w-md mx-auto mb-8">
                <div class="text-center">
                    <div class="w-12 h-12 bg-primary-100 rounded-full flex items-center justify-center mx-auto mb-2"><i
                            class="fas fa-chart-pie text-primary-600"></i></div>
                    <span class="text-xs text-gray-600">تحلیل داده‌ها</span>
                </div>
                <div class="text-center">
                    <div class="w-12 h-12 bg-secondary-100 rounded-full flex items-center justify-center mx-auto mb-2">
                        <i class="fas fa-brain text-secondary-600"></i>
                    </div>
                    <span class="text-xs text-gray-600">بررسی روان‌شناختی</span>
                </div>
                <div class="text-center">
                    <div class="w-12 h-12 bg-yellow-100 rounded-full flex items-center justify-center mx-auto mb-2"><i
                            class="fas fa-file-alt text-yellow-600"></i></div>
                    <span class="text-xs text-gray-600">تهیه گزارش</span>
                </div>
            </div>

            <div class="mb-4">
                <label for="mobile-number" class="block text-gray-700 font-medium mb-2">شماره موبایل</label>
                <input type="text" id="mobile-number" name="mobile-number" class="w-full p-2 border rounded"
                    placeholder="09123456789">
                <div id="mobile-error" class="hidden text-red-500 text-sm mt-1"></div>
            </div>

            <button id="show-results-btn"
                class="bg-gradient-to-r from-primary-700 to-secondary-600 hover:from-primary-700 hover:to-secondary-700 text-white font-bold py-3 px-8 rounded-xl transition transform hover:scale-105"
                onclick="showResults()">
                مشاهده نتایج
            </button>
        </div>
    </div>
    <!-- <?php include '../footer/sb-footer.php'; ?> -->
</body>

</html>