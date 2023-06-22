<?php

define('ROOT', __DIR__);
define('InputPath', ROOT . "/input");
define('OutputPath', ROOT . "/output");

foreach ([InputPath, OutputPath] as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir);
    }
}

require_once ROOT . '/scripts/user_interface.php';
