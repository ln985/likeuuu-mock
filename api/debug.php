<?php
/**
 * 诊断脚本 - 检查虚拟主机是否支持路由
 * 上传后访问: https://你的域名/api/debug.php
 */

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html><head><meta charset="utf-8"><title>诊断</title>
<style>body{font-family:sans-serif;max-width:600px;margin:40px auto;padding:0 20px}
.pass{color:#52c41a}.fail{color:#ff4d4f}.warn{color:#faad14}
code{background:#f5f5f5;padding:2px 6px;border-radius:3px}</style>
</head><body>
<h2>🔍 likeuuu Mock Server 环境诊断</h2>
<pre>
<?php
$tests = [];

// PHP 版本
$phpVer = PHP_VERSION;
$tests[] = ['PHP 版本', version_compare($phpVer, '7.4', '>='), "$phpVer (需要 ≥ 7.4)"];

// JSON 支持
$tests[] = ['JSON 支持', function_exists('json_encode'), ''];

// 目录写入权限
$dataDir = __DIR__ . '/../data';
$canWrite = is_dir($dataDir) ? is_writable($dataDir) : @mkdir($dataDir, 0755, true);
$tests[] = ['data/ 目录可写', $canWrite, $dataDir];

// mod_rewrite
$rewrite = function_exists('apache_get_modules') ? in_array('mod_rewrite', apache_get_modules()) : null;
if ($rewrite === null) {
    // 无法检测，尝试通过 .htaccess 测试
    $htaccess = file_get_contents(__DIR__ . '/.htaccess');
    $rewrite = $htaccess && strpos($htaccess, 'RewriteEngine') !== false;
}
$tests[] = ['mod_rewrite', $rewrite, $rewrite === true ? '可用' : ($rewrite === false ? '不可用' : '未知')];

// PATH_INFO
$tests[] = ['PATH_INFO 支持', isset($_SERVER['PATH_INFO']) || ini_get('cgi.fix_pathinfo'), ''];

// MultiViews
$tests[] = ['MultiViews', '需要在 .htaccess 中测试', ''];

// 当前 URI 信息
echo "\n--- 环境信息 ---\n";
echo "REQUEST_URI: " . ($_SERVER['REQUEST_URI'] ?? 'N/A') . "\n";
echo "SCRIPT_NAME: " . ($_SERVER['SCRIPT_NAME'] ?? 'N/A') . "\n";
echo "PATH_INFO: " . ($_SERVER['PATH_INFO'] ?? 'N/A') . "\n";
echo "DOCUMENT_ROOT: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'N/A') . "\n";
echo "PHP Handler: " . (php_sapi_name()) . "\n";

echo "\n--- 测试结果 ---\n";
foreach ($tests as [$name, $ok, $detail]) {
    $icon = $ok ? '✅' : '❌';
    echo "$icon $name";
    if ($detail) echo " ($detail)";
    echo "\n";
}

// API 测试
echo "\n--- API 测试 ---\n";
$apiTests = [
    '/api/health.php' => '健康检查',
    '/api/app/config.php' => '应用配置',
    '/api/app/announcement/active/index.php' => '公告列表',
    '/api/location/index.php' => '位置列表',
];

$proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';

foreach ($apiTests as $path => $desc) {
    $url = "$proto://$host$path";
    $ctx = stream_context_create(['http' => ['timeout' => 5]]);
    $result = @file_get_contents($url, false, $ctx);
    $ok = $result && json_decode($result);
    echo ($ok ? '✅' : '❌') . " $desc → $path\n";
}

// 建议
echo "\n--- 建议 ---\n";
if (!$rewrite) {
    echo "⚠️  mod_rewrite 可能不可用\n";
    echo "   解决方案:\n";
    echo "   1. 联系虚拟主机商开启 mod_rewrite\n";
    echo "   2. 使用带 .php 后缀的 URL (需要修改 DEX 补丁)\n";
    echo "   3. 直接访问完整路径如 /api/app/config.php\n";
}
?>
</pre>
<p><a href="health.php">测试健康检查</a> | <a href="app/config.php">测试配置</a> | <a href="app/announcement/active/index.php">测试公告</a> | <a href="location/index.php">测试位置</a></p>
</body></html>
