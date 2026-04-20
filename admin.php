<?php
/**
 * 管理面板 - 访问 https://你的域名/admin.php
 */
$dataDir = __DIR__ . '/data';
if (!is_dir($dataDir)) @mkdir($dataDir, 0755, true);

function loadJson($f) {
    $p = __DIR__ . '/data/' . $f;
    if (file_exists($p)) return json_decode(file_get_contents($p), true);
    return null;
}
function saveJson($f, $d) {
    file_put_contents(__DIR__ . '/data/' . $f, json_encode($d, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'save_config') {
        saveJson('config.json', [
            'linkConfig' => ['mainUrl' => $_POST['mainUrl'] ?? ''],
            'appLinkConfig' => ['communityGroup' => $_POST['communityGroup'] ?? '']
        ]);
        $msg = '✅ 配置已保存';
    } elseif ($action === 'add') {
        $list = loadJson('announcements.json') ?? [];
        $maxId = max(array_column($list, 'id') ?: [0]);
        $now = date('c');
        $list[] = [
            'id' => $maxId + 1, 'type' => $_POST['type'] ?? 'NORMAL',
            'title' => $_POST['title'] ?? '', 'content' => $_POST['content'] ?? '',
            'active' => isset($_POST['active']), 'isPopup' => isset($_POST['isPopup']),
            'order' => intval($_POST['order'] ?? $maxId + 1),
            'startTime' => $_POST['startTime'] ?: $now,
            'endTime' => $_POST['endTime'] ?: '2099-12-31T23:59:59Z',
            'createdAt' => $now, 'updatedAt' => $now,
        ];
        saveJson('announcements.json', $list);
        $msg = '✅ 已添加';
    } elseif ($action === 'delete') {
        $list = loadJson('announcements.json') ?? [];
        $id = intval($_POST['id'] ?? 0);
        saveJson('announcements.json', array_values(array_filter($list, fn($a) => $a['id'] !== $id)));
        $msg = '✅ 已删除';
    } elseif ($action === 'toggle') {
        $list = loadJson('announcements.json') ?? [];
        $id = intval($_POST['id'] ?? 0);
        foreach ($list as &$a) if ($a['id'] === $id) $a['active'] = !$a['active'];
        saveJson('announcements.json', $list);
        $msg = '✅ 已切换';
    }
}

$config = loadJson('config.json') ?? ['linkConfig'=>['mainUrl'=>''],'appLinkConfig'=>['communityGroup'=>'']];
$list = loadJson('announcements.json') ?? [];
?>
<!DOCTYPE html><html lang="zh"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>管理面板</title>
<style>*{box-sizing:border-box;margin:0;padding:0}body{font-family:-apple-system,"Microsoft YaHei",sans-serif;background:#f0f2f5;padding:20px;color:#333}
.c{max-width:800px;margin:0 auto}h1{text-align:center;margin-bottom:24px;font-size:22px}
.card{background:#fff;border-radius:8px;padding:20px;margin-bottom:16px;box-shadow:0 1px 3px rgba(0,0,0,.1)}
.card h2{font-size:16px;margin-bottom:14px;padding-bottom:6px;border-bottom:2px solid #1890ff;display:inline-block}
.fg{margin-bottom:10px}.fg label{display:block;font-weight:500;margin-bottom:3px;font-size:13px}
.fg input,.fg textarea,.fg select{width:100%;padding:7px 10px;border:1px solid #d9d9d9;border-radius:4px;font-size:13px}
.fg textarea{height:70px;resize:vertical}.fr{display:flex;gap:10px}.fr .fg{flex:1}
.btn{padding:6px 16px;border:none;border-radius:4px;cursor:pointer;font-size:13px;color:#fff}
.bp{background:#1890ff}.bd{background:#ff4d4f}.bs{padding:3px 10px;font-size:12px}
.msg{background:#f6ffed;border:1px solid #b7eb8f;padding:8px;border-radius:4px;margin-bottom:16px;text-align:center}
table{width:100%;border-collapse:collapse;font-size:13px}th,td{padding:8px 6px;text-align:left;border-bottom:1px solid #f0f0f0}
th{font-weight:600;background:#fafafa}.on{color:#52c41a}.off{color:#ff4d4f}</style>
</head><body><div class="c">
<h1>🛠️ likeuuu 管理面板</h1>
<?php if($msg):?><div class="msg"><?=$msg?></div><?php endif;?>

<div class="card"><h2>⚙️ 应用配置</h2>
<form method="post"><input type="hidden" name="action" value="save_config">
<div class="fg"><label>主页 URL</label><input name="mainUrl" value="<?=htmlspecialchars($config['linkConfig']['mainUrl']??'')?>"></div>
<div class="fg"><label>社群链接</label><input name="communityGroup" value="<?=htmlspecialchars($config['appLinkConfig']['communityGroup']??'')?>"></div>
<button class="btn bp" type="submit">保存</button></form></div>

<div class="card"><h2>📢 公告管理</h2>
<table><tr><th>ID</th><th>标题</th><th>类型</th><th>状态</th><th>操作</th></tr>
<?php foreach($list as $a):?><tr>
<td><?=$a['id']?></td><td><?=htmlspecialchars($a['title'])?></td><td><?=$a['type']?></td>
<td><span class="<?=$a['active']?'on':'off'?>"><?=$a['active']?'✓启用':'✗禁用'?></span></td>
<td><form method="post" style="display:inline"><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?=$a['id']?>"><button class="btn bp bs" type="submit"><?=$a['active']?'禁用':'启用'?></button></form>
<form method="post" style="display:inline" onsubmit="return confirm('删除?')"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?=$a['id']?>"><button class="btn bd bs" type="submit">删除</button></form></td>
</tr><?php endforeach?></table></div>

<div class="card"><h2>➕ 添加公告</h2>
<form method="post"><input type="hidden" name="action" value="add">
<div class="fr"><div class="fg"><label>标题</label><input name="title" required></div>
<div class="fg"><label>类型</label><select name="type"><option value="NORMAL">普通</option><option value="IMPORTANT">重要</option><option value="URGENT">紧急</option></select></div></div>
<div class="fg"><label>内容</label><textarea name="content" required></textarea></div>
<div class="fr"><div class="fg"><label>排序</label><input name="order" type="number" value="1"></div>
<div class="fg"><label>开始时间</label><input name="startTime" type="datetime-local"></div>
<div class="fg"><label>结束时间</label><input name="endTime" type="datetime-local"></div></div>
<div class="fr"><div class="fg"><label><input type="checkbox" name="active" checked> 启用</label></div>
<div class="fg"><label><input type="checkbox" name="isPopup"> 弹窗</label></div></div>
<button class="btn bp" type="submit">添加</button></form></div>
</div></body></html>
