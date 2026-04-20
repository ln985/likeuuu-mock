<?php
// GET /api/location          → 省份列表
// GET /api/location?adcode=X → 行政区划详情
// GET /api/location/X        → 行政区划详情 (通过 .htaccess 重写)
require_once dirname(__DIR__, 3) . '/inc.php';

// 尝试从 PATH_INFO 或 URI 获取 adcode
$adcode = $_GET['adcode'] ?? null;

if (!$adcode) {
    // 检查 PATH_INFO: /api/location/440305 → PATH_INFO = /440305
    $pathInfo = $_SERVER['PATH_INFO'] ?? '';
    if (preg_match('#^/(\d+)$#', $pathInfo, $m)) {
        $adcode = $m[1];
    }
    // 检查 REQUEST_URI: /api/location/440305
    if (!$adcode) {
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        if (preg_match('#/api/location/(\d+)/?$#', $uri, $m)) {
            $adcode = $m[1];
        }
    }
}

$regions = loadJson('regions.json');
if (!$regions) {
    $regions = getDefaultRegions();
    saveJson('regions.json', $regions);
}

if (!$adcode) {
    // 返回省份列表
    $provinces = array_map(fn($r) => ['adcode' => $r['adcode'], 'name' => $r['name']], $regions);
    jsonResponse(['status' => 'ok', 'data' => ['provinces' => $provinces]]);
}

$chain = findParentChain($regions, $adcode);
if (!$chain) {
    jsonResponse(['status' => 'error', 'message' => "未找到: $adcode"], 404);
}

jsonResponse(['status' => 'ok', 'data' => [
    'adcode' => $adcode,
    'provinceName' => $chain['province']['name'] ?? '',
    'provinceAdcode' => $chain['province']['adcode'] ?? '',
    'cityName' => $chain['city']['name'] ?? '',
    'cityAdcode' => $chain['city']['adcode'] ?? '',
    'districtName' => $chain['district']['name'] ?? '',
]]);
