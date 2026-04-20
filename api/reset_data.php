<?php
/**
 * 一键修复脚本 - 运行一次后删除此文件
 * 访问: http://dl.wzydqq.icu/reset_data.php
 */

$dir = __DIR__ . '/data';
$deleted = [];

// 1. 删除旧的 config.json（让它用新的默认值重新生成）
$configFile = $dir . '/config.json';
if (file_exists($configFile)) {
    unlink($configFile);
    $deleted[] = 'config.json (mainUrl 将重新生成为 http://dl.wzydqq.icu/)';
}

// 2. 删除旧的 regions.json（新的有经纬度数据）
$regionsFile = $dir . '/regions.json';
if (file_exists($regionsFile)) {
    unlink($regionsFile);
    $deleted[] = 'regions.json (将重新生成，含经纬度)';
}

// 3. 验证新配置
require_once __DIR__ . '/inc.php';

$newConfig = loadJson('config.json');
$newRegions = loadJson('regions.json');

header('Content-Type: text/html; charset=utf-8');
echo "<h2>🔧 数据修复完成</h2>";

if ($deleted) {
    echo "<p>已删除旧文件:</p><ul>";
    foreach ($deleted as $f) echo "<li>❌ $f</li>";
    echo "</ul>";
} else {
    echo "<p>没有旧文件需要删除</p>";
}

echo "<h3>✅ 新配置验证</h3>";
echo "<p><b>mainUrl:</b> " . ($newConfig['linkConfig']['mainUrl'] ?? 'N/A') . "</p>";

if (isset($newRegions[0]['lat'])) {
    echo "<p><b>地区数据:</b> ✅ 已包含经纬度</p>";
    echo "<p>示例: {$newRegions[0]['name']} lat={$newRegions[0]['lat']} lng={$newRegions[0]['lng']}</p>";
} else {
    echo "<p><b>地区数据:</b> ❌ 仍然缺少经纬度，请检查 inc.php</p>";
}

echo "<hr><p>⚠️ <b>请删除此文件 reset_data.php！</b> 不要留在服务器上。</p>";
