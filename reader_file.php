<?php
require_once('./converter/idna_convert.class.php');
// Считывание файла и запись в массив
$files = scandir(__DIR__ . './input_files/');
$csv = '';
$filesName = [];
for ($i = 2; $i < count($files); $i++) {
    $csv .= file_get_contents(__DIR__ . './input_files/' . $files[$i]);
    $filesName[] = basename($files[$i],'.csv');
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
    $str = str_replace(['@@', '\'', '?'], ['@', '', ''], $str);
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

// Проверка всех Email первый рег
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


// Запись корректных Email
$buffer = fopen(__DIR__ . "./output_files/$filesName[0]_correct_emails.csv", 'w');
fputs($buffer, chr(0xEF) . chr(0xBB) . chr(0xBF));
fputcsv($buffer, $correctEmail, "\n");
fclose($buffer);

// Запись ошибочных Email
$buffer = fopen(__DIR__ . "./output_files/$filesName[0]_incorrect_emails.csv", 'w');
fputs($buffer, chr(0xEF) . chr(0xBB) . chr(0xBF));
fputcsv($buffer, $wrongEmails, "\n");
fclose($buffer);
var_dump($filesName);
