<link rel="stylesheet" href="/style/output.css">
<?php

$rootPath = dirname(__DIR__);

foreach ([$rootPath . "/input", $rootPath . "/output"] as $dir) {
    if (is_dir($dir)) {
        foreach (scandir($dir) as $file) {
            $filepath = $dir . "/" . $file;

            if (!is_dir($filepath)) {
                unlink($filepath);
            }
        }
    }
}
?>
<a class="px-10 py-4 cursor-pointer flex items-center" href="../index.php">
    <img src="../left_arrow.svg" alt=""><span class="text-red-400">Вернуться на главную</span>
</a>
<span class="text-xl underline px-10">Все файлы успешно удалены!</span>