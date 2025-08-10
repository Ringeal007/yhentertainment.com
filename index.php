<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
$ip = $_SERVER['REMOTE_ADDR'];
$json = @file_get_contents("http://ip-api.com/json/$ip");
var_dump($json);//测试

// 读取配置
$config_file = __DIR__ . '/configuration.json';
$config = [];
if (file_exists($config_file)) {
    $config = json_decode(file_get_contents($config_file), true);
}

// 语言代码自动检测，优先GET参数
$lang_code = 'zh-cn'; // 默认

if (isset($_GET['lang'])) {
    $lang = strtolower($_GET['lang']);
    switch ($lang) {
        case 'zh-cn':
            $lang_code = 'zh-cn';
            setcookie('country_code', 'CN', time() + 3600 * 24 * 30, '/');
            break;
        case 'en':
            $lang_code = 'en';
            setcookie('country_code', 'EN', time() + 3600 * 24 * 30, '/');
            break;
        case 'zh-tw':
            $lang_code = 'zh-tw';
            setcookie('country_code', 'TW', time() + 3600 * 24 * 30, '/');
            break;
        case 'jp':
            $lang_code = 'jp';
            setcookie('country_code', 'JP', time() + 3600 * 24 * 30, '/');
            break;
        case 'ru':
            $lang_code = 'ru';
            setcookie('country_code', 'RU', time() + 3600 * 24 * 30, '/');
            break;
        case 'uy':
            $lang_code = 'uy';
            setcookie('country_code', 'UY', time() + 3600 * 24 * 30, '/');
            break;
        default:
            // keep default
            break;
    }
} elseif (isset($_COOKIE['country_code'])) {
    $country_code = strtoupper($_COOKIE['country_code']);
    switch ($country_code) {
        case 'US':
        case 'CA':
        case 'GB':
        case 'AU':
        case 'NZ':
        case 'IE':
        case 'SG':
        case 'EN':
            $lang_code = 'en';
            break;
        case 'TW':
        case 'HK':
        case 'MO':
            $lang_code = 'zh-tw';
            break;
        case 'CN':
            $lang_code = 'zh-cn';
            break;
        case 'UY':
            $lang_code = 'uy';
            break;
        case 'JP':
            $lang_code = 'jp';
            break;
        case 'RU':
            $lang_code = 'ru';
            break;
        default:
            // keep default
            break;
    }
} else {
    // 使用 ip-api.com 检测国家代码
    $ip = $_SERVER['REMOTE_ADDR'];
    $json = @file_get_contents("http://ip-api.com/json/$ip");
    $data = json_decode($json, true);
    if ($data && $data['status'] === 'success') {
        $country = strtoupper($data['countryCode']);
        switch ($country_code) {
            case 'US':
            case 'CA':
            case 'GB':
            case 'AU':
            case 'NZ':
            case 'IE':
            case 'SG':
            case 'EN':
                $lang_code = 'en';
                break;
            case 'TW':
            case 'HK':
            case 'MO':
                $lang_code = 'zh-tw';
                break;
            case 'CN':
                $lang_code = 'zh-cn';
                break;
            case 'UY':
                $lang_code = 'uy';
                break;
            case 'JP':
                $lang_code = 'jp';
                break;
            case 'RU':
                $lang_code = 'ru';
                break;
            default:
                // keep default
                break;
        }
    } else {
        $lang_code = 'zh-cn';
    }
}

// 加载语言包
$lang_file = __DIR__ . "/languages/{$lang_code}.json";
if (!file_exists($lang_file)) {
    $lang_file = __DIR__ . "/languages/zh-cn.json";
}
$lang = json_decode(@file_get_contents($lang_file), true) ?? [];

// 输出当前IP和属地（测试用）
echo "你的IP: $ip<br>";
echo "检测到国家: " . ($data['countryCode'] ?? '未知') . "<br>";
echo "当前语言: $lang_code<br>";

// 读取对应语言文件
$lang_file = __DIR__ . "/languages/{$lang_code}.json";
if (!file_exists($lang_file)) {
    $lang_file = __DIR__ . "/languages/zh-cn.json"; // 回退
}
$lang = json_decode(@file_get_contents($lang_file), true) ?? [];

// 统计文件路径
$statistics_dir = __DIR__ . '/statistics';
if (!is_dir($statistics_dir))
    mkdir($statistics_dir, 0777, true);

$daily_counter_file = "{$statistics_dir}/daily_counter.json";
$total_counter_file = "{$statistics_dir}/total_counter.json";
$online_file = "{$statistics_dir}/online.txt";

// 日期
$today = date('Y-m-d');

// ========== 防刷访问量 ==========
// 每个IP冷却时间（秒）
$ip_limit_seconds = 10;
$ip_limiter_file = "{$statistics_dir}/ip_limiter.json";
$ip_limiter = [];
if (file_exists($ip_limiter_file)) {
    $ip_limiter = json_decode(file_get_contents($ip_limiter_file), true);
    if (!is_array($ip_limiter))
        $ip_limiter = [];
}
$now_time = time();
$ip = $_SERVER['REMOTE_ADDR'];
$can_count = false;
if (!isset($ip_limiter[$ip]) || ($now_time - $ip_limiter[$ip]) > $ip_limit_seconds) {
    $can_count = true;
    $ip_limiter[$ip] = $now_time;
    file_put_contents($ip_limiter_file, json_encode($ip_limiter, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
}

// ========== 单日访问量 ==========
$daily_data = [];
if (file_exists($daily_counter_file)) {
    $daily_data = json_decode(file_get_contents($daily_counter_file), true);
    if (!is_array($daily_data))
        $daily_data = [];
}
switch (true) {
    case !isset($daily_data[$today]):
        $daily_data[$today] = 1;
        break;
    default:
        $daily_data[$today]++;
        break;
}
file_put_contents($daily_counter_file, json_encode($daily_data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
$today_count = $daily_data[$today];

// ========== 总访问量 ==========
$total_count = 0;
if (file_exists($total_counter_file)) {
    $total_count = (int) file_get_contents($total_counter_file);
}
$total_count++;
file_put_contents($total_counter_file, $total_count);

// ========== 在线人数 ==========
$timeout = 1; // 1s
$ip = $_SERVER['REMOTE_ADDR'];
$now = time();
$onlines = [];
$new_onlines = [];
$found = false;
if (file_exists($online_file)) {
    $onlines = file($online_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($onlines as $line) {
        [$online_ip, $last_time] = explode('|', $line);
        if ($now - $last_time < $timeout) {
            if ($online_ip == $ip) {
                if (!$found) { // 只更新一次当前IP
                    $new_onlines[] = "$ip|$now";
                    $found = true;
                }
                // 如果已经添加过当前IP，则跳过
            } else {
                $new_onlines[] = "$online_ip|$last_time";
            }
        }
    }
}
if (!$found) {
    $new_onlines[] = "$ip|$now";
}
file_put_contents($online_file, implode("\n", $new_onlines));
$online_count = count($new_onlines);

// ========== 网站运行时长 ==========
$site_days = '';
if (!empty($config['site_start_date'])) {
    $site_days = '1';
    $start = strtotime($config['site_start_date']);
    $now = strtotime(date('Y-m-d'));

    if ($start && $now >= $start) {
        $site_days = floor(($now - $start) / 86400) + 1;
    }
}
?>

<!DOCTYPE html>
<html class="no-js" lang="<?php echo htmlspecialchars($lang_code); ?>">

<head>

    <!--- basic page needs
        ================================================== -->
    <meta charset="utf-8">
    <title>Entertainment_YH</title>
    <meta name="description" content="Entertainment_YH's Personal Website.">
    <meta name="author" content="Entertainment_YH">
    <meta name="keywords"
        content="Entertainment_YH,YH,YH的网站,YH Community,娱乐之神,YH娱乐之神,娱乐,个人网站,网站,个人,Entertainment,_,Entertainment_YH,God of Entertainment,Entertainment_YH个人网站,娱乐之神的个人网站,娱乐之神的网站">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">

    <!-- mobile specific metas
        ================================================== -->
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSS
        ================================================== -->
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/vendor.css">
    <link rel="stylesheet" href="css/statistics-chart.css">

    <!-- script
        ================================================== -->
    <script defer src="js/vendor/fontawesome-free-6.7.2-web/js/all.js"></script>

    <!-- favicons
        ================================================== -->
    <link rel="apple-touch-icon" href="favicon_io/apple-touch-icon.png">
    <link rel="icon" type="image/png" href="favicon_io/favicon-32x32.png" sizes="32x32">
    <link rel="icon" type="image/png" href="favicon_io/favicon-16x16.png" sizes="16x16">
    <link rel="icon" href="favicon_io/favicon.ico" type="image/x-icon">

</head>

<body>
    <script>
        window.dailyData = <?php
        $recent_days = [];
        $today = date('Y-m-d');
        for ($i = 14; $i >= 0; $i--) {
            $d = date('Y-m-d', strtotime("-$i day", strtotime($today)));
            $recent_days[] = $daily_data[$d] ?? 0;
        }
        echo json_encode($recent_days, JSON_UNESCAPED_UNICODE);
        ?>;
    </script>

    <!-- header
        ================================================== -->
    <header class="s-header">
        <div class="row s-header__nav-wrap">
            <nav class="s-header__nav">
                <ul>
                    <li<?php if ($lang_code === 'zh-cn')
                        echo ' class="current"'; ?>>
                        <a href="?lang=zh-cn">简体中文</a>
                        </li>
                        <li<?php if ($lang_code === 'en')
                            echo ' class="current"'; ?>>
                            <a href="?lang=en">English</a>
                            </li>
                            <li<?php if ($lang_code === 'zh-tw')
                                echo ' class="current"'; ?>>
                                <a href="?lang=zh-tw">繁體中文</a>
                                </li>
                                <li<?php if ($lang_code === 'jp')
                                    echo ' class="current"'; ?>>
                                    <a href="?lang=jp">日本語</a>
                                    </li>
                                    <li<?php if ($lang_code === 'ru')
                                        echo ' class="current"'; ?>>
                                        <a href="?lang=ru">Русский</a>
                                        </li>
                                        <li<?php if ($lang_code === 'uy')
                                            echo ' class="current"'; ?>>
                                            <a href="?lang=uy">ئۇيغۇرچە</a>
                                            </li>
                </ul>
            </nav>
        </div> <!-- end row -->

        <a class="s-header__menu-toggle" href="#0" title="Menu">
            <span class="s-header__menu-icon"></span>
        </a>
    </header> <!-- end s-header -->


    <!-- hero
        ================================================== -->
    <section id="hero" class="s-hero target-section">
        <section id="about"></section>

        <div class="s-hero__bg rellax" data-rellax-speed="-7"></div>

        <div class="row s-hero__content">
            <div class="column">

                <div class="s-hero__content-about">

                    <h1> <?php echo htmlspecialchars($lang['hello-title'] ?? ''); ?></h1>

                    <h3>
                        <?php echo htmlspecialchars($lang['title-description'] ?? ''); ?>
                        <br \>
                        <?php echo htmlspecialchars($lang['language-using'] ?? ''); ?>
                        <br \>
                        <?php echo htmlspecialchars($lang['buttons-icon'] ?? ''); ?>
                    </h3>

                    <div class="s-hero__content-social">
                        <a href="https://space.bilibili.com/1977333915?spm_id_from=333.1007.0.0" title="Bilibili主页"><i
                                class="fa-brands fa-bilibili" aria-hidden="true"></i></a>
                        <a href="https://github.com/EntertainmentYH/yhentertainment.com" title="GitHub主页"><i
                                class="fa-brands fa-square-github" aria-hidden="true"></i></a>
                        <a href="https://steamcommunity.com/id/Entertainment_YH/" title="Steam主页"><i
                                class="fab fa-steam" aria-hidden="true"></i></a>
                        <a href="https://www.youtube.com/@Entertainment_CHINESE" title="YouTube频道"><i
                                class="fab fa-youtube" aria-hidden="true"></i></a>
                        <a href="https://x.com/Entertainm15252" title="X (Twitter)主页"><i class="fab fa-square-x-twitter"
                                aria-hidden="true"></i></a>
                    </div>

                </div> <!-- end s-hero__content-about -->

            </div>
        </div> <!-- s-hero__content -->

        <div class="s-hero__scroll">
            <a href="#about" class="s-hero__scroll-link smoothscroll">
                <span class="scroll-arrow">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                        style="fill:rgba(0, 0, 0, 1);">
                        <path
                            d="M18.707 12.707L17.293 11.293 13 15.586 13 6 11 6 11 15.586 6.707 11.293 5.293 12.707 12 19.414z">
                        </path>
                    </svg>
                </span>
                <span class="scroll-text"><?php echo htmlspecialchars($lang['scroll-text'] ?? ''); ?></span>
            </a>
        </div> <!-- s-hero__scroll -->

    </section> <!-- end s-hero -->

    <!-- about
        ================================================== -->
    <section id="about" class="s-about target-section">

        <div class="row">
            <div class="column large-3 tab-12">
                <img class="s-about__pic" src="https://2.z.wiki/autoupload/20250529/S1u7/690X690/icon.jpg" alt="">
            </div>
            <div class="column large-9 tab-12 s-about__content">
                <h3><?php echo htmlspecialchars($lang['about-web'] ?? ''); ?></h3>
                <p>
                    &emsp;&emsp;<?php echo htmlspecialchars($lang['about-web-text'] ?? ''); ?>
                </p>

                <hr>

                <div class="row s-about__content-bottom">
                    <div class="column w-1000-stack">
                        <h3><?php echo htmlspecialchars($lang['web-history'] ?? ''); ?></h3>
                        <p>&emsp;&emsp;<?php echo htmlspecialchars($lang['web-history-text'] ?? ''); ?></p>
                    </div>
                </div>

                <hr>

                <div class="row s-about__content-bottom">
                    <div class="column w-1000-stack">
                        <h3><?php echo htmlspecialchars($lang['contact-me'] ?? ''); ?></h3>
                        <p>
                            <?php echo htmlspecialchars($lang['my-email-text'] ?? ''); ?>
                            &emsp;
                            <?php echo htmlspecialchars($lang['my-email'] ?? ''); ?>
                            <br \>
                            <?php echo htmlspecialchars($lang['my-qid-text'] ?? ''); ?>
                            &emsp;
                            <?php echo htmlspecialchars($lang['my-qid'] ?? ''); ?>
                            <br \>
                            <?php echo htmlspecialchars($lang['other-info'] ?? ''); ?>
                        </p>
                    </div>
                </div>
            </div>
        </div> <!-- end row -->

    </section> <!-- end s-about -->

    <div id="main">
        <!-- resume
        ================================================== -->
        <section id="vote" class="s-resume target-section">

            <!-- 是的，我知道这个投票的逻辑很不合理，这个投票根本就不可信，有人可以重复投票，可以每10分钟来刷一个投票，但可悲的是，我没有足够的技术力来阻止，我无法直接通过IP来限制投票，因为这样会导致处在同一网络环境下的人无法投票。 -->
            <div class="row s-resume__section">
                <div class="column large-3 tab-12">
                    <h3 class="section-header-allcaps">vote</h3>
                </div>
                <div class="column large-9 tab-12">
                    <div class="resume-block"
                        style="padding: 2em 2em 1em 2em; background: #e3f6fd; border-radius: 12px; box-shadow: 0 2px 8px rgba(155, 228, 241, 0.8);">
                        <div class="resume-block__header" style="margin-bottom: 1em;">
                            <h4 class="h3" style="margin-bottom: 0.5em;">
                                <?php echo htmlspecialchars($lang['vote-title'] ?? ''); ?>
                            </h4>
                            <div style="font-size:0.95em;color:#888;margin-bottom:0.5em;">
                                <?php echo htmlspecialchars($lang['vote-deadline'] ?? ''); ?><span
                                    id="vote-countdown"></span><?php echo htmlspecialchars($lang['vote-deadline-end'] ?? ''); ?>
                            </div>
                        </div>
                        <div id="vote-area">
                            <form id="vote-form" method="post" style="display: flex; flex-direction: column; gap: 1em;">
                                <label style="display: flex; align-items: center; gap: 1em; width: 100%;">
                                    <input type="radio" class="vote-btn" name="option" value="option1"
                                        style="accent-color: #00bfae;">
                                    <span
                                        style="flex: 1; padding: 1em; background: #fff; border-radius: 6px; border: 2px solid #000; color: #222;"><?php echo htmlspecialchars($lang['vote-option1'] ?? ''); ?></span>
                                </label>
                                <label style="display: flex; align-items: center; gap: 1em; width: 100%;">
                                    <input type="radio" class="vote-btn" name="option" value="option2"
                                        style="accent-color: #00bfae;">
                                    <span
                                        style="flex: 1; padding: 1em; background: #fff; border-radius: 6px; border: 2px solid #000; color: #222;"><?php echo htmlspecialchars($lang['vote-option2'] ?? ''); ?></span>
                                </label>
                                <label style="display: flex; align-items: center; gap: 1em; width: 100%;">
                                    <input type="radio" class="vote-btn" name="option" value="option3"
                                        style="accent-color: #00bfae;">
                                    <span
                                        style="flex: 1; padding: 1em; background: #fff; border-radius: 6px; border: 2px solid #000; color: #222;"><?php echo htmlspecialchars($lang['vote-option3'] ?? ''); ?></span>
                                </label>
                                <label style="display: flex; align-items: center; gap: 1em; width: 100%;">
                                    <input type="radio" class="vote-btn" name="option" value="option4"
                                        style="accent-color: #00bfae;">
                                    <span
                                        style="flex: 1; padding: 1em; background: #fff; border-radius: 6px; border: 2px solid #000; color: #222;"><?php echo htmlspecialchars($lang['vote-option4'] ?? ''); ?></span>
                                </label>
                                <button type="submit" class="vote-btn"
                                    style="margin-top: 1em; padding: 0.75em 0; background: #00bfae; color: #fff; border: none; border-radius: 6px; font-size: 1.1em; cursor: pointer; transition: background 0.2s; width: 100%;">
                                    <?php echo htmlspecialchars($lang['vote-submit'] ?? ''); ?>
                                </button>
                            </form>
                            <div id="vote-result" style="margin-top: 1.5em;"></div>
                        </div>
                    </div>
                </div>
            </div> <!-- post-section -->
            <section id="statistics"></section>


            <div class="row s-resume__section">
                <div class="column large-3 tab-12">
                    <h3 class="section-header-allcaps">Statistics</h3>
                </div>
                <div class="column large-9 tab-12">
                    <div class="resume-block">
                        <div class="resume-block__header">
                            <h4 class="h3"><?php echo htmlspecialchars($lang['statistics-title'] ?? ''); ?></h4>
                        </div>
                        <div id="chart-container">
                            <canvas id="myChart" height="300"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <section id="announcement">
                <div class="row s-resume__section">
                    <div class="column large-3 tab-12">
                        <h3 class="section-header-allcaps">announcement</h3>
                    </div>
                    <div class="column large-9 tab-12">
                        <div class="resume-block">
                            <!-- announcement-code 006 -->
                            <div class="resume-block__header">
                                <h4 class="h3">
                                    <?php echo htmlspecialchars($lang['announcement006-title'] ?? ''); ?>
                                </h4>
                                <p class="resume-block__header-meta">
                                    <span><?php echo htmlspecialchars($lang['ID'] ?? ''); ?></span>
                                    <span class="resume-block__header-date">
                                        Aug 10<sup>th</sup>, 2025 - Present
                                    </span>
                                </p>
                            </div>

                            <ul>
                                <h4><?php echo htmlspecialchars($lang['announcement006-text-1'] ?? ''); ?></h4>
                                <li>
                                    <?php echo htmlspecialchars($lang['announcement006-text-1-1'] ?? ''); ?><br \>
                                </li>
                            </ul>

                            <ul>
                                <h4><?php echo htmlspecialchars($lang['announcement006-text-2'] ?? ''); ?></h4>
                                <li>
                                    <?php echo htmlspecialchars($lang['announcement006-text-2-1'] ?? ''); ?><br \>
                                </li>
                            </ul>

                            <hr style="border: none; border-top: 2px dashed #00bfae; margin: 2.5em 0; width: 80%;">

                            <!-- announcement-code 005 -->
                            <div class="resume-block__header">
                                <h4 class="h3">
                                    <?php echo htmlspecialchars($lang['announcement005-title'] ?? ''); ?>
                                </h4>
                                <p class="resume-block__header-meta">
                                    <span><?php echo htmlspecialchars($lang['ID'] ?? ''); ?></span>
                                    <span class="resume-block__header-date">
                                        Aug 8<sup>th</sup>, 2025 - Present
                                    </span>
                                </p>
                            </div>

                            <ul>
                                <h4><?php echo htmlspecialchars($lang['announcement005-text-1'] ?? ''); ?></h4>
                                <li>
                                    <?php echo htmlspecialchars($lang['announcement005-text-1-1'] ?? ''); ?><br \>
                                </li>
                            </ul>

                            <hr style="border: none; border-top: 2px dashed #00bfae; margin: 2.5em 0; width: 80%;">

                            <!-- announcement-code 004 -->
                            <div class="resume-block__header">
                                <h4 class="h3">
                                    <?php echo htmlspecialchars($lang['announcement004-title'] ?? ''); ?>
                                </h4>
                                <p class="resume-block__header-meta">
                                    <span><?php echo htmlspecialchars($lang['ID'] ?? ''); ?></span>
                                    <span class="resume-block__header-date">
                                        Aug 8<sup>th</sup>, 2025 - Present
                                    </span>
                                </p>
                            </div>

                            <p><?php echo htmlspecialchars($lang['announcement004-text'] ?? ''); ?></p>

                            <hr style="border: none; border-top: 2px dashed #00bfae; margin: 2.5em 0; width: 80%;">

                            <!-- announcement-code 003 -->
                            <div class="resume-block__header">
                                <h4 class="h3">
                                    <?php echo htmlspecialchars($lang['announcement003-title'] ?? ''); ?>
                                </h4>
                                <p class="resume-block__header-meta">
                                    <span><?php echo htmlspecialchars($lang['ID'] ?? ''); ?></span>
                                    <span class="resume-block__header-date">
                                        Aug 7<sup>th</sup>, 2025 - Present
                                    </span>
                                </p>
                            </div>

                            <ul>
                                <h4><?php echo htmlspecialchars($lang['announcement003-text-1'] ?? ''); ?></h4>
                                <li>
                                    <?php echo htmlspecialchars($lang['announcement003-text-1-1'] ?? ''); ?><br \>
                                </li>
                                <li>
                                    <?php echo htmlspecialchars($lang['announcement003-text-1-2'] ?? ''); ?><br \>
                                </li>
                                <li>
                                    <?php echo htmlspecialchars($lang['announcement003-text-1-3'] ?? ''); ?><br \><br \>
                                </li>
                                <h4><?php echo htmlspecialchars($lang['announcement003-text-2'] ?? ''); ?></h4>
                                <li>
                                    <?php echo htmlspecialchars($lang['announcement003-text-2-1'] ?? ''); ?><br \>
                                </li>
                                <li>
                                    <?php echo htmlspecialchars($lang['announcement003-text-2-2'] ?? ''); ?><br \>
                                </li>
                            </ul>

                            <hr style="border: none; border-top: 2px dashed #00bfae; margin: 2.5em 0; width: 80%;">

                            <!-- announcement-code 002 -->
                            <div class="resume-block__header">
                                <h4 class="h3">
                                    <?php echo htmlspecialchars($lang['announcement002-title'] ?? ''); ?>
                                </h4>
                                <p class="resume-block__header-meta">
                                    <span><?php echo htmlspecialchars($lang['ID'] ?? ''); ?></span>
                                    <span class="resume-block__header-date">
                                        May 30<sup>th</sup>, 2025 - Present
                                    </span>
                                </p>
                            </div>

                            <p>
                                &emsp;&emsp;<?php echo htmlspecialchars($lang['announcement002-text'] ?? ''); ?><br \>
                            </p>

                            <hr style="border: none; border-top: 2px dashed #00bfae; margin: 2.5em 0; width: 80%;">

                            <!-- announcement-code 001 -->
                            <div class="resume-block__header">
                                <h4 class="h3">
                                    <?php echo htmlspecialchars($lang['announcement001-title'] ?? ''); ?>
                                </h4>
                                <p class="resume-block__header-meta">
                                    <span><?php echo htmlspecialchars($lang['ID'] ?? ''); ?></span>
                                    <span class="resume-block__header-date">
                                        May 11<sup>th</sup>, 2025 - Present
                                    </span>
                                </p>
                            </div>

                            <p>
                            <ul>
                                <li>
                                    <?php echo htmlspecialchars($lang['announcement001-text-1'] ?? ''); ?>
                                </li>
                                <li>
                                    <?php echo htmlspecialchars($lang['announcement001-text-2'] ?? ''); ?>
                                </li>
                                <li>
                                    <?php echo htmlspecialchars($lang['announcement001-text-3'] ?? ''); ?>
                                </li>
                            </ul>
                            </p>

                        </div> <!-- end resume-block -->
                    </div>

                </div> <!-- post-section -->
                <section id="post">

                    <div class="row s-resume__section">
                        <div class="column large-3 tab-12">
                            <h3 class="section-header-allcaps">post</h3>
                        </div>
                        <div class="column large-9 tab-12">
                            <div class="resume-block">

                                <div class="resume-block__header">
                                    <h4 class="h3"><?php echo htmlspecialchars($lang['post002-title'] ?? ''); ?>
                                    </h4>
                                    <p class="resume-block__header-meta">
                                        <span>Entertainment_YH</span>
                                        <span class="resume-block__header-date">
                                            Published on May 23<sup>th</sup>, 2025
                                        </span>
                                    </p>
                                </div>

                                <p>
                                    <?php echo htmlspecialchars($lang['post002-text'] ?? ''); ?>
                                </p>

                                <hr style="border: none; border-top: 2px dashed #00bfae; margin: 2.5em 0; width: 80%;">

                                <div class="resume-block__header">
                                    <h4 class="h3"><?php echo htmlspecialchars($lang['post001-title'] ?? ''); ?>
                                    </h4>
                                    <p class="resume-block__header-meta">
                                        <span>Entertainment_YH</span>
                                        <span class="resume-block__header-date">
                                            Published on May 17<sup>th</sup>, 2025
                                        </span>
                                    </p>
                                </div>

                                <p>
                                    <?php echo htmlspecialchars($lang['post001-text'] ?? ''); ?>
                                </p>

                            </div> <!-- post-section -->

                        </div>
                    </div> <!-- s-resume__section -->

                    <section id="utilities">
                        <div class="row s-resume__section">
                            <div class="column large-3 tab-12">
                                <h3 class="section-header-allcaps">Utilities</h3>
                            </div>
                            <div class="column large-9 tab-12">
                                <div class="resume-block">
                                    <!-- utilities 001 -->
                                    <div class="resume-block__header">
                                        <h4 class="h3">
                                            <?php echo htmlspecialchars($lang['utilities001-title'] ?? ''); ?>
                                        </h4>
                                        <p class="resume-block__header-meta">
                                            <span><?php echo htmlspecialchars($lang['ID'] ?? ''); ?></span>
                                            <span class="resume-block__header-date">
                                                June 18<sup>th</sup>, 2025 - Present
                                            </span>
                                        </p>
                                    </div>

                                    <p>
                                        &emsp;&emsp;<?php echo htmlspecialchars($lang['utilities001-description'] ?? ''); ?><br
                                            \>
                                    <blockquote>
                                        <ul>
                                            <li>
                                                <a href="https://store.steampowered.com/" title="Steam主页"
                                                    style="text-shadow: 0 0 3px #000; color: snow;"><i
                                                        class="fab fa-steam" aria-hidden="true"></i></a>
                                                <a href="https://store.steampowered.com/"
                                                    style="text-shadow: 0 0 3px #000; color: snow;">
                                                    <?php echo htmlspecialchars($lang['utilities001-steam'] ?? ''); ?>
                                                </a>
                                            </li>
                                            <li>
                                                <a href="https://www.runoob.com/"
                                                    style="text-shadow: 0 0 3px #000; color: snow;">
                                                    <?php echo htmlspecialchars($lang['utilities001-runoob'] ?? ''); ?>
                                                </a>
                                            </li>
                                        </ul>
                                    </blockquote>
                                    </p>


                                </div> <!-- end resume-block -->
                            </div>

                        </div>

                        <section id="article1">
                            <div class="row s-resume__section">
                                <div class="column large-3 tab-12">
                                    <h3 class="section-header-allcaps">article</h3>
                                </div>
                                <div class="column large-9 tab-12">
                                    <div class="resume-block">
                                        <!-- article 001 -->
                                        <div class="resume-block__header">
                                            <h4 class="h3">
                                                <?php echo htmlspecialchars($lang['article001-title'] ?? ''); ?>
                                            </h4>
                                            <p class="resume-block__header-meta">
                                                <span><?php echo htmlspecialchars($lang['ID'] ?? ''); ?></span>
                                                <span><?php echo htmlspecialchars($lang['article001-description'] ?? ''); ?></span>
                                                <span class="resume-block__header-date">
                                                    June 18<sup>th</sup>, 2025 - Present
                                                </span>
                                            </p>
                                        </div>

                                        <p>
                                            &emsp;&emsp;<?php echo htmlspecialchars($lang['article001-paragraph-1'] ?? ''); ?>
                                            <br \>
                                            &emsp;&emsp;<?php echo htmlspecialchars($lang['article001-paragraph-2'] ?? ''); ?>
                                            <br \>
                                            &emsp;&emsp;<?php echo htmlspecialchars($lang['article001-paragraph-3'] ?? ''); ?>
                                            <br \>
                                        <blockquote>
                                            &emsp;&emsp;<?php echo htmlspecialchars($lang['article001-paragraph-4'] ?? ''); ?>
                                        </blockquote>
                                        &emsp;&emsp;<?php echo htmlspecialchars($lang['article001-paragraph-5'] ?? ''); ?>
                                        <br \>
                                        &emsp;&emsp;<?php echo htmlspecialchars($lang['article001-paragraph-6'] ?? ''); ?>
                                        <br \>
                                        &emsp;&emsp;<?php echo htmlspecialchars($lang['article001-paragraph-7'] ?? ''); ?>
                                        <br \>
                                        <blockquote>
                                            &emsp;&emsp;<?php echo htmlspecialchars($lang['article001-paragraph-8'] ?? ''); ?>
                                        </blockquote>
                                        &emsp;&emsp;<?php echo htmlspecialchars($lang['article001-paragraph-9'] ?? ''); ?>
                                        <?php echo htmlspecialchars($lang['article001-paragraph-10'] ?? ''); ?>
                                        </p>

                                        <hr
                                            style="border: none; border-top: 2px dashed #00bfae; margin: 2.5em 0; width: 80%;">


                                        <section id="article2">
                                            <!-- article 002 -->
                                            <div class="resume-block__header">
                                                <h4 class="h3">
                                                    <?php echo htmlspecialchars($lang['article002-title'] ?? ''); ?>
                                                </h4>
                                                <p class="resume-block__header-meta">
                                                    <span><?php echo htmlspecialchars($lang['ID'] ?? ''); ?></span>
                                                    <span><?php echo htmlspecialchars($lang['article002-description'] ?? ''); ?></span>
                                                    <span class="resume-block__header-date">
                                                        June 26<sup>th</sup>, 2025 - Present
                                                    </span>
                                                </p>
                                            </div>

                                            <p>
                                                &emsp;&emsp;<?php echo htmlspecialchars($lang['article002-citation-1'] ?? ''); ?>
                                                <br \>
                                                &emsp;&emsp;<?php echo htmlspecialchars($lang['article002-citation-2'] ?? ''); ?>
                                                <br \>
                                            <blockquote>
                                                &emsp;&emsp;<?php echo htmlspecialchars($lang['article002-subtitle-1'] ?? ''); ?>
                                            </blockquote>
                                            &emsp;&emsp;<?php echo htmlspecialchars($lang['article002-paragraph-1-1'] ?? ''); ?>
                                            <br \>
                                            &emsp;&emsp;<?php echo htmlspecialchars($lang['article002-paragraph-1-2'] ?? ''); ?><br
                                                \>
                                            <blockquote>
                                                &emsp;&emsp;<?php echo htmlspecialchars($lang['article002-subtitle-2'] ?? ''); ?>
                                            </blockquote>
                                            &emsp;&emsp;<?php echo htmlspecialchars($lang['article002-paragraph-2-1'] ?? ''); ?>
                                            <br \>
                                            &emsp;&emsp;<?php echo htmlspecialchars($lang['article002-paragraph-2-2'] ?? ''); ?>
                                            <br \>
                                            &emsp;&emsp;<?php echo htmlspecialchars($lang['article002-paragraph-2-3'] ?? ''); ?>
                                            <br \>
                                            <blockquote>
                                                &emsp;&emsp;<?php echo htmlspecialchars($lang['article002-subtitle-3'] ?? ''); ?>
                                            </blockquote>
                                            &emsp;&emsp;<?php echo htmlspecialchars($lang['article002-paragraph-3-1'] ?? ''); ?>
                                            <br \>
                                            &emsp;&emsp;<?php echo htmlspecialchars($lang['article002-paragraph-3-2'] ?? ''); ?>
                                            <br \>
                                            &emsp;&emsp;<?php echo htmlspecialchars($lang['article002-paragraph-3-3'] ?? ''); ?>
                                            <br \>
                                            &emsp;&emsp;<?php echo htmlspecialchars($lang['article002-paragraph-3-4'] ?? ''); ?>
                                            <br \>
                                            <blockquote>
                                                &emsp;&emsp;<?php echo htmlspecialchars($lang['article002-subtitle-4'] ?? ''); ?>
                                            </blockquote>
                                            &emsp;&emsp;<?php echo htmlspecialchars($lang['article002-paragraph-4-1'] ?? ''); ?>
                                            <br \>
                                            &emsp;&emsp;<?php echo htmlspecialchars($lang['article002-paragraph-4-2'] ?? ''); ?>
                                            <br \>
                                            &emsp;&emsp;<?php echo htmlspecialchars($lang['article002-paragraph-4-3'] ?? ''); ?>
                                            <br \>
                                            &emsp;&emsp;<?php echo htmlspecialchars($lang['article002-paragraph-4-4'] ?? ''); ?>
                                            <br \>
                                            <blockquote>
                                                &emsp;&emsp;<?php echo htmlspecialchars($lang['article002-subtitle-5'] ?? ''); ?>
                                            </blockquote>
                                            &emsp;&emsp;<?php echo htmlspecialchars($lang['article002-paragraph-5-1'] ?? ''); ?>
                                            <br \>
                                            &emsp;&emsp;<?php echo htmlspecialchars($lang['article002-paragraph-5-2'] ?? ''); ?>
                                            <br \>
                                            &emsp;&emsp;<?php echo htmlspecialchars($lang['article002-paragraph-5-3'] ?? ''); ?>
                                            <br \>
                                            <blockquote>
                                                &emsp;&emsp;<?php echo htmlspecialchars($lang['article002-subtitle-6'] ?? ''); ?>
                                            </blockquote>
                                            &emsp;&emsp;<?php echo htmlspecialchars($lang['article002-paragraph-6-1'] ?? ''); ?>
                                            <br \>
                                            &emsp;&emsp;<?php echo htmlspecialchars($lang['article002-paragraph-6-2'] ?? ''); ?>
                                            <br \>
                                            &emsp;&emsp;<?php echo htmlspecialchars($lang['article002-paragraph-6-3'] ?? ''); ?>
                                            <br \>
                                            &emsp;&emsp;<?php echo htmlspecialchars($lang['article002-paragraph-6-4'] ?? ''); ?>
                                            <br \>
                                            <blockquote>
                                                &emsp;&emsp;<?php echo htmlspecialchars($lang['article002-subtitle-7'] ?? ''); ?>
                                            </blockquote>
                                            &emsp;&emsp;<?php echo htmlspecialchars($lang['article002-paragraph-7-1'] ?? ''); ?>
                                            <br \>
                                            &emsp;&emsp;<?php echo htmlspecialchars($lang['article002-paragraph-7-2'] ?? ''); ?>
                                            <br \>
                                            &emsp;&emsp;<?php echo htmlspecialchars($lang['article002-paragraph-7-3'] ?? ''); ?>
                                            <br \>
                                            <blockquote>
                                                &emsp;&emsp;<?php echo htmlspecialchars($lang['article002-subtitle-8'] ?? ''); ?>
                                            </blockquote>
                                            &emsp;&emsp;<?php echo htmlspecialchars($lang['article002-paragraph-8-1'] ?? ''); ?>
                                            <br \>
                                            &emsp;&emsp;<?php echo htmlspecialchars($lang['article002-paragraph-8-2'] ?? ''); ?>
                                            <br \>
                                            &emsp;&emsp;<?php echo htmlspecialchars($lang['article002-paragraph-8-3'] ?? ''); ?>
                                            <br \>
                                            <blockquote>
                                                &emsp;&emsp;<?php echo htmlspecialchars($lang['article002-subtitle-9'] ?? ''); ?>
                                            </blockquote>
                                            &emsp;&emsp;<?php echo htmlspecialchars($lang['article002-paragraph-9-1'] ?? ''); ?>
                                            <br \>
                                            &emsp;&emsp;<?php echo htmlspecialchars($lang['article002-paragraph-9-2'] ?? ''); ?>
                                            <br \>
                                            &emsp;&emsp;<?php echo htmlspecialchars($lang['article002-paragraph-9-3'] ?? ''); ?>
                                            <br \>
                                            &emsp;&emsp;<?php echo htmlspecialchars($lang['article002-paragraph-9-4'] ?? ''); ?>
                                            <br \>
                                            </p>

                                            <!--article 003-->
                                            <hr
                                                style="border: none; border-top: 2px dashed #00bfae; margin: 2.5em 0; width: 80%;">

                                            <section id="article3">

                                                <div class="resume-block__header">
                                                    <h4 class="h3">
                                                        <?php echo htmlspecialchars($lang['article003-title'] ?? ''); ?>
                                                    </h4>
                                                    <p class="resume-block__header-meta">
                                                        <span><?php echo htmlspecialchars($lang['ID'] ?? ''); ?></span>
                                                        <span><?php echo htmlspecialchars($lang['article003-description'] ?? ''); ?></span>
                                                        <span class="resume-block__header-date">
                                                            July 14<sup>th</sup>, 2025 - Present
                                                        </span>
                                                    </p>
                                                </div>

                                                <p>
                                                    &emsp;&emsp;<?php echo htmlspecialchars($lang['article003-citation-1'] ?? ''); ?>
                                                </p>

                                                <h4>
                                                    <?php echo htmlspecialchars($lang['article003-subtitle-1'] ?? ''); ?>
                                                </h4>

                                                <p>
                                                <blockquote>
                                                    <?php echo htmlspecialchars($lang['article003-CPU-1'] ?? ''); ?>
                                                </blockquote>
                                                <ul>
                                                    &emsp;&emsp;<?php echo htmlspecialchars($lang['article003-CPU-1-1'] ?? ''); ?>
                                                    <br \><br \>
                                                    <p style="font-weight: bold">
                                                        <?php echo htmlspecialchars($lang['article003-CPU-1-2'] ?? ''); ?>
                                                    </p>
                                                    <li><?php echo htmlspecialchars($lang['article003-CPU-1-2-1'] ?? ''); ?>
                                                    </li>
                                                    <li><?php echo htmlspecialchars($lang['article003-CPU-1-2-2'] ?? ''); ?>
                                                    </li>
                                                    <li><?php echo htmlspecialchars($lang['article003-CPU-1-2-3'] ?? ''); ?>
                                                    </li>
                                                    <li><?php echo htmlspecialchars($lang['article003-CPU-1-2-4'] ?? ''); ?>
                                                    </li>
                                                    <li><?php echo htmlspecialchars($lang['article003-CPU-1-2-5'] ?? ''); ?>
                                                    </li>
                                                    <li><?php echo htmlspecialchars($lang['article003-CPU-1-2-6'] ?? ''); ?>
                                                    </li>
                                                    <br \><br \>
                                                    <p style="font-weight: bold">
                                                        <?php echo htmlspecialchars($lang['article003-CPU-1-3'] ?? ''); ?>
                                                    </p>
                                                    <li><?php echo htmlspecialchars($lang['article003-CPU-1-3-1'] ?? ''); ?>
                                                    </li>
                                                    <li><?php echo htmlspecialchars($lang['article003-CPU-1-3-2'] ?? ''); ?>
                                                    </li>
                                                    <li><?php echo htmlspecialchars($lang['article003-CPU-1-3-3'] ?? ''); ?>
                                                    </li>
                                                    <li><?php echo htmlspecialchars($lang['article003-CPU-1-3-4'] ?? ''); ?>
                                                    </li>
                                                    <li><?php echo htmlspecialchars($lang['article003-CPU-1-3-5'] ?? ''); ?>
                                                    </li>
                                                    <br \><br \>
                                                    <p style="font-weight: bold">
                                                        <?php echo htmlspecialchars($lang['article003-CPU-1-4'] ?? ''); ?>
                                                    </p>
                                                    <li><?php echo htmlspecialchars($lang['article003-CPU-1-4-1'] ?? ''); ?>
                                                    </li>
                                                    <li><?php echo htmlspecialchars($lang['article003-CPU-1-4-2'] ?? ''); ?>
                                                    </li>
                                                    <li><?php echo htmlspecialchars($lang['article003-CPU-1-4-3'] ?? ''); ?>
                                                    </li>
                                                    <li><?php echo htmlspecialchars($lang['article003-CPU-1-4-4'] ?? ''); ?>
                                                    </li>
                                                    <li><?php echo htmlspecialchars($lang['article003-CPU-1-4-5'] ?? ''); ?>
                                                    </li>
                                                    <li><?php echo htmlspecialchars($lang['article003-CPU-1-4-6'] ?? ''); ?>
                                                    </li>
                                                </ul>
                                                <blockquote>
                                                    <?php echo htmlspecialchars($lang['article003-GPU-1'] ?? ''); ?>
                                                </blockquote>
                                                <ul>
                                                    &emsp;&emsp;<?php echo htmlspecialchars($lang['article003-GPU-1-1'] ?? ''); ?>
                                                    <br \><br \>
                                                    <p style="font-weight: bold">
                                                        <?php echo htmlspecialchars($lang['article003-GPU-1-2'] ?? ''); ?>
                                                    </p>
                                                    <li><?php echo htmlspecialchars($lang['article003-GPU-1-2-1'] ?? ''); ?>
                                                    </li>
                                                    <li><?php echo htmlspecialchars($lang['article003-GPU-1-2-2'] ?? ''); ?>
                                                    </li>
                                                    <li><?php echo htmlspecialchars($lang['article003-GPU-1-2-3'] ?? ''); ?>
                                                    </li>
                                                    <li><?php echo htmlspecialchars($lang['article003-GPU-1-2-4'] ?? ''); ?>
                                                    </li>
                                                    <li><?php echo htmlspecialchars($lang['article003-GPU-1-2-5'] ?? ''); ?>
                                                    </li>
                                                    <li><?php echo htmlspecialchars($lang['article003-GPU-1-2-6'] ?? ''); ?>
                                                    </li>
                                                    <br \><br \>
                                                    <p style="font-weight: bold">
                                                        <?php echo htmlspecialchars($lang['article003-GPU-1-3'] ?? ''); ?>
                                                    </p>
                                                    <li><?php echo htmlspecialchars($lang['article003-GPU-1-3-1'] ?? ''); ?>
                                                    </li>
                                                    <li><?php echo htmlspecialchars($lang['article003-GPU-1-3-2'] ?? ''); ?>
                                                    </li>
                                                    <li><?php echo htmlspecialchars($lang['article003-GPU-1-3-3'] ?? ''); ?>
                                                    </li>
                                                    <li><?php echo htmlspecialchars($lang['article003-GPU-1-3-4'] ?? ''); ?>
                                                    </li>
                                                    <li><?php echo htmlspecialchars($lang['article003-GPU-1-3-5'] ?? ''); ?>
                                                    </li>
                                                    <br \><br \>
                                                    <p style="font-weight: bold">
                                                        <?php echo htmlspecialchars($lang['article003-GPU-1-4'] ?? ''); ?>
                                                    </p>
                                                    <li><?php echo htmlspecialchars($lang['article003-GPU-1-4-1'] ?? ''); ?>
                                                    </li>
                                                    <li><?php echo htmlspecialchars($lang['article003-GPU-1-4-2'] ?? ''); ?>
                                                    </li>
                                                    <li><?php echo htmlspecialchars($lang['article003-GPU-1-4-3'] ?? ''); ?>
                                                    </li>
                                                    <li><?php echo htmlspecialchars($lang['article003-GPU-1-4-4'] ?? ''); ?>
                                                    </li>
                                                    <li><?php echo htmlspecialchars($lang['article003-GPU-1-4-5'] ?? ''); ?>
                                                    </li>
                                                    <li><?php echo htmlspecialchars($lang['article003-GPU-1-4-6'] ?? ''); ?>
                                                    </li>
                                                    <br \><br \>
                                                    <p style="font-weight: bold">
                                                        <?php echo htmlspecialchars($lang['article003-GPU-1-5'] ?? ''); ?>
                                                    </p>
                                                    &emsp;&emsp;<?php echo htmlspecialchars($lang['article003-GPU-1-5-1'] ?? ''); ?>
                                                    <br \>
                                                    &emsp;&emsp;<?php echo htmlspecialchars($lang['article003-GPU-1-5-2'] ?? ''); ?>
                                                </ul>
                                                <blockquote>
                                                    <?php echo htmlspecialchars($lang['article003-Mb'] ?? ''); ?>
                                                </blockquote>
                                                <ul>
                                                    &emsp;&emsp;<?php echo htmlspecialchars($lang['article003-Mb-1'] ?? ''); ?>
                                                    <br \><br \>
                                                    <p style="font-weight: bold">
                                                        <?php echo htmlspecialchars($lang['article003-Mb-2'] ?? ''); ?>
                                                    </p>
                                                    <li><?php echo htmlspecialchars($lang['article003-Mb-2-1'] ?? ''); ?>
                                                    </li>
                                                    <li><?php echo htmlspecialchars($lang['article003-Mb-2-2'] ?? ''); ?>
                                                    </li>
                                                    <li><?php echo htmlspecialchars($lang['article003-Mb-2-3'] ?? ''); ?>
                                                    </li>
                                                    <li><?php echo htmlspecialchars($lang['article003-Mb-2-4'] ?? ''); ?>
                                                    </li>
                                                    <li><?php echo htmlspecialchars($lang['article003-Mb-2-5'] ?? ''); ?>
                                                    </li>
                                                    <li><?php echo htmlspecialchars($lang['article003-Mb-2-6'] ?? ''); ?>
                                                    </li>
                                                    <br \><br \>
                                                    <p style="font-weight: bold">
                                                        <?php echo htmlspecialchars($lang['article003-Mb-3'] ?? ''); ?>
                                                    </p>
                                                    <li><?php echo htmlspecialchars($lang['article003-Mb-3-1'] ?? ''); ?>
                                                    </li>
                                                    <li><?php echo htmlspecialchars($lang['article003-Mb-3-2'] ?? ''); ?>
                                                    </li>
                                                    <li><?php echo htmlspecialchars($lang['article003-Mb-3-3'] ?? ''); ?>
                                                    </li>
                                                    <li><?php echo htmlspecialchars($lang['article003-Mb-3-4'] ?? ''); ?>
                                                    </li>
                                                    <li><?php echo htmlspecialchars($lang['article003-Mb-3-5'] ?? ''); ?>
                                                    </li>
                                                    <li><?php echo htmlspecialchars($lang['article003-Mb-3-6'] ?? ''); ?>
                                                    </li>
                                                    <li><?php echo htmlspecialchars($lang['article003-Mb-3-7'] ?? ''); ?>
                                                    </li>
                                                    <li><?php echo htmlspecialchars($lang['article003-Mb-3-8'] ?? ''); ?>
                                                    </li>
                                                    <li><?php echo htmlspecialchars($lang['article003-Mb-3-9'] ?? ''); ?>
                                                    </li>
                                                    <br \><br \>
                                                    <p style="font-weight: bold">
                                                        <?php echo htmlspecialchars($lang['article003-Mb-4'] ?? ''); ?>
                                                    </p>
                                                    <li><?php echo htmlspecialchars($lang['article003-Mb-4-1'] ?? ''); ?>
                                                    </li>
                                                    <li><?php echo htmlspecialchars($lang['article003-Mb-4-2'] ?? ''); ?>
                                                    </li>
                                                    <li><?php echo htmlspecialchars($lang['article003-Mb-4-3'] ?? ''); ?>
                                                    </li>
                                                    <br \><br \>
                                                    <p style="font-weight: bold">
                                                        <?php echo htmlspecialchars($lang['article003-Mb-5'] ?? ''); ?>
                                                    </p>
                                                    <li><?php echo htmlspecialchars($lang['article003-Mb-5-1'] ?? ''); ?>
                                                    </li>
                                                    <li><?php echo htmlspecialchars($lang['article003-Mb-5-2'] ?? ''); ?>
                                                    </li>
                                                    <li><?php echo htmlspecialchars($lang['article003-Mb-5-3'] ?? ''); ?>
                                                    </li>
                                                    <li><?php echo htmlspecialchars($lang['article003-Mb-5-4'] ?? ''); ?>
                                                    </li>
                                                    <li><?php echo htmlspecialchars($lang['article003-Mb-5-5'] ?? ''); ?>
                                                    </li>
                                                    <li><?php echo htmlspecialchars($lang['article003-Mb-5-6'] ?? ''); ?>
                                                    </li>
                                                    <li><?php echo htmlspecialchars($lang['article003-Mb-5-7'] ?? ''); ?>
                                                    </li>
                                                </ul>
                                                <h3 style="text-align: center;">
                                                    <?php echo htmlspecialchars($lang['to-be-continued'] ?? ''); ?>
                                                </h3>
                                    </div> <!-- end resume-block -->
                                </div>

                            </div>

                            <section id="partnership">
                                <div class="row s-resume__section">
                                    <div class="column large-3 tab-12">
                                        <h3 class="section-header-allcaps">partnership</h3>
                                    </div>
                                    <div class="column large-9 tab-12">
                                        <div class="resume-block">

                                            <div class="resume-block__header">
                                                <h4 class="h3">Voident_Game</h4>
                                                <p class="resume-block__header-meta">
                                                    <span><?php echo htmlspecialchars($lang['voident-game'] ?? ''); ?></span>
                                                    <span class="resume-block__header-date">
                                                        Established in Jun, 2022
                                                    </span>
                                                </p>
                                            </div>

                                            <p>
                                                <?php echo htmlspecialchars($lang['voident-game-description'] ?? ''); ?>
                                            </p>

                                        </div> <!-- end resume-block -->

                                        <div class="resume-block">

                                            <div class="resume-block__header">
                                                <h4 class="h4">
                                                    <?php echo htmlspecialchars($lang['voident-game-about'] ?? ''); ?>
                                                </h4>
                                                <p class="resume-block__header-meta">
                                                    <span><?php echo htmlspecialchars($lang['voident-game'] ?? ''); ?></span>
                                                    <span class="resume-block__header-date">
                                                        Established in Jun, 2022
                                                    </span>
                                                </p>
                                            </div>

                                            <p>
                                                <?php echo htmlspecialchars($lang['voident-game-product1'] ?? ''); ?>
                                                <br \>
                                                <?php echo htmlspecialchars($lang['voident-game-product2'] ?? ''); ?>
                                            </p>

                                        </div> <!-- end resume-block -->
                                    </div>
                                </div> <!-- s-resume__section -->

                                <section id="language">
                                    <div class="row s-resume__section">
                                        <div class="column large-3 tab-12">
                                            <h3 class="section-header-allcaps">language<br \>localization</h3>
                                        </div>
                                        <div class="column large-9 tab-12">
                                            <div class="resume-block">

                                                <p>
                                                    <?php echo htmlspecialchars($lang['translation-description'] ?? ''); ?>
                                                </p>

                                                <ul class="skill-bars-fat">
                                                    <li>
                                                        <div class="progress percent100"></div>
                                                        <strong>
                                                            简体中文/people's republic of china
                                                        </strong>
                                                    </li>
                                                    <li>
                                                        <div class="progress percent90"></div>
                                                        <strong>繁體中文/hong kong, taiwan, macau (PRC)</strong>
                                                    </li>
                                                    <li>
                                                        <div class="progress percent85"></div>
                                                        <strong>
                                                            english/united states
                                                        </strong>
                                                    </li>
                                                    <li>
                                                        <div class="progress percent75"></div>
                                                        <strong>Русский язык/russian federation</strong>
                                                    </li>
                                                    <li>
                                                        <div class="progress percent5"></div>
                                                        <strong>Deutsch/germany</strong>
                                                    </li>
                                                    <li>
                                                        <div class="progress percent75"></div>
                                                        <strong>日本語/japan</strong>
                                                    </li>
                                                    <li>
                                                        <div class="progress percent5"></div>
                                                        <strong>한국어/republic of korea</strong>
                                                    </li>
                                                    <li>
                                                        <div class="progress percent65"></div>
                                                        <strong>uygur/xinjiang, PRC</strong>
                                                    </li>
                                                </ul>

                                            </div> <!-- end resume-block -->

                                        </div>
                                    </div> <!-- s-resume__section -->

                                </section> <!-- end s-resume -->


                                <!-- portfolio
                                    ===================== -->
                                <section id="portfolio" class="s-portfolio target-section">
                                    <section id="photos">

                                        <div class="row s-portfolio__header">
                                            <div class="column large-12">
                                                <h3><?php echo htmlspecialchars($lang['photo-title'] ?? ''); ?>
                                                </h3>
                                            </div>
                                        </div>

                                        <div
                                            class="row collapse block-large-1-4 block-medium-1-3 block-tab-1-2 block-500-stack folio-list">

                                            <div class="column folio-item">
                                                <a href="#modal-01" class="folio-item__thumb">
                                                    <img src="https://cdn.z.wiki/autoupload/20250529/zftP/964X964/laifu.jpg"
                                                        srcset="https://cdn.z.wiki/autoupload/20250529/zftP/964X964/laifu.jpg"
                                                        alt="图片未加载，请联系网站管理员。">
                                                </a>
                                            </div> <!-- end folio-item -->

                                            <div class="column folio-item">
                                                <a href="#modal-02" class="folio-item__thumb">
                                                    <img src="https://2.z.wiki/autoupload/20250529/tk6C/2679X2679/7430562FEACC6D1C7AE435774265C090.png"
                                                        srcset="https://2.z.wiki/autoupload/20250529/tk6C/2679X2679/7430562FEACC6D1C7AE435774265C090.png"
                                                        alt="图片未加载，请联系网站管理员。">
                                                </a>
                                            </div> <!-- end folio-item -->

                                            <div class="column folio-item">
                                                <a href="#modal-03" class="folio-item__thumb">
                                                    <img src="https://2.z.wiki/autoupload/20250529/5jzL/3024X3024/D899347890A7B9B8AE0587A353697366.jpg"
                                                        srcset="https://2.z.wiki/autoupload/20250529/5jzL/3024X3024/D899347890A7B9B8AE0587A353697366.jpg"
                                                        alt="图片未加载，请联系网站管理员。">
                                                </a>
                                            </div> <!-- end folio-item -->

                                            <div class="column folio-item">
                                                <a href="#modal-04" class="folio-item__thumb">
                                                    <img src="https://2.z.wiki/autoupload/20250529/WGGJ/828X828/thunder.jpg"
                                                        srcset="https://2.z.wiki/autoupload/20250529/WGGJ/828X828/thunder.jpg"
                                                        alt="图片未加载，请联系网站管理员。">
                                                </a>
                                            </div> <!-- end folio-item -->

                                        </div> <!-- end folio-list -->


                                        <!-- Modal Templates Popup
                                                    == -->
                                        <div id="modal-01" hidden>
                                            <div class="modal-popup">
                                                <img src="https://cdn.z.wiki/autoupload/20250529/zftP/964X964/laifu.jpg"
                                                    alt="来福" />

                                                <div class="modal-popup__desc">
                                                    <h5><?php echo htmlspecialchars($lang['laifu'] ?? ''); ?>
                                                    </h5>
                                                    <p><?php echo htmlspecialchars($lang['laifu-description'] ?? ''); ?>
                                                    </p>
                                                    <ul class="modal-popup__cat">
                                                        <li><?php echo htmlspecialchars($lang['laifu-time'] ?? ''); ?>
                                                        </li>
                                                        <li><?php echo htmlspecialchars($lang['laifu-type'] ?? ''); ?>
                                                        </li>
                                                        <li><?php echo htmlspecialchars($lang['laifu-localtion'] ?? ''); ?>
                                                        </li>
                                                    </ul>
                                                </div>

                                            </div>
                                        </div> <!-- end modal -->

                                        <div id="modal-02" hidden>
                                            <div class="modal-popup">
                                                <img src="https://2.z.wiki/autoupload/20250529/tk6C/2679X2679/7430562FEACC6D1C7AE435774265C090.png"
                                                    alt="" />

                                                <div class="modal-popup__desc">
                                                    <h5><?php echo htmlspecialchars($lang['xianmi-national'] ?? ''); ?>
                                                    </h5>
                                                    <p><?php echo htmlspecialchars($lang['xianmi-description'] ?? ''); ?>
                                                    </p>
                                                    <ul class="modal-popup__cat">
                                                        <li><?php echo htmlspecialchars($lang['xianmi-time'] ?? ''); ?>
                                                        </li>
                                                        <li><?php echo htmlspecialchars($lang['xianmi-type'] ?? ''); ?>
                                                        </li>
                                                        <li><?php echo htmlspecialchars($lang['xianmi-location'] ?? ''); ?>
                                                        </li>
                                                    </ul>
                                                </div>

                                            </div>
                                        </div> <!-- end modal -->

                                        <div id="modal-03" hidden>
                                            <div class="modal-popup">
                                                <img src="https://2.z.wiki/autoupload/20250529/5jzL/3024X3024/D899347890A7B9B8AE0587A353697366.jpg"
                                                    alt="" />

                                                <div class="modal-popup__desc">
                                                    <h5><?php echo htmlspecialchars($lang['taklamakan-desert'] ?? ''); ?>
                                                    </h5>
                                                    <p><?php echo htmlspecialchars($lang['taklamakan-description'] ?? ''); ?>
                                                    </p>
                                                    <ul class="modal-popup__cat">
                                                        <li><?php echo htmlspecialchars($lang['taklamakan-time'] ?? ''); ?>
                                                        </li>
                                                        <li><?php echo htmlspecialchars($lang['taklamakan-type'] ?? ''); ?>
                                                        </li>
                                                        <li><?php echo htmlspecialchars($lang['taklamakan-location'] ?? ''); ?>
                                                        </li>
                                                    </ul>
                                                </div>

                                            </div>
                                        </div> <!-- end modal -->

                                        <div id="modal-04" hidden>
                                            <div class="modal-popup">
                                                <img src="https://2.z.wiki/autoupload/20250529/WGGJ/828X828/thunder.jpg"
                                                    alt="" />

                                                <div class="modal-popup__desc">
                                                    <h5><?php echo htmlspecialchars($lang['thunder'] ?? ''); ?>
                                                    </h5>
                                                    <p><?php echo htmlspecialchars($lang['thunder-description'] ?? ''); ?>
                                                    </p>
                                                    <ul class="modal-popup__cat">
                                                        <li><?php echo htmlspecialchars($lang['thunder-time'] ?? ''); ?>
                                                        </li>
                                                        <li><?php echo htmlspecialchars($lang['thunder-type'] ?? ''); ?>
                                                        </li>
                                                        <li><?php echo htmlspecialchars($lang['thunder-location'] ?? ''); ?>
                                                        </li>
                                                    </ul>
                                                </div>

                                            </div>
                                        </div> <!-- end modal -->

                                    </section> <!-- end s-portfolio -->
                                </section>
    </div>


    <!-- testimonials
        ================================================== -->
    <section id="testimonials" class="s-testimonials target-section">

        <div class="s-testimonials__bg"></div>

        <div class="row s-testimonials__header">
            <div class="column large-12">
                <h3><?php echo htmlspecialchars($lang['contributors'] ?? ''); ?></h3>
            </div>
        </div>

        <div class="row s-testimonials__content">

            <div class="column">

                <div class="swiper-container testimonial-slider">

                    <div class="swiper-wrapper">

                        <div class="testimonial-slider__slide swiper-slide">
                            <div class="testimonial-slider__author">
                                <img src="http://q1.qlogo.cn/g?b=qq&nk=3329261270&s=100" alt="Author image"
                                    class="testimonial-slider__avatar">
                                <cite class="testimonial-slider__cite">
                                    <strong>Entertainment_YH</strong>
                                    <span><?php echo htmlspecialchars($lang['yh-position'] ?? ''); ?></span>
                                </cite>
                            </div>
                            <p>
                                <?php echo htmlspecialchars($lang['yh-description'] ?? ''); ?>
                            </p>
                        </div> <!-- end testimonial-slider__slide -->

                        <div class="testimonial-slider__slide swiper-slide">
                            <div class="testimonial-slider__author">
                                <img src="http://q1.qlogo.cn/g?b=qq&nk=439751420&s=100" alt="Author image"
                                    class="testimonial-slider__avatar">
                                <cite class="testimonial-slider__cite">
                                    <strong>QN</strong>
                                    <span><?php echo htmlspecialchars($lang['qn-position'] ?? ''); ?></span>
                                </cite>
                            </div>
                            <p>
                                <?php echo htmlspecialchars($lang['qn-description'] ?? ''); ?>
                            </p>
                        </div> <!-- end testimonial-slider__slide -->

                        <div class="testimonial-slider__slide swiper-slide">
                            <div class="testimonial-slider__author">
                                <img src="https://2.z.wiki/autoupload/20250529/cxZ5/1024X1024/fallen-star.jpeg"
                                    alt="Author image" class="testimonial-slider__avatar">
                                <cite class="testimonial-slider__cite">
                                    <strong><?php echo htmlspecialchars($lang['fallen-star-name'] ?? ''); ?></strong>
                                    <span><?php echo htmlspecialchars($lang['fallen-star-position'] ?? ''); ?></span>
                                </cite>
                            </div>
                            <p>
                                <?php echo htmlspecialchars($lang['fallen-star-description'] ?? ''); ?>
                            </p>
                        </div> <!-- end testimonial-slider__slide -->

                        <div class="testimonial-slider__slide swiper-slide">
                            <div class="testimonial-slider__author">
                                <img src="https://cdn.z.wiki/autoupload/20250529/3zZ6/474X483/you.png"
                                    alt="Author image" class="testimonial-slider__avatar">
                                <cite class="testimonial-slider__cite">
                                    <strong><?php echo htmlspecialchars($lang['you-name'] ?? ''); ?></strong>
                                    <span><?php echo htmlspecialchars($lang['you-position'] ?? ''); ?></span>
                                </cite>
                            </div>
                            <p>
                                <?php echo htmlspecialchars($lang['you-description'] ?? ''); ?>
                            </p>
                        </div> <!-- end testimonial-slider__slide -->

                    </div> <!-- end testimonial slider swiper-wrapper -->

                    <div class="swiper-pagination"></div>

                </div> <!-- end swiper-container -->

            </div> <!-- end column -->

        </div> <!-- end row -->
        </div> <!-- Add this closing div for the row -->

    </section> <!-- end s-testimonials -->


    <!-- footer
        ================================================== -->
    <section id="footer">
        <footer class="s-footer">
            <div class="row">
                <div class="column large-4 medium-6 w-1000-stack s-footer__social-block">
                    <ul class="s-footer__social">
                        <li><a href="https://space.bilibili.com/1977333915?spm_id_from=333.1007.0.0"
                                title="Bilibili主页"><i class="fa-brands fa-bilibili" aria-hidden="true"></i></a></li>
                        <li><a href="https://github.com/EntertainmentYH/yhentertainment.com" title="GitHub主页"><i
                                    class="fa-brands fa-square-github" aria-hidden="true"></i></a></li>
                        <li><a href="https://steamcommunity.com/id/Entertainment_YH/" title="Steam主页"><i
                                    class="fab fa-steam" aria-hidden="true"></i></a></li>
                        <li><a href="https://www.youtube.com/@Entertainment_CHINESE" title="YouTube频道"><i
                                    class="fab fa-youtube" aria-hidden="true"></i></a></li>
                        <li><a href="https://x.com/Entertainm15252" title="X (Twitter)主页"><i
                                    class="fab fa-square-x-twitter" aria-hidden="true"></i></a></li>
                    </ul>
                </div>

                <div class="column large-7 medium-6 w-1000-stack ss-copyright">
                    <span><?php echo htmlspecialchars($lang['copyright'] ?? ''); ?></span>
                    <span><a target="_blank" href=""
                            title="备案号"><?php echo htmlspecialchars($lang['ICP'] ?? ''); ?></a></span>
                </div>

                <div class="column large-12 medium-8 w-1000-stack ss-statistics">
                    <span>
                        <?php echo htmlspecialchars($lang['today-statistics'] ?? ''); ?> <span
                            id="today_count"><?php echo $today_count; ?></span>
                        <?php echo htmlspecialchars($lang['today-statistics-people'] ?? ''); ?>
                    </span>
                    <span>
                        <?php echo htmlspecialchars($lang['total-statistics'] ?? ''); ?> <span
                            id="total_count"><?php echo $total_count; ?></span>
                        <?php echo htmlspecialchars($lang['total-statistics-people'] ?? ''); ?>
                    </span>
                    <span>
                        <?php echo htmlspecialchars($lang['online-statistics'] ?? ''); ?> <span
                            id="online_count"><?php echo $online_count; ?></span>
                        <?php echo htmlspecialchars($lang['online-statistics-people'] ?? ''); ?>
                    </span>
                    <span>
                        <?php echo htmlspecialchars($lang['site-days'] ?? ''); ?> <span
                            id="site_days"><?php echo $site_days; ?></span>
                        <?php echo htmlspecialchars($lang['days'] ?? ''); ?>
                    </span>
                </div>


                <div class="ss-go-top">
                    <a class="smoothscroll" title="Back to Top" href="#top">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                            <path d="M6 4h12v2H6zm5 10v6h2v-6h5l-6-6-6 6z" />
                        </svg>
                    </a>
                </div> <!-- end ss-go-top -->

        </footer>

        <!-- script
        ================================================== -->
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/swiper@9/swiper-bundle.min.js"></script>
        <script src="js/plugins.js"></script>
        <script src="js/main.js"></script>
        <script src="js/statistics-chart.js"></script>
        <script>
            window.chartLang = {
                daily: "<?php echo htmlspecialchars($lang['statistics-daily'] ?? '每日访问量'); ?>",
                date: "<?php echo htmlspecialchars($lang['statistics-date'] ?? '日期'); ?>",
                count: "<?php echo htmlspecialchars($lang['statistics-count'] ?? '访问人数'); ?>"
            };
        </script>

        <!-- 右侧目录导航 -->
        <nav id="side-toc">
            <div style="font-weight:bold;font-size:18px;margin-bottom:12px;text-align:center;">
                <?php echo htmlspecialchars($lang['directory'] ?? ''); ?>
            </div>
            <ul>
                <li><a href="#body" class=""><?php echo htmlspecialchars($lang['body'] ?? ''); ?></a>
                    <ul>
                        <li><a href="#vote" class=""><?php echo htmlspecialchars($lang['vote'] ?? ''); ?></a></li>
                        <li><a href="#statistics"
                                class=""><?php echo htmlspecialchars($lang['statistics'] ?? ''); ?></a>
                        </li>
                        <li><a href="#announcement"
                                class=""><?php echo htmlspecialchars($lang['announcement'] ?? ''); ?></a></li>
                        <li><a href="#post" class=""><?php echo htmlspecialchars($lang['post'] ?? ''); ?></a></li>
                        <li><a href="#utilities" class=""><?php echo htmlspecialchars($lang['utilities'] ?? ''); ?></a>
                        </li>
                        <li><a href="#article" class=""><?php echo htmlspecialchars($lang['article'] ?? ''); ?></a>
                            <ul>
                                <li><a href="#article1"
                                        class=""><?php echo htmlspecialchars($lang['article1'] ?? ''); ?></a></li>
                                <li><a href="#article2"
                                        class=""><?php echo htmlspecialchars($lang['article2'] ?? ''); ?></a></li>
                                <li><a href="#article3"
                                        class=""><?php echo htmlspecialchars($lang['article3'] ?? ''); ?></a></li>
                            </ul>
                        </li>
                        <li><a href="#partnership"
                                class=""><?php echo htmlspecialchars($lang['partnership'] ?? ''); ?></a>
                        </li>
                        <li><a href="#language" class=""><?php echo htmlspecialchars($lang['language'] ?? ''); ?></a>
                        </li>
                        <li><a href="#photos" class=""><?php echo htmlspecialchars($lang['photos'] ?? ''); ?></a>
                        </li>
                    </ul>
                </li>
            </ul>
        </nav>

</body>

</html>