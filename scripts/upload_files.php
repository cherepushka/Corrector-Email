<?php
if (isset($_FILES['fileUpload']['name'])) {
    $total_files = count($_FILES['fileUpload']['name']);

    for ($key = 0; $key < $total_files; $key++) {
        if (isset(
            $_FILES['fileUpload']['name'][$key], // При проверке name, другие ключи могут не существовать, если уж проверять, то все необходимые
            $_FILES['fileUpload']['size'][$key],
            $_FILES['fileUpload']['tmp_name'][$key]
        )) {
            $name = $_FILES['fileUpload']['name'][$key];
            $size = $_FILES['fileUpload']['size'][$key];
            $tmp = $_FILES['fileUpload']['tmp_name'][$key];

            if ($size > 0 && $size < 5 * 1024 * 1024) {
                $original_filename = $name;
                $target = InputPath . "/" . basename($original_filename);
                move_uploaded_file($tmp, $target);
            }
        }
    }
}
