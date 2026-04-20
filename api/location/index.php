<?php
// GET /api/location          → 省份列表
// GET /api/location?adcode=X → 行政区划详情
// GET /api/location/X        → 行政区划详情 (通过 .htaccess 重写)
require_once dirname(__DIR__, 2) . '/inc.php';

$adcode = $_GET['adcode'] ?? null;

if (!$adcode) {
    $pathInfo = $_SERVER['PATH_INFO'] ?? '';
    if (preg_match('#^/(\d+)$#', $pathInfo, $m)) {
        $adcode = $m[1];
    }
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
    $provinces = array_map(fn($r) => [
        'adcode' => $r['adcode'],
        'name' => $r['name'],
        'lat' => $r['lat'] ?? null,
        'lng' => $r['lng'] ?? null,
    ], $regions);
    jsonResponse(['status' => 'ok', 'data' => ['provinces' => $provinces]]);
}

$chain = findParentChain($regions, $adcode);
if (!$chain) {
    jsonResponse(['status' => 'error', 'message' => "未找到: $adcode"], 404);
}

// 获取当前匹配节点的经纬度
$matchedNode = null;
if ($chain['district']) {
    $matchedNode = $chain['district'];
} elseif ($chain['city']) {
    $matchedNode = $chain['city'];
} elseif ($chain['province']) {
    $matchedNode = $chain['province'];
}

jsonResponse(['status' => 'ok', 'data' => [
    'adcode' => $adcode,
    'provinceName' => $chain['province']['name'] ?? '',
    'provinceAdcode' => $chain['province']['adcode'] ?? '',
    'cityName' => $chain['city']['name'] ?? '',
    'cityAdcode' => $chain['city']['adcode'] ?? '',
    'districtName' => $chain['district']['name'] ?? '',
    'lat' => $matchedNode['lat'] ?? null,
    'lng' => $matchedNode['lng'] ?? null,
]]);
