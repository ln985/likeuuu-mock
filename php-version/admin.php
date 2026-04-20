<?php
/**
 * likeuuu Mock Server - 管理面板
 * 访问 https://你的域名/admin.php
 */
$dataDir = __DIR__ . '/data';
if (!is_dir($dataDir)) mkdir($dataDir, 0755, true);

function loadJson($f) {
    $p1 = __DIR__ . '/' . $f;
    $p2 = __DIR__ . '/data/' . $f;
    if (file_exists($p1)) return json_decode(file_get_contents($p1), true);
    if (file_exists($p2)) return json_decode(file_get_contents($p2), true);
    return null;
}
function saveJson($f, $d) {
    file_put_contents(__DIR__ . '/data/' . $f, json_encode($d, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

// 处理表单提交
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'save_config') {
        $config = [
            'linkConfig' => ['mainUrl' => $_POST['mainUrl'] ?? ''],
            'appLinkConfig' => ['communityGroup' => $_POST['communityGroup'] ?? '']
        ];
        saveJson('config.json', $config);
        $msg = '✅ 配置已保存';
    }
    elseif ($action === 'add_announcement') {
        $announcements = loadJson('announcements.json') ?? [];
        $maxId = 0;
        foreach ($announcements as $a) if ($a['id'] > $maxId) $maxId = $a['id'];
        $now = date('c');
        $announcements[] = [
            'id' => $maxId + 1,
            'type' => $_POST['type'] ?? 'NORMAL',
            'title' => $_POST['title'] ?? '',
            'content' => $_POST['content'] ?? '',
            'active' => isset($_POST['active']),
            'isPopup' => isset($_POST['isPopup']),
            'order' => intval($_POST['order'] ?? $maxId + 1),
            'startTime' => $_POST['startTime'] ?? $now,
            'endTime' => $_POST['endTime'] ?? '2099-12-31T23:59:59Z',
            'createdAt' => $now, 'updatedAt' => $now,
        ];
        saveJson('announcements.json', $announcements);
        $msg = '✅ 公告已添加';
    }
    elseif ($action === 'delete_announcement') {
        $announcements = loadJson('announcements.json') ?? [];
        $id = intval($_POST['id'] ?? 0);
        $announcements = array_values(array_filter($announcements, fn($a) => $a['id'] !== $id));
        saveJson('announcements.json', $announcements);
        $msg = '✅ 公告已删除';
    }
    elseif ($action === 'toggle_announcement') {
        $announcements = loadJson('announcements.json') ?? [];
        $id = intval($_POST['id'] ?? 0);
        foreach ($announcements as &$a) {
            if ($a['id'] === $id) $a['active'] = !$a['active'];
        }
        saveJson('announcements.json', $announcements);
        $msg = '✅ 状态已切换';
    }
}

$config = loadJson('config.json') ?? ['linkConfig'=>['mainUrl'=>''], 'appLinkConfig'=>['communityGroup'=>'']];
$announcements = loadJson('announcements.json') ?? [];
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>likeuuu 管理面板</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: -apple-system, "Microsoft YaHei", sans-serif; background: #f0f2f5; padding: 20px; color: #333; }
.container { max-width: 800px; margin: 0 auto; }
h1 { text-align: center; margin-bottom: 30px; font-size: 24px; }
.card { background: #fff; border-radius: 8px; padding: 24px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,.1); }
.card h2 { font-size: 18px; margin-bottom: 16px; padding-bottom: 8px; border-bottom: 2px solid #1890ff; display: inline-block; }
.form-group { margin-bottom: 12px; }
.form-group label { display: block; font-weight: 500; margin-bottom: 4px; font-size: 14px; }
.form-group input, .form-group textarea, .form-group select { width: 100%; padding: 8px 12px; border: 1px solid #d9d9d9; border-radius: 4px; font-size: 14px; }
.form-group textarea { height: 80px; resize: vertical; }
.form-row { display: flex; gap: 12px; }
.form-row .form-group { flex: 1; }
.btn { padding: 8px 20px; border: none; border-radius: 4px; cursor: pointer; font-size: 14px; }
.btn-primary { background: #1890ff; color: #fff; }
.btn-danger { background: #ff4d4f; color: #fff; }
.btn-sm { padding: 4px 12px; font-size: 12px; }
.msg { background: #f6ffed; border: 1px solid #b7eb8f; padding: 10px; border-radius: 4px; margin-bottom: 20px; text-align: center; }
table { width: 100%; border-collapse: collapse; font-size: 14px; }
th, td { padding: 10px 8px; text-align: left; border-bottom: 1px solid #f0f0f0; }
th { font-weight: 600; background: #fafafa; }
.badge { padding: 2px 8px; border-radius: 10px; font-size: 12px; }
.badge-on { background: #f6ffed; color: #52c41a; border: 1px solid #b7eb8f; }
.badge-off { background: #fff2f0; color: #ff4d4f; border: 1px solid #ffccc7; }
.api-list { font-size: 13px; color: #666; }
.api-list code { background: #f5f5f5; padding: 2px 6px; border-radius: 3px; font-family: monospace; }
</style>
</head>
<body>
<div class="container">
<h1>🛠️ likeuuu Mock Server 管理面板</h1>

<?php if ($msg): ?><div class="msg"><?= $msg ?></div><?php endif; ?>

<!-- 配置 -->
<div class="card">
<h2>⚙️ 应用配置</h2>
<form method="post">
    <input type="hidden" name="action" value="save_config">
    <div class="form-group">
        <label>主页 URL (linkConfig.mainUrl)</label>
        <input name="mainUrl" value="<?= htmlspecialchars($config['linkConfig']['mainUrl'] ?? '') ?>">
    </div>
    <div class="form-group">
        <label>社群链接 (appLinkConfig.communityGroup)</label>
        <input name="communityGroup" value="<?= htmlspecialchars($config['appLinkConfig']['communityGroup'] ?? '') ?>">
    </div>
    <button class="btn btn-primary" type="submit">保存配置</button>
</form>
</div>

<!-- 公告管理 -->
<div class="card">
<h2>📢 公告管理</h2>
<table>
<tr><th>ID</th><th>标题</th><th>类型</th><th>状态</th><th>操作</th></tr>
<?php foreach ($announcements as $a): ?>
<tr>
    <td><?= $a['id'] ?></td>
    <td><?= htmlspecialchars($a['title']) ?></td>
    <td><?= $a['type'] ?></td>
    <td><span class="badge <?= $a['active'] ? 'badge-on' : 'badge-off' ?>"><?= $a['active'] ? '启用' : '禁用' ?></span></td>
    <td>
        <form method="post" style="display:inline">
            <input type="hidden" name="action" value="toggle_announcement">
            <input type="hidden" name="id" value="<?= $a['id'] ?>">
            <button class="btn btn-sm btn-primary" type="submit"><?= $a['active'] ? '禁用' : '启用' ?></button>
        </form>
        <form method="post" style="display:inline" onsubmit="return confirm('确定删除?')">
            <input type="hidden" name="action" value="delete_announcement">
            <input type="hidden" name="id" value="<?= $a['id'] ?>">
            <button class="btn btn-sm btn-danger" type="submit">删除</button>
        </form>
    </td>
</tr>
<?php endforeach; ?>
</table>
</div>

<!-- 添加公告 -->
<div class="card">
<h2>➕ 添加公告</h2>
<form method="post">
    <input type="hidden" name="action" value="add_announcement">
    <div class="form-row">
        <div class="form-group">
            <label>标题</label>
            <input name="title" required>
        </div>
        <div class="form-group">
            <label>类型</label>
            <select name="type">
                <option value="NORMAL">普通</option>
                <option value="IMPORTANT">重要</option>
                <option value="URGENT">紧急</option>
            </select>
        </div>
    </div>
    <div class="form-group">
        <label>内容</label>
        <textarea name="content" required></textarea>
    </div>
    <div class="form-row">
        <div class="form-group">
            <label>排序</label>
            <input name="order" type="number" value="1">
        </div>
        <div class="form-group">
            <label>开始时间</label>
            <input name="startTime" type="datetime-local">
        </div>
        <div class="form-group">
            <label>结束时间</label>
            <input name="endTime" type="datetime-local">
        </div>
    </div>
    <div class="form-row">
        <div class="form-group"><label><input type="checkbox" name="active" checked> 启用</label></div>
        <div class="form-group"><label><input type="checkbox" name="isPopup"> 弹窗显示</label></div>
    </div>
    <button class="btn btn-primary" type="submit">添加公告</button>
</form>
</div>

<!-- API 列表 -->
<div class="card">
<h2>📋 API 接口</h2>
<div class="api-list">
<p><code>GET /api/app/config</code> — 应用配置</p>
<p><code>GET /api/app/announcement/active</code> — 活跃公告</p>
<p><code>GET /api/app/announcement/active?limit=N</code> — 限量公告</p>
<p><code>GET /api/location</code> — 省份列表</p>
<p><code>GET /api/location/:adcode</code> — 行政区划详情</p>
<p><code>GET /api/location/provinces</code> — 省份列表</p>
<p><code>GET /api/location/cities?provinceAdcode=XXX</code> — 城市列表</p>
<p><code>GET /api/location/districts?cityAdcode=XXX</code> — 区县列表</p>
<p><code>GET /api/health</code> — 健康检查</p>
</div>
</div>

</div>
</body>
</html>
