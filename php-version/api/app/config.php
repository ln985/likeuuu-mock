<?php
// GET /api/app/config
require_once dirname(__DIR__, 3) . '/inc.php';

$config = loadJson('config.json');
if (!$config) {
    $config = [
        'linkConfig' => ['mainUrl' => 'https://zq.likeuuu.top/'],
        'appLinkConfig' => ['communityGroup' => 'https://qm.qq.com/cgi-bin/qm/qr?k=example123456']
    ];
    saveJson('config.json', $config);
}
jsonResponse(['status' => 'ok', 'data' => $config]);
