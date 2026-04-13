<?php
require __DIR__ . '/bootstrap.php';
header('Content-Type: text/plain');

echo "logged_in: " . ($user->_logged_in ? '1' : '0') . PHP_EOL;
echo "user_id: " . ($user->_data['user_id'] ?? 'null') . PHP_EOL;
echo "user_name: " . ($user->_data['user_name'] ?? 'null') . PHP_EOL;
echo "user_group: " . ($user->_data['user_group'] ?? 'null') . PHP_EOL;
echo "is_admin: " . ($user->_is_admin ? '1' : '0') . PHP_EOL;
echo "is_moderator: " . ($user->_is_moderator ? '1' : '0') . PHP_EOL;
echo "cookie_log_as: " . ($_COOKIE['log_as'] ?? 'null') . PHP_EOL;
echo "host: " . ($_SERVER['HTTP_HOST'] ?? 'null') . PHP_EOL;
