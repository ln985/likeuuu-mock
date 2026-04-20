<?php
// GET /api/health
require_once dirname(__DIR__, 1) . '/inc.php';
jsonResponse(['status' => 'ok', 'time' => date('c')]);
