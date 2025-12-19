<?php
// ===== CLIFTON Standalone: results.php =====
header('Content-Type: text/html; charset=utf-8');
if (!session_id())
    session_start();

ini_set('display_errors', 1);
error_reporting(E_ALL);

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
$baseUri = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
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
    <title>نتایج آزمون کلیفتون (CSTA)</title>

    <script src="https://elm-angize.ir/wp-content/themes/ahura/assets/css/tailwind.css"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { 'vazir': ['Vazir', 'sans-serif'] },
                    colors: {
                       'executing-primary':'#7B2481',
                       'executing-bg': '#F4E6F9',
                       'executing-sep': '#C8A1DE',
                       'influencing-primary': '#E97200',
                       'influencing-bg': '#FFF4E5',
                       'influencing-sep': '#F7C88A',
                       'relationship_building-primary': '#0070CD',
                       'relationship_building-bg': '#E6F4FF',
                       'relationship_building-sep': '#7FBCEC',
                       'strategic_thinking-primary': '#00945D',
                       'strategic_thinking-bg': '#E8FAF1',
                       'strategic_thinking-sep': '#70D1AA'
                    }
                }
            }
        }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.5/dist/chart.umd.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- HTML2Canvas & jsPDF -->
    <script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jspdf@2.5.1/dist/jspdf.umd.min.js"></script>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/vazir-font@29.1.0/dist/font-face.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet"/>
    <link rel="stylesheet" href="<?= htmlspecialchars($baseUrl) ?>/assets/css/clifton-style.css">

    <script> window.CLIFTON = <?= json_encode($CLIFTON_CONFIG, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>; </script>
    <script src="<?= htmlspecialchars($baseUrl) ?>/assets/js/clifton-bridge.js" defer></script>
    <script src="<?= htmlspecialchars($baseUrl) ?>/assets/js/main.js" defer></script>
</head>

<body class="bg-pic min-h-screen relative overflow-x-hidden">
    <!-- Header -->
    <div class="container mx-auto px-4 py-8 animate__animated animate__fadeIn">
        <div class="max-w-6xl mx-auto">
            <div
                class="bg-gradient-to-r from-primary-600 to-secondary-600 rounded-t-2xl py-8 px-10 text-center relative overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-r from-primary-500/20 to-secondary-500/20"></div>
                <h1 class="text-3xl md:text-4xl font-bold text-white relative z-10 neon-text">نتایج آزمون کلیفتون</h1>
                <div class="mt-6 relative z-10">
                    <span
                        class="inline-block bg-white text-primary-600 px-6 py-2 rounded-full text-lg font-medium shadow-lg">
                        نمایه شما: <span id="personality-type" class="font-bold"></span>
                    </span>
                </div>
                <div class="absolute bottom-0 left-0 right-0 h-1 bg-white/20"></div>
            </div>

            <!-- Tabs -->
            <div class="result-card rounded-b-2xl shadow-2xs overflow-hidden bg-littlewhite">
                <div class="relative">
                    <div id="tab-container" class="flex space-x-2 space-x-reverse px-6 pt-6 pb-1 overflow-x-auto">
                        <button class="tab-btn py-3 px-4 font-medium text-gray-500 hover:text-gray-700 relative"
                            onclick="showTab('summary')">
                            خلاصه نتایج
                        </button>
                        <button class="tab-btn py-3 px-4 font-medium text-gray-500 hover:text-gray-700 relative"
                        onclick="showTab('dominant-themes')">
                        استعداد های غالب
                        </button>
                        <button class="tab-btn py-3 px-4 font-medium text-gray-500 hover:text-gray-700 relative"
                            onclick="showTab('domain-dominance')">
                            زمینه های استعدادی شما
                        </button>
                        <button class="tab-btn py-3 px-4 font-medium text-gray-500 hover:text-gray-700 relative"
                            onclick="showTab('dominant-theme-1')">
                            بررسی استعداد اول
                        </button>
                        <button class="tab-btn py-3 px-4 font-medium text-gray-500 hover:text-gray-700 relative"
                            onclick="showTab('dominant-theme-2')">
                            بررسی استعداد دوم
                        </button>
                        <button class="tab-btn py-3 px-4 font-medium text-gray-500 hover:text-gray-700 relative"
                            onclick="showTab('dominant-theme-3')">
                            بررسی استعداد سوم
                        </button>
                        <button class="tab-btn py-3 px-4 font-medium text-gray-500 hover:text-gray-700 relative"
                            onclick="showTab('dominant-theme-4')">
                            بررسی استعداد چهارم
                        </button>
                        <button class="tab-btn py-3 px-4 font-medium text-gray-500 hover:text-gray-700 relative"
                            onclick="showTab('dominant-theme-5')">
                            بررسی استعداد پنجم
                        </button>
                        <button class="tab-btn py-3 px-4 font-medium text-gray-500 hover:text-gray-700 relative"
                            onclick="showTab('secondary-themes')">
                            بررسی استعداد های فرعی
                        </button>
                        <!-- <button class="tab-btn py-3 px-4 font-medium text-gray-500 hover:text-gray-700 relative"
                            onclick="showTab('job-suggestions')">
                            پیشنهادات شغلی
                        </button> -->
                        <button class="tab-btn py-3 px-4 font-medium text-gray-500 hover:text-gray-700 relative"
                            onclick="showTab('weaknesses')">
                            بررسی نقاط ضعف استعدادی
                        </button>
                        <button class="tab-btn py-3 px-4 font-medium text-gray-500 hover:text-gray-700 relative"
                            onclick="showTab('ai-chat')">
                            <i class="fas fa-robot text-blue-400 ml-2"></i>
                            مکالمه با AI
                        </button>
                    </div>
                </div>
                
                <!-- تب خلاصه تست -->
                <div id="summary-tab" class="tab-content hidden px-5 md:px-10 py-10">
                    <div class="grid grid-cols-1 gap-8 md:grid-cols-2 md:gap-12 lg:gap-16 items-center">
                        <div class="flex flex-col gap-8 md:gap-10">
                            <div class="text-center md:text-left">
                                <h2 class="text-3xl font-bold text-gray-900 sm:text-4xl text-right">نتایج تست کلیفتون شما</h2>
                                <p class="mt-2 text-lg text-gray-600 text-right">
                                    تبریک می‌گوییم! این قسمت، نمایی سریع و متمرکز از جوهره‌ی توانایی‌های منحصربه‌فرد شماست؛ کلیدی برای شروع مسیر تحصیلی و شغلی که واقعاً شما را تعریف می‌کند.
                                </p>
                            </div>

                            <div class="flex flex-col space-y-3">
                                <!-- Card 1 -->

                                <div class="rounded-lg border border-gray-100  bg-white  p-6 shadow-sm transition-shadow hover:shadow-md">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 ">غالبترین استعداد</dt>

                                        <dd class="mt-1 text-2xl font-semibold tracking-tight text-gray-900 placeholder-dominant-theme">راهبردی</dd>
                                    </dl>
                                </div>

                                <!-- Card 2 -->

                                <div class="rounded-lg border border-gray-100  bg-white  p-6 shadow-sm transition-shadow hover:shadow-md">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 ">غالبترین دسته استعدادی</dt>

                                        <dd class="mt-1 text-2xl font-semibold tracking-tight text-gray-900 placeholder-dominant-domain">تاثیرگذار</dd>
                                    </dl>
                                </div>

                                <!-- Card 3 -->

                                <div class="rounded-lg border border-gray-100  bg-white  p-6 shadow-sm transition-shadow hover:shadow-md">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 ">ضعیفترین استعداد</dt>

                                        <dd class="mt-1 text-2xl font-semibold tracking-tight text-gray-900 placeholder-least-theme">پرتلاش</dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                        <div class="flex justify-center md:justify-start">
                            <img
                                alt="An abstract illustration representing personality themes"
                                class="h-auto w-full max-w-sm rounded-lg object-cover"
                                src="http://localhost/clifton/assets/images/theme_illustrations/default-picture.jpg"
                            >
                        </div>

                    </div>
                </div>

                <!-- تب استعداد های غالب -->
                <div id="dominant-themes-tab" class="tab-content hidden flex justify-center px-5 md:px-10 py-10">
                    <div class="layout-content-container flex flex-col w-full  flex-1 mx-auto">
                        <div class="flex flex-wrap justify-between gap-4 py-4">
                            <h1 class="text-gray-900  text-3xl lg:text-4xl font-black leading-tight tracking-[-0.033em] min-w-72">استعداد های غالب شما</h1>
                        </div>

                        <div class="flex flex-wrap justify-between gap-4 py-4">
                            <p class="text-gray-600 text-base lg:text-[18px] lg:leading-[32px]">
                                نتایج تست کلیفتون، مجموعه‌ای از ۳۴ تم استعدادی را بر اساس شیوه طبیعیِ فکر کردن، احساس کردن و عمل کردن شما رتبه‌بندی می‌کند. هر تم، یک الگوی رفتاری پایدار است که نشان می‌دهد شما چگونه به مسائل نزدیک می‌شوید، چگونه انگیزه می‌گیرید، چگونه تصمیم می‌گیرید و در چه شرایطی بیشترین بازدهی را دارید. این تم‌ها در کنار یکدیگر تصویری کلی از توانمندی‌های ذاتی و سبک عملکرد شما ارائه می‌دهند؛ تصویری که می‌تواند در انتخاب مسیر تحصیلی، تصمیمات شغلی و شناخت بهتر نقاط قوت خودتان بسیار مؤثر باشد.
نتایج زیر نشان می‌دهد کدام استعدادها بیشترین حضور و تأثیر را در رفتار روزمره شما دارند. پنج تم اول، هستهٔ اصلی سبک شخصیتی شما هستند و بیشترین نقش را در تصمیم‌ها و عملکردتان ایفا می‌کنند. پنج تم بعدی، استعدادهای پشتیبان محسوب می‌شوند؛ تم‌هایی که اگرچه به اندازه تم‌های اصلی تعیین‌کننده نیستند، اما در بسیاری از موقعیت‌ها بر نحوه برخورد شما با چالش‌ها و وظایف اثر می‌گذارند. سایر تم‌ها نیز به‌ترتیب رتبه نمایش داده می‌شوند تا تصویری کامل از “چیدمان استعدادی” شما ارائه شود. این ترتیب، مشابه یک DNA شخصیتی عمل می‌کند: نشان می‌دهد چه الگوهایی بیشترین انرژی و انگیزه را در شما ایجاد می‌کنند و کدام بخش‌ها احتمالاً نیازمند مدیریت، توسعه یا آگاهی بیشتر هستند.
                            </p>

                        </div>

                        <div class="flex flex-col mt-10 gap-20">
                            <div>
                                <h2 class="text-3xl font-bold text-slate-900 mb-6">5 استعداد برتر شما</h2>

                                <div class="grid grid-cols-1 lg:grid-cols-5 gap-6 first-5-themes">

                                    <!-- FIRST MAIN THEME -->
                                    <div class=" lg:col-span-2 group relative bg-white p-6 rounded-lg shadow-sm border border-slate-200 first-dominant-theme">
                                        <div class="flex flex-col items-center text-center">
                                            <div class="relative mb-4">
                                                <span class="material-symbols-outlined text-6xl text-primary theme-icon">rocket_launch</span>

                                                <span class="absolute -top-2 -right-2 text-white text-xs font-bold rounded-full h-6 w-6 flex items-center justify-center theme-rank">1</span>
                                            </div>

                                            <h3 class="text-2xl font-semibold text-slate-900 theme-title">Achiever</h3>

                                            <p class="mt-2 text-sm text-slate-500 theme-id-phrase">
                                                People exceptionally talented in the Achiever theme work hard and possess a great deal of stamina.
                                            </p>
                                        </div>
                                    </div>

                                    <!-- OTHER 4 THEMES -->
                                    <div class="lg:col-span-3 grid grid-cols-1 sm:grid-cols-2 gap-6" id="1-5-themes">

                                        <!-- CARD 2 -->
                                        <div class="group relative bg-white p-6 rounded-lg shadow-sm border border-slate-200 theme-card">
                                            <div class="flex items-center gap-2">
                                                <div class="relative">
                                                    <span class="material-symbols-outlined text-4xl text-primary theme-icon">play_circle</span>

                                                    <span class="absolute -top-1 -right-1 text-white text-[10px] font-bold rounded-full h-4 w-4 flex items-center justify-center theme-rank">2</span>
                                                </div>

                                                <div>
                                                    <h4 class="text-lg font-semibold text-slate-900 theme-title">Activator</h4>
                                                    <p class="text-sm text-slate-500 theme-id-phrase">Make things happen</p>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- CARD 3 -->
                                        <div class=" group relative bg-white p-6 rounded-lg shadow-sm border border-slate-200 theme-card">
                                            <div class="flex items-center gap-2">
                                                <div class="relative">
                                                    <span class="material-symbols-outlined text-4xl text-primary theme-icon">call_split</span>

                                                    <span class="absolute -top-1 -right-1 text-white text-[10px] font-bold rounded-full h-4 w-4 flex items-center justify-center theme-rank">3</span>
                                                </div>

                                                <div>
                                                    <h4 class="text-lg font-semibold text-slate-900 theme-title">Adaptability</h4>
                                                    <p class="text-sm text-slate-500 theme-id-phrase">Go with the flow</p>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- CARD 4 -->
                                        <div class=" group relative bg-white p-6 rounded-lg shadow-sm border border-slate-200 theme-card">
                                            <div class="flex items-center gap-2">
                                                <div class="relative">
                                                    <span class="material-symbols-outlined text-4xl text-primary theme-icon">analytics</span>

                                                    <span class="absolute -top-1 -right-1 text-white text-[10px] font-bold rounded-full h-4 w-4 flex items-center justify-center theme-rank">4</span>
                                                </div>

                                                <div>
                                                    <h4 class="text-lg font-semibold text-slate-900 theme-title">Analytical</h4>
                                                    <p class="text-sm text-slate-500 theme-id-phrase">Search for reasons</p>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- CARD 5 -->
                                        <div class=" group relative bg-white p-6 rounded-lg shadow-sm border border-slate-200 theme-card">
                                            <div class="flex items-center gap-2">
                                                <div class="relative">
                                                    <span class="material-symbols-outlined text-4xl text-primary theme-icon">widgets</span>

                                                    <span class="absolute -top-1 -right-1 text-white text-[10px] font-bold rounded-full h-4 w-4 flex items-center justify-center theme-rank">5</span>
                                                </div>

                                                <div>
                                                    <h4 class="text-lg font-semibold text-slate-900 theme-title">Arranger</h4>
                                                    <p class="text-sm text-slate-500 theme-id-phrase">Orchestrate and organize</p>
                                                </div>
                                            </div>
                                        </div>

                                    </div>
                                </div>
                            </div>


                            <div>
                                <h2 class="text-3xl font-bold text-slate-900  mb-6">استعداد های فرعی شما</h2>

                                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-6 second-5-themes">
                                    <div class="theme-card group bg-white p-6 rounded-lg shadow-sm border border-slate-200  transition-all duration-300 text-center hover:border-primary  flex flex-col justify-between">
                                        <div class="flex-grow">
                                            <div class="flex justify-center items-center mb-4">
                                                <div class="bg-primary/10  p-3 rounded-full">
                                                    <span class="material-symbols-outlined text-3xl text-primary theme-icon">verified</span>
                                                </div>
                                            </div>

                                            <div class="flex justify-center items-baseline gap-2 mb-2">
                                                <h3 class="text-lg font-semibold text-slate-800 theme-title">Belief</h3>

                                                <span class="text-sm font-semibold text-slate-400 theme-rank">#6</span>
                                            </div>

                                            <p class="text-sm text-slate-500 theme-id-phrase">Strive to do what is right.</p>
                                        </div>
                                    </div>
                                    <div class="theme-card group bg-white p-6 rounded-lg shadow-sm border border-slate-200  transition-all duration-300 text-center hover:border-primary  flex flex-col justify-between">
                                        <div class="flex-grow">
                                            <div class="flex justify-center items-center mb-4">
                                                <div class="bg-primary/10  p-3 rounded-full">
                                                    <span class="material-symbols-outlined text-3xl text-primary theme-icon">verified</span>
                                                </div>
                                            </div>

                                            <div class="flex justify-center items-baseline gap-2 mb-2">
                                                <h3 class="text-lg font-semibold text-slate-800 theme-title">Belief</h3>

                                                <span class="text-sm font-semibold text-slate-400 theme-rank">#6</span>
                                            </div>

                                            <p class="text-sm text-slate-500 theme-id-phrase">Strive to do what is right.</p>
                                        </div>
                                    </div>
                                    <div class="theme-card group bg-white p-6 rounded-lg shadow-sm border border-slate-200  transition-all duration-300 text-center hover:border-primary  flex flex-col justify-between">
                                        <div class="flex-grow">
                                            <div class="flex justify-center items-center mb-4">
                                                <div class="bg-primary/10  p-3 rounded-full">
                                                    <span class="material-symbols-outlined text-3xl text-primary theme-icon">verified</span>
                                                </div>
                                            </div>

                                            <div class="flex justify-center items-baseline gap-2 mb-2">
                                                <h3 class="text-lg font-semibold text-slate-800 theme-title">Belief</h3>

                                                <span class="text-sm font-semibold text-slate-400 theme-rank">#6</span>
                                            </div>

                                            <p class="text-sm text-slate-500 theme-id-phrase">Strive to do what is right.</p>
                                        </div>
                                    </div>
                                    <div class="theme-card group bg-white p-6 rounded-lg shadow-sm border border-slate-200  transition-all duration-300 text-center hover:border-primary  flex flex-col justify-between">
                                        <div class="flex-grow">
                                            <div class="flex justify-center items-center mb-4">
                                                <div class="bg-primary/10  p-3 rounded-full">
                                                    <span class="material-symbols-outlined text-3xl text-primary theme-icon">verified</span>
                                                </div>
                                            </div>

                                            <div class="flex justify-center items-baseline gap-2 mb-2">
                                                <h3 class="text-lg font-semibold text-slate-800 theme-title">Belief</h3>

                                                <span class="text-sm font-semibold text-slate-400 theme-rank">#6</span>
                                            </div>

                                            <p class="text-sm text-slate-500 theme-id-phrase">Strive to do what is right.</p>
                                        </div>
                                    </div>
                                    <div class="theme-card group bg-white p-6 rounded-lg shadow-sm border border-slate-200  transition-all duration-300 text-center hover:border-primary  flex flex-col justify-between">
                                        <div class="flex-grow">
                                            <div class="flex justify-center items-center mb-4">
                                                <div class="bg-primary/10  p-3 rounded-full">
                                                    <span class="material-symbols-outlined text-3xl text-primary theme-icon">verified</span>
                                                </div>
                                            </div>

                                            <div class="flex justify-center items-baseline gap-2 mb-2">
                                                <h3 class="text-lg font-semibold text-slate-800 theme-title">Belief</h3>

                                                <span class="text-sm font-semibold text-slate-400 theme-rank">#6</span>
                                            </div>

                                            <p class="text-sm text-slate-500 theme-id-phrase">Strive to do what is right.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <h2 class="text-3xl font-bold text-slate-900  mb-6">سایر استعداد  های شما</h2>

                                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-x-8 gap-y-4 other-themes">
                                    <div class="flex items-center gap-3 py-2 border-b border-slate-200 theme-card">
                                        <span class="text-slate-500  text-sm font-medium w-6 text-right theme-rank">11.</span>

                                        <div class="flex items-center gap-1 ">
                                            <span
                                                class="material-symbols-outlined text-slate-400 theme-icon"
                                                style="font-variation-settings: 'FILL' 1, 'opsz' 20"
                                            >balance</span>

                                            <span class="font-medium text-slate-700 text-sm theme-title">Deliberative</span>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-3 py-2 border-b border-slate-200 theme-card">
                                        <span class="text-slate-500  text-sm font-medium w-6 text-right theme-rank">11.</span>

                                        <div class="flex items-center gap-1 ">
                                            <span
                                                class="material-symbols-outlined text-slate-400 theme-icon"
                                                style="font-variation-settings: 'FILL' 1, 'opsz' 20"
                                            >balance</span>

                                            <span class="font-medium text-slate-700 text-sm theme-title">Deliberative</span>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-3 py-2 border-b border-slate-200 theme-card">
                                        <span class="text-slate-500  text-sm font-medium w-6 text-right theme-rank">11.</span>

                                        <div class="flex items-center gap-1 ">
                                            <span
                                                class="material-symbols-outlined text-slate-400 theme-icon"
                                                style="font-variation-settings: 'FILL' 1, 'opsz' 20"
                                            >balance</span>

                                            <span class="font-medium text-slate-700 text-sm theme-title">Deliberative</span>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-3 py-2 border-b border-slate-200 theme-card">
                                        <span class="text-slate-500  text-sm font-medium w-6 text-right theme-rank">11.</span>

                                        <div class="flex items-center gap-1 ">
                                            <span
                                                class="material-symbols-outlined text-slate-400 theme-icon"
                                                style="font-variation-settings: 'FILL' 1, 'opsz' 20"
                                            >balance</span>

                                            <span class="font-medium text-slate-700 text-sm theme-title">Deliberative</span>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-3 py-2 border-b border-slate-200 theme-card">
                                        <span class="text-slate-500  text-sm font-medium w-6 text-right theme-rank">11.</span>

                                        <div class="flex items-center gap-1 ">
                                            <span
                                                class="material-symbols-outlined text-slate-400 theme-icon"
                                                style="font-variation-settings: 'FILL' 1, 'opsz' 20"
                                            >balance</span>

                                            <span class="font-medium text-slate-700 text-sm theme-title">Deliberative</span>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-3 py-2 border-b border-slate-200 theme-card">
                                        <span class="text-slate-500  text-sm font-medium w-6 text-right theme-rank">11.</span>

                                        <div class="flex items-center gap-1 ">
                                            <span
                                                class="material-symbols-outlined text-slate-400 theme-icon"
                                                style="font-variation-settings: 'FILL' 1, 'opsz' 20"
                                            >balance</span>

                                            <span class="font-medium text-slate-700 text-sm theme-title">Deliberative</span>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-3 py-2 border-b border-slate-200 theme-card">
                                        <span class="text-slate-500  text-sm font-medium w-6 text-right theme-rank">11.</span>

                                        <div class="flex items-center gap-1 ">
                                            <span
                                                class="material-symbols-outlined text-slate-400 theme-icon"
                                                style="font-variation-settings: 'FILL' 1, 'opsz' 20"
                                            >balance</span>

                                            <span class="font-medium text-slate-700 text-sm theme-title">Deliberative</span>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-3 py-2 border-b border-slate-200 theme-card">
                                        <span class="text-slate-500  text-sm font-medium w-6 text-right theme-rank">11.</span>

                                        <div class="flex items-center gap-1 ">
                                            <span
                                                class="material-symbols-outlined text-slate-400 theme-icon"
                                                style="font-variation-settings: 'FILL' 1, 'opsz' 20"
                                            >balance</span>

                                            <span class="font-medium text-slate-700 text-sm theme-title">Deliberative</span>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-3 py-2 border-b border-slate-200 theme-card">
                                        <span class="text-slate-500  text-sm font-medium w-6 text-right theme-rank">11.</span>

                                        <div class="flex items-center gap-1 ">
                                            <span
                                                class="material-symbols-outlined text-slate-400 theme-icon"
                                                style="font-variation-settings: 'FILL' 1, 'opsz' 20"
                                            >balance</span>

                                            <span class="font-medium text-slate-700 text-sm theme-title">Deliberative</span>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-3 py-2 border-b border-slate-200 theme-card">
                                        <span class="text-slate-500  text-sm font-medium w-6 text-right theme-rank">11.</span>

                                        <div class="flex items-center gap-1 ">
                                            <span
                                                class="material-symbols-outlined text-slate-400 theme-icon"
                                                style="font-variation-settings: 'FILL' 1, 'opsz' 20"
                                            >balance</span>

                                            <span class="font-medium text-slate-700 text-sm theme-title">Deliberative</span>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-3 py-2 border-b border-slate-200 theme-card">
                                        <span class="text-slate-500  text-sm font-medium w-6 text-right theme-rank">11.</span>

                                        <div class="flex items-center gap-1 ">
                                            <span
                                                class="material-symbols-outlined text-slate-400 theme-icon"
                                                style="font-variation-settings: 'FILL' 1, 'opsz' 20"
                                            >balance</span>

                                            <span class="font-medium text-slate-700 text-sm theme-title">Deliberative</span>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-3 py-2 border-b border-slate-200 theme-card">
                                        <span class="text-slate-500  text-sm font-medium w-6 text-right theme-rank">11.</span>

                                        <div class="flex items-center gap-1 ">
                                            <span
                                                class="material-symbols-outlined text-slate-400 theme-icon"
                                                style="font-variation-settings: 'FILL' 1, 'opsz' 20"
                                            >balance</span>

                                            <span class="font-medium text-slate-700 text-sm theme-title">Deliberative</span>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-3 py-2 border-b border-slate-200 theme-card">
                                        <span class="text-slate-500  text-sm font-medium w-6 text-right theme-rank">11.</span>

                                        <div class="flex items-center gap-1 ">
                                            <span
                                                class="material-symbols-outlined text-slate-400 theme-icon"
                                                style="font-variation-settings: 'FILL' 1, 'opsz' 20"
                                            >balance</span>

                                            <span class="font-medium text-slate-700 text-sm theme-title">Deliberative</span>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-3 py-2 border-b border-slate-200 theme-card">
                                        <span class="text-slate-500  text-sm font-medium w-6 text-right theme-rank">11.</span>

                                        <div class="flex items-center gap-1 ">
                                            <span
                                                class="material-symbols-outlined text-slate-400 theme-icon"
                                                style="font-variation-settings: 'FILL' 1, 'opsz' 20"
                                            >balance</span>

                                            <span class="font-medium text-slate-700 text-sm theme-title">Deliberative</span>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-3 py-2 border-b border-slate-200 theme-card">
                                        <span class="text-slate-500  text-sm font-medium w-6 text-right theme-rank">11.</span>

                                        <div class="flex items-center gap-1 ">
                                            <span
                                                class="material-symbols-outlined text-slate-400 theme-icon"
                                                style="font-variation-settings: 'FILL' 1, 'opsz' 20"
                                            >balance</span>

                                            <span class="font-medium text-slate-700 text-sm theme-title">Deliberative</span>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-3 py-2 border-b border-slate-200 theme-card">
                                        <span class="text-slate-500  text-sm font-medium w-6 text-right theme-rank">11.</span>

                                        <div class="flex items-center gap-1 ">
                                            <span
                                                class="material-symbols-outlined text-slate-400 theme-icon"
                                                style="font-variation-settings: 'FILL' 1, 'opsz' 20"
                                            >balance</span>

                                            <span class="font-medium text-slate-700 text-sm theme-title">Deliberative</span>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-3 py-2 border-b border-slate-200 theme-card">
                                        <span class="text-slate-500  text-sm font-medium w-6 text-right theme-rank">11.</span>

                                        <div class="flex items-center gap-1 ">
                                            <span
                                                class="material-symbols-outlined text-slate-400 theme-icon"
                                                style="font-variation-settings: 'FILL' 1, 'opsz' 20"
                                            >balance</span>

                                            <span class="font-medium text-slate-700 text-sm theme-title">Deliberative</span>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-3 py-2 border-b border-slate-200 theme-card">
                                        <span class="text-slate-500  text-sm font-medium w-6 text-right theme-rank">11.</span>

                                        <div class="flex items-center gap-1 ">
                                            <span
                                                class="material-symbols-outlined text-slate-400 theme-icon"
                                                style="font-variation-settings: 'FILL' 1, 'opsz' 20"
                                            >balance</span>

                                            <span class="font-medium text-slate-700 text-sm theme-title">Deliberative</span>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-3 py-2 border-b border-slate-200 theme-card">
                                        <span class="text-slate-500  text-sm font-medium w-6 text-right theme-rank">11.</span>

                                        <div class="flex items-center gap-1 ">
                                            <span
                                                class="material-symbols-outlined text-slate-400 theme-icon"
                                                style="font-variation-settings: 'FILL' 1, 'opsz' 20"
                                            >balance</span>

                                            <span class="font-medium text-slate-700 text-sm theme-title">Deliberative</span>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-3 py-2 border-b border-slate-200 theme-card">
                                        <span class="text-slate-500  text-sm font-medium w-6 text-right theme-rank">11.</span>

                                        <div class="flex items-center gap-1 ">
                                            <span
                                                class="material-symbols-outlined text-slate-400 theme-icon"
                                                style="font-variation-settings: 'FILL' 1, 'opsz' 20"
                                            >balance</span>

                                            <span class="font-medium text-slate-700 text-sm theme-title">Deliberative</span>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-3 py-2 border-b border-slate-200 theme-card">
                                        <span class="text-slate-500  text-sm font-medium w-6 text-right theme-rank">11.</span>

                                        <div class="flex items-center gap-1 ">
                                            <span
                                                class="material-symbols-outlined text-slate-400 theme-icon"
                                                style="font-variation-settings: 'FILL' 1, 'opsz' 20"
                                            >balance</span>

                                            <span class="font-medium text-slate-700 text-sm theme-title">Deliberative</span>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-3 py-2 border-b border-slate-200 theme-card">
                                        <span class="text-slate-500  text-sm font-medium w-6 text-right theme-rank">11.</span>

                                        <div class="flex items-center gap-1 ">
                                            <span
                                                class="material-symbols-outlined text-slate-400 theme-icon"
                                                style="font-variation-settings: 'FILL' 1, 'opsz' 20"
                                            >balance</span>

                                            <span class="font-medium text-slate-700 text-sm theme-title">Deliberative</span>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-3 py-2 border-b border-slate-200 theme-card">
                                        <span class="text-slate-500  text-sm font-medium w-6 text-right theme-rank">11.</span>

                                        <div class="flex items-center gap-1 ">
                                            <span
                                                class="material-symbols-outlined text-slate-400 theme-icon"
                                                style="font-variation-settings: 'FILL' 1, 'opsz' 20"
                                            >balance</span>

                                            <span class="font-medium text-slate-700 text-sm theme-title">Deliberative</span>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-3 py-2 border-b border-slate-200 theme-card">
                                        <span class="text-slate-500  text-sm font-medium w-6 text-right theme-rank">11.</span>

                                        <div class="flex items-center gap-1 ">
                                            <span
                                                class="material-symbols-outlined text-slate-400 theme-icon"
                                                style="font-variation-settings: 'FILL' 1, 'opsz' 20"
                                            >balance</span>

                                            <span class="font-medium text-slate-700 text-sm theme-title">Deliberative</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- تب زمینه های استعدادی شما -->
                <div id="domain-dominance-tab" class="tab-content hidden px-5 md:px-10 py-10">
                    <div class="flex-1 w-full">
                        <div class="mx-auto">
                            <div class="flex flex-wrap justify-between gap-3 mb-12">
                                <div class="flex min-w-72 flex-col">
                                    <p class="text-slate-900 py-4 text-3xl lg:text-4xl font-black">زمینه های استعدادی شما</p>

                                    <p class="text-gray-600 text-base py-4 lg:text-[18px] lg:leading-[32px] font-normal leading-normal">
                                    این بخش، چهار دسته اصلی استعدادهای کلیفتون را به‌صورت بصری و قابل‌مقایسه نمایش می‌دهد تا بتوانید در یک نگاه تشخیص دهید کدام حوزه‌ها در شما فعال‌تر، برجسته‌تر یا اثرگذارتر هستند. نمودارهای دایره‌ای و خطی این صفحه به شما کمک می‌کنند تا ابتدا یک درک کلی از «الگوی عمومی عملکرد» خود به‌دست آورید و متوجه شوید تمایل طبیعی شما بیشتر به کدام نوع توانمندی‌ها نزدیک است: تفکر عمیق، ساخت روابط، تأثیرگذاری یا اجرای عملی. این معرفی اولیه، نقطه شروعی برای تحلیل دقیق‌تر استعدادهای شماست و کمک می‌کند هنگام مشاهدهٔ هر تم یا نماد رنگی، تصویر روشنی از جایگاه آن در ساختار شخصیتی‌ خود داشته باشید.
نمایش درصد هر دسته و نیز میزان برجستگی تم‌های درون آن، دیدی شفاف از نحوه توزیع انرژی، انگیزه‌ها و نقاط قوت شما ارائه می‌دهد. این اطلاعات می‌تواند در انتخاب مسیر تحصیلی، شیوهٔ مطالعه، همکاری در پروژه‌ها‌ و حتی انتخاب نقش‌های کاری مناسب بسیار مؤثر باشد. علاوه بر این، این بخش کمک می‌کند الگوهای ذهنی و رفتاری غالب خود را بهتر بشناسید و هنگام مطالعهٔ بخش‌های تفسیری بعدی، ارتباط بین هر تم و حوزهٔ کلان آن را سریع‌تر و دقیق‌تر درک کنید. به‌طور خلاصه، این صفحه به‌عنوان یک نقشهٔ اولیه عمل می‌کند؛ نقشه‌ای که مسیر تحلیل نتایج شما را روشن می‌سازد و امکان می‌دهد سایر بخش‌های تست را با درک و دقت بیشتری دنبال کنید.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="w-full flex flex-col main-section gap-20">
                            <div class="w-full bg-transparent domain-section">
                                <div class="grid md:grid-cols-[1fr_1fr] gap-10">
                                    <div class="flex flex-col justify-center gap-8 domain-col">
                                        <div class="flex items-center gap-6">
                                            <div class="relative size-24">
                                                <svg class="w-full h-full" fill="none" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
                                                    <circle class="domain-chart-stroke" cx="50" cy="50" fill="none" r="45" stroke-width="10"></circle>

                                                    <circle
                                                        class="-rotate-90 origin-center domain-chart"
                                                        cx="50"
                                                        cy="50"
                                                        fill="none"
                                                        r="45"
                                                        stroke-dasharray="282.74"
                                                        stroke-dashoffset="183.78"
                                                        stroke-linecap="round"
                                                        stroke-width="10"
                                                    ></circle>
                                                </svg>

                                                <div class="absolute inset-0 flex items-center justify-center">
                                                    <span class="text-2xl font-bold text-slate-900 domain-percentage">35%</span>
                                                </div>
                                            </div>


                                            <div class="flex flex-col gap-4">
                                                <div class="flex items-center justify-center size-16 rounded-xl bg-blue-100/50 text-blue-600">
                                                    <span class="material-symbols-outlined !text-5xl text-blue-600 domain-icon">
                                                        rocket_launch
                                                    </span>
                                                </div>

                                                <h2 class="text-blue-700 text-3xl font-black leading-tight tracking-[-0.02em] domain-name">
                                                    Executing
                                                </h2>
                                            </div>
                                        </div>

                                        <p class="text-slate-600  text-base font-normal leading-relaxed max-w-xl domain-description">
                                            People exceptionally talented in the Executing domain know how to make things happen. They have the ability to catch an idea and make it a reality. When the team needs someone to implement a solution, they look to a person with strong Executing themes. These individuals work tirelessly to get things done, and are often seen as the backbone of any successful project or initiative.
                                        </p>
                                    </div>

                                    <div class="flex flex-col gap-6 bg-slate-100/50 themes-col">
                                        <p class="text-slate-900  text-xl font-bold leading-normal">استعداد های این دسته</p>

                                        <div class="flex flex-col gap-6 themes-container">
                                            <div class="grid grid-cols-[110px_1fr_40px] items-center gap-x-4 theme-row">
                                                <p class="text-slate-600 text-base font-medium leading-normal truncate theme-name">Achiever</p>

                                                <div class="w-full h-2 rounded-full shadow-md">
                                                    <div class="h-2 rounded-full theme-chart" ></div>
                                                </div>

                                                <span class="text-base font-medium text-slate-500 text-right theme-percentage">85%</span>
                                            </div>
                                        </div>

                                    </div>
                                </div>
                            </div>
                            <div class="w-full bg-transparent domain-section">
                                <div class="grid md:grid-cols-[1fr_1fr] gap-10">
                                    <div class="flex flex-col justify-center gap-8 domain-col">
                                        <div class="flex items-center gap-6">
                                            <div class="relative size-24">
                                                <svg class="w-full h-full" fill="none" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
                                                    <circle class="domain-chart-stroke" cx="50" cy="50" fill="none" r="45" stroke-width="10"></circle>

                                                    <circle
                                                        class="-rotate-90 origin-center domain-chart"
                                                        cx="50"
                                                        cy="50"
                                                        fill="none"
                                                        r="45"
                                                        stroke-dasharray="282.74"
                                                        stroke-dashoffset="183.78"
                                                        stroke-linecap="round"
                                                        stroke-width="10"
                                                    ></circle>
                                                </svg>

                                                <div class="absolute inset-0 flex items-center justify-center">
                                                    <span class="text-2xl font-bold text-slate-900 domain-percentage">35%</span>
                                                </div>
                                            </div>


                                            <div class="flex flex-col gap-4">
                                                <div class="flex items-center justify-center size-16 rounded-xl bg-blue-100/50 text-blue-600">
                                                    <span class="material-symbols-outlined !text-5xl text-blue-600 domain-icon">
                                                        rocket_launch
                                                    </span>
                                                </div>

                                                <h2 class="text-blue-700 text-3xl font-black leading-tight tracking-[-0.02em] domain-name">
                                                    Executing
                                                </h2>
                                            </div>
                                        </div>

                                        <p class="text-slate-600  text-base font-normal leading-relaxed max-w-xl domain-description">
                                            People exceptionally talented in the Executing domain know how to make things happen. They have the ability to catch an idea and make it a reality. When the team needs someone to implement a solution, they look to a person with strong Executing themes. These individuals work tirelessly to get things done, and are often seen as the backbone of any successful project or initiative.
                                        </p>
                                    </div>

                                    <div class="flex flex-col gap-6 bg-slate-100/50 themes-col">
                                        <p class="text-slate-900  text-xl font-bold leading-normal">استعداد های این دسته</p>

                                        <div class="flex flex-col gap-6 themes-container">
                                            <div class="grid grid-cols-[110px_1fr_40px] items-center gap-x-4 theme-row">
                                                <p class="text-slate-600 text-base font-medium leading-normal truncate theme-name">Achiever</p>

                                                <div class="w-full h-2 rounded-full shadow-md">
                                                    <div class="h-2 rounded-full theme-chart" ></div>
                                                </div>

                                                <span class="text-base font-medium text-slate-500 text-right theme-percentage">85%</span>
                                            </div>
                                        </div>

                                    </div>
                                </div>
                            </div>
                            <div class="w-full bg-transparent domain-section">
                                <div class="grid md:grid-cols-[1fr_1fr] gap-10">
                                    <div class="flex flex-col justify-center gap-8 domain-col">
                                        <div class="flex items-center gap-6">
                                            <div class="relative size-24">
                                                <svg class="w-full h-full" fill="none" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
                                                    <circle class="domain-chart-stroke" cx="50" cy="50" fill="none" r="45" stroke-width="10"></circle>

                                                    <circle
                                                        class="-rotate-90 origin-center domain-chart"
                                                        cx="50"
                                                        cy="50"
                                                        fill="none"
                                                        r="45"
                                                        stroke-dasharray="282.74"
                                                        stroke-dashoffset="183.78"
                                                        stroke-linecap="round"
                                                        stroke-width="10"
                                                    ></circle>
                                                </svg>

                                                <div class="absolute inset-0 flex items-center justify-center">
                                                    <span class="text-2xl font-bold text-slate-900 domain-percentage">35%</span>
                                                </div>
                                            </div>


                                            <div class="flex flex-col gap-4">
                                                <div class="flex items-center justify-center size-16 rounded-xl bg-blue-100/50 text-blue-600">
                                                    <span class="material-symbols-outlined !text-5xl text-blue-600 domain-icon">
                                                        rocket_launch
                                                    </span>
                                                </div>

                                                <h2 class="text-blue-700 text-3xl font-black leading-tight tracking-[-0.02em] domain-name">
                                                    Executing
                                                </h2>
                                            </div>
                                        </div>

                                        <p class="text-slate-600  text-base font-normal leading-relaxed max-w-xl domain-description">
                                            People exceptionally talented in the Executing domain know how to make things happen. They have the ability to catch an idea and make it a reality. When the team needs someone to implement a solution, they look to a person with strong Executing themes. These individuals work tirelessly to get things done, and are often seen as the backbone of any successful project or initiative.
                                        </p>
                                    </div>

                                    <div class="flex flex-col gap-6 bg-slate-100/50 themes-col">
                                        <p class="text-slate-900  text-xl font-bold leading-normal">استعداد های این دسته</p>

                                        <div class="flex flex-col gap-6 themes-container">
                                            <div class="grid grid-cols-[110px_1fr_40px] items-center gap-x-4 theme-row">
                                                <p class="text-slate-600 text-base font-medium leading-normal truncate theme-name">Achiever</p>

                                                <div class="w-full h-2 rounded-full shadow-md">
                                                    <div class="h-2 rounded-full theme-chart" ></div>
                                                </div>

                                                <span class="text-base font-medium text-slate-500 text-right theme-percentage">85%</span>
                                            </div>
                                        </div>

                                    </div>
                                </div>
                            </div>
                            <div class="w-full bg-transparent domain-section">
                                <div class="grid md:grid-cols-[1fr_1fr] gap-10">
                                    <div class="flex flex-col justify-center gap-8 domain-col">
                                        <div class="flex items-center gap-6">
                                            <div class="relative size-24">
                                                <svg class="w-full h-full" fill="none" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
                                                    <circle class="domain-chart-stroke" cx="50" cy="50" fill="none" r="45" stroke-width="10"></circle>

                                                    <circle
                                                        class="-rotate-90 origin-center domain-chart"
                                                        cx="50"
                                                        cy="50"
                                                        fill="none"
                                                        r="45"
                                                        stroke-dasharray="282.74"
                                                        stroke-dashoffset="183.78"
                                                        stroke-linecap="round"
                                                        stroke-width="10"
                                                    ></circle>
                                                </svg>

                                                <div class="absolute inset-0 flex items-center justify-center">
                                                    <span class="text-2xl font-bold text-slate-900 domain-percentage">35%</span>
                                                </div>
                                            </div>


                                            <div class="flex flex-col gap-4">
                                                <div class="flex items-center justify-center size-16 rounded-xl bg-blue-100/50 text-blue-600">
                                                    <span class="material-symbols-outlined !text-5xl text-blue-600 domain-icon">
                                                        rocket_launch
                                                    </span>
                                                </div>

                                                <h2 class="text-blue-700 text-3xl font-black leading-tight tracking-[-0.02em] domain-name">
                                                    Executing
                                                </h2>
                                            </div>
                                        </div>

                                        <p class="text-slate-600  text-base font-normal leading-relaxed max-w-xl domain-description">
                                            People exceptionally talented in the Executing domain know how to make things happen. They have the ability to catch an idea and make it a reality. When the team needs someone to implement a solution, they look to a person with strong Executing themes. These individuals work tirelessly to get things done, and are often seen as the backbone of any successful project or initiative.
                                        </p>
                                    </div>

                                    <div class="flex flex-col gap-6 bg-slate-100/50 themes-col">
                                        <p class="text-slate-900  text-xl font-bold leading-normal">استعداد های این دسته</p>

                                        <div class="flex flex-col gap-6 themes-container">
                                            <div class="grid grid-cols-[110px_1fr_40px] items-center gap-x-4 theme-row">
                                                <p class="text-slate-600 text-base font-medium leading-normal truncate theme-name">Achiever</p>

                                                <div class="w-full h-2 rounded-full shadow-md">
                                                    <div class="h-2 rounded-full theme-chart" ></div>
                                                </div>

                                                <span class="text-base font-medium text-slate-500 text-right theme-percentage">85%</span>
                                            </div>
                                        </div>

                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- تب بررسی استعداد اول -->
                <div id="dominant-theme-1-tab" class="tab-content hidden px-5 md:px-10 py-10">
                    <div class="layout-content-container flex flex-col w-full max-w-7xl flex-1 items-center justify-center theme-intro-section">
                        <div class="w-full">
                            <div class="grid w-full grid-cols-1 gap-12 lg:grid-cols-2 lg:gap-16">
                                <div class="flex items-center justify-center">
                                    <div
                                        class="w-full bg-center bg-no-repeat aspect-square bg-cover rounded-xl theme-picture"
                                        style="background-image: url(&quot;https://lh3.googleusercontent.com/aida-public/AB6AXuDObg_nDnzKafMkLyxgtOGG-_R2juvOmrdKzGZo4hzFkl2UYEJ6K0h62qkJYoAzLKcVHBWfyfCTLfsdarDdtP8gWXtTfLV5R6o4fqZ8-uG7FXiZkWSRj5sGaSuoXdFgzf_savJLCvIDRf-gVlAuDiMZBqwYjdRn_YxtzHYz5r2DRjGdj8p1gXc5_8zOB5KKrjTYPEp6_lMDFBCcYoF2oduIRtdKW9x2ou0D4wRIauz4sk0f6bk1SmOLC2-Q5n2blqFHJxG3YfV8G2y5&quot;);"
                                    ></div>
                                </div>

                                <div class="flex flex-col justify-center gap-6">
                                    <div class="flex flex-col gap-4">
                                        <p class="text-primary  text-base font-bold leading-normal tracking-wider uppercase tab-header">اولین استعداد برجسته شما</p>

                                        <h2 class="text-[#111418]  text-4xl font-black leading-tight tracking-tight sm:text-5xl theme-title">Achiever</h2>

                                        <p class="text-[#111418]/80 text-base lg:text-lg font-normal leading-relaxed theme-description">
                                            لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپ و با استفاده از طراحان گرافیک است چاپگرها و متون بلکه روزنامه و مجله در ستون و سطرآنچنان که لازم است و برای شرایط فعلی تکنولوژی مورد نیاز و کاربردهای متنوع با هدف بهبود ابزارهای کاربردی می باشد
                                            لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپ و با استفاده از طراحان گرافیک است چاپگرها و متون بلکه روزنامه و مجله در ستون و سطرآنچنان که لازم است و برای شرایط فعلی تکنولوژی مورد نیاز و کاربردهای متنوع با هدف بهبود ابزارهای کاربردی می باشدلورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپ و با استفاده از طراحان گرافیک است چاپگرها و متون بلکه روزنامه و مجله در ستون و سطرآنچنان که لازم است و برای شرایط فعلی تکنولوژی مورد نیاز و کاربردهای متنوع با هدف بهبود ابزارهای کاربردی می باشد
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="grid md:grid-cols-2 gap-8 md:gap-12 mt-20 dominance-section">
                        <div class="flex flex-col items-center justify-center bg-background-light p-6 rounded-lg md:order-first">
                            <div class="flex flex-col items-center gap-4 text-center">
                                <p class="text-[#111418]  text-base font-medium leading-normal">درصد غلبه</p>

                                <div class="chart-container relative w-60 h-60 rounded-full dominance-chart"
                                    style="background: radial-gradient(closest-side, white 0%, white 75%, transparent 75%, transparent 100%),
                                                    conic-gradient(#28a745 0% 85%, #e9ecef 85% 100%);">

                                    <div class="chart-value absolute inset-0 m-auto w-[75%] h-[75%] rounded-full flex items-center justify-center bg-white">
                                        <p class="text-[#111418] tracking-light text-[32px] font-bold leading-tight truncate dominance-percentage">۸۵٪</p>
                                    </div>
                                </div>

                                <p class="text-green-600 text-xl font-semibold leading-normal dominance-level">غلبه بالا</p>
                            </div>
                        </div>

                        <div class="flex flex-col gap-8 text-right md:order-last">
                            <!-- <div class="grid grid-cols-[1fr_auto] gap-x-6 gap-y-5">
                                <p class="text-[#617289]  text-sm font-medium">رتبه</p>
                                <p class="text-[#111418]  text-sm font-normal">۱</p>


                                
                                <p class="text-[#617289]  text-sm font-medium">نام لاتین</p>
                                <p class="text-[#111418]  text-sm font-normal">Consequi</p>

                                
                                <p class="text-[#617289]  text-sm font-medium">حوزه</p>
                                <p class="text-[#111418]  text-sm font-normal">اجرایی</p>
                            </div> -->

                            <div class="grid grid-cols:2 sm:grid-cols-3 md:grid-cols-2 lg:grid-cols-3 gap-6 p-4 cards">
                                <div class="flex flex-1 flex-col justify-center rounded-lg p-3 shadow-sm bg-white h-fit domain-card">
                                    <p class="text-cyan-800/80 text-xs font-medium">دسته استعدادی</p>

                                    <div class="my-2 h-px bg-cyan-200"></div>

                                    <p class="text-cyan-900 text-base xl:text-lg font-bold text">Achiever</p>
                                </div>

                                <div class="flex flex-1 flex-col justify-center rounded-lg  p-3 shadow-sm bg-white h-fit english-name-card">
                                    <p class="text-cyan-800/80  text-xs font-medium">نام انگلیسی</p>

                                    <div class="my-2 h-px bg-cyan-200"></div>

                                    <p class="text-cyan-900 text-base xl:text-lg font-bold text">Influencing</p>
                                </div>

                                <div class="flex flex-1 flex-col justify-center rounded-lg  p-3 shadow-sm bg-white h-fit rank-card">
                                    <p class="text-cyan-800/80  text-xs font-medium">رتبه استعداد</p>

                                    <div class="my-2 h-px bg-cyan-200 "></div>

                                    <p class="text-cyan-900 text-base xl:text-lg font-bold text">Executing</p>
                                </div>
                            </div>

                            <div class="flex flex-col gap-4">
                                <p class="text-[#111418] text-base lg:text-lg font-normal leading-relaxed dominance-description">
                                    تسلط بالای شما در استعداد کمال‌گرا نشان‌دهنده یک انگیزه دائمی برای موفقیت است. شما احساس می‌کنید که هر روز از صفر شروع می‌شود و تا پایان روز باید به چیزی ملموس دست یابید تا احساس خوبی داشته باشید. این آتش درونی شما را به سخت‌کوشی و بهره‌وری سوق می‌دهد.
                                </p>
                            </div>
                        </div>

                    </div>

                    <div class="flex flex-col gap-20 mt-20 identifiers-section ">
                        <div class="flex flex-col gap-5">
                            <h3 class="text-lg md:text-xl font-bold">
                                نقاط قوت کلیدی
                            </h3>
                            <div class="flex gap-5 flex-wrap positives">
                                <span class="bg-green-200 p-3 shadow-sm rounded-md text-xs lg:text-sm text-nowrap card">تحلیل‌گر</span>
                                <span class="bg-green-200 p-3 shadow-sm rounded-md text-xs lg:text-sm text-nowrap card">یادگیرنده سریع</span>
                                <span class="bg-green-200 p-3 shadow-sm rounded-md text-xs lg:text-sm text-nowrap card">پرسشگر و کنجکاو</span>
                                <span class="bg-green-200 p-3 shadow-sm rounded-md text-xs lg:text-sm text-nowrap card">بینش عمیق</span>
                                <span class="bg-green-200 p-3 shadow-sm rounded-md text-xs lg:text-sm text-nowrap card">علاقه‌مند به رشد علمی</span>
                            </div>
                        </div>
                        <div class="flex flex-col gap-5 ">
                            <h3 class="text-xl font-bold">
                                چالش های احتمالی
                            </h3>
                            <div class="flex gap-5 flex-wrap negatives">
                                <span class="bg-red-200 p-3 shadow-sm rounded-md text-xs lg:text-sm text-nowrap card">شک ذهنی زیاد</span>
                                <span class="bg-red-200 p-3 shadow-sm rounded-md text-xs lg:text-sm text-nowrap card">نوسان در تمرکز</span>
                                <span class="bg-red-200 p-3 shadow-sm rounded-md text-xs lg:text-sm text-nowrap card">تغییر جهت‌های فکری پی‌درپی</span>
                                <span class="bg-red-200 p-3 shadow-sm rounded-md text-xs lg:text-sm text-nowrap card">تحقیقات بی‌پایان</span>
                                <span class="bg-red-200 p-3 shadow-sm rounded-md text-xs lg:text-sm text-nowrap card">نظریه‌پردازی بدون عمل</span>
                            </div>
                        </div>
                    </div>

                    
                    <div class="flex flex-col mt-20 gap-4">
                        <div class="flex items-center gap-2 w-full">
                            <h3 class="text-2xl md:text-3xl font-bold text-gray-700">
                                چگونه استعداد اول شما میتواند به شما کمک کند؟
                            </h3>
                            <div class="flex-1 h-[3px] bg-gray-300"></div>
                        </div>
                        <div class="flex flex-col py-6 gap-4 theme-powers">
                            <div class="flex gap-7 items-center item">
                                <i class="fa-solid fa-circle-check text-green-700 text-[25px]"></i>
                                <p class="text-base lg:text-lg text-gray-800 text">
                                    لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپ و با استفاده از طراحان گرافیک است چاپگرها و متون بلکه روزنامه و مجله در ستون و سطرآنچنان که لازم است
                                </p>
                            </div>
                            <div class="flex gap-7 items-center item">
                                <i class="fa-solid fa-circle-check text-green-700 text-[25px]"></i>
                                <p class="text-base lg:text-lg text-gray-800 text">
                                    لورم ایپسوم متن ساختگی بالورم ایپسوم متن ساختگی بالورم ایپسوم متن ساختگی بالورم ایپسوم متن ساختگی بالورم ایپسوم متن ساختگی با
                                </p>
                            </div>
                            <div class="flex gap-7 items-center item">
                                <i class="fa-solid fa-circle-check text-green-700 text-[25px]"></i>
                                <p class="text-base lg:text-lg text-gray-800 text">
                                    لورم ایپسوم متن ساختگی بالورم ایپسوم متن ساختگی بالورم ایپسوم متن ساختگی با
                                </p>
                            </div>
                            <div class="flex gap-7 items-center item">
                                <i class="fa-solid fa-circle-check text-green-700 text-[25px]"></i>
                                <p class="text-base lg:text-lg text-gray-800 text">
                                    لورم ایپسوم متن ساختگی بالورم ایپسوم متن ساختگی بالورم ایپسوم متن ساختگی با
                                </p>
                            </div>
                            <div class="flex gap-7 items-center item">
                                <i class="fa-solid fa-circle-check text-green-700 text-[25px]"></i>
                                <p class="text-base lg:text-lg text-gray-800 text">
                                    لورم ایپسوم متن ساختگی بالورم ایپسوم متن ساختگی بالورم ایپسوم متن ساختگی بالورم ایپسوم متن ساختگی بالورم ایپسوم متن ساختگی بالورم ایپسوم متن ساختگی با
                                </p>
                            </div>
                            <div class="flex gap-7 items-center item">
                                <i class="fa-solid fa-circle-check text-green-700 text-[25px]"></i>
                                <p class="text-base lg:text-lg text-gray-800 text">
                                    لورم ایپسوم متن ساختگی بالورم ایپسوم متن ساختگی بالورم ایپسوم متن ساختگی با
                                </p>
                            </div>
                            <div class="flex gap-7 items-center item">
                                <i class="fa-solid fa-circle-check text-green-700 text-[25px]"></i>
                                <p class="text-base lg:text-lg text-gray-800 text">
                                    لورم ایپسوم متن ساختگی بالورم ایپسوم متن ساختگی بالورم ایپسوم متن ساختگی بالورم ایپسوم متن ساختگی بالورم ایپسوم متن ساختگی با
                                </p>
                            </div>
                            <div class="flex gap-7 items-center item">
                                <i class="fa-solid fa-circle-check text-green-700 text-[25px]"></i>
                                <p class="text-base lg:text-lg text-gray-800 text">
                                    لورم ایپسوم متن ساختگی بالورم ایپسوم متن ساختگی بالورم ایپسوم متن ساختگی با
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="layout-content-container flex flex-col  flex-1 gap-8 mt-10 behavioral-profile">

                        <div class="flex items-center gap-2 w-full">
                            <h3 class=" text-2xl md:text-3xl font-bold text-gray-700">
                                پروفایل رفتاری استعداد اول شما
                            </h3>
                            <div class="flex-1 h-[2px] bg-gray-300"></div>
                        </div>

                        <!-- Styles Grid -->

                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            <!-- Card 1: Stress Handling -->

                            <div class="flex flex-col gap-4 p-6 bg-white  border border-gray-200  rounded-xl shadow-sm pressure">
                                <div class="flex items-center gap-4">
                                    <div class="flex items-center justify-center size-12 rounded-lg bg-[#136dec]/10 text-primary">
                                        <span class="material-symbols-outlined text-3xl text-[#136dec]">shield</span>
                                    </div>

                                    <div>
                                        <h3 class="text-gray-900 text-lg md:text-xl font-bold leading-normal">شیوه مدیریت استرس و چالش‌ها</h3>

                                        <p class="text-gray-500  text-sm font-normal italic">
                                            چطور با شرایط سخت و فشار ها مقابله می کنید؟
                                        </p>
                                    </div>
                                </div>

                                <p class="text-gray-600  text-base font-normal leading-relaxed text">
                                    لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپلورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپلورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپ
                                    لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپلورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپ
                                    لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپلورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپ
                                </p>

                            </div>

                            <!-- Card 2: Decision Making -->

                            <div class="flex flex-col gap-4 p-6 bg-white  border border-gray-200  rounded-xl shadow-sm decision">
                                <div class="flex items-center gap-4">
                                    <div class="flex items-center justify-center size-12 rounded-lg bg-[#136dec]/10 text-primary">
                                        <span class="material-symbols-outlined text-3xl text-[#136dec]">psychology</span>
                                    </div>

                                    <div>
                                        <h3 class="text-gray-900 text-lg md:text-xl font-bold leading-normal">شیوه تحلیل و تصمیم‌گیری</h3>

                                        <p class="text-gray-500  text-sm font-normal italic">
                                            چگونگی شکل‌گیری یک تصمیم در ذهن شما
                                        </p>
                                    </div>
                                </div>

                                <p class="text-gray-600  text-base font-normal leading-relaxed text">
                                    لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپلورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپلورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپ
                                    لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپلورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپ
                                    لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپلورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپلورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپ
                                </p>
                            </div>

                            <!-- Card 3: Learning Style -->

                            <div class="flex flex-col gap-4 p-6 bg-white  border border-gray-200  rounded-xl shadow-sm learning">
                                <div class="flex items-center gap-4">
                                    <div class="flex items-center justify-center size-12 rounded-lg bg-[#136dec]/10 text-primary">
                                        <span class="material-symbols-outlined text-3xl text-[#136dec]">lightbulb</span>
                                    </div>

                                    <div>
                                        <h3 class="text-gray-900 text-lg md:text-xl font-bold leading-normal">شیوه برخورد شما با دانش جدید</h3>

                                        <p class="text-gray-500  text-sm font-normal italic">
                                            فرمول شخصی شما هنگام یادگیری چیست؟
                                        </p>
                                    </div>
                                </div>

                                <p class="text-gray-600  text-base font-normal leading-relaxed text">
                                    لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپلورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپلورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپلورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپ
                                    لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپلورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپلورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپلورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپلورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپ
                                    لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپلورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپ
                                </p>
                            </div>

                            <!-- Card 4: Motivation Style -->

                            <div class="flex flex-col gap-4 p-6 bg-white  border border-gray-200  rounded-xl shadow-sm motivation">
                                <div class="flex items-center gap-4">
                                    <div class="flex items-center justify-center size-12 rounded-lg bg-[#136dec]/10 text-primary">
                                        <span class="material-symbols-outlined text-3xl text-[#136dec]">explore</span>
                                    </div>

                                    <div>
                                        <h3 class="text-gray-900  text-xl font-bold leading-normal">منابع انگیزه در شما</h3>

                                        <p class="text-gray-500  text-sm font-normal italic">
                                            ریشه‌ای که میل شما به پیشرفت را زنده نگه می‌دارد
                                        </p>
                                    </div>
                                </div>

                                <p class="text-gray-600  text-base font-normal leading-relaxed text">
                                    لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپلورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپلورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپلورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپلورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپ
                                    لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپلورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپ
                                    لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپلورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپلورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپلورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپ
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="flex-1 mt-20 workplace">
                        <div class="flex items-center gap-2 w-full">
                            <h3 class=" text-2xl md:text-3xl font-bold text-gray-700">
                                نقاط قوت و چالش‌های شما در محیط کاری
                            </h3>
                            <div class="flex-1 h-[2px] bg-gray-300"></div>
                        </div>

                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mt-8">
                            <div class="flex flex-col gap-4 bg-green-50  p-4 rounded-xl shadow-sm strengths">
                                <h2 class="text-green-800 text-lg md:text-xl font-bold leading-tight tracking-[-0.015em] px-4 pb-3 pt-2">نقاط قوت</h2>

                                <div class="flex flex-col gap-2">
                                    <div class="flex items-center gap-4 bg-white  px-4 min-h-[72px] py-3 justify-between rounded-lg item">
                                        <div class="flex items-center gap-4">
                                            <div class="flex flex-col justify-center gap-2">
                                                <p class="text-gray-900 text-base lg:text-lg font-medium leading-normal  title">Strategic Thinking</p>

                                                <p class="text-gray-500  text-sm font-normal leading-normal  description">
                                                    Excels at analyzing complex situations and identifying long-term opportunities.
                                                </p>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div>

                            <div class="flex flex-col gap-4 bg-red-50 p-4 rounded-xl shadow-sm weaknesses">
                                <h2 class="text-red-800 text-lg md:text-xl font-bold leading-tight tracking-[-0.015em] px-4 pb-3 pt-2">چالش ها</h2>

                                <div class="flex flex-col gap-2">
                                    <div class="flex items-center gap-4 bg-white  px-4 min-h-[72px] py-3 justify-between rounded-lg item">
                                        <div class="flex items-center gap-4">
                                            <div class="flex flex-col justify-center gap-2">
                                                <p class="text-gray-900  text-base lg:text-lg font-medium leading-normal  title">Delegation</p>

                                                <p class="text-gray-500  text-sm font-normal leading-normal  description">
                                                    Tends to take on too much work, could improve by trusting colleagues with more tasks.
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="layout-content-container flex flex-col w-full flex-1 gap-8 mt-20">
                        <div class="flex flex-col gap-3 p-4">
                            <div class="flex items-center gap-2 w-full">
                                <h3 class=" text-2xl md:text-3xl font-bold text-gray-700">
                                    اقدامات کلیدی برای توسعه استعداد
                                </h3>
                                <div class="flex-1 h-[2px] bg-gray-300"></div>
                            </div>

                            <p class="text-base lg:text-lg font-normal text-neutral-600  max-w-2xl">
                                در این بخش مجموعه‌ای از توصیه‌های کاربردی ارائه شده است تا به شما کمک کند توانمندی‌های طبیعی خود را آگاهانه تقویت کنید و چالش‌های احتمالی مرتبط با تم استعدادی‌تان را بهتر مدیریت کنید. این راهکارها با نگاه عملی، رفتارمحور و قابل اجرا طراحی شده‌اند تا بتوانید آن‌ها را مستقیماً در کار، تحصیل و روابط حرفه‌ای خود به‌کار بگیرید و مسیر رشد شخصی‌تان را هدفمندتر ادامه دهید.
                            </p>
                        </div>

                        <div class="flex flex-col border-t border-neutral-200/80 improvement-methods">
                            <div class="flex flex-col md:flex-row gap-2 md:gap-8 py-8 px-4 border-b border-neutral-200/80 item">
                                <div class="flex items-start gap-2 md:w-1/3">
                                    <h3 class="text-lg md:text-xl font-bold text-neutral-900 title">Leveraging Your Top Strengths</h3>
                                </div>

                                <div class="flex-1 flex flex-col gap-2 text-neutral-700 description">
                                    <p class="text-sm leading-relaxed">
                                        Identify one task this week where you can consciously apply your top strength. For example, if you lead with 'Achiever', break a large project into smaller, completable tasks to build momentum.
                                    </p>

                                    <p class="text-sm leading-relaxed">
                                        Schedule a 15-minute reflection at the end of the week to note how using this strength felt and what the outcome was.
                                    </p>
                                </div>
                            </div>

                            <div class="flex flex-col md:flex-row gap-2 md:gap-8 py-8 px-4 border-b border-neutral-200/80 item">
                                <div class="flex items-start gap-2 md:w-1/3">
                                    <h3 class="text-lg md:text-xl font-bold text-neutral-900 title">Communicating Effectively</h3>
                                </div>

                                <div class="flex-1 flex flex-col gap-4 text-neutral-700 description">
                                    <p class="text-sm leading-relaxed">
                                        When starting a conversation, state your main point first, especially if your theme is 'Command' or 'Activator'. This ensures clarity and directness, which aligns with your natural communication style.
                                    </p>

                                    <p class="text-sm leading-relaxed">
                                        Practice active listening by summarizing what the other person said before you respond. This is especially helpful for themes like 'Harmony' or 'Relator' to build stronger connections.
                                    </p>
                                </div>
                            </div>

                            <div class="flex flex-col md:flex-row gap-2 md:gap-8 py-8 px-4 border-b border-neutral-200/80 item">
                                <div class="flex items-start gap-2 md:w-1/3">
                                    <h3 class="text-lg md:text-xl font-bold text-neutral-900 title">Navigating Team Collaboration</h3>
                                </div>

                                <div class="flex-1 flex flex-col gap-4 text-neutral-700 description">
                                    <p class="text-sm leading-relaxed">
                                        Volunteer for roles on your team that align with your strengths. If you have 'Ideation', offer to lead brainstorming sessions. If you have 'Discipline', offer to organize project plans and timelines.
                                    </p>

                                    <p class="text-sm leading-relaxed">
                                        Recognize and appreciate the strengths of your teammates. Acknowledging their contributions can foster a more positive and effective team environment.
                                    </p>
                                </div>
                            </div>

                            <div class="flex flex-col md:flex-row gap-2 md:gap-8 py-8 px-4 item">
                                <div class="flex items-start gap-4 md:w-1/3">
                                    <h3 class="text-lg md:text-xl font-bold text-neutral-900 title">Areas for Potential Growth</h3>
                                </div>

                                <div class="flex-1 flex flex-col gap-4 text-neutral-700 description">
                                    <p class="text-sm leading-relaxed">
                                        Identify potential blind spots associated with your dominant themes. For example, a strong 'Focus' might sometimes lead to tunnel vision. Make a conscious effort to solicit diverse perspectives before making key decisions.
                                    </p>

                                    <p class="text-sm leading-relaxed">
                                        Pair up with a colleague who has strengths that complement your own. This partnership can provide a balanced approach to tasks and foster mutual learning and development.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="layout-content-container flex flex-col w-full flex-1">
                        <div class="flex flex-wrap justify-between gap-4 p-4 mb-8">
                            <div class="flex w-full flex-col gap-3">
                                <div class="flex items-center gap-2 w-full">
                                    <h3 class=" text-2xl md:text-3xl font-bold text-gray-700">
                                        معرفی کتاب
                                    </h3>
                                    <div class="flex-1 h-[2px] bg-gray-300"></div>
                                </div>

                                <p class="text-gray-500 text-base lg:text-lg font-normal leading-normal max-w-2xl">
برای هر تم استعدادی، مجموعه‌ای از کتاب‌های منتخب معرفی شده‌اند تا به شما کمک کنند شناخت عمیق‌تری از توانمندی‌هایتان به دست آورید و مسیر رشد شخصی و حرفه‌ای خود را با پشتوانه‌ای علمی و کاربردی ادامه دهید. این کتاب‌ها بر اساس محتوای معتبر، پژوهش‌های روانشناسی و تجربه‌های عملی انتخاب شده‌اند تا بتوانند هم در تقویت نقاط قوت و هم در مدیریت نقاط ضعف احتمالی همراه شما باشند.
                                </p>
                            </div>
                        </div>

                        <div class="flex flex-col px-5 lg:px-20 gap-10 xl:gap-0 books">
                            <!-- Book Row 1: Image Left -->

                            <div class="flex flex-col md:flex-row items-center gap-8 pb-5 item image-left">
                                <div class="w-full md:w-1/4 flex justify-center">
                                    <div
                                        class="bg-center bg-no-repeat aspect-[2/3] bg-cover rounded-lg w-48  picture"
                                        style="background-image: url(&quot;https://lh3.googleusercontent.com/aida-public/AB6AXuBzWOiLRVdVrXWfUicSN2Qs6Uwk2K16n-yyZbRQo6SCvcjPGRMfZ63GOglixDIRHYiD1IGCfiXS5C4nA9iEXpP2v5EfmOCxizZo9TwNZ-q0Q0hZTrakCn2jmcq_BAtRo8fpvHFQ44RTHbLq_kzEZNxJb2l_R-T4Nwgq-wt3xoTV5FzdUmFUyp7T0NP5lTX6cskVu6ANTX66kTqoCw7oxNc6hJp37m5RK2U0EvnW-ZnZTT5qYyfBXRIRoZqSFoiCB4V_Ub-CUPsau_MF&quot;);"
                                    ></div>
                                </div>

                                <div class="flex flex-col flex-1 text-center md:text-right">
                                    <h3 class="text-xl md:text-2xl font-bold text-gray-900 book-name">StrengthsFinder 2.0</h3>

                                    <p class="text-lg font-medium text-gray-500 mt-1 mb-2 book-author">By Tom Rath</p>

                                    <p class="text-base font-normal leading-relaxed text-gray-600 description">
                                        This book is the foundational text for the CliftonStrengths assessment. It will help you understand your identified themes in-depth and provide actionable advice on how to apply your strengths in your daily life and career.
                                    </p>
                                </div>
                            </div>

                            <!-- Book Row 2: Image Right -->

                            <div class="flex flex-col md:flex-row items-center gap-8 pb-5 item image-right">
                                <div class="flex flex-col gap-3 flex-1 text-center md:text-right order-2 md:order-1">
                                    <h3 class="text-xl md:text-2xl font-bold text-gray-900 book-name">
                                        How to Win Friends and Influence People
                                    </h3>

                                    <p class="text-lg font-medium text-gray-500 mt-1 mb-2 book-author">By Dale Carnegie</p>

                                    <p class="text-base font-normal leading-relaxed text-gray-600 description">
                                        With your high 'Woo' and 'Communication' themes, this classic will provide timeless strategies to enhance your natural ability to connect with others, build rapport, and inspire action in those around you.
                                    </p>
                                </div>

                                <div class="w-full md:w-1/4 flex justify-center order-1 md:order-2">
                                    <div
                                        class="bg-center bg-no-repeat aspect-[2/3] bg-cover rounded-lg w-48  picture"
                                        style="background-image: url(&quot;https://lh3.googleusercontent.com/aida-public/AB6AXuDjghQqieBP53oRQELVBfX0ZzsdOdEgV9nCciYp6-PodZv-eEnDZDaMP2d9UJuIVhMfGlKhjqO3HTyoycJyvJoHohrYjdCQ15Z8w5M3NtaIVxa33eAdHhwxcb51pkkLB2JDegVFK_sLJcXEeYlK8hl8pGcwOCEx5S7cMcmQ26pMtrBVBckGltOwZRjwbEtC2S6wOJDIt3S34u19KEQy0PeqNxAFRiJOk6Pf9j2D2W_7emKcUmQg4Zsl4wzQbZudy2-PLa1MdR8ZXIYH&quot;);"
                                    ></div>
                                </div>
                            </div>

                            <!-- Book Row 3: Image Left -->

                            <div class="flex flex-col md:flex-row items-center gap-8 pb-5 item image-left">
                                <div class="w-full md:w-1/4 flex justify-center">
                                    <div
                                        class="bg-center bg-no-repeat aspect-[2/3] bg-cover rounded-lg w-48  picture"
                                        style="background-image: url(&quot;https://lh3.googleusercontent.com/aida-public/AB6AXuD18ArJlWEIRbtPU2vvCL7JvD7G6NuPqeHaTsPQb0iNfSik5JKb4owIG_RUKISWMimeYf0WarWImFW4MTrPWQpYHqKNzf3ZMp1vyUjve1mOu8JzGxweq4zVMTbVwjZY5IYK6QpfNHZJKg1rTVqhibdf0wnheJjCp50ocHI2HcfXY2AMAFkrXrRK9lvMx_mDBmQL7idJk1DcdSVMNxxcKfrNSmSAHc_RydoyoKL-UvULDSlXdUqMoLeLPTXsVqsPky8mt9Kx8T-XgjmW&quot;);"
                                    ></div>
                                </div>

                                <div class="flex flex-col gap-3 flex-1 text-center md:text-left">
                                    <h3 class="text-xl md:text-2xl font-bold text-gray-900 book-name">
                                        The 7 Habits of Highly Effective People
                                    </h3>

                                    <p class="text-lg font-medium text-gray-500 mt-1 mb-2 book-author">By Stephen R. Covey</p>

                                    <p class="text-base font-normal leading-relaxed text-gray-600 description">
                                        Your 'Achiever' and 'Discipline' themes suggest a drive for effectiveness. This book offers a principle-centered approach to personal and professional productivity that will resonate with your desire for structured success.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- تب بررسی استعداد دوم -->
                <div id="dominant-theme-2-tab" class="tab-content hidden  px-5 md:px-10 py-10">

                </div>

                <!-- تب بررسی استعداد سوم -->
                <div id="dominant-theme-3-tab" class="tab-content hidden  px-5 md:px-10 py-10">

                </div>

                <!-- تب بررسی استعداد چهارم -->
                <div id="dominant-theme-4-tab" class="tab-content hidden  px-5 md:px-10 py-10">

                </div>

                <!-- تب بررسی استعداد پنجم -->
                <div id="dominant-theme-5-tab" class="tab-content hidden  px-5 md:px-10 py-10">

                </div>

                <!-- تب بررسی استعداد های فرعی -->
                <div id="secondary-themes-tab" class="tab-content hidden px-5 md:px-20 py-10 flex flex-col">
                    <p class="text-base md:text-lg text-gray-800">
                        در تست کلیفتون، استعدادهای شما بر اساس قدرت و تأثیرگذاری‌شان رتبه‌بندی می‌شوند. تا اینجا پنج استعداد اول را به شما گفتیم که به عنوان استعدادهای اصلی شما شناخته می‌شوند که بیشترین تأثیر را در رفتار و موفقیت‌های شما دارند. اما استعدادهایی که در رتبه‌های ششم تا دهم قرار می‌گیرند، همچنان مهم و کاربردی هستند و به عنوان «استعدادهای فرعی» شناخته می‌شوند.

استعدادهای فرعی، گرچه به اندازه پنج استعداد برتر قوی نیستند، در شرایط خاص می‌توانند به شما کمک کنند تا عملکرد بهتری داشته باشید. این تفسیر به شما کمک می‌کند تا شناخت دقیق‌تری از توانایی‌ها و استعدادهای خود داشته باشید و بتوانید از هر یک از آن‌ها در بهترین زمان ممکن استفاده کنید. در زیر ۵ استعداد فرعی شما مشخص شده اند.
                    </p>

                    <div class="mt-16">
                        <div class="flex items-center gap-2 w-full mb-10">
                            <h3 class=" text-2xl md:text-3xl font-bold text-gray-700">
                                تم های فرعی شما
                            </h3>
                            <div class="flex-1 h-[2px] bg-gray-300"></div>
                        </div>

                        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-6 themes-overview">
                            <div class="group bg-white  p-6 rounded-lg shadow-sm border border-slate-200  transition-all duration-300 text-center hover:border-primary flex flex-col justify-between card">
                                <div class="flex-grow">
                                    <div class="flex justify-center items-center mb-4">
                                        <div class="bg-primary/10  p-3 rounded-full">
                                            <span class="material-symbols-outlined text-3xl text-primary icon">verified</span>
                                        </div>
                                    </div>

                                    <div class="flex justify-center items-baseline gap-2 mb-2">
                                        <h3 class="text-lg font-semibold text-slate-800 title">Belief</h3>

                                        <span class="text-sm font-semibold text-slate-400 rank">#6</span>
                                    </div>

                                    <p class="text-sm text-slate-500 id-phrase">Strive to do what is right.</p>
                                </div>
                            </div>

                            <div class="group bg-white  p-6 rounded-lg shadow-sm border border-slate-200  transition-all duration-300 text-center hover:border-primary  flex flex-col justify-between card">
                                <div class="flex-grow">
                                    <div class="flex justify-center items-center mb-4">
                                        <div class="bg-primary/10  p-3 rounded-full">
                                            <span class="material-symbols-outlined text-3xl text-primary icon">emoji_events</span>
                                        </div>
                                    </div>

                                    <div class="flex justify-center items-baseline gap-2 mb-2">
                                        <h3 class="text-lg font-semibold text-slate-800 title">Competition</h3>

                                        <span class="text-sm font-semibold text-slate-400 rank">#7</span>
                                    </div>

                                    <p class="text-sm text-slate-500 id-phrase">Measure your progress.</p>
                                </div>
                            </div>

                            <div class="group bg-white  p-6 rounded-lg shadow-sm border border-slate-200  transition-all duration-300 text-center hover:border-primary  flex flex-col justify-between card">
                                <div class="flex-grow">
                                    <div class="flex justify-center items-center mb-4">
                                        <div class="bg-primary/10  p-3 rounded-full">
                                            <span class="material-symbols-outlined text-3xl text-primary icon">hub</span>
                                        </div>
                                    </div>

                                    <div class="flex justify-center items-baseline gap-2 mb-2">
                                        <h3 class="text-lg font-semibold text-slate-800 title">Connectedness</h3>

                                        <span class="text-sm font-semibold text-slate-400 rank ">#8</span>
                                    </div>

                                    <p class="text-sm text-slate-500 id-phrase">
                                        Bridge divides between people.
                                    </p>
                                </div>
                            </div>

                            <div class="group bg-white  p-6 rounded-lg shadow-sm border border-slate-200  transition-all duration-300 text-center hover:border-primary  flex flex-col justify-between card">
                                <div class="flex-grow">
                                    <div class="flex justify-center items-center mb-4">
                                        <div class="bg-primary/10  p-3 rounded-full">
                                            <span class="material-symbols-outlined text-3xl text-primary icon">checklist</span>
                                        </div>
                                    </div>

                                    <div class="flex justify-center items-baseline gap-2 mb-2">
                                        <h3 class="text-lg font-semibold text-slate-800 title">Consistency</h3>

                                        <span class="text-sm font-semibold text-slate-400 rank">#9</span>
                                    </div>

                                    <p class="text-sm text-slate-500 id-phrase">Treat everyone the same.</p>
                                </div>
                            </div>

                            <div class="group bg-white  p-6 rounded-lg shadow-sm border border-slate-200  transition-all duration-300 text-center hover:border-primary  flex flex-col justify-between card">
                                <div class="flex-grow">
                                    <div class="flex justify-center items-center mb-4">
                                        <div class="bg-primary/10  p-3 rounded-full">
                                            <span class="material-symbols-outlined text-3xl text-primary icon">lightbulb</span>
                                        </div>
                                    </div>

                                    <div class="flex justify-center items-baseline gap-2 mb-2">
                                        <h3 class="text-lg font-semibold text-slate-800 title">Context</h3>

                                        <span class="text-sm font-semibold text-slate-400 rank">#10</span>
                                    </div>

                                    <p class="text-sm text-slate-500 id-phrase">Look back to understand.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div>
                        <div class="flex items-center gap-2 w-full mt-20 mb-10">
                            <h3 class=" text-2xl md:text-3xl font-bold text-gray-700">
                                تحلیل تم های فرعی شما
                            </h3>
                            <div class="flex-1 h-[2px] bg-gray-300"></div>
                        </div>
                        <p class="text-gray-800 text-base md:text-lg">
                            در این بخش، ما به بررسی خلاصه‌ای از پنج استعداد فرعی شما می‌پردازیم که در واقع استعدادهای ششم تا دهم شما را شامل می‌شوند. با شناخت بهتر و آگاهی از کاربردهای این استعدادها، شما می‌توانید با سرمایه‌گذاری و تمرکز بیشتر بر روی آن‌ها، این استعدادها را تقویت کنید. شما حتی در صورت تمایل می توانید استعدادهای فرعی خود را به سطح استعدادهای برتر ارتقا دهید و از آن‌ها در موقعیت‌هایی که نیاز به مهارت‌ها و توانایی‌های خاصی دارید، بهره‌مند شوید.
                        </p>
                    </div>

                    <!-- <div class="w-full mt-20 flex flex-col gap-8 md:gap-12 p-4 md:p-0">
                        <div class="flex flex-col md:flex-row flex-wrap gap-4">
                            <div class="flex min-w-[158px] flex-1 flex-col gap-2 rounded-xl p-6 border border-border-light bg-white ">
                                <p class="text-text-muted-light  text-sm font-medium leading-normal">Theme Rank</p>

                                <p class="text-text-light  tracking-light text-3xl font-bold leading-tight">#1</p>
                            </div>

                            <div class="flex min-w-[158px] flex-1 flex-col gap-2 rounded-xl p-6 border border-border-light  bg-white ">
                                <p class="text-text-muted-light  text-sm font-medium leading-normal">Theme Domain</p>

                                <p class="text-text-light  tracking-light text-3xl font-bold leading-tight">Executing</p>
                            </div>

                            <div class="flex min-w-[158px] flex-1 flex-col gap-2 rounded-xl p-6 border border-border-light  bg-white ">
                                <p class="text-text-muted-light  text-sm font-medium leading-normal">Theme Latin Name</p>

                                <p class="text-text-light  tracking-light text-3xl font-bold leading-tight">Consectator</p>
                            </div>
                        </div> -->
                        <div class="theme-section">
                            <div class="grid grid-cols:2 mt-20 sm:grid-cols-3 md:grid-cols-2 lg:grid-cols-3 gap-6 p-4 cards">
                                <div class="flex flex-1 flex-col justify-center rounded-lg p-5 shadow-md bg-white h-fit domain-card">
                                    <p class="text-cyan-800/80 text-sm font-medium title">دسته استعدادی</p>

                                    <div class="my-2 h-px sep"></div>

                                    <p class="text-cyan-900 text-xl xl:text-2xl font-bold text">{THEME_DOMAIN}</p>
                                </div>
                                <div class="flex flex-1 flex-col justify-center rounded-lg p-5 shadow-md bg-white h-fit english-name-card">
                                    <p class="text-cyan-800/80 text-sm font-medium title">نام انگلیسی</p>

                                    <div class="my-2 h-px sep"></div>

                                    <p class="text-cyan-900 text-xl xl:text-2xl font-bold text">{ENGLISH_NAME}</p>
                                </div>
                                <div class="flex flex-1 flex-col justify-center rounded-lg p-5 shadow-md bg-white h-fit rank-card ">
                                    <p class="text-cyan-800/80 text-sm font-medium title">رتبه استعداد</p>

                                    <div class="my-2 h-px sep"></div>

                                    <p class="text-cyan-900 text-xl xl:text-2xl font-bold text">{THEME_RANK}</p>
                                </div>
                            </div>
                        
                            <div class="flex flex-col md:flex-row items-center gap-8 md:gap-12 p-8">
                                <div class="flex flex-col items-center text-center md:items-start md:text-left gap-4 flex-1">
                                    <div class="flex items-center gap-6">
                                        <span class="material-symbols-outlined text-cyan-800 !text-6xl md:!text-7xl -ml-2 theme-icon">rocket_launch</span>

                                        <h1 class="text-5xl md:text-5xl text-cyan-800 leading-none tracking-[-0.04em] theme-title">تاثیرگذار</h1>
                                    </div>
                                </div>

                                <div class="flex flex-col items-center gap-4">
                                    <div class="relative size-32">
                                        <svg class="size-full" viewBox="0 0 36 36">
                                            <path
                                                class="text-white theme-chart-stroke"
                                                d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                                                fill="none"
                                                stroke="currentColor"
                                                stroke-width="4"
                                            ></path>

                                            <path
                                                class="text-cyan-800 theme-chart"
                                                d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                                                fill="none"
                                                stroke="currentColor"
                                                stroke-dasharray="85, 100"
                                                stroke-linecap="round"
                                                stroke-width="4"
                                                transform="rotate(-90 18 18)"
                                            ></path>
                                        </svg>

                                        <div class="absolute inset-0 flex flex-col items-center justify-center">
                                            <span class="text-cyan-800 text-3xl font-bold leading-none tracking-tight theme-percentage">
                                                85%
                                            </span>
                                        </div>
                                    </div>


                                    <p class="text-text-muted-light text-sm font-medium">درصد غلبه</p>
                                </div>
                            </div>

                            <div class="pt-0">
                                <div class="prose prose-lg max-w-none text-text-muted-light">
                                    <p class="text-gray-800 text-base md:text-lg theme-description">
                                        از آنجا که استعداد هماهنگ‌کننده به عنوان ششمین نقطه قوت شما قرار گرفته است، احتمال دارد در جستجوی توافق و اجماع در میان افراد گروه خود برجسته باشید. این قابلیت به شما این امکان را می‌دهد که مناقشات را کاهش دهید و به دنبال زمینه‌های مشترک باشید، که این می‌تواند در هماهنگ‌سازی تیم‌ها و پروژه‌های گروهی به شما کمک کند.
                                    </p>

                                    <p class="text-xl md:text-2xl font-bold mt-10">
                                        کاربردهای مهم این استعداد
                                    </p>

                                    <div class="flex flex-col py-6 gap-4 theme-usages">
                                        <div class="flex gap-7 items-center item">
                                            <i class="fa-solid fa-circle-check text-green-700 text-[25px]"></i>
                                            <p class="text-base lg:text-lg text-gray-800 text">
                                                لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپ و با استفاده از طراحان گرافیک است چاپگرها و متون بلکه روزنامه و مجله در ستون و سطرآنچنان که لازم است
                                            </p>
                                        </div>
                                        <div class="flex gap-7 items-center item">
                                            <i class="fa-solid fa-circle-check text-green-700 text-[25px]"></i>
                                            <p class="text-base lg:text-lg text-gray-800 text">
                                                لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپ و با استفاده از طراحان گرافیک است چاپگرها و متون بلکه روزنامه و مجله در ستون و سطرآنچنان که لازم است
                                            </p>
                                        </div>
                                        <div class="flex gap-7 items-center item">
                                            <i class="fa-solid fa-circle-check text-green-700 text-[25px]"></i>
                                            <p class="text-base lg:text-lg text-gray-800 text">
                                                لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپ و با استفاده از طراحان گرافیک است چاپگرها و متون بلکه روزنامه و مجله در ستون و سطرآنچنان که لازم است
                                            </p>
                                        </div>
                                        <div class="flex gap-7 items-center item">
                                            <i class="fa-solid fa-circle-check text-green-700 text-[25px]"></i>
                                            <p class="text-base lg:text-lg text-gray-800 text">
                                                لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپ و با استفاده از طراحان گرافیک است چاپگرها و متون بلکه روزنامه و مجله در ستون و سطرآنچنان که لازم است
                                            </p>
                                        </div>
                                        <div class="flex gap-7 items-center item">
                                            <i class="fa-solid fa-circle-check text-green-700 text-[25px]"></i>
                                            <p class="text-base lg:text-lg text-gray-800 text">
                                                لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپ و با استفاده از طراحان گرافیک است چاپگرها و متون بلکه روزنامه و مجله در ستون و سطرآنچنان که لازم است
                                            </p>
                                        </div>
                                        <div class="flex gap-7 items-center item">
                                            <i class="fa-solid fa-circle-check text-green-700 text-[25px]"></i>
                                            <p class="text-base lg:text-lg text-gray-800 text">
                                                لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپ و با استفاده از طراحان گرافیک است چاپگرها و متون بلکه روزنامه و مجله در ستون و سطرآنچنان که لازم است
                                            </p>
                                        </div>
                                        <div class="flex gap-7 items-center item">
                                            <i class="fa-solid fa-circle-check text-green-700 text-[25px]"></i>
                                            <p class="text-base lg:text-lg text-gray-800 text">
                                                لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپ و با استفاده از طراحان گرافیک است چاپگرها و متون بلکه روزنامه و مجله در ستون و سطرآنچنان که لازم است
                                            </p>
                                        </div>
                                        <div class="flex gap-7 items-center item">
                                            <i class="fa-solid fa-circle-check text-green-700 text-[25px]"></i>
                                            <p class="text-base lg:text-lg text-gray-800 text">
                                                لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپ و با استفاده از طراحان گرافیک است چاپگرها و متون بلکه روزنامه و مجله در ستون و سطرآنچنان که لازم است
                                            </p>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>

                        <div class="w-full mt-20 h-[2px] bg-gray-300 separator"></div>
                        <div class="theme-section">
                            <div class="grid grid-cols:2 mt-20 sm:grid-cols-3 md:grid-cols-2 lg:grid-cols-3 gap-6 p-4 cards">
                                <div class="flex flex-1 flex-col justify-center rounded-lg p-5 shadow-md bg-white h-fit domain-card">
                                    <p class="text-cyan-800/80 text-sm font-medium title">دسته استعدادی</p>

                                    <div class="my-2 h-px sep"></div>

                                    <p class="text-cyan-900 text-xl xl:text-2xl font-bold text">{THEME_DOMAIN}</p>
                                </div>
                                <div class="flex flex-1 flex-col justify-center rounded-lg p-5 shadow-md bg-white h-fit english-name-card">
                                    <p class="text-cyan-800/80 text-sm font-medium title">نام انگلیسی</p>

                                    <div class="my-2 h-px sep"></div>

                                    <p class="text-cyan-900 text-xl xl:text-2xl font-bold text">{ENGLISH_NAME}</p>
                                </div>
                                <div class="flex flex-1 flex-col justify-center rounded-lg p-5 shadow-md bg-white h-fit rank-card ">
                                    <p class="text-cyan-800/80 text-sm font-medium title">رتبه استعداد</p>

                                    <div class="my-2 h-px sep"></div>

                                    <p class="text-cyan-900 text-xl xl:text-2xl font-bold text">{THEME_RANK}</p>
                                </div>
                            </div>
                        
                            <div class="flex flex-col md:flex-row items-center gap-8 md:gap-12 p-8">
                                <div class="flex flex-col items-center text-center md:items-start md:text-left gap-4 flex-1">
                                    <div class="flex items-center gap-6">
                                        <span class="material-symbols-outlined text-cyan-800 !text-6xl md:!text-7xl -ml-2 theme-icon">rocket_launch</span>

                                        <h1 class="text-5xl md:text-5xl text-cyan-800 leading-none tracking-[-0.04em] theme-title">تاثیرگذار</h1>
                                    </div>
                                </div>

                                <div class="flex flex-col items-center gap-4">
                                    <div class="relative size-32">
                                        <svg class="size-full" viewBox="0 0 36 36">
                                            <path
                                                class="text-white theme-chart-stroke"
                                                d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                                                fill="none"
                                                stroke="currentColor"
                                                stroke-width="4"
                                            ></path>

                                            <path
                                                class="text-cyan-800 theme-chart"
                                                d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                                                fill="none"
                                                stroke="currentColor"
                                                stroke-dasharray="85, 100"
                                                stroke-linecap="round"
                                                stroke-width="4"
                                                transform="rotate(-90 18 18)"
                                            ></path>
                                        </svg>

                                        <div class="absolute inset-0 flex flex-col items-center justify-center">
                                            <span class="text-cyan-800 text-3xl font-bold leading-none tracking-tight theme-percentage">
                                                85%
                                            </span>
                                        </div>
                                    </div>


                                    <p class="text-text-muted-light text-sm font-medium">درصد غلبه</p>
                                </div>
                            </div>

                            <div class="pt-0">
                                <div class="prose prose-lg max-w-none text-text-muted-light">
                                    <p class="text-gray-800 text-base md:text-lg theme-description">
                                        از آنجا که استعداد هماهنگ‌کننده به عنوان ششمین نقطه قوت شما قرار گرفته است، احتمال دارد در جستجوی توافق و اجماع در میان افراد گروه خود برجسته باشید. این قابلیت به شما این امکان را می‌دهد که مناقشات را کاهش دهید و به دنبال زمینه‌های مشترک باشید، که این می‌تواند در هماهنگ‌سازی تیم‌ها و پروژه‌های گروهی به شما کمک کند.
                                    </p>

                                    <p class="text-xl md:text-2xl font-bold mt-10">
                                        کاربردهای مهم این استعداد
                                    </p>

                                    <div class="flex flex-col py-6 gap-4 theme-usages">
                                        <div class="flex gap-7 items-center item">
                                            <i class="fa-solid fa-circle-check text-green-700 text-[25px]"></i>
                                            <p class="text-base lg:text-lg text-gray-800 text">
                                                لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپ و با استفاده از طراحان گرافیک است چاپگرها و متون بلکه روزنامه و مجله در ستون و سطرآنچنان که لازم است
                                            </p>
                                        </div>
                                        <div class="flex gap-7 items-center item">
                                            <i class="fa-solid fa-circle-check text-green-700 text-[25px]"></i>
                                            <p class="text-base lg:text-lg text-gray-800 text">
                                                لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپ و با استفاده از طراحان گرافیک است چاپگرها و متون بلکه روزنامه و مجله در ستون و سطرآنچنان که لازم است
                                            </p>
                                        </div>
                                        <div class="flex gap-7 items-center item">
                                            <i class="fa-solid fa-circle-check text-green-700 text-[25px]"></i>
                                            <p class="text-base lg:text-lg text-gray-800 text">
                                                لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپ و با استفاده از طراحان گرافیک است چاپگرها و متون بلکه روزنامه و مجله در ستون و سطرآنچنان که لازم است
                                            </p>
                                        </div>
                                        <div class="flex gap-7 items-center item">
                                            <i class="fa-solid fa-circle-check text-green-700 text-[25px]"></i>
                                            <p class="text-base lg:text-lg text-gray-800 text">
                                                لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپ و با استفاده از طراحان گرافیک است چاپگرها و متون بلکه روزنامه و مجله در ستون و سطرآنچنان که لازم است
                                            </p>
                                        </div>
                                        <div class="flex gap-7 items-center item">
                                            <i class="fa-solid fa-circle-check text-green-700 text-[25px]"></i>
                                            <p class="text-base lg:text-lg text-gray-800 text">
                                                لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپ و با استفاده از طراحان گرافیک است چاپگرها و متون بلکه روزنامه و مجله در ستون و سطرآنچنان که لازم است
                                            </p>
                                        </div>
                                        <div class="flex gap-7 items-center item">
                                            <i class="fa-solid fa-circle-check text-green-700 text-[25px]"></i>
                                            <p class="text-base lg:text-lg text-gray-800 text">
                                                لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپ و با استفاده از طراحان گرافیک است چاپگرها و متون بلکه روزنامه و مجله در ستون و سطرآنچنان که لازم است
                                            </p>
                                        </div>
                                        <div class="flex gap-7 items-center item">
                                            <i class="fa-solid fa-circle-check text-green-700 text-[25px]"></i>
                                            <p class="text-base lg:text-lg text-gray-800 text">
                                                لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپ و با استفاده از طراحان گرافیک است چاپگرها و متون بلکه روزنامه و مجله در ستون و سطرآنچنان که لازم است
                                            </p>
                                        </div>
                                        <div class="flex gap-7 items-center item">
                                            <i class="fa-solid fa-circle-check text-green-700 text-[25px]"></i>
                                            <p class="text-base lg:text-lg text-gray-800 text">
                                                لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپ و با استفاده از طراحان گرافیک است چاپگرها و متون بلکه روزنامه و مجله در ستون و سطرآنچنان که لازم است
                                            </p>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>

                        <div class="w-full mt-20 h-[2px] bg-gray-300 separator"></div>
                        <div class="theme-section">
                            <div class="grid grid-cols:2 mt-20 sm:grid-cols-3 md:grid-cols-2 lg:grid-cols-3 gap-6 p-4 cards">
                                <div class="flex flex-1 flex-col justify-center rounded-lg p-5 shadow-md bg-white h-fit domain-card">
                                    <p class="text-cyan-800/80 text-sm font-medium title">دسته استعدادی</p>

                                    <div class="my-2 h-px sep"></div>

                                    <p class="text-cyan-900 text-xl xl:text-2xl font-bold text">{THEME_DOMAIN}</p>
                                </div>
                                <div class="flex flex-1 flex-col justify-center rounded-lg p-5 shadow-md bg-white h-fit english-name-card">
                                    <p class="text-cyan-800/80 text-sm font-medium title">نام انگلیسی</p>

                                    <div class="my-2 h-px sep"></div>

                                    <p class="text-cyan-900 text-xl xl:text-2xl font-bold text">{ENGLISH_NAME}</p>
                                </div>
                                <div class="flex flex-1 flex-col justify-center rounded-lg p-5 shadow-md bg-white h-fit rank-card ">
                                    <p class="text-cyan-800/80 text-sm font-medium title">رتبه استعداد</p>

                                    <div class="my-2 h-px sep"></div>

                                    <p class="text-cyan-900 text-xl xl:text-2xl font-bold text">{THEME_RANK}</p>
                                </div>
                            </div>
                        
                            <div class="flex flex-col md:flex-row items-center gap-8 md:gap-12 p-8">
                                <div class="flex flex-col items-center text-center md:items-start md:text-left gap-4 flex-1">
                                    <div class="flex items-center gap-6">
                                        <span class="material-symbols-outlined text-cyan-800 !text-6xl md:!text-7xl -ml-2 theme-icon">rocket_launch</span>

                                        <h1 class="text-5xl md:text-5xl text-cyan-800 leading-none tracking-[-0.04em] theme-title">تاثیرگذار</h1>
                                    </div>
                                </div>

                                <div class="flex flex-col items-center gap-4">
                                    <div class="relative size-32">
                                        <svg class="size-full" viewBox="0 0 36 36">
                                            <path
                                                class="text-white theme-chart-stroke"
                                                d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                                                fill="none"
                                                stroke="currentColor"
                                                stroke-width="4"
                                            ></path>

                                            <path
                                                class="text-cyan-800 theme-chart"
                                                d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                                                fill="none"
                                                stroke="currentColor"
                                                stroke-dasharray="85, 100"
                                                stroke-linecap="round"
                                                stroke-width="4"
                                                transform="rotate(-90 18 18)"
                                            ></path>
                                        </svg>

                                        <div class="absolute inset-0 flex flex-col items-center justify-center">
                                            <span class="text-cyan-800 text-3xl font-bold leading-none tracking-tight theme-percentage">
                                                85%
                                            </span>
                                        </div>
                                    </div>


                                    <p class="text-text-muted-light text-sm font-medium">درصد غلبه</p>
                                </div>
                            </div>

                            <div class="pt-0">
                                <div class="prose prose-lg max-w-none text-text-muted-light">
                                    <p class="text-gray-800 text-base md:text-lg theme-description">
                                        از آنجا که استعداد هماهنگ‌کننده به عنوان ششمین نقطه قوت شما قرار گرفته است، احتمال دارد در جستجوی توافق و اجماع در میان افراد گروه خود برجسته باشید. این قابلیت به شما این امکان را می‌دهد که مناقشات را کاهش دهید و به دنبال زمینه‌های مشترک باشید، که این می‌تواند در هماهنگ‌سازی تیم‌ها و پروژه‌های گروهی به شما کمک کند.
                                    </p>

                                    <p class="text-xl md:text-2xl font-bold mt-10">
                                        کاربردهای مهم این استعداد
                                    </p>

                                    <div class="flex flex-col py-6 gap-4 theme-usages">
                                        <div class="flex gap-7 items-center item">
                                            <i class="fa-solid fa-circle-check text-green-700 text-[25px]"></i>
                                            <p class="text-base lg:text-lg text-gray-800 text">
                                                لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپ و با استفاده از طراحان گرافیک است چاپگرها و متون بلکه روزنامه و مجله در ستون و سطرآنچنان که لازم است
                                            </p>
                                        </div>
                                        <div class="flex gap-7 items-center item">
                                            <i class="fa-solid fa-circle-check text-green-700 text-[25px]"></i>
                                            <p class="text-base lg:text-lg text-gray-800 text">
                                                لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپ و با استفاده از طراحان گرافیک است چاپگرها و متون بلکه روزنامه و مجله در ستون و سطرآنچنان که لازم است
                                            </p>
                                        </div>
                                        <div class="flex gap-7 items-center item">
                                            <i class="fa-solid fa-circle-check text-green-700 text-[25px]"></i>
                                            <p class="text-base lg:text-lg text-gray-800 text">
                                                لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپ و با استفاده از طراحان گرافیک است چاپگرها و متون بلکه روزنامه و مجله در ستون و سطرآنچنان که لازم است
                                            </p>
                                        </div>
                                        <div class="flex gap-7 items-center item">
                                            <i class="fa-solid fa-circle-check text-green-700 text-[25px]"></i>
                                            <p class="text-base lg:text-lg text-gray-800 text">
                                                لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپ و با استفاده از طراحان گرافیک است چاپگرها و متون بلکه روزنامه و مجله در ستون و سطرآنچنان که لازم است
                                            </p>
                                        </div>
                                        <div class="flex gap-7 items-center item">
                                            <i class="fa-solid fa-circle-check text-green-700 text-[25px]"></i>
                                            <p class="text-base lg:text-lg text-gray-800 text">
                                                لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپ و با استفاده از طراحان گرافیک است چاپگرها و متون بلکه روزنامه و مجله در ستون و سطرآنچنان که لازم است
                                            </p>
                                        </div>
                                        <div class="flex gap-7 items-center item">
                                            <i class="fa-solid fa-circle-check text-green-700 text-[25px]"></i>
                                            <p class="text-base lg:text-lg text-gray-800 text">
                                                لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپ و با استفاده از طراحان گرافیک است چاپگرها و متون بلکه روزنامه و مجله در ستون و سطرآنچنان که لازم است
                                            </p>
                                        </div>
                                        <div class="flex gap-7 items-center item">
                                            <i class="fa-solid fa-circle-check text-green-700 text-[25px]"></i>
                                            <p class="text-base lg:text-lg text-gray-800 text">
                                                لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپ و با استفاده از طراحان گرافیک است چاپگرها و متون بلکه روزنامه و مجله در ستون و سطرآنچنان که لازم است
                                            </p>
                                        </div>
                                        <div class="flex gap-7 items-center item">
                                            <i class="fa-solid fa-circle-check text-green-700 text-[25px]"></i>
                                            <p class="text-base lg:text-lg text-gray-800 text">
                                                لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپ و با استفاده از طراحان گرافیک است چاپگرها و متون بلکه روزنامه و مجله در ستون و سطرآنچنان که لازم است
                                            </p>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>

                        <div class="w-full mt-20 h-[2px] bg-gray-300 separator"></div>
                        <div class="theme-section">
                            <div class="grid grid-cols:2 mt-20 sm:grid-cols-3 md:grid-cols-2 lg:grid-cols-3 gap-6 p-4 cards">
                                <div class="flex flex-1 flex-col justify-center rounded-lg p-5 shadow-md bg-white h-fit domain-card">
                                    <p class="text-cyan-800/80 text-sm font-medium title">دسته استعدادی</p>

                                    <div class="my-2 h-px sep"></div>

                                    <p class="text-cyan-900 text-xl xl:text-2xl font-bold text">{THEME_DOMAIN}</p>
                                </div>
                                <div class="flex flex-1 flex-col justify-center rounded-lg p-5 shadow-md bg-white h-fit english-name-card">
                                    <p class="text-cyan-800/80 text-sm font-medium title">نام انگلیسی</p>

                                    <div class="my-2 h-px sep"></div>

                                    <p class="text-cyan-900 text-xl xl:text-2xl font-bold text">{ENGLISH_NAME}</p>
                                </div>
                                <div class="flex flex-1 flex-col justify-center rounded-lg p-5 shadow-md bg-white h-fit rank-card ">
                                    <p class="text-cyan-800/80 text-sm font-medium title">رتبه استعداد</p>

                                    <div class="my-2 h-px sep"></div>

                                    <p class="text-cyan-900 text-xl xl:text-2xl font-bold text">{THEME_RANK}</p>
                                </div>
                            </div>
                        
                            <div class="flex flex-col md:flex-row items-center gap-8 md:gap-12 p-8">
                                <div class="flex flex-col items-center text-center md:items-start md:text-left gap-4 flex-1">
                                    <div class="flex items-center gap-6">
                                        <span class="material-symbols-outlined text-cyan-800 !text-6xl md:!text-7xl -ml-2 theme-icon">rocket_launch</span>

                                        <h1 class="text-5xl md:text-5xl text-cyan-800 leading-none tracking-[-0.04em] theme-title">تاثیرگذار</h1>
                                    </div>
                                </div>

                                <div class="flex flex-col items-center gap-4">
                                    <div class="relative size-32">
                                        <svg class="size-full" viewBox="0 0 36 36">
                                            <path
                                                class="text-white theme-chart-stroke"
                                                d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                                                fill="none"
                                                stroke="currentColor"
                                                stroke-width="4"
                                            ></path>

                                            <path
                                                class="text-cyan-800 theme-chart"
                                                d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                                                fill="none"
                                                stroke="currentColor"
                                                stroke-dasharray="85, 100"
                                                stroke-linecap="round"
                                                stroke-width="4"
                                                transform="rotate(-90 18 18)"
                                            ></path>
                                        </svg>

                                        <div class="absolute inset-0 flex flex-col items-center justify-center">
                                            <span class="text-cyan-800 text-3xl font-bold leading-none tracking-tight theme-percentage">
                                                85%
                                            </span>
                                        </div>
                                    </div>


                                    <p class="text-text-muted-light text-sm font-medium">درصد غلبه</p>
                                </div>
                            </div>

                            <div class="pt-0">
                                <div class="prose prose-lg max-w-none text-text-muted-light">
                                    <p class="text-gray-800 text-base md:text-lg theme-description">
                                        از آنجا که استعداد هماهنگ‌کننده به عنوان ششمین نقطه قوت شما قرار گرفته است، احتمال دارد در جستجوی توافق و اجماع در میان افراد گروه خود برجسته باشید. این قابلیت به شما این امکان را می‌دهد که مناقشات را کاهش دهید و به دنبال زمینه‌های مشترک باشید، که این می‌تواند در هماهنگ‌سازی تیم‌ها و پروژه‌های گروهی به شما کمک کند.
                                    </p>

                                    <p class="text-xl md:text-2xl font-bold mt-10">
                                        کاربردهای مهم این استعداد
                                    </p>

                                    <div class="flex flex-col py-6 gap-4 theme-usages">
                                        <div class="flex gap-7 items-center item">
                                            <i class="fa-solid fa-circle-check text-green-700 text-[25px]"></i>
                                            <p class="text-base lg:text-lg text-gray-800 text">
                                                لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپ و با استفاده از طراحان گرافیک است چاپگرها و متون بلکه روزنامه و مجله در ستون و سطرآنچنان که لازم است
                                            </p>
                                        </div>
                                        <div class="flex gap-7 items-center item">
                                            <i class="fa-solid fa-circle-check text-green-700 text-[25px]"></i>
                                            <p class="text-base lg:text-lg text-gray-800 text">
                                                لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپ و با استفاده از طراحان گرافیک است چاپگرها و متون بلکه روزنامه و مجله در ستون و سطرآنچنان که لازم است
                                            </p>
                                        </div>
                                        <div class="flex gap-7 items-center item">
                                            <i class="fa-solid fa-circle-check text-green-700 text-[25px]"></i>
                                            <p class="text-base lg:text-lg text-gray-800 text">
                                                لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپ و با استفاده از طراحان گرافیک است چاپگرها و متون بلکه روزنامه و مجله در ستون و سطرآنچنان که لازم است
                                            </p>
                                        </div>
                                        <div class="flex gap-7 items-center item">
                                            <i class="fa-solid fa-circle-check text-green-700 text-[25px]"></i>
                                            <p class="text-base lg:text-lg text-gray-800 text">
                                                لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپ و با استفاده از طراحان گرافیک است چاپگرها و متون بلکه روزنامه و مجله در ستون و سطرآنچنان که لازم است
                                            </p>
                                        </div>
                                        <div class="flex gap-7 items-center item">
                                            <i class="fa-solid fa-circle-check text-green-700 text-[25px]"></i>
                                            <p class="text-base lg:text-lg text-gray-800 text">
                                                لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپ و با استفاده از طراحان گرافیک است چاپگرها و متون بلکه روزنامه و مجله در ستون و سطرآنچنان که لازم است
                                            </p>
                                        </div>
                                        <div class="flex gap-7 items-center item">
                                            <i class="fa-solid fa-circle-check text-green-700 text-[25px]"></i>
                                            <p class="text-base lg:text-lg text-gray-800 text">
                                                لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپ و با استفاده از طراحان گرافیک است چاپگرها و متون بلکه روزنامه و مجله در ستون و سطرآنچنان که لازم است
                                            </p>
                                        </div>
                                        <div class="flex gap-7 items-center item">
                                            <i class="fa-solid fa-circle-check text-green-700 text-[25px]"></i>
                                            <p class="text-base lg:text-lg text-gray-800 text">
                                                لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپ و با استفاده از طراحان گرافیک است چاپگرها و متون بلکه روزنامه و مجله در ستون و سطرآنچنان که لازم است
                                            </p>
                                        </div>
                                        <div class="flex gap-7 items-center item">
                                            <i class="fa-solid fa-circle-check text-green-700 text-[25px]"></i>
                                            <p class="text-base lg:text-lg text-gray-800 text">
                                                لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپ و با استفاده از طراحان گرافیک است چاپگرها و متون بلکه روزنامه و مجله در ستون و سطرآنچنان که لازم است
                                            </p>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>
                        <div class="w-full mt-20 h-[2px] bg-gray-300 separator"></div>
                        <div class="theme-section">
                            <div class="grid grid-cols:2 mt-20 sm:grid-cols-3 md:grid-cols-2 lg:grid-cols-3 gap-6 p-4 cards">
                                <div class="flex flex-1 flex-col justify-center rounded-lg p-5 shadow-md bg-white h-fit domain-card">
                                    <p class="text-cyan-800/80 text-sm font-medium title">دسته استعدادی</p>

                                    <div class="my-2 h-px sep"></div>

                                    <p class="text-cyan-900 text-xl xl:text-2xl font-bold text">{THEME_DOMAIN}</p>
                                </div>
                                <div class="flex flex-1 flex-col justify-center rounded-lg p-5 shadow-md bg-white h-fit english-name-card">
                                    <p class="text-cyan-800/80 text-sm font-medium title">نام انگلیسی</p>

                                    <div class="my-2 h-px sep"></div>

                                    <p class="text-cyan-900 text-xl xl:text-2xl font-bold text">{ENGLISH_NAME}</p>
                                </div>
                                <div class="flex flex-1 flex-col justify-center rounded-lg p-5 shadow-md bg-white h-fit rank-card ">
                                    <p class="text-cyan-800/80 text-sm font-medium title">رتبه استعداد</p>

                                    <div class="my-2 h-px sep"></div>

                                    <p class="text-cyan-900 text-xl xl:text-2xl font-bold text">{THEME_RANK}</p>
                                </div>
                            </div>
                        
                            <div class="flex flex-col md:flex-row items-center gap-8 md:gap-12 p-8">
                                <div class="flex flex-col items-center text-center md:items-start md:text-left gap-4 flex-1">
                                    <div class="flex items-center gap-6">
                                        <span class="material-symbols-outlined text-cyan-800 !text-6xl md:!text-7xl -ml-2 theme-icon">rocket_launch</span>

                                        <h1 class="text-5xl md:text-5xl text-cyan-800 leading-none tracking-[-0.04em] theme-title">تاثیرگذار</h1>
                                    </div>
                                </div>

                                <div class="flex flex-col items-center gap-4">
                                    <div class="relative size-32">
                                        <svg class="size-full" viewBox="0 0 36 36">
                                            <path
                                                class="text-white theme-chart-stroke"
                                                d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                                                fill="none"
                                                stroke="currentColor"
                                                stroke-width="4"
                                            ></path>

                                            <path
                                                class="text-cyan-800 theme-chart"
                                                d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                                                fill="none"
                                                stroke="currentColor"
                                                stroke-dasharray="85, 100"
                                                stroke-linecap="round"
                                                stroke-width="4"
                                                transform="rotate(-90 18 18)"
                                            ></path>
                                        </svg>

                                        <div class="absolute inset-0 flex flex-col items-center justify-center">
                                            <span class="text-cyan-800 text-3xl font-bold leading-none tracking-tight theme-percentage">
                                                85%
                                            </span>
                                        </div>
                                    </div>


                                    <p class="text-text-muted-light text-sm font-medium">درصد غلبه</p>
                                </div>
                            </div>

                            <div class="pt-0">
                                <div class="prose prose-lg max-w-none text-text-muted-light">
                                    <p class="text-gray-800 text-base md:text-lg theme-description">
                                        از آنجا که استعداد هماهنگ‌کننده به عنوان ششمین نقطه قوت شما قرار گرفته است، احتمال دارد در جستجوی توافق و اجماع در میان افراد گروه خود برجسته باشید. این قابلیت به شما این امکان را می‌دهد که مناقشات را کاهش دهید و به دنبال زمینه‌های مشترک باشید، که این می‌تواند در هماهنگ‌سازی تیم‌ها و پروژه‌های گروهی به شما کمک کند.
                                    </p>

                                    <p class="text-xl md:text-2xl font-bold mt-10">
                                        کاربردهای مهم این استعداد
                                    </p>

                                    <div class="flex flex-col py-6 gap-4 theme-usages">
                                        <div class="flex gap-7 items-center item">
                                            <i class="fa-solid fa-circle-check text-green-700 text-[25px]"></i>
                                            <p class="text-base lg:text-lg text-gray-800 text">
                                                لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپ و با استفاده از طراحان گرافیک است چاپگرها و متون بلکه روزنامه و مجله در ستون و سطرآنچنان که لازم است
                                            </p>
                                        </div>
                                        <div class="flex gap-7 items-center item">
                                            <i class="fa-solid fa-circle-check text-green-700 text-[25px]"></i>
                                            <p class="text-base lg:text-lg text-gray-800 text">
                                                لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپ و با استفاده از طراحان گرافیک است چاپگرها و متون بلکه روزنامه و مجله در ستون و سطرآنچنان که لازم است
                                            </p>
                                        </div>
                                        <div class="flex gap-7 items-center item">
                                            <i class="fa-solid fa-circle-check text-green-700 text-[25px]"></i>
                                            <p class="text-base lg:text-lg text-gray-800 text">
                                                لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپ و با استفاده از طراحان گرافیک است چاپگرها و متون بلکه روزنامه و مجله در ستون و سطرآنچنان که لازم است
                                            </p>
                                        </div>
                                        <div class="flex gap-7 items-center item">
                                            <i class="fa-solid fa-circle-check text-green-700 text-[25px]"></i>
                                            <p class="text-base lg:text-lg text-gray-800 text">
                                                لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپ و با استفاده از طراحان گرافیک است چاپگرها و متون بلکه روزنامه و مجله در ستون و سطرآنچنان که لازم است
                                            </p>
                                        </div>
                                        <div class="flex gap-7 items-center item">
                                            <i class="fa-solid fa-circle-check text-green-700 text-[25px]"></i>
                                            <p class="text-base lg:text-lg text-gray-800 text">
                                                لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپ و با استفاده از طراحان گرافیک است چاپگرها و متون بلکه روزنامه و مجله در ستون و سطرآنچنان که لازم است
                                            </p>
                                        </div>
                                        <div class="flex gap-7 items-center item">
                                            <i class="fa-solid fa-circle-check text-green-700 text-[25px]"></i>
                                            <p class="text-base lg:text-lg text-gray-800 text">
                                                لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپ و با استفاده از طراحان گرافیک است چاپگرها و متون بلکه روزنامه و مجله در ستون و سطرآنچنان که لازم است
                                            </p>
                                        </div>
                                        <div class="flex gap-7 items-center item">
                                            <i class="fa-solid fa-circle-check text-green-700 text-[25px]"></i>
                                            <p class="text-base lg:text-lg text-gray-800 text">
                                                لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپ و با استفاده از طراحان گرافیک است چاپگرها و متون بلکه روزنامه و مجله در ستون و سطرآنچنان که لازم است
                                            </p>
                                        </div>
                                        <div class="flex gap-7 items-center item">
                                            <i class="fa-solid fa-circle-check text-green-700 text-[25px]"></i>
                                            <p class="text-base lg:text-lg text-gray-800 text">
                                                لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپ و با استفاده از طراحان گرافیک است چاپگرها و متون بلکه روزنامه و مجله در ستون و سطرآنچنان که لازم است
                                            </p>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>

                    </div>
                
                <!-- تب پیشنهادات شغلی -->
                <!-- <div id="job-suggestions-tab" class="tab-content hidden">
        
                </div> -->
                
                <!-- تب نقاط ضعف استعدادی -->
                <div id="weaknesses-tab" class="tab-content hidden px-5 md:px-20 py-10 flex flex-col">
                    <p class="text-base md:text-lg text-gray-800">
                    ۵ استعداد ضعیف شما آن دسته از توانمندی‌هایی هستند که در حال حاضر کمترین نقش را در رفتارها، تصمیم‌گیری‌ها و سبک عملکرد شما ایفا می‌کنند. این به معنی ناتوانی یا ضعف شخصیتی نیست؛ بلکه صرفاً نشان می‌دهد این استعدادها برای شما کمتر انرژی‌زا، کمتر طبیعی و کمتر الهام‌بخش هستند و معمولاً به‌صورت خودکار فعال نمی‌شوند.
توجه به این بخش مهم است، چون به شما کمک می‌کند الگوهای رفتاری کمتر‌فعال خود را بشناسید و بفهمید در چه موقعیت‌هایی ممکن است انرژی بیشتری مصرف کنید، تمرکز کاهش پیدا کند یا نیاز به استراتژی‌های جبرانی داشته باشید. همچنین این شناخت به شما دید می‌دهد که چطور می‌توانید با انتخاب محیط، نقش، مسئولیت و سبک کار مناسب عملکردتان را بهینه کنید و از فشارهای غیرضروری جلوگیری کنید.
                    </p>

                    <div class="mt-16">
                        <div class="flex items-center gap-2 w-full mb-10">
                            <h3 class="text-2xl md:text-3xl font-bold text-gray-700">نقاط ضعف احتمالی</h3>

                            <div class="flex-1 h-[2px] bg-gray-300"></div>
                        </div>

                        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-6 themes-overview">
                            <div class="group bg-white  p-6 rounded-lg shadow-sm border border-slate-200  transition-all duration-300 text-center hover:border-primary flex flex-col justify-between card">
                                <div class="flex-grow">
                                    <div class="flex justify-center items-center mb-4">
                                        <div class="bg-primary/10  p-3 rounded-full">
                                            <span class="material-symbols-outlined text-3xl text-primary icon">verified</span>
                                        </div>
                                    </div>

                                    <div class="flex justify-center items-baseline gap-2 mb-2">
                                        <h3 class="text-lg font-semibold text-slate-800 title">Belief</h3>

                                        <span class="text-sm font-semibold text-slate-400 rank">#6</span>
                                    </div>

                                    <p class="text-sm text-slate-500 id-phrase">Strive to do what is right.</p>
                                </div>
                            </div>

                            <div class="group bg-white  p-6 rounded-lg shadow-sm border border-slate-200  transition-all duration-300 text-center hover:border-primary  flex flex-col justify-between card">
                                <div class="flex-grow">
                                    <div class="flex justify-center items-center mb-4">
                                        <div class="bg-primary/10  p-3 rounded-full">
                                            <span class="material-symbols-outlined text-3xl text-primary icon">emoji_events</span>
                                        </div>
                                    </div>

                                    <div class="flex justify-center items-baseline gap-2 mb-2">
                                        <h3 class="text-lg font-semibold text-slate-800 title">Competition</h3>

                                        <span class="text-sm font-semibold text-slate-400 rank">#7</span>
                                    </div>

                                    <p class="text-sm text-slate-500 id-phrase">Measure your progress.</p>
                                </div>
                            </div>

                            <div class="group bg-white  p-6 rounded-lg shadow-sm border border-slate-200  transition-all duration-300 text-center hover:border-primary  flex flex-col justify-between card">
                                <div class="flex-grow">
                                    <div class="flex justify-center items-center mb-4">
                                        <div class="bg-primary/10  p-3 rounded-full">
                                            <span class="material-symbols-outlined text-3xl text-primary icon">hub</span>
                                        </div>
                                    </div>

                                    <div class="flex justify-center items-baseline gap-2 mb-2">
                                        <h3 class="text-lg font-semibold text-slate-800 title">Connectedness</h3>

                                        <span class="text-sm font-semibold text-slate-400 rank ">#8</span>
                                    </div>

                                    <p class="text-sm text-slate-500 id-phrase">
                                        Bridge divides between people.
                                    </p>
                                </div>
                            </div>

                            <div class="group bg-white  p-6 rounded-lg shadow-sm border border-slate-200  transition-all duration-300 text-center hover:border-primary  flex flex-col justify-between card">
                                <div class="flex-grow">
                                    <div class="flex justify-center items-center mb-4">
                                        <div class="bg-primary/10  p-3 rounded-full">
                                            <span class="material-symbols-outlined text-3xl text-primary icon">checklist</span>
                                        </div>
                                    </div>

                                    <div class="flex justify-center items-baseline gap-2 mb-2">
                                        <h3 class="text-lg font-semibold text-slate-800 title">Consistency</h3>

                                        <span class="text-sm font-semibold text-slate-400 rank">#9</span>
                                    </div>

                                    <p class="text-sm text-slate-500 id-phrase">Treat everyone the same.</p>
                                </div>
                            </div>

                            <div class="group bg-white  p-6 rounded-lg shadow-sm border border-slate-200  transition-all duration-300 text-center hover:border-primary  flex flex-col justify-between card">
                                <div class="flex-grow">
                                    <div class="flex justify-center items-center mb-4">
                                        <div class="bg-primary/10  p-3 rounded-full">
                                            <span class="material-symbols-outlined text-3xl text-primary icon">lightbulb</span>
                                        </div>
                                    </div>

                                    <div class="flex justify-center items-baseline gap-2 mb-2">
                                        <h3 class="text-lg font-semibold text-slate-800 title">Context</h3>

                                        <span class="text-sm font-semibold text-slate-400 rank">#10</span>
                                    </div>

                                    <p class="text-sm text-slate-500 id-phrase">Look back to understand.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div>
                        <div class="flex items-center gap-2 w-full mt-20 mb-10">
                            <h3 class="text-2xl md:text-3xl font-bold text-gray-700">بررسی و تحلیل نقاط ضعف</h3>

                            <div class="flex-1 h-[2px] bg-gray-300"></div>
                        </div>

                        <p class="text-gray-800 text-base md:text-lg">
                        در ادامه، تحلیل اختصاصی هر یک از پنج استعداد ضعیف شما آورده شده است. برای هر استعداد ابتدا یک توضیح و معرفی کوتاه می‌بینید تا دقیق‌تر درک کنید این توانمندی چه معنایی دارد و وقتی در شما کم‌فعال است، چه اطلاعاتی درباره الگوهای رفتاری‌تان به دست می‌دهد. سپس یک فهرست از چالش‌ها و تهدیدهای احتمالی ارائه شده که ممکن است در صورت ضعیف‌بودن آن استعداد در شما ظاهر شود.
در بخش پایانی نیز مجموعه‌ای از راهکارها و فرصت‌های کاملاً عملی قرار دارد که می‌توانید با استفاده از آنها این چالش‌ها را مدیریت، جبران یا به‌خوبی خنثی کنید تا عملکردتان در محیط کاری و زندگی بهینه‌تر شود
                        </p>
                    </div>

                    <div class="theme-section">
                        <div class="grid grid-cols:2 mt-20 sm:grid-cols-3 md:grid-cols-2 lg:grid-cols-3 gap-6 p-4 cards">
                            <div class="flex flex-1 flex-col justify-center rounded-lg p-5 shadow-md bg-white h-fit domain-card">
                                <p class="text-cyan-800/80 text-sm font-medium title">دسته استعدادی</p>

                                <div class="my-2 h-px sep"></div>

                                <p class="text-cyan-900 text-xl xl:text-2xl font-bold text">{THEME_DOMAIN}</p>
                            </div>
                            <div class="flex flex-1 flex-col justify-center rounded-lg p-5 shadow-md bg-white h-fit english-name-card">
                                <p class="text-cyan-800/80 text-sm font-medium title">نام انگلیسی</p>

                                <div class="my-2 h-px sep"></div>

                                <p class="text-cyan-900 text-xl xl:text-2xl font-bold text">{ENGLISH_NAME}</p>
                            </div>
                            <div class="flex flex-1 flex-col justify-center rounded-lg p-5 shadow-md bg-white h-fit rank-card ">
                                <p class="text-cyan-800/80 text-sm font-medium title">رتبه استعداد</p>

                                <div class="my-2 h-px sep"></div>

                                <p class="text-cyan-900 text-xl xl:text-2xl font-bold text">{THEME_RANK}</p>
                            </div>
                        </div>

                        <div class="flex flex-col md:flex-row items-center gap-8 md:gap-12 p-8">
                            <div class="flex flex-col items-center text-center md:items-start md:text-left gap-4 flex-1">
                                <div class="flex items-center gap-6">
                                    <span class="material-symbols-outlined text-cyan-800 !text-6xl md:!text-7xl -ml-2 theme-icon">rocket_launch</span>

                                    <h1 class="text-5xl md:text-5xl text-cyan-800 leading-none tracking-[-0.04em] theme-title">تاثیرگذار</h1>
                                </div>
                            </div>

                            <div class="flex flex-col items-center gap-4">
                                <div class="relative size-32">
                                    <svg
                                        class="size-full"
                                        viewBox="0 0 36 36"
                                    >
                                        <path
                                            class="text-white theme-chart-stroke"
                                            d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="4"
                                        ></path>

                                        <path
                                            class="text-cyan-800 theme-chart"
                                            d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-dasharray="85, 100"
                                            stroke-linecap="round"
                                            stroke-width="4"
                                            transform="rotate(-90 18 18)"
                                        ></path>
                                    </svg>

                                    <div class="absolute inset-0 flex flex-col items-center justify-center">
                                        <span class="text-cyan-800 text-3xl font-bold leading-none tracking-tight theme-percentage">85%</span>
                                    </div>
                                </div>

                                <p class="text-text-muted-light text-sm font-medium">درصد غلبه</p>
                            </div>
                        </div>

                        <div class="pt-0">
                            <div class="prose prose-lg max-w-none text-text-muted-light">
                                <p class="text-gray-800 text-base md:text-lg theme-description">
                                    از آنجا که استعداد هماهنگ‌کننده به عنوان ششمین نقطه قوت شما قرار گرفته است، احتمال دارد در جستجوی توافق و اجماع در میان افراد گروه خود برجسته باشید. این قابلیت به شما این امکان را می‌دهد که مناقشات را کاهش دهید و به دنبال زمینه‌های مشترک باشید، که این می‌تواند در هماهنگ‌سازی تیم‌ها و پروژه‌های گروهی به شما کمک کند.
                                </p>

                                <p class="text-xl md:text-2xl font-bold mt-10">موانع و تهدیدها</p>

                                <div class="flex flex-col py-6 gap-4 theme-obstacles">
                                    <div class="flex gap-7 items-center item">
                                        <i class="fa-solid fa-circle-check text-red-700 text-[25px]"></i>

                                        <p class="text-base lg:text-lg text-gray-800 text">
                                            لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپ و با استفاده از طراحان گرافیک است چاپگرها و متون بلکه روزنامه و مجله در ستون و سطرآنچنان که لازم است
                                        </p>
                                    </div>
                                    <div class="flex gap-7 items-center item">
                                        <i class="fa-solid fa-circle-check text-red-700 text-[25px]"></i>

                                        <p class="text-base lg:text-lg text-gray-800 text">
                                            لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپ و با استفاده از طراحان گرافیک است چاپگرها و متون بلکه روزنامه و مجله در ستون و سطرآنچنان که لازم است
                                        </p>
                                    </div>
                                    <div class="flex gap-7 items-center item">
                                        <i class="fa-solid fa-circle-check text-red-700 text-[25px]"></i>

                                        <p class="text-base lg:text-lg text-gray-800 text">
                                            لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپ و با استفاده از طراحان گرافیک است چاپگرها و متون بلکه روزنامه و مجله در ستون و سطرآنچنان که لازم است
                                        </p>
                                    </div>
                                    <div class="flex gap-7 items-center item">
                                        <i class="fa-solid fa-circle-check text-red-700 text-[25px]"></i>

                                        <p class="text-base lg:text-lg text-gray-800 text">
                                            لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپ و با استفاده از طراحان گرافیک است چاپگرها و متون بلکه روزنامه و مجله در ستون و سطرآنچنان که لازم است
                                        </p>
                                    </div>
                                    <div class="flex gap-7 items-center item">
                                        <i class="fa-solid fa-circle-check text-red-700 text-[25px]"></i>

                                        <p class="text-base lg:text-lg text-gray-800 text">
                                            لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپ و با استفاده از طراحان گرافیک است چاپگرها و متون بلکه روزنامه و مجله در ستون و سطرآنچنان که لازم است
                                        </p>
                                    </div>
                                    <div class="flex gap-7 items-center item">
                                        <i class="fa-solid fa-circle-check text-red-700 text-[25px]"></i>

                                        <p class="text-base lg:text-lg text-gray-800 text">
                                            لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپ و با استفاده از طراحان گرافیک است چاپگرها و متون بلکه روزنامه و مجله در ستون و سطرآنچنان که لازم است
                                        </p>
                                    </div>
                                    <div class="flex gap-7 items-center item">
                                        <i class="fa-solid fa-circle-check text-red-700 text-[25px]"></i>

                                        <p class="text-base lg:text-lg text-gray-800 text">
                                            لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپ و با استفاده از طراحان گرافیک است چاپگرها و متون بلکه روزنامه و مجله در ستون و سطرآنچنان که لازم است
                                        </p>
                                    </div>
                                    <div class="flex gap-7 items-center item">
                                        <i class="fa-solid fa-circle-check text-red-700 text-[25px]"></i>

                                        <p class="text-base lg:text-lg text-gray-800 text">
                                            لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپ و با استفاده از طراحان گرافیک است چاپگرها و متون بلکه روزنامه و مجله در ستون و سطرآنچنان که لازم است
                                        </p>
                                    </div>
                                </div>

                                <p class="text-xl md:text-2xl font-bold mt-10">فرصت ها و راهکارها</p>

                                <div class="flex flex-col py-6 gap-4 theme-opportunities">
                                    <div class="flex gap-7 items-center item">
                                        <i class="fa-solid fa-circle-check text-green-700 text-[25px]"></i>

                                        <p class="text-base lg:text-lg text-gray-800 text">
                                            لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپ و با استفاده از طراحان گرافیک است چاپگرها و متون بلکه روزنامه و مجله در ستون و سطرآنچنان که لازم است
                                        </p>
                                    </div>
                                    <div class="flex gap-7 items-center item">
                                        <i class="fa-solid fa-circle-check text-green-700 text-[25px]"></i>

                                        <p class="text-base lg:text-lg text-gray-800 text">
                                            لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپ و با استفاده از طراحان گرافیک است چاپگرها و متون بلکه روزنامه و مجله در ستون و سطرآنچنان که لازم است
                                        </p>
                                    </div>
                                    <div class="flex gap-7 items-center item">
                                        <i class="fa-solid fa-circle-check text-green-700 text-[25px]"></i>

                                        <p class="text-base lg:text-lg text-gray-800 text">
                                            لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپ و با استفاده از طراحان گرافیک است چاپگرها و متون بلکه روزنامه و مجله در ستون و سطرآنچنان که لازم است
                                        </p>
                                    </div>
                                    <div class="flex gap-7 items-center item">
                                        <i class="fa-solid fa-circle-check text-green-700 text-[25px]"></i>

                                        <p class="text-base lg:text-lg text-gray-800 text">
                                            لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپ و با استفاده از طراحان گرافیک است چاپگرها و متون بلکه روزنامه و مجله در ستون و سطرآنچنان که لازم است
                                        </p>
                                    </div>
                                    <div class="flex gap-7 items-center item">
                                        <i class="fa-solid fa-circle-check text-green-700 text-[25px]"></i>

                                        <p class="text-base lg:text-lg text-gray-800 text">
                                            لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپ و با استفاده از طراحان گرافیک است چاپگرها و متون بلکه روزنامه و مجله در ستون و سطرآنچنان که لازم است
                                        </p>
                                    </div>
                                    <div class="flex gap-7 items-center item">
                                        <i class="fa-solid fa-circle-check text-green-700 text-[25px]"></i>

                                        <p class="text-base lg:text-lg text-gray-800 text">
                                            لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپ و با استفاده از طراحان گرافیک است چاپگرها و متون بلکه روزنامه و مجله در ستون و سطرآنچنان که لازم است
                                        </p>
                                    </div>
                                    <div class="flex gap-7 items-center item">
                                        <i class="fa-solid fa-circle-check text-green-700 text-[25px]"></i>

                                        <p class="text-base lg:text-lg text-gray-800 text">
                                            لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپ و با استفاده از طراحان گرافیک است چاپگرها و متون بلکه روزنامه و مجله در ستون و سطرآنچنان که لازم است
                                        </p>
                                    </div>
                                    <div class="flex gap-7 items-center item">
                                        <i class="fa-solid fa-circle-check text-green-700 text-[25px]"></i>

                                        <p class="text-base lg:text-lg text-gray-800 text">
                                            لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپ و با استفاده از طراحان گرافیک است چاپگرها و متون بلکه روزنامه و مجله در ستون و سطرآنچنان که لازم است
                                        </p>
                                    </div>
                                    <div class="flex gap-7 items-center item">
                                        <i class="fa-solid fa-circle-check text-green-700 text-[25px]"></i>

                                        <p class="text-base lg:text-lg text-gray-800 text">
                                            لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپ و با استفاده از طراحان گرافیک است چاپگرها و متون بلکه روزنامه و مجله در ستون و سطرآنچنان که لازم است
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="w-full mt-20 h-[2px] bg-gray-300 separator"></div>

                    
                    <div class="theme-section">
                        <div class="grid grid-cols:2 mt-20 sm:grid-cols-3 md:grid-cols-2 lg:grid-cols-3 gap-6 p-4 cards">
                            <div class="flex flex-1 flex-col justify-center rounded-lg p-5 shadow-md bg-white h-fit domain-card">
                                <p class="text-cyan-800/80 text-sm font-medium title">دسته استعدادی</p>

                                <div class="my-2 h-px sep"></div>

                                <p class="text-cyan-900 text-xl xl:text-2xl font-bold text">{THEME_DOMAIN}</p>
                            </div>
                            <div class="flex flex-1 flex-col justify-center rounded-lg p-5 shadow-md bg-white h-fit english-name-card">
                                <p class="text-cyan-800/80 text-sm font-medium title">نام انگلیسی</p>

                                <div class="my-2 h-px sep"></div>

                                <p class="text-cyan-900 text-xl xl:text-2xl font-bold text">{ENGLISH_NAME}</p>
                            </div>
                            <div class="flex flex-1 flex-col justify-center rounded-lg p-5 shadow-md bg-white h-fit rank-card ">
                                <p class="text-cyan-800/80 text-sm font-medium title">رتبه استعداد</p>

                                <div class="my-2 h-px sep"></div>

                                <p class="text-cyan-900 text-xl xl:text-2xl font-bold text">{THEME_RANK}</p>
                            </div>
                        </div>

                        <div class="flex flex-col md:flex-row items-center gap-8 md:gap-12 p-8">
                            <div class="flex flex-col items-center text-center md:items-start md:text-left gap-4 flex-1">
                                <div class="flex items-center gap-6">
                                    <span class="material-symbols-outlined text-cyan-800 !text-6xl md:!text-7xl -ml-2 theme-icon">rocket_launch</span>

                                    <h1 class="text-5xl md:text-5xl text-cyan-800 leading-none tracking-[-0.04em] theme-title">تاثیرگذار</h1>
                                </div>
                            </div>

                            <div class="flex flex-col items-center gap-4">
                                <div class="relative size-32">
                                    <svg
                                        class="size-full"
                                        viewBox="0 0 36 36"
                                    >
                                        <path
                                            class="text-white theme-chart-stroke"
                                            d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="4"
                                        ></path>

                                        <path
                                            class="text-cyan-800 theme-chart"
                                            d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-dasharray="85, 100"
                                            stroke-linecap="round"
                                            stroke-width="4"
                                            transform="rotate(-90 18 18)"
                                        ></path>
                                    </svg>

                                    <div class="absolute inset-0 flex flex-col items-center justify-center">
                                        <span class="text-cyan-800 text-3xl font-bold leading-none tracking-tight theme-percentage">85%</span>
                                    </div>
                                </div>

                                <p class="text-text-muted-light text-sm font-medium">درصد غلبه</p>
                            </div>
                        </div>

                        <div class="pt-0">
                            <div class="prose prose-lg max-w-none text-text-muted-light">
                                <p class="text-gray-800 text-base md:text-lg theme-description">
                                    از آنجا که استعداد هماهنگ‌کننده به عنوان ششمین نقطه قوت شما قرار گرفته است، احتمال دارد در جستجوی توافق و اجماع در میان افراد گروه خود برجسته باشید. این قابلیت به شما این امکان را می‌دهد که مناقشات را کاهش دهید و به دنبال زمینه‌های مشترک باشید، که این می‌تواند در هماهنگ‌سازی تیم‌ها و پروژه‌های گروهی به شما کمک کند.
                                </p>

                                <p class="text-xl md:text-2xl font-bold mt-10">موانع و تهدیدها</p>

                                <div class="flex flex-col py-6 gap-4 theme-obstacles">
                                    <div class="flex gap-7 items-center item">
                                        <i class="fa-solid fa-circle-check text-red-700 text-[25px]"></i>

                                        <p class="text-base lg:text-lg text-gray-800 text">
                                            لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپ و با استفاده از طراحان گرافیک است چاپگرها و متون بلکه روزنامه و مجله در ستون و سطرآنچنان که لازم است
                                        </p>
                                    </div>
                                    <div class="flex gap-7 items-center item">
                                        <i class="fa-solid fa-circle-check text-red-700 text-[25px]"></i>

                                        <p class="text-base lg:text-lg text-gray-800 text">
                                            لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپ و با استفاده از طراحان گرافیک است چاپگرها و متون بلکه روزنامه و مجله در ستون و سطرآنچنان که لازم است
                                        </p>
                                    </div>
                                    <div class="flex gap-7 items-center item">
                                        <i class="fa-solid fa-circle-check text-red-700 text-[25px]"></i>

                                        <p class="text-base lg:text-lg text-gray-800 text">
                                            لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپ و با استفاده از طراحان گرافیک است چاپگرها و متون بلکه روزنامه و مجله در ستون و سطرآنچنان که لازم است
                                        </p>
                                    </div>
                                    <div class="flex gap-7 items-center item">
                                        <i class="fa-solid fa-circle-check text-red-700 text-[25px]"></i>

                                        <p class="text-base lg:text-lg text-gray-800 text">
                                            لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپ و با استفاده از طراحان گرافیک است چاپگرها و متون بلکه روزنامه و مجله در ستون و سطرآنچنان که لازم است
                                        </p>
                                    </div>
                                    <div class="flex gap-7 items-center item">
                                        <i class="fa-solid fa-circle-check text-red-700 text-[25px]"></i>

                                        <p class="text-base lg:text-lg text-gray-800 text">
                                            لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپ و با استفاده از طراحان گرافیک است چاپگرها و متون بلکه روزنامه و مجله در ستون و سطرآنچنان که لازم است
                                        </p>
                                    </div>
                                    <div class="flex gap-7 items-center item">
                                        <i class="fa-solid fa-circle-check text-red-700 text-[25px]"></i>

                                        <p class="text-base lg:text-lg text-gray-800 text">
                                            لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپ و با استفاده از طراحان گرافیک است چاپگرها و متون بلکه روزنامه و مجله در ستون و سطرآنچنان که لازم است
                                        </p>
                                    </div>
                                    <div class="flex gap-7 items-center item">
                                        <i class="fa-solid fa-circle-check text-red-700 text-[25px]"></i>

                                        <p class="text-base lg:text-lg text-gray-800 text">
                                            لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپ و با استفاده از طراحان گرافیک است چاپگرها و متون بلکه روزنامه و مجله در ستون و سطرآنچنان که لازم است
                                        </p>
                                    </div>
                                    <div class="flex gap-7 items-center item">
                                        <i class="fa-solid fa-circle-check text-red-700 text-[25px]"></i>

                                        <p class="text-base lg:text-lg text-gray-800 text">
                                            لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپ و با استفاده از طراحان گرافیک است چاپگرها و متون بلکه روزنامه و مجله در ستون و سطرآنچنان که لازم است
                                        </p>
                                    </div>
                                </div>

                                <p class="text-xl md:text-2xl font-bold mt-10">فرصت ها و راهکارها</p>

                                <div class="flex flex-col py-6 gap-4 theme-opportunities">
                                    <div class="flex gap-7 items-center item">
                                        <i class="fa-solid fa-circle-check text-green-700 text-[25px]"></i>

                                        <p class="text-base lg:text-lg text-gray-800 text">
                                            لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپ و با استفاده از طراحان گرافیک است چاپگرها و متون بلکه روزنامه و مجله در ستون و سطرآنچنان که لازم است
                                        </p>
                                    </div>
                                    <div class="flex gap-7 items-center item">
                                        <i class="fa-solid fa-circle-check text-green-700 text-[25px]"></i>

                                        <p class="text-base lg:text-lg text-gray-800 text">
                                            لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپ و با استفاده از طراحان گرافیک است چاپگرها و متون بلکه روزنامه و مجله در ستون و سطرآنچنان که لازم است
                                        </p>
                                    </div>
                                    <div class="flex gap-7 items-center item">
                                        <i class="fa-solid fa-circle-check text-green-700 text-[25px]"></i>

                                        <p class="text-base lg:text-lg text-gray-800 text">
                                            لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپ و با استفاده از طراحان گرافیک است چاپگرها و متون بلکه روزنامه و مجله در ستون و سطرآنچنان که لازم است
                                        </p>
                                    </div>
                                    <div class="flex gap-7 items-center item">
                                        <i class="fa-solid fa-circle-check text-green-700 text-[25px]"></i>

                                        <p class="text-base lg:text-lg text-gray-800 text">
                                            لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپ و با استفاده از طراحان گرافیک است چاپگرها و متون بلکه روزنامه و مجله در ستون و سطرآنچنان که لازم است
                                        </p>
                                    </div>
                                    <div class="flex gap-7 items-center item">
                                        <i class="fa-solid fa-circle-check text-green-700 text-[25px]"></i>

                                        <p class="text-base lg:text-lg text-gray-800 text">
                                            لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپ و با استفاده از طراحان گرافیک است چاپگرها و متون بلکه روزنامه و مجله در ستون و سطرآنچنان که لازم است
                                        </p>
                                    </div>
                                    <div class="flex gap-7 items-center item">
                                        <i class="fa-solid fa-circle-check text-green-700 text-[25px]"></i>

                                        <p class="text-base lg:text-lg text-gray-800 text">
                                            لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپ و با استفاده از طراحان گرافیک است چاپگرها و متون بلکه روزنامه و مجله در ستون و سطرآنچنان که لازم است
                                        </p>
                                    </div>
                                    <div class="flex gap-7 items-center item">
                                        <i class="fa-solid fa-circle-check text-green-700 text-[25px]"></i>

                                        <p class="text-base lg:text-lg text-gray-800 text">
                                            لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپ و با استفاده از طراحان گرافیک است چاپگرها و متون بلکه روزنامه و مجله در ستون و سطرآنچنان که لازم است
                                        </p>
                                    </div>
                                    <div class="flex gap-7 items-center item">
                                        <i class="fa-solid fa-circle-check text-green-700 text-[25px]"></i>

                                        <p class="text-base lg:text-lg text-gray-800 text">
                                            لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپ و با استفاده از طراحان گرافیک است چاپگرها و متون بلکه روزنامه و مجله در ستون و سطرآنچنان که لازم است
                                        </p>
                                    </div>
                                    <div class="flex gap-7 items-center item">
                                        <i class="fa-solid fa-circle-check text-green-700 text-[25px]"></i>

                                        <p class="text-base lg:text-lg text-gray-800 text">
                                            لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپ و با استفاده از طراحان گرافیک است چاپگرها و متون بلکه روزنامه و مجله در ستون و سطرآنچنان که لازم است
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="w-full mt-20 h-[2px] bg-gray-300 separator"></div>

                    <div class="theme-section">
                        <div class="grid grid-cols:2 mt-20 sm:grid-cols-3 md:grid-cols-2 lg:grid-cols-3 gap-6 p-4 cards">
                            <div class="flex flex-1 flex-col justify-center rounded-lg p-5 shadow-md bg-white h-fit domain-card">
                                <p class="text-cyan-800/80 text-sm font-medium title">دسته استعدادی</p>

                                <div class="my-2 h-px sep"></div>

                                <p class="text-cyan-900 text-xl xl:text-2xl font-bold text">{THEME_DOMAIN}</p>
                            </div>
                            <div class="flex flex-1 flex-col justify-center rounded-lg p-5 shadow-md bg-white h-fit english-name-card">
                                <p class="text-cyan-800/80 text-sm font-medium title">نام انگلیسی</p>

                                <div class="my-2 h-px sep"></div>

                                <p class="text-cyan-900 text-xl xl:text-2xl font-bold text">{ENGLISH_NAME}</p>
                            </div>
                            <div class="flex flex-1 flex-col justify-center rounded-lg p-5 shadow-md bg-white h-fit rank-card ">
                                <p class="text-cyan-800/80 text-sm font-medium title">رتبه استعداد</p>

                                <div class="my-2 h-px sep"></div>

                                <p class="text-cyan-900 text-xl xl:text-2xl font-bold text">{THEME_RANK}</p>
                            </div>
                        </div>

                        <div class="flex flex-col md:flex-row items-center gap-8 md:gap-12 p-8">
                            <div class="flex flex-col items-center text-center md:items-start md:text-left gap-4 flex-1">
                                <div class="flex items-center gap-6">
                                    <span class="material-symbols-outlined text-cyan-800 !text-6xl md:!text-7xl -ml-2 theme-icon">rocket_launch</span>

                                    <h1 class="text-5xl md:text-5xl text-cyan-800 leading-none tracking-[-0.04em] theme-title">تاثیرگذار</h1>
                                </div>
                            </div>

                            <div class="flex flex-col items-center gap-4">
                                <div class="relative size-32">
                                    <svg
                                        class="size-full"
                                        viewBox="0 0 36 36"
                                    >
                                        <path
                                            class="text-white theme-chart-stroke"
                                            d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="4"
                                        ></path>

                                        <path
                                            class="text-cyan-800 theme-chart"
                                            d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-dasharray="85, 100"
                                            stroke-linecap="round"
                                            stroke-width="4"
                                            transform="rotate(-90 18 18)"
                                        ></path>
                                    </svg>

                                    <div class="absolute inset-0 flex flex-col items-center justify-center">
                                        <span class="text-cyan-800 text-3xl font-bold leading-none tracking-tight theme-percentage">85%</span>
                                    </div>
                                </div>

                                <p class="text-text-muted-light text-sm font-medium">درصد غلبه</p>
                            </div>
                        </div>

                        <div class="pt-0">
                            <div class="prose prose-lg max-w-none text-text-muted-light">
                                <p class="text-gray-800 text-base md:text-lg theme-description">
                                    از آنجا که استعداد هماهنگ‌کننده به عنوان ششمین نقطه قوت شما قرار گرفته است، احتمال دارد در جستجوی توافق و اجماع در میان افراد گروه خود برجسته باشید. این قابلیت به شما این امکان را می‌دهد که مناقشات را کاهش دهید و به دنبال زمینه‌های مشترک باشید، که این می‌تواند در هماهنگ‌سازی تیم‌ها و پروژه‌های گروهی به شما کمک کند.
                                </p>

                                <p class="text-xl md:text-2xl font-bold mt-10">موانع و تهدیدها</p>

                                <div class="flex flex-col py-6 gap-4 theme-obstacles">
                                    <div class="flex gap-7 items-center item">
                                        <i class="fa-solid fa-circle-check text-red-700 text-[25px]"></i>

                                        <p class="text-base lg:text-lg text-gray-800 text">
                                            لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپ و با استفاده از طراحان گرافیک است چاپگرها و متون بلکه روزنامه و مجله در ستون و سطرآنچنان که لازم است
                                        </p>
                                    </div>
                                    <div class="flex gap-7 items-center item">
                                        <i class="fa-solid fa-circle-check text-red-700 text-[25px]"></i>

                                        <p class="text-base lg:text-lg text-gray-800 text">
                                            لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپ و با استفاده از طراحان گرافیک است چاپگرها و متون بلکه روزنامه و مجله در ستون و سطرآنچنان که لازم است
                                        </p>
                                    </div>
                                    <div class="flex gap-7 items-center item">
                                        <i class="fa-solid fa-circle-check text-red-700 text-[25px]"></i>

                                        <p class="text-base lg:text-lg text-gray-800 text">
                                            لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپ و با استفاده از طراحان گرافیک است چاپگرها و متون بلکه روزنامه و مجله در ستون و سطرآنچنان که لازم است
                                        </p>
                                    </div>
                                    <div class="flex gap-7 items-center item">
                                        <i class="fa-solid fa-circle-check text-red-700 text-[25px]"></i>

                                        <p class="text-base lg:text-lg text-gray-800 text">
                                            لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپ و با استفاده از طراحان گرافیک است چاپگرها و متون بلکه روزنامه و مجله در ستون و سطرآنچنان که لازم است
                                        </p>
                                    </div>
                                    <div class="flex gap-7 items-center item">
                                        <i class="fa-solid fa-circle-check text-red-700 text-[25px]"></i>

                                        <p class="text-base lg:text-lg text-gray-800 text">
                                            لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپ و با استفاده از طراحان گرافیک است چاپگرها و متون بلکه روزنامه و مجله در ستون و سطرآنچنان که لازم است
                                        </p>
                                    </div>
                                    <div class="flex gap-7 items-center item">
                                        <i class="fa-solid fa-circle-check text-red-700 text-[25px]"></i>

                                        <p class="text-base lg:text-lg text-gray-800 text">
                                            لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپ و با استفاده از طراحان گرافیک است چاپگرها و متون بلکه روزنامه و مجله در ستون و سطرآنچنان که لازم است
                                        </p>
                                    </div>
                                    <div class="flex gap-7 items-center item">
                                        <i class="fa-solid fa-circle-check text-red-700 text-[25px]"></i>

                                        <p class="text-base lg:text-lg text-gray-800 text">
                                            لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپ و با استفاده از طراحان گرافیک است چاپگرها و متون بلکه روزنامه و مجله در ستون و سطرآنچنان که لازم است
                                        </p>
                                    </div>
                                    <div class="flex gap-7 items-center item">
                                        <i class="fa-solid fa-circle-check text-red-700 text-[25px]"></i>

                                        <p class="text-base lg:text-lg text-gray-800 text">
                                            لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپ و با استفاده از طراحان گرافیک است چاپگرها و متون بلکه روزنامه و مجله در ستون و سطرآنچنان که لازم است
                                        </p>
                                    </div>
                                </div>

                                <p class="text-xl md:text-2xl font-bold mt-10">فرصت ها و راهکارها</p>

                                <div class="flex flex-col py-6 gap-4 theme-opportunities">
                                    <div class="flex gap-7 items-center item">
                                        <i class="fa-solid fa-circle-check text-green-700 text-[25px]"></i>

                                        <p class="text-base lg:text-lg text-gray-800 text">
                                            لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپ و با استفاده از طراحان گرافیک است چاپگرها و متون بلکه روزنامه و مجله در ستون و سطرآنچنان که لازم است
                                        </p>
                                    </div>
                                    <div class="flex gap-7 items-center item">
                                        <i class="fa-solid fa-circle-check text-green-700 text-[25px]"></i>

                                        <p class="text-base lg:text-lg text-gray-800 text">
                                            لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپ و با استفاده از طراحان گرافیک است چاپگرها و متون بلکه روزنامه و مجله در ستون و سطرآنچنان که لازم است
                                        </p>
                                    </div>
                                    <div class="flex gap-7 items-center item">
                                        <i class="fa-solid fa-circle-check text-green-700 text-[25px]"></i>

                                        <p class="text-base lg:text-lg text-gray-800 text">
                                            لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپ و با استفاده از طراحان گرافیک است چاپگرها و متون بلکه روزنامه و مجله در ستون و سطرآنچنان که لازم است
                                        </p>
                                    </div>
                                    <div class="flex gap-7 items-center item">
                                        <i class="fa-solid fa-circle-check text-green-700 text-[25px]"></i>

                                        <p class="text-base lg:text-lg text-gray-800 text">
                                            لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپ و با استفاده از طراحان گرافیک است چاپگرها و متون بلکه روزنامه و مجله در ستون و سطرآنچنان که لازم است
                                        </p>
                                    </div>
                                    <div class="flex gap-7 items-center item">
                                        <i class="fa-solid fa-circle-check text-green-700 text-[25px]"></i>

                                        <p class="text-base lg:text-lg text-gray-800 text">
                                            لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپ و با استفاده از طراحان گرافیک است چاپگرها و متون بلکه روزنامه و مجله در ستون و سطرآنچنان که لازم است
                                        </p>
                                    </div>
                                    <div class="flex gap-7 items-center item">
                                        <i class="fa-solid fa-circle-check text-green-700 text-[25px]"></i>

                                        <p class="text-base lg:text-lg text-gray-800 text">
                                            لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپ و با استفاده از طراحان گرافیک است چاپگرها و متون بلکه روزنامه و مجله در ستون و سطرآنچنان که لازم است
                                        </p>
                                    </div>
                                    <div class="flex gap-7 items-center item">
                                        <i class="fa-solid fa-circle-check text-green-700 text-[25px]"></i>

                                        <p class="text-base lg:text-lg text-gray-800 text">
                                            لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپ و با استفاده از طراحان گرافیک است چاپگرها و متون بلکه روزنامه و مجله در ستون و سطرآنچنان که لازم است
                                        </p>
                                    </div>
                                    <div class="flex gap-7 items-center item">
                                        <i class="fa-solid fa-circle-check text-green-700 text-[25px]"></i>

                                        <p class="text-base lg:text-lg text-gray-800 text">
                                            لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپ و با استفاده از طراحان گرافیک است چاپگرها و متون بلکه روزنامه و مجله در ستون و سطرآنچنان که لازم است
                                        </p>
                                    </div>
                                    <div class="flex gap-7 items-center item">
                                        <i class="fa-solid fa-circle-check text-green-700 text-[25px]"></i>

                                        <p class="text-base lg:text-lg text-gray-800 text">
                                            لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپ و با استفاده از طراحان گرافیک است چاپگرها و متون بلکه روزنامه و مجله در ستون و سطرآنچنان که لازم است
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="w-full mt-20 h-[2px] bg-gray-300 separator"></div>

                    <div class="theme-section">
                        <div class="grid grid-cols:2 mt-20 sm:grid-cols-3 md:grid-cols-2 lg:grid-cols-3 gap-6 p-4 cards">
                            <div class="flex flex-1 flex-col justify-center rounded-lg p-5 shadow-md bg-white h-fit domain-card">
                                <p class="text-cyan-800/80 text-sm font-medium title">دسته استعدادی</p>

                                <div class="my-2 h-px sep"></div>

                                <p class="text-cyan-900 text-xl xl:text-2xl font-bold text">{THEME_DOMAIN}</p>
                            </div>
                            <div class="flex flex-1 flex-col justify-center rounded-lg p-5 shadow-md bg-white h-fit english-name-card">
                                <p class="text-cyan-800/80 text-sm font-medium title">نام انگلیسی</p>

                                <div class="my-2 h-px sep"></div>

                                <p class="text-cyan-900 text-xl xl:text-2xl font-bold text">{ENGLISH_NAME}</p>
                            </div>
                            <div class="flex flex-1 flex-col justify-center rounded-lg p-5 shadow-md bg-white h-fit rank-card ">
                                <p class="text-cyan-800/80 text-sm font-medium title">رتبه استعداد</p>

                                <div class="my-2 h-px sep"></div>

                                <p class="text-cyan-900 text-xl xl:text-2xl font-bold text">{THEME_RANK}</p>
                            </div>
                        </div>

                        <div class="flex flex-col md:flex-row items-center gap-8 md:gap-12 p-8">
                            <div class="flex flex-col items-center text-center md:items-start md:text-left gap-4 flex-1">
                                <div class="flex items-center gap-6">
                                    <span class="material-symbols-outlined text-cyan-800 !text-6xl md:!text-7xl -ml-2 theme-icon">rocket_launch</span>

                                    <h1 class="text-5xl md:text-5xl text-cyan-800 leading-none tracking-[-0.04em] theme-title">تاثیرگذار</h1>
                                </div>
                            </div>

                            <div class="flex flex-col items-center gap-4">
                                <div class="relative size-32">
                                    <svg
                                        class="size-full"
                                        viewBox="0 0 36 36"
                                    >
                                        <path
                                            class="text-white theme-chart-stroke"
                                            d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="4"
                                        ></path>

                                        <path
                                            class="text-cyan-800 theme-chart"
                                            d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-dasharray="85, 100"
                                            stroke-linecap="round"
                                            stroke-width="4"
                                            transform="rotate(-90 18 18)"
                                        ></path>
                                    </svg>

                                    <div class="absolute inset-0 flex flex-col items-center justify-center">
                                        <span class="text-cyan-800 text-3xl font-bold leading-none tracking-tight theme-percentage">85%</span>
                                    </div>
                                </div>

                                <p class="text-text-muted-light text-sm font-medium">درصد غلبه</p>
                            </div>
                        </div>

                        <div class="pt-0">
                            <div class="prose prose-lg max-w-none text-text-muted-light">
                                <p class="text-gray-800 text-base md:text-lg theme-description">
                                    از آنجا که استعداد هماهنگ‌کننده به عنوان ششمین نقطه قوت شما قرار گرفته است، احتمال دارد در جستجوی توافق و اجماع در میان افراد گروه خود برجسته باشید. این قابلیت به شما این امکان را می‌دهد که مناقشات را کاهش دهید و به دنبال زمینه‌های مشترک باشید، که این می‌تواند در هماهنگ‌سازی تیم‌ها و پروژه‌های گروهی به شما کمک کند.
                                </p>

                                <p class="text-xl md:text-2xl font-bold mt-10">موانع و تهدیدها</p>

                                <div class="flex flex-col py-6 gap-4 theme-obstacles">
                                    <div class="flex gap-7 items-center item">
                                        <i class="fa-solid fa-circle-check text-red-700 text-[25px]"></i>

                                        <p class="text-base lg:text-lg text-gray-800 text">
                                            لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپ و با استفاده از طراحان گرافیک است چاپگرها و متون بلکه روزنامه و مجله در ستون و سطرآنچنان که لازم است
                                        </p>
                                    </div>
                                    <div class="flex gap-7 items-center item">
                                        <i class="fa-solid fa-circle-check text-red-700 text-[25px]"></i>

                                        <p class="text-base lg:text-lg text-gray-800 text">
                                            لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپ و با استفاده از طراحان گرافیک است چاپگرها و متون بلکه روزنامه و مجله در ستون و سطرآنچنان که لازم است
                                        </p>
                                    </div>
                                    <div class="flex gap-7 items-center item">
                                        <i class="fa-solid fa-circle-check text-red-700 text-[25px]"></i>

                                        <p class="text-base lg:text-lg text-gray-800 text">
                                            لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپ و با استفاده از طراحان گرافیک است چاپگرها و متون بلکه روزنامه و مجله در ستون و سطرآنچنان که لازم است
                                        </p>
                                    </div>
                                    <div class="flex gap-7 items-center item">
                                        <i class="fa-solid fa-circle-check text-red-700 text-[25px]"></i>

                                        <p class="text-base lg:text-lg text-gray-800 text">
                                            لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپ و با استفاده از طراحان گرافیک است چاپگرها و متون بلکه روزنامه و مجله در ستون و سطرآنچنان که لازم است
                                        </p>
                                    </div>
                                    <div class="flex gap-7 items-center item">
                                        <i class="fa-solid fa-circle-check text-red-700 text-[25px]"></i>

                                        <p class="text-base lg:text-lg text-gray-800 text">
                                            لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپ و با استفاده از طراحان گرافیک است چاپگرها و متون بلکه روزنامه و مجله در ستون و سطرآنچنان که لازم است
                                        </p>
                                    </div>
                                    <div class="flex gap-7 items-center item">
                                        <i class="fa-solid fa-circle-check text-red-700 text-[25px]"></i>

                                        <p class="text-base lg:text-lg text-gray-800 text">
                                            لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپ و با استفاده از طراحان گرافیک است چاپگرها و متون بلکه روزنامه و مجله در ستون و سطرآنچنان که لازم است
                                        </p>
                                    </div>
                                    <div class="flex gap-7 items-center item">
                                        <i class="fa-solid fa-circle-check text-red-700 text-[25px]"></i>

                                        <p class="text-base lg:text-lg text-gray-800 text">
                                            لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپ و با استفاده از طراحان گرافیک است چاپگرها و متون بلکه روزنامه و مجله در ستون و سطرآنچنان که لازم است
                                        </p>
                                    </div>
                                    <div class="flex gap-7 items-center item">
                                        <i class="fa-solid fa-circle-check text-red-700 text-[25px]"></i>

                                        <p class="text-base lg:text-lg text-gray-800 text">
                                            لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپ و با استفاده از طراحان گرافیک است چاپگرها و متون بلکه روزنامه و مجله در ستون و سطرآنچنان که لازم است
                                        </p>
                                    </div>
                                </div>

                                <p class="text-xl md:text-2xl font-bold mt-10">فرصت ها و راهکارها</p>

                                <div class="flex flex-col py-6 gap-4 theme-opportunities">
                                    <div class="flex gap-7 items-center item">
                                        <i class="fa-solid fa-circle-check text-green-700 text-[25px]"></i>

                                        <p class="text-base lg:text-lg text-gray-800 text">
                                            لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپ و با استفاده از طراحان گرافیک است چاپگرها و متون بلکه روزنامه و مجله در ستون و سطرآنچنان که لازم است
                                        </p>
                                    </div>
                                    <div class="flex gap-7 items-center item">
                                        <i class="fa-solid fa-circle-check text-green-700 text-[25px]"></i>

                                        <p class="text-base lg:text-lg text-gray-800 text">
                                            لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپ و با استفاده از طراحان گرافیک است چاپگرها و متون بلکه روزنامه و مجله در ستون و سطرآنچنان که لازم است
                                        </p>
                                    </div>
                                    <div class="flex gap-7 items-center item">
                                        <i class="fa-solid fa-circle-check text-green-700 text-[25px]"></i>

                                        <p class="text-base lg:text-lg text-gray-800 text">
                                            لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپ و با استفاده از طراحان گرافیک است چاپگرها و متون بلکه روزنامه و مجله در ستون و سطرآنچنان که لازم است
                                        </p>
                                    </div>
                                    <div class="flex gap-7 items-center item">
                                        <i class="fa-solid fa-circle-check text-green-700 text-[25px]"></i>

                                        <p class="text-base lg:text-lg text-gray-800 text">
                                            لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپ و با استفاده از طراحان گرافیک است چاپگرها و متون بلکه روزنامه و مجله در ستون و سطرآنچنان که لازم است
                                        </p>
                                    </div>
                                    <div class="flex gap-7 items-center item">
                                        <i class="fa-solid fa-circle-check text-green-700 text-[25px]"></i>

                                        <p class="text-base lg:text-lg text-gray-800 text">
                                            لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپ و با استفاده از طراحان گرافیک است چاپگرها و متون بلکه روزنامه و مجله در ستون و سطرآنچنان که لازم است
                                        </p>
                                    </div>
                                    <div class="flex gap-7 items-center item">
                                        <i class="fa-solid fa-circle-check text-green-700 text-[25px]"></i>

                                        <p class="text-base lg:text-lg text-gray-800 text">
                                            لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپ و با استفاده از طراحان گرافیک است چاپگرها و متون بلکه روزنامه و مجله در ستون و سطرآنچنان که لازم است
                                        </p>
                                    </div>
                                    <div class="flex gap-7 items-center item">
                                        <i class="fa-solid fa-circle-check text-green-700 text-[25px]"></i>

                                        <p class="text-base lg:text-lg text-gray-800 text">
                                            لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپ و با استفاده از طراحان گرافیک است چاپگرها و متون بلکه روزنامه و مجله در ستون و سطرآنچنان که لازم است
                                        </p>
                                    </div>
                                    <div class="flex gap-7 items-center item">
                                        <i class="fa-solid fa-circle-check text-green-700 text-[25px]"></i>

                                        <p class="text-base lg:text-lg text-gray-800 text">
                                            لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپ و با استفاده از طراحان گرافیک است چاپگرها و متون بلکه روزنامه و مجله در ستون و سطرآنچنان که لازم است
                                        </p>
                                    </div>
                                    <div class="flex gap-7 items-center item">
                                        <i class="fa-solid fa-circle-check text-green-700 text-[25px]"></i>

                                        <p class="text-base lg:text-lg text-gray-800 text">
                                            لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپ و با استفاده از طراحان گرافیک است چاپگرها و متون بلکه روزنامه و مجله در ستون و سطرآنچنان که لازم است
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="w-full mt-20 h-[2px] bg-gray-300 separator"></div>

                    <div class="theme-section">
                        <div class="grid grid-cols:2 mt-20 sm:grid-cols-3 md:grid-cols-2 lg:grid-cols-3 gap-6 p-4 cards">
                            <div class="flex flex-1 flex-col justify-center rounded-lg p-5 shadow-md bg-white h-fit domain-card">
                                <p class="text-cyan-800/80 text-sm font-medium title">دسته استعدادی</p>

                                <div class="my-2 h-px sep"></div>

                                <p class="text-cyan-900 text-xl xl:text-2xl font-bold text">{THEME_DOMAIN}</p>
                            </div>
                            <div class="flex flex-1 flex-col justify-center rounded-lg p-5 shadow-md bg-white h-fit english-name-card">
                                <p class="text-cyan-800/80 text-sm font-medium title">نام انگلیسی</p>

                                <div class="my-2 h-px sep"></div>

                                <p class="text-cyan-900 text-xl xl:text-2xl font-bold text">{ENGLISH_NAME}</p>
                            </div>
                            <div class="flex flex-1 flex-col justify-center rounded-lg p-5 shadow-md bg-white h-fit rank-card ">
                                <p class="text-cyan-800/80 text-sm font-medium title">رتبه استعداد</p>

                                <div class="my-2 h-px sep"></div>

                                <p class="text-cyan-900 text-xl xl:text-2xl font-bold text">{THEME_RANK}</p>
                            </div>
                        </div>

                        <div class="flex flex-col md:flex-row items-center gap-8 md:gap-12 p-8">
                            <div class="flex flex-col items-center text-center md:items-start md:text-left gap-4 flex-1">
                                <div class="flex items-center gap-6">
                                    <span class="material-symbols-outlined text-cyan-800 !text-6xl md:!text-7xl -ml-2 theme-icon">rocket_launch</span>

                                    <h1 class="text-5xl md:text-5xl text-cyan-800 leading-none tracking-[-0.04em] theme-title">تاثیرگذار</h1>
                                </div>
                            </div>

                            <div class="flex flex-col items-center gap-4">
                                <div class="relative size-32">
                                    <svg
                                        class="size-full"
                                        viewBox="0 0 36 36"
                                    >
                                        <path
                                            class="text-white theme-chart-stroke"
                                            d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="4"
                                        ></path>

                                        <path
                                            class="text-cyan-800 theme-chart"
                                            d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-dasharray="85, 100"
                                            stroke-linecap="round"
                                            stroke-width="4"
                                            transform="rotate(-90 18 18)"
                                        ></path>
                                    </svg>

                                    <div class="absolute inset-0 flex flex-col items-center justify-center">
                                        <span class="text-cyan-800 text-3xl font-bold leading-none tracking-tight theme-percentage">85%</span>
                                    </div>
                                </div>

                                <p class="text-text-muted-light text-sm font-medium">درصد غلبه</p>
                            </div>
                        </div>

                        <div class="pt-0">
                            <div class="prose prose-lg max-w-none text-text-muted-light">
                                <p class="text-gray-800 text-base md:text-lg theme-description">
                                    از آنجا که استعداد هماهنگ‌کننده به عنوان ششمین نقطه قوت شما قرار گرفته است، احتمال دارد در جستجوی توافق و اجماع در میان افراد گروه خود برجسته باشید. این قابلیت به شما این امکان را می‌دهد که مناقشات را کاهش دهید و به دنبال زمینه‌های مشترک باشید، که این می‌تواند در هماهنگ‌سازی تیم‌ها و پروژه‌های گروهی به شما کمک کند.
                                </p>

                                <p class="text-xl md:text-2xl font-bold mt-10">موانع و تهدیدها</p>

                                <div class="flex flex-col py-6 gap-4 theme-obstacles">
                                    <div class="flex gap-7 items-center item">
                                        <i class="fa-solid fa-circle-check text-red-700 text-[25px]"></i>

                                        <p class="text-base lg:text-lg text-gray-800 text">
                                            لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپ و با استفاده از طراحان گرافیک است چاپگرها و متون بلکه روزنامه و مجله در ستون و سطرآنچنان که لازم است
                                        </p>
                                    </div>
                                    <div class="flex gap-7 items-center item">
                                        <i class="fa-solid fa-circle-check text-red-700 text-[25px]"></i>

                                        <p class="text-base lg:text-lg text-gray-800 text">
                                            لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپ و با استفاده از طراحان گرافیک است چاپگرها و متون بلکه روزنامه و مجله در ستون و سطرآنچنان که لازم است
                                        </p>
                                    </div>
                                    <div class="flex gap-7 items-center item">
                                        <i class="fa-solid fa-circle-check text-red-700 text-[25px]"></i>

                                        <p class="text-base lg:text-lg text-gray-800 text">
                                            لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپ و با استفاده از طراحان گرافیک است چاپگرها و متون بلکه روزنامه و مجله در ستون و سطرآنچنان که لازم است
                                        </p>
                                    </div>
                                    <div class="flex gap-7 items-center item">
                                        <i class="fa-solid fa-circle-check text-red-700 text-[25px]"></i>

                                        <p class="text-base lg:text-lg text-gray-800 text">
                                            لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپ و با استفاده از طراحان گرافیک است چاپگرها و متون بلکه روزنامه و مجله در ستون و سطرآنچنان که لازم است
                                        </p>
                                    </div>
                                    <div class="flex gap-7 items-center item">
                                        <i class="fa-solid fa-circle-check text-red-700 text-[25px]"></i>

                                        <p class="text-base lg:text-lg text-gray-800 text">
                                            لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپ و با استفاده از طراحان گرافیک است چاپگرها و متون بلکه روزنامه و مجله در ستون و سطرآنچنان که لازم است
                                        </p>
                                    </div>
                                    <div class="flex gap-7 items-center item">
                                        <i class="fa-solid fa-circle-check text-red-700 text-[25px]"></i>

                                        <p class="text-base lg:text-lg text-gray-800 text">
                                            لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپ و با استفاده از طراحان گرافیک است چاپگرها و متون بلکه روزنامه و مجله در ستون و سطرآنچنان که لازم است
                                        </p>
                                    </div>
                                    <div class="flex gap-7 items-center item">
                                        <i class="fa-solid fa-circle-check text-red-700 text-[25px]"></i>

                                        <p class="text-base lg:text-lg text-gray-800 text">
                                            لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپ و با استفاده از طراحان گرافیک است چاپگرها و متون بلکه روزنامه و مجله در ستون و سطرآنچنان که لازم است
                                        </p>
                                    </div>
                                    <div class="flex gap-7 items-center item">
                                        <i class="fa-solid fa-circle-check text-red-700 text-[25px]"></i>

                                        <p class="text-base lg:text-lg text-gray-800 text">
                                            لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپ و با استفاده از طراحان گرافیک است چاپگرها و متون بلکه روزنامه و مجله در ستون و سطرآنچنان که لازم است
                                        </p>
                                    </div>
                                </div>

                                <p class="text-xl md:text-2xl font-bold mt-10">فرصت ها و راهکارها</p>

                                <div class="flex flex-col py-6 gap-4 theme-opportunities">
                                    <div class="flex gap-7 items-center item">
                                        <i class="fa-solid fa-circle-check text-green-700 text-[25px]"></i>

                                        <p class="text-base lg:text-lg text-gray-800 text">
                                            لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپ و با استفاده از طراحان گرافیک است چاپگرها و متون بلکه روزنامه و مجله در ستون و سطرآنچنان که لازم است
                                        </p>
                                    </div>
                                    <div class="flex gap-7 items-center item">
                                        <i class="fa-solid fa-circle-check text-green-700 text-[25px]"></i>

                                        <p class="text-base lg:text-lg text-gray-800 text">
                                            لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپ و با استفاده از طراحان گرافیک است چاپگرها و متون بلکه روزنامه و مجله در ستون و سطرآنچنان که لازم است
                                        </p>
                                    </div>
                                    <div class="flex gap-7 items-center item">
                                        <i class="fa-solid fa-circle-check text-green-700 text-[25px]"></i>

                                        <p class="text-base lg:text-lg text-gray-800 text">
                                            لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپ و با استفاده از طراحان گرافیک است چاپگرها و متون بلکه روزنامه و مجله در ستون و سطرآنچنان که لازم است
                                        </p>
                                    </div>
                                    <div class="flex gap-7 items-center item">
                                        <i class="fa-solid fa-circle-check text-green-700 text-[25px]"></i>

                                        <p class="text-base lg:text-lg text-gray-800 text">
                                            لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپ و با استفاده از طراحان گرافیک است چاپگرها و متون بلکه روزنامه و مجله در ستون و سطرآنچنان که لازم است
                                        </p>
                                    </div>
                                    <div class="flex gap-7 items-center item">
                                        <i class="fa-solid fa-circle-check text-green-700 text-[25px]"></i>

                                        <p class="text-base lg:text-lg text-gray-800 text">
                                            لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپ و با استفاده از طراحان گرافیک است چاپگرها و متون بلکه روزنامه و مجله در ستون و سطرآنچنان که لازم است
                                        </p>
                                    </div>
                                    <div class="flex gap-7 items-center item">
                                        <i class="fa-solid fa-circle-check text-green-700 text-[25px]"></i>

                                        <p class="text-base lg:text-lg text-gray-800 text">
                                            لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپ و با استفاده از طراحان گرافیک است چاپگرها و متون بلکه روزنامه و مجله در ستون و سطرآنچنان که لازم است
                                        </p>
                                    </div>
                                    <div class="flex gap-7 items-center item">
                                        <i class="fa-solid fa-circle-check text-green-700 text-[25px]"></i>

                                        <p class="text-base lg:text-lg text-gray-800 text">
                                            لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپ و با استفاده از طراحان گرافیک است چاپگرها و متون بلکه روزنامه و مجله در ستون و سطرآنچنان که لازم است
                                        </p>
                                    </div>
                                    <div class="flex gap-7 items-center item">
                                        <i class="fa-solid fa-circle-check text-green-700 text-[25px]"></i>

                                        <p class="text-base lg:text-lg text-gray-800 text">
                                            لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپ و با استفاده از طراحان گرافیک است چاپگرها و متون بلکه روزنامه و مجله در ستون و سطرآنچنان که لازم است
                                        </p>
                                    </div>
                                    <div class="flex gap-7 items-center item">
                                        <i class="fa-solid fa-circle-check text-green-700 text-[25px]"></i>

                                        <p class="text-base lg:text-lg text-gray-800 text">
                                            لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپ و با استفاده از طراحان گرافیک است چاپگرها و متون بلکه روزنامه و مجله در ستون و سطرآنچنان که لازم است
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="ai-chat-tab" class="tab-content hidden">
                    <div class="glass-card rounded-xl p-8 mbti-test">
                        <div class="chat-container" id="chat-box">
                            <!-- پیام‌های چت اینجا رندر می‌شوند -->
                        </div>
                        <div class="input-container">
                            <input type="text" id="user-input" placeholder="سوال خود را بپرسید..." autocomplete="off">
                            <button class="send-button" id="send-button">ارسال</button>
                            <button class="stop-button" id="stop-button" style="display: none;">توقف</button>
                            <button class="clear-button" id="clear-button">پاک کردن</button>
                        </div>
                    </div>
                </div>
                <div class="px-8 pb-8">
                    <div class="border-t border-gray-200 pt-8 flex flex-col md:flex-row justify-between items-center">
                        <div class="mb-4 md:mb-0">
                            <button id="download-pdf-btn"
                                class="bg-gradient-to-r from-primary-600 to-secondary-600 hover:from-primary-700 hover:to-secondary-700 text-white font-medium py-3 px-8 rounded-xl transition flex items-center transform hover:scale-105">
                                <i class="fas fa-download ml-3 text-lg"></i>
                                دانلود PDF نتایج
                            </button>
                        </div>
                        <!-- Loading Overlay -->
                        <div id="loading-overlay"
                            class="fixed inset-0 bg-black bg-opacity-10 backdrop-blur-3xl flex items-center justify-center z-50 hidden">
                            <div
                                class="bg-white bg-opacity-10 backdrop-blur-lg rounded-xl p-8 border border-white border-opacity-20 shadow-lg flex flex-col items-center max-w-md w-full">
                                <div
                                    class="w-16 h-16 border-4 border-t-transparent border-primary-600 rounded-full animate-spin mb-4">
                                </div>
                                <p
                                    class="text-white font-vazir text-lg bg-primary-600 bg-opacity-80 rounded-lg px-6 py-3 border border-primary-400">
                                    در حال تولید PDF...</p>
                                <div class="w-full bg-gray-300 rounded-full h-3 mt-4">
                                    <div id="progress-bar"
                                        class="bg-primary-600 h-3 rounded-full transition-all duration-300"
                                        style="width: 0%"></div>
                                </div>
                            </div>
                        </div>
                        <div class="flex gap-2 space-x-reverse">
                            <button id="shareBtn" type="button" class="btn btn-primary copy-btn"
                                onclick="shareResults()" title="کپی لینک">
                                🔗 کپی لینک
                            </button>
                            <a href="https://elm-angize.ir/personality/clifton">
                                <button
                                    class="bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 font-medium py-3 px-8 rounded-xl transition flex items-center transform hover:scale-105"
                                    onclick="retakeTest()">
                                    <i class="fas fa-redo-alt ml-3 text-lg"></i>
                                    آزمون دوباره
                                </button>
                            </a>
                            <!-- <button id="upgrade-btn"
                                class="bg-gradient-to-r from-yellow-500 to-yellow-600 hover:from-yellow-600 hover:to-yellow-700 text-white font-medium py-3 px-8 rounded-xl transition flex items-center transform hover:scale-105 hidden"
                                onclick="upgradeToPremium()">
                                <i class="fas fa-crown ml-3 text-lg"></i>
                                ارتقا به نسخه پیشرفته
                            </button> -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        (function () {
            const btn = document.getElementById('download-pdf-btn');
            if (!btn) return;

            btn.addEventListener('click', exportCliftonPdf);

            async function exportCliftonPdf() {
                const overlay = document.getElementById('loading-overlay');
                const progressBar = document.getElementById('progress-bar');
                const setProgress = (p) => { if (progressBar) progressBar.style.width = p + '%'; };

                document.body.classList.add('clifton-pdf-exporting');
                overlay && overlay.classList.remove('hidden');
                setProgress(6);

                try {
                    const { jsPDF } = window.jspdf;
                    const doc = new jsPDF('p', 'pt', 'a4');
                    const pageW = doc.internal.pageSize.getWidth();
                    const pageH = doc.internal.pageSize.getHeight();

                    // هدر بالای صفحه (کارت گرادیانی که h1.neon-text داخلشه)
                    const cover = document.querySelector('h1.neon-text')?.closest('div[class*="bg-gradient-to-"]');

                    // همه تب‌ها به ترتیب DOM
                    const tabs = Array.from(document.querySelectorAll('.tab-content'))
                        .filter(el => el.id !== 'ai-chat-tab'); // حذف تب چت از خروجی PDF

                    const blocks = [];
                    if (cover) blocks.push(cover);
                    blocks.push(...tabs);

                    // برای رندر درست، اسکرول بالا
                    window.scrollTo(0, 0);

                    for (let i = 0; i < blocks.length; i++) {
                        setProgress(6 + Math.round((i / Math.max(1, blocks.length)) * 86));

                        const el = blocks[i];
                        // تب‌های hidden را موقتا نمایش بده
                        const wasHidden = el.classList.contains('hidden');
                        if (wasHidden) el.classList.remove('hidden');

                        // اسکرول داخلی هر تب از ابتدا
                        el.scrollTop = 0;

                        const canvas = await html2canvas(el, {
                            scale: 2,
                            backgroundColor: '#ffffff',
                            useCORS: true,
                            logging: false,
                            windowWidth: Math.max(el.scrollWidth, document.documentElement.clientWidth)
                        });

                        // برش هوشمند به صفحات A4
                        const ratio = pageW / canvas.width;       // px -> pt
                        const pageHeightPx = Math.floor(pageH / ratio);

                        let cutTopPx = 0, pageIndexForBlock = 0;
                        while (cutTopPx < canvas.height) {
                            const sliceH = Math.min(pageHeightPx, canvas.height - cutTopPx);

                            const sliceCanvas = document.createElement('canvas');
                            sliceCanvas.width = canvas.width;
                            sliceCanvas.height = sliceH;

                            const ctx = sliceCanvas.getContext('2d');
                            ctx.drawImage(canvas,
                                0, cutTopPx, canvas.width, sliceH,
                                0, 0, canvas.width, sliceH
                            );

                            const img = sliceCanvas.toDataURL('image/jpeg', 0.98);

                            if (i > 0 || pageIndexForBlock > 0) doc.addPage();
                            doc.addImage(img, 'JPEG', 0, 0, pageW, sliceH * ratio);

                            cutTopPx += sliceH;
                            pageIndexForBlock++;
                        }

                        // برگرداندن وضعیت تب
                        if (wasHidden) el.classList.add('hidden');
                    }

                    setProgress(98);
                    const today = new Date().toISOString().slice(0, 10);
                    doc.save(`Clifton-Results-${today}.pdf`);
                } catch (e) {
                    console.error(e);
                    alert('خطا در ساخت PDF. لطفاً دوباره تلاش کنید.');
                } finally {
                    setProgress(100);
                    setTimeout(() => {
                        overlay && overlay.classList.add('hidden');
                        setProgress(0);
                        document.body.classList.remove('clifton-pdf-exporting');
                    }, 300);
                }
            }
        })();
    </script>

    <script>
        // اجرای لود نتایج پس از آماده‌شدن
        document.addEventListener("DOMContentLoaded", function () {
            if (typeof window.displayResults === "function") window.displayResults();
        });
    </script>
    <!-- <?php include '../footer/sb-footer.php'; ?> -->

</body>

</html>