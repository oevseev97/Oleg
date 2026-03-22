<?php
$uploadDir = __DIR__ . '/uploads/';
var_dump($uploadDir);
var_dump(is_dir($uploadDir));
var_dump(is_writable($uploadDir));
exit;