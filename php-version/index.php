<?php
/**
 * likeuuu Mock Server - PHP版
 * 
 * 部署方式:
 *   1. 创建子域名 zq.你的域名.com
 *   2. 把本目录所有文件上传到子域名根目录
 *   3. 把 classes2.dex 中的 https://zq.likeuuu.top/ 替换为 https://zq.你的域名.com/
 *      (注意替换后的 URL 不能超过 23 字节)
 * 
 * 上传结构:
 *   ├── .htaccess           ← URL 重写
 *   ├── index.php           ← 入口文件
 *   ├── config.json         ← 首次访问自动生成
 *   ├── announcements.json  ← 首次访问自动生成
 *   ├── regions.json        ← 首次访问自动生成
 *   ├── admin.php           ← 管理面板 (可选)
 *   └── data/               ← 数据存储目录 (需要可写权限)
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// ============================================================
// 路由解析
// ============================================================
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];
$query = $_GET;

log_request($method, $uri, $query);

// ============================================================
// 路由分发
// ============================================================

// GET /api/app/config
if ($method === 'GET' && preg_match('#/api/app/config/?$#', $uri)) {
    jsonResponse(getAppConfig());
}
// GET /api/app/announcement/active
elseif ($method === 'GET' && preg_match('#/api/app/announcement/active/?$#', $uri)) {
    $limit = isset($query['limit']) ? intval($query['limit']) : null;
    jsonResponse(getActiveAnnouncements($limit));
}
// GET /api/location?adcode=XXX
elseif ($method === 'GET' && preg_match('#/api/location/?$#', $uri)) {
    $adcode = isset($query['adcode']) ? $query['adcode'] : null;
    jsonResponse(getLocation($adcode));
}
// GET /api/location/:adcode
elseif ($method === 'GET' && preg_match('#/api/location/(\d+)/?$#', $uri, $matches)) {
    jsonResponse(getLocation($matches[1]));
}
// GET /api/location/provinces
elseif ($method === 'GET' && preg_match('#/api/location/provinces/?$#', $uri)) {
    jsonResponse(getProvinces());
}
// GET /api/location/cities
elseif ($method === 'GET' && preg_match('#/api/location/cities/?$#', $uri)) {
    $provinceAdcode = isset($query['provinceAdcode']) ? $query['provinceAdcode'] : null;
    jsonResponse(getCities($provinceAdcode));
}
// GET /api/location/districts
elseif ($method === 'GET' && preg_match('#/api/location/districts/?$#', $uri)) {
    $cityAdcode = isset($query['cityAdcode']) ? $query['cityAdcode'] : null;
    jsonResponse(getDistricts($cityAdcode));
}
// GET /api/health
elseif ($method === 'GET' && preg_match('#/api/health/?$#', $uri)) {
    jsonResponse(['status' => 'ok', 'time' => date('c')]);
}
// POST /api/admin/announcement
elseif ($method === 'POST' && preg_match('#/api/admin/announcement/?$#', $uri)) {
    $input = json_decode(file_get_contents('php://input'), true);
    jsonResponse(addAnnouncement($input));
}
// DELETE /api/admin/announcement/:id
elseif ($method === 'DELETE' && preg_match('#/api/admin/announcement/(\d+)/?$#', $uri, $matches)) {
    jsonResponse(deleteAnnouncement(intval($matches[1])));
}
// GET /api/admin/announcements
elseif ($method === 'GET' && preg_match('#/api/admin/announcements/?$#', $uri)) {
    jsonResponse(getAllAnnouncements());
}
else {
    http_response_code(404);
    jsonResponse(['status' => 'error', 'message' => 'Not Found: ' . $uri], 404);
}

// ============================================================
// 数据读写
// ============================================================

function getDataDir() {
    $dir = __DIR__ . '/data';
    if (!is_dir($dir)) @mkdir($dir, 0755, true);
    return $dir;
}

function loadJson($filename) {
    // 优先从根目录读取 (方便直接编辑)
    $rootPath = __DIR__ . '/' . $filename;
    $dataPath = getDataDir() . '/' . $filename;
    
    if (file_exists($rootPath)) return json_decode(file_get_contents($rootPath), true);
    if (file_exists($dataPath)) return json_decode(file_get_contents($dataPath), true);
    return null;
}

function saveJson($filename, $data) {
    // 保存到 data 目录
    $path = getDataDir() . '/' . $filename;
    file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

// ============================================================
// API 逻辑
// ============================================================

function getAppConfig() {
    $config = loadJson('config.json');
    if (!$config) {
        $config = [
            'linkConfig' => [
                'mainUrl' => 'https://zq.likeuuu.top/'
            ],
            'appLinkConfig' => [
                'communityGroup' => 'https://qm.qq.com/cgi-bin/qm/qr?k=example123456'
            ]
        ];
        saveJson('config.json', $config);
    }
    return ['status' => 'ok', 'data' => $config];
}

function getActiveAnnouncements($limit = null) {
    $announcements = loadJson('announcements.json');
    if (!$announcements) {
        $announcements = getDefaultAnnouncements();
        saveJson('announcements.json', $announcements);
    }

    $active = array_values(array_filter($announcements, function($a) {
        return $a['active'];
    }));
    usort($active, function($a, $b) {
        return $a['order'] - $b['order'];
    });

    if ($limit && $limit > 0) {
        $active = array_slice($active, 0, $limit);
    }

    return ['status' => 'ok', 'data' => $active];
}

function getAllAnnouncements() {
    $announcements = loadJson('announcements.json');
    if (!$announcements) {
        $announcements = getDefaultAnnouncements();
        saveJson('announcements.json', $announcements);
    }
    return ['status' => 'ok', 'data' => $announcements];
}

function addAnnouncement($input) {
    $announcements = loadJson('announcements.json');
    if (!$announcements) $announcements = [];

    $maxId = 0;
    foreach ($announcements as $a) {
        if ($a['id'] > $maxId) $maxId = $a['id'];
    }

    $now = date('c');
    $new = [
        'id' => $maxId + 1,
        'type' => $input['type'] ?? 'NORMAL',
        'title' => $input['title'] ?? '',
        'content' => $input['content'] ?? '',
        'active' => true,
        'isPopup' => $input['isPopup'] ?? false,
        'order' => $input['order'] ?? ($maxId + 1),
        'startTime' => $input['startTime'] ?? $now,
        'endTime' => $input['endTime'] ?? '2099-12-31T23:59:59Z',
        'createdAt' => $now,
        'updatedAt' => $now,
    ];

    $announcements[] = $new;
    saveJson('announcements.json', $announcements);
    return ['status' => 'ok', 'data' => $new];
}

function deleteAnnouncement($id) {
    $announcements = loadJson('announcements.json');
    if (!$announcements) return ['status' => 'error', 'message' => '公告不存在'];

    $found = false;
    foreach ($announcements as $k => $a) {
        if ($a['id'] === $id) {
            unset($announcements[$k]);
            $found = true;
            break;
        }
    }
    if (!$found) return ['status' => 'error', 'message' => '公告不存在'];

    $announcements = array_values($announcements);
    saveJson('announcements.json', $announcements);
    return ['status' => 'ok', 'message' => '已删除'];
}

function getLocation($adcode = null) {
    $regions = loadJson('regions.json');
    if (!$regions) {
        $regions = getDefaultRegions();
        saveJson('regions.json', $regions);
    }

    if (!$adcode) {
        $provinces = array_map(function($r) {
            return ['adcode' => $r['adcode'], 'name' => $r['name']];
        }, $regions);
        return ['status' => 'ok', 'data' => ['provinces' => $provinces]];
    }

    $chain = findParentChain($regions, $adcode);
    if (!$chain) {
        return ['status' => 'error', 'message' => "未找到行政区划代码: $adcode"];
    }

    return ['status' => 'ok', 'data' => [
        'adcode' => $adcode,
        'provinceName' => $chain['province'] ? $chain['province']['name'] : '',
        'provinceAdcode' => $chain['province'] ? $chain['province']['adcode'] : '',
        'cityName' => $chain['city'] ? $chain['city']['name'] : '',
        'cityAdcode' => $chain['city'] ? $chain['city']['adcode'] : '',
        'districtName' => $chain['district'] ? $chain['district']['name'] : '',
    ]];
}

function getProvinces() {
    $regions = loadJson('regions.json');
    if (!$regions) {
        $regions = getDefaultRegions();
        saveJson('regions.json', $regions);
    }
    return ['status' => 'ok', 'data' => array_map(function($r) {
        return ['adcode' => $r['adcode'], 'name' => $r['name']];
    }, $regions)];
}

function getCities($provinceAdcode) {
    $regions = loadJson('regions.json');
    if (!$regions) return ['status' => 'error', 'message' => '无数据'];

    foreach ($regions as $p) {
        if ($p['adcode'] === $provinceAdcode) {
            return ['status' => 'ok', 'data' => array_map(function($c) {
                return ['adcode' => $c['adcode'], 'name' => $c['name']];
            }, $p['children'] ?? [])];
        }
    }
    return ['status' => 'error', 'message' => "未找到省份: $provinceAdcode"];
}

function getDistricts($cityAdcode) {
    $regions = loadJson('regions.json');
    if (!$regions) return ['status' => 'error', 'message' => '无数据'];

    foreach ($regions as $p) {
        foreach ($p['children'] ?? [] as $c) {
            if ($c['adcode'] === $cityAdcode) {
                return ['status' => 'ok', 'data' => array_map(function($d) {
                    return ['adcode' => $d['adcode'], 'name' => $d['name']];
                }, $c['children'] ?? [])];
            }
        }
    }
    return ['status' => 'error', 'message' => "未找到城市: $cityAdcode"];
}

// ============================================================
// 辅助函数
// ============================================================

function findParentChain($regions, $adcode) {
    foreach ($regions as $province) {
        if ($province['adcode'] === $adcode) {
            return ['province' => $province, 'city' => null, 'district' => null];
        }
        foreach ($province['children'] ?? [] as $city) {
            if ($city['adcode'] === $adcode) {
                return ['province' => $province, 'city' => $city, 'district' => null];
            }
            foreach ($city['children'] ?? [] as $district) {
                if ($district['adcode'] === $adcode) {
                    return ['province' => $province, 'city' => $city, 'district' => $district];
                }
            }
        }
    }
    return null;
}

function jsonResponse($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

function log_request($method, $uri, $query) {
    $log = getDataDir() . '/access.log';
    $line = date('Y-m-d H:i:s') . " [$method] $uri";
    if (!empty($query)) $line .= '?' . http_build_query($query);
    $line .= "\n";
    @file_put_contents($log, $line, FILE_APPEND);
}

function getDefaultAnnouncements() {
    return [
        [
            'id' => 1, 'type' => 'IMPORTANT',
            'title' => '系统维护通知',
            'content' => '服务器将于今晚22:00-23:00进行例行维护，届时服务可能短暂不可用，请提前做好准备。',
            'active' => true, 'isPopup' => true, 'order' => 1,
            'startTime' => '2026-04-01T00:00:00Z', 'endTime' => '2026-12-31T23:59:59Z',
            'createdAt' => '2026-04-01T10:00:00Z', 'updatedAt' => '2026-04-15T08:30:00Z',
        ],
        [
            'id' => 2, 'type' => 'NORMAL',
            'title' => '新版本发布',
            'content' => 'v2.5.0 已发布，新增多项功能优化，建议尽快更新。',
            'active' => true, 'isPopup' => false, 'order' => 2,
            'startTime' => '2026-04-10T00:00:00Z', 'endTime' => '2026-06-30T23:59:59Z',
            'createdAt' => '2026-04-10T09:00:00Z', 'updatedAt' => '2026-04-10T09:00:00Z',
        ],
    ];
}

function getDefaultRegions() {
    return [
        ['adcode' => '110000', 'name' => '北京市', 'children' => [
            ['adcode' => '110100', 'name' => '北京市', 'children' => [
                ['adcode' => '110101', 'name' => '东城区', 'children' => []],
                ['adcode' => '110102', 'name' => '西城区', 'children' => []],
                ['adcode' => '110105', 'name' => '朝阳区', 'children' => []],
                ['adcode' => '110106', 'name' => '丰台区', 'children' => []],
                ['adcode' => '110108', 'name' => '海淀区', 'children' => []],
            ]]
        ]],
        ['adcode' => '440000', 'name' => '广东省', 'children' => [
            ['adcode' => '440300', 'name' => '深圳市', 'children' => [
                ['adcode' => '440303', 'name' => '罗湖区', 'children' => []],
                ['adcode' => '440304', 'name' => '福田区', 'children' => []],
                ['adcode' => '440305', 'name' => '南山区', 'children' => []],
                ['adcode' => '440306', 'name' => '宝安区', 'children' => []],
                ['adcode' => '440307', 'name' => '龙岗区', 'children' => []],
            ]],
            ['adcode' => '440100', 'name' => '广州市', 'children' => [
                ['adcode' => '440103', 'name' => '荔湾区', 'children' => []],
                ['adcode' => '440104', 'name' => '越秀区', 'children' => []],
                ['adcode' => '440105', 'name' => '海珠区', 'children' => []],
                ['adcode' => '440106', 'name' => '天河区', 'children' => []],
            ]]
        ]],
        ['adcode' => '310000', 'name' => '上海市', 'children' => [
            ['adcode' => '310100', 'name' => '上海市', 'children' => [
                ['adcode' => '310101', 'name' => '黄浦区', 'children' => []],
                ['adcode' => '310104', 'name' => '徐汇区', 'children' => []],
                ['adcode' => '310105', 'name' => '长宁区', 'children' => []],
                ['adcode' => '310106', 'name' => '静安区', 'children' => []],
            ]]
        ]],
        ['adcode' => '510000', 'name' => '四川省', 'children' => [
            ['adcode' => '510100', 'name' => '成都市', 'children' => [
                ['adcode' => '510104', 'name' => '锦江区', 'children' => []],
                ['adcode' => '510105', 'name' => '青羊区', 'children' => []],
                ['adcode' => '510106', 'name' => '金牛区', 'children' => []],
                ['adcode' => '510107', 'name' => '武侯区', 'children' => []],
            ]]
        ]],
        ['adcode' => '330000', 'name' => '浙江省', 'children' => [
            ['adcode' => '330100', 'name' => '杭州市', 'children' => [
                ['adcode' => '330102', 'name' => '上城区', 'children' => []],
                ['adcode' => '330106', 'name' => '西湖区', 'children' => []],
                ['adcode' => '330109', 'name' => '萧山区', 'children' => []],
            ]]
        ]],
    ];
}
