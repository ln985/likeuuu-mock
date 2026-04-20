<?php
// GET /api/app/announcement/active
// GET /api/app/announcement/active?limit=N
require_once dirname(__DIR__, 4) . '/inc.php';

$announcements = loadJson('announcements.json');
if (!$announcements) {
    $announcements = getDefaultAnnouncements();
    saveJson('announcements.json', $announcements);
}

$active = array_values(array_filter($announcements, fn($a) => $a['active']));
usort($active, fn($a, $b) => $a['order'] - $b['order']);

$limit = isset($_GET['limit']) ? intval($_GET['limit']) : null;
if ($limit && $limit > 0) {
    $active = array_slice($active, 0, $limit);
}

jsonResponse(['status' => 'ok', 'data' => $active]);
