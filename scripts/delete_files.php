<link rel="stylesheet" href="../style/output.css">
<?php
if (file_exists('../input_files')) {
    foreach (glob('../input_files/*') as $file)
        unlink($file);
}
?>
<a class="px-10 py-4 cursor-pointer flex items-center" href="../index.php">
    <img src="../left_arrow.svg" alt=""><span class="text-red-400">Вернуться на главную</span>
</a>
<span class="text-xl underline px-10">Все файлы успешно удалены!</span>