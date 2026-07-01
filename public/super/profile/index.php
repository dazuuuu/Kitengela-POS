<?php
// public/super/profile/index.php — redirects to Settings
require_once __DIR__ . '/../../../app/app.php';
PageGuard::auth();
header('Location: /Rongai/public/super/settings/');
exit;
