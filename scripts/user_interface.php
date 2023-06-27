<?php
require_once ROOT . '/scripts/upload_files.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Super Emails Script</title>
    <link rel="stylesheet" href="/style/output.css">
</head>

<body>
    <form id="list_files" class="flex flex-col gap-y-3 w-1/3 py-4 px-10" method="post" enctype="multipart/form-data">
        <label>Выберите файлы для загрузки: (с расширением .CSV)</label>
        <input type="file" name="fileUpload[]" multiple>
        <input class="border-orange-400 border-2 p-1 bg-amber-300 cursor-pointer rounded-md" type="submit" name="Submit" value="Загрузить файлы">
    </form>
    <hr class="my-4">
    <?php if (isset($_FILES['fileUpload'])) : ?>
        <div class="px-10">
            <p class="text-2xl underline">Нажмите на название файла, для которого нужно проверить Email'ы!!!</p>
            <div class="flex my-4 gap-3">
                <?php
                foreach ($_FILES['fileUpload']['name'] as $file) : ?>
                    <a class="border p-3 gap-4 flex w-1/3 justify-center" href="../scripts/corrector.php?fileName=<?php echo $file ?>" id="<?php echo $file ?>" type="submit">
                        <!-- <a class="border p-3 gap-4 flex w-1/3 justify-center" href="./corrector.php?fileName=<?php echo $file ?>" id="<?php echo $file ?>" type="submit"> -->
                        <span class=" text-xl text-blue-300">
                            Проверить <?php echo $file ?>
                        </span>
                    </a>
                <?php
                endforeach; ?>
            </div>
            <div class="flex justify-between">
                <a class="border p-3 gap-4 flex w-1/3 justify-center" href="../scripts/corrector.php?checkAll=all_files" type="submit">
                    <span class=" text-xl text-blue-300">
                        Проверить все файлы за один клик
                    </span>
                </a>
                <a class="border p-3 gap-4 flex w-1/3 justify-center" href="../scripts/delete_files.php" type="submit">
                    <span class=" text-xl text-blue-300">
                        Удалить все файлы за один клик
                    </span>
                </a>
            </div>
        <?php endif; ?>

        </div>
</body>

</html>