<?php
require __DIR__ . '/common.php';

session_destroy();
json_out(['ok'=>true]);
