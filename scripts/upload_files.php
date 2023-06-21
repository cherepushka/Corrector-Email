<?php
$input_path = './input_files/';
$output_path = './output_files/';

if (isset($_FILES['fileUpload']['name'])) {

    $total_files = count($_FILES['fileUpload']['name']);
    for ($key = 0; $key < $total_files; $key++) {

        // Check if file is selected
        if (
            isset($_FILES['fileUpload']['name'][$key])
            && $_FILES['fileUpload']['size'][$key] > 0 && $_FILES['fileUpload']['size'][$key] < 5 * 1024 * 1024
        ) {
            $original_filename = $_FILES['fileUpload']['name'][$key];
            $target = $input_path . basename($original_filename);
            $tmp  = $_FILES['fileUpload']['tmp_name'][$key];
            move_uploaded_file($tmp, $target);
        }
    }
} 
// <?php
// $input_path = __DIR__ . './input_files/';
// $output_path = __DIR__ . './output_files/';

// if (isset($_FILES['fileUpload']['name'])) {

//     $total_files = count($_FILES['fileUpload']['name']);
//     if ($total_files > 1) {
//         for ($key = 0; $key < $total_files; $key++) {

//             // Check if file is selected
//             if (
//                 isset($_FILES['fileUpload']['name'][$key])
//                 && $_FILES['fileUpload']['size'][$key] > 0 && $_FILES['fileUpload']['size'][$key] < 5 * 1024 * 1024
//             ) {
//                 $original_filename = $_FILES['fileUpload']['name'][$key];
//                 $target = $input_path . basename($original_filename);
//                 $tmp  = $_FILES['fileUpload']['tmp_name'][$key];
//                 move_uploaded_file($tmp, $target);
//             }
//         }
//     } else if ($total_files = 1) {
//         if (
//             isset($_FILES['fileUpload']['name'])
//             && $_FILES['fileUpload']['size'] > 0 && $_FILES['fileUpload']['size'] < 5 * 1024 * 1024
//         ) {
//             $original_filename = $_FILES['fileUpload']['name'];
//             $target = $input_path . basename($original_filename);
//             $tmp  = $_FILES['fileUpload']['tmp_name'];
//             move_uploaded_file($tmp, $target);
//         }
//     }
// }
