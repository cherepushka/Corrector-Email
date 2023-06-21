<?php
require_once __DIR__ . './scripts/user_interface.php';

$output_dir = 'output_files';
$input_dir = 'input_files';

if (!is_dir($input_dir)) {
    mkdir($input_dir, 0777, true);
}

if (!is_dir($output_dir)) {
    mkdir($output_dir, 0777, true);
}
