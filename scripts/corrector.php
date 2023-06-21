<link rel="stylesheet" href="/style/output.css">

<?php

define('ROOT', dirname(__DIR__));
define('InputPath', ROOT . "/input");
define('OutputPath', ROOT . "/output");

require_once(ROOT . '/converter/idna_convert.class.php');

$fileName = $_GET['fileName'] ?? null;
$checkAll = $_GET['checkAll'] ?? null;

if ($checkAll) {
    $checkAll = $_GET['checkAll'] === 'all_files' ? true : false;
}

$csv = '';

if (isset($fileName) || $checkAll) {
    // Считывание файла и запись в массив
    if ($checkAll) {
        $scanedFiles = scandir(InputPath);

        for ($i = 2; $i < count($scanedFiles); $i++) {
            $csv .= file_get_contents(InputPath . "/" . $scanedFiles[$i]);
        }
    } else {
        $csv = file_get_contents(InputPath . "/" . $fileName);
    }

    $rows = explode(PHP_EOL, $csv);
    $data = [];

    foreach ($rows as $row) {
        if ($row != null) {
            $data[] = trim($row, '\?,\.\SP\!\"\#\$\%\&\(\)\*\+\`\~\,\-\;\:\<\>\=\@');
        }
    }

    // Для красивого отображения массива
    function p($input)
    {
        echo '<pre>';
        print_r($input);
        echo '</pre>';
    }

    // Проверяет на окончание ru/com/org, разделяет строку на отдельные email и сравнивает их, удаляет если повторяется
    function filtration($str, $pattern)
    {
        $str_only_show = $str;
        if (stripos($str, ':')) {
            $pos = stripos($str, ':') + 1;
            $str = substr($str, $pos);
        }
        $str = str_replace(['@@', '\'', '?', '/'], ['@', '', '', ''], $str);
        $booleanResult = preg_match($pattern, $str, $found);
        $comparisonPattern = '/^[a-zA-Z0-9_.+-?\']+@/i';
        if ($booleanResult) {
            $foundResultFirst = $found[0];
            $str = trim(str_replace($foundResultFirst, '', $str), '\.');
            $booleanResult = preg_match($pattern, $str, $found);

            preg_match($comparisonPattern, $foundResultFirst, $firstCompar);
            preg_match($comparisonPattern, $str, $secondCompar);
            if ($firstCompar === $secondCompar) {
                $str = str_replace($str, '', $str);
            } else {
                if ($booleanResult) {
                    $foundResultSecond = $found[0];
                    $str = str_replace($foundResultSecond, '', $str);
                } else {
                    if (!strpos('@', $str)) {
                        $str = str_replace($str, '', $str);
                    } else {
                        $notRepeat = $str;
                    }
                }
            }
        } else {
            $notEntered = $str;
        }
        // Выбирает Email которых осталось после первой проверки больше 1го
        $patterns = '/[a-zA-Z0-9_.+-?\']+@([a-z0-9.-]+)[.]((ru)|(com)|(net)|(org)|(kz)|(su))/i';
        if (preg_match_all($patterns, $str, $found)) {
            $tempArrStr = $found[0];
        }
        return [
            'str' => $str,
            'initial_str_only_show' => $str_only_show,
            'after_reg_1' => $foundResultFirst ?? false,
            'after_reg_2' => $foundResultSecond ?? false,
            'balance_after_cut_2' => $ostatokSecondReplace ?? false,
            'not_repeat_but_did_not_pass_reg' => $notRepeat ?? false,
            'did_not_pass_first_reg' => $notEntered ?? false,
            'temp_arr' => $tempArrStr ?? false,
        ];
    }
    $arrayAfterRegCycle = [];
    $correctEmail = [];
    $wrongEmail = [];
    $notEmptyString = [];
    $tempArrStr = [];

    // Проверка всех Email первый preg_match
    $patterns = '/^[a-zA-Z0-9_.+-?\']+@([a-z0-9.-]+)[.]((ru)|(com)|(net)|(org)|(kz)|(su))/i';
    foreach ($data as $str) {
        $arrayAfterRegCycle[] = filtration($str, $patterns);
    }

    // Распределение по массивам корректных и некорректных email'ов после первого рег
    foreach ($arrayAfterRegCycle as $value) {
        if ($value['after_reg_1'] != null) {
            $correctEmail[] = $value['after_reg_1'];
        }
        if ($value['after_reg_2'] != null) {
            $correctEmail[] = $value['after_reg_2'];
        }
        if ($value['did_not_pass_first_reg'] != null) {
            $wrongEmail[] = $value['did_not_pass_first_reg'];
        }
        if ($value['not_repeat_but_did_not_pass_reg'] != null) {
            $wrongEmail[] = $value['not_repeat_but_did_not_pass_reg'];
        }
        if ($value['str'] != null) {
            $notEmptyString[] = $value['str'];
        }
        if ($value['temp_arr'] != null) {
            foreach ($value['temp_arr'] as $item) {
                $correctEmail[] = $item;
            }
        }
    }

    // Добавление доменов в конец
    foreach ($wrongEmail as $key => &$email) {
        if (strripos($email, '@mail.')) {
            $pos = strrpos($email, '.');
            $email = substr($email, 0, $pos) . '.ru';
            $correctEmail[] = $email;
            unset($wrongEmail[$key]);
        }
        if (strripos($email, '@yandex')) {
            $pos = strrpos($email, '.');
            $email = substr($email, 0, $pos) . '.ru';
            $correctEmail[] = $email;
            unset($wrongEmail[$key]);
        }
        if (strripos($email, '@gmail')) {
            $pos = strrpos($email, '.');
            $email = substr($email, 0, $pos) . '.com';
            $correctEmail[] = $email;
            unset($wrongEmail[$key]);
        } else if (preg_match('/[.][rucm]$/', $email)) {
            $email = preg_replace(['/[.][ru]$/', '/[.][cm]$/'], ['.ru', '.com'], $email);
            $correctEmail[] = $email;
            unset($wrongEmail[$key]);
        }
    }

    // Очищение массива с неправильными Email от пустых элементов и перекодировка Punycode в домен РФ
    $wrongEmails = [];
    $idn = new idna_convert();
    foreach ($wrongEmail as $item) {
        if ($item != null) {
            if (strrpos(idn_to_utf8($item), '.рф')) {
                $encoded = $idn->decode($item);
                $correctEmail[] = $encoded;
            } else {
                $wrongEmails[] = $item;
            }
        }
    }

    $basename = basename($fileName, '.csv'); // Повторный вызов функции

    file_put_contents(OutputPath . "/" . $basename . '_correct_emails.csv', implode("\n", $correctEmail));
    file_put_contents(OutputPath . "/" . $basename . '_incorrect_emails.csv', implode("\n", $wrongEmails));
?>
    <a class="px-10 py-4 cursor-pointer flex items-center" onclick="javascript:history.back(); return false;">
        <img src="../left_arrow.svg" alt=""><span class="text-red-400">Вернуться на главную</span>
    </a>
    <div class="px-10 py-6 flex flex-col justify-center">
        <span class="text-xl underline">Файл <?php echo $fileName ?> успешно проверен!</span>
        <div class="border-orange-400 bg-amber-300 rounded-md border-2 px-2 py-3 gap-4 flex flex-col w-1/3 my-4">
            <!-- Скачать Правильные Email -->
            <?php if (isset($checkAll)) { ?>
                <a class="border-2 p-3 border-orange-400" href="/output/all_files_correct_emails.csv" download>
                    Скачать файл с <b>корректными</b> Email
                </a>
            <?php } else { ?>
                <a class="border-2 p-3 border-orange-400" href="/output/<?php echo basename($fileName, '.csv') ?>_correct_emails.csv" download>
                    Скачать файл с <b>корректными</b> Email
                </a>
            <?php } ?>

            <!-- Скачать Неправильные Email -->
            <?php if (isset($checkAll)) { ?>
                <a class="border-2 p-3 border-orange-400" href="/output/all_files_incorrect_emails.csv" download>
                    Скачать файл с <b>неправильными</b> Email
                </a>
            <?php } else { ?>
                <a class="border-2 p-3 border-orange-400" href="/output/<?php echo basename($fileName, '.csv') ?>_incorrect_emails.csv" download>
                    Скачать файл с <b>неправильными</b> Email
                </a>
            <?php } ?>

        </div>
    </div>
<?php
} else { ?>
    <span class="text-xl underline uppercase">Файл <?php echo $fileName ?> проверить не удалось!</span>
<?php
}
?>