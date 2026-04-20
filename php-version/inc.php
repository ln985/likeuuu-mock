<?php
/**
 * 公共函数库 - 所有入口文件共享
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

function getDataDir() {
    // 从任意子目录向上找到 data 目录
    $dir = dirname(__DIR__, 2) . '/data';
    if (!is_dir($dir)) @mkdir($dir, 0755, true);
    return $dir;
}

function loadJson($filename) {
    $dataPath = getDataDir() . '/' . $filename;
    if (file_exists($dataPath)) return json_decode(file_get_contents($dataPath), true);
    return null;
}

function saveJson($filename, $data) {
    file_put_contents(getDataDir() . '/' . $filename, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

function jsonResponse($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

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
