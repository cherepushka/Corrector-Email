<link rel="stylesheet" href="../style/output.css">
<?php
require_once('../converter/idna_convert.class.php');
define('InputPath', "../input/");
define('OutputPath', "../output/");


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
            $csv .= file_get_contents(InputPath . $scanedFiles[$i]);
        }
    } else {
        $csv = file_get_contents(InputPath . $fileName);
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

    // Проверяет на окончание biz|com|edu|info|org|pro|az|by|kg|kz|ru|su|tj|tm|uz, разделяет строку на отдельные email и сравнивает их, удаляет если повторяется
    function filtration($str, $pattern)
    {
        if (stripos($str, ':')) {
            $pos = stripos($str, ':') + 1;
            $str = substr($str, $pos);
        }
        if (stripos($str, '=')) {
            $pos = stripos($str, '=') + 1;
            $str = substr($str, $pos);
        }
        preg_match_all($pattern, $str, $match);

        // $foundResultFirst = $match[0];
        foreach ($match[0] as $matched) {
            $foundResultFirst[] = trim($matched, '\.\_');

            $str = str_replace($matched, '', $str);
            $str = trim($matched, '\.\,\_');
        }

        $notEntered = $str;

        return [
            'str' => $str,
            'after_reg_1' => $foundResultFirst ?? false,
            'did_not_pass_first_reg' => $notEntered ?? false,
        ];
    }

    $arrayAfterRegCycle = [];
    $correctEmail = [];
    $wrongEmail = [];
    $notEmptyString = [];

    // Проверка всех Email первый preg_match
    $patterns = '/[\w+_\.\+\-\?\']+@([\w+.-]+?)\.(biz|com|edu|info|org|pro|az|by|kg|kz|ru|su|tj|tm|uz)/i';
    // $patterns = '/([\w+|_?|\-?]+\.?+)\@([\w+|\.?|\-?]+)+\.(ru|com|net|org|kz|su)/i'; Мишин
    // $patterns = '/([\w+|_?|\-?]\.?+)+\@([\w+|\.?|\-?]+)\.(ru|com|net|org|kz|su)/i'; Изначальный вариант
    foreach ($data as $str) {
        $str = str_replace(['@@', '\'', '?', '/'], ['@', '', '', ''], $str);
        $arrayAfterRegCycle[] = filtration($str, $patterns);
    }

    // Распределение по массивам корректных и некорректных email'ов после первого preg_match
    foreach ($arrayAfterRegCycle as $value) {

        if ($value['after_reg_1'] != null) {
            foreach ($value['after_reg_1'] as $reg) {
                $correctEmail[] = $reg;
            }
        }
        if ($value['did_not_pass_first_reg'] != null) {
            $wrongEmail[] = $value['did_not_pass_first_reg'];
        }
        if ($value['str'] != null) {
            $notEmptyString[] = $value['str'];
        }
    }

    // Добавление доменов в конец
    foreach ($wrongEmail as $key => &$email) {
        $email = trim($email, '\.\,');
        if (!preg_match_all('#@#', $email)) {
            unset($wrongEmail[$key]);
            continue;
        }

        // preg_match_all('#\@([\w+|\.?|\-?]+.?)#', $email, $match2);
        // switch ($match2[1]) {
        //     default:
        //     case ".ya":
        //     case ".yand": {
        //             $email = str_replace(['.yand', '.ya'], 'yandex', $email);
        //             break;
        //         }
        //         break;
        // }
        preg_match('#\@(yandex|mail|laserservice|akvilon-holod|gazprom-neft)#', $email, $rusDomain);
        if (!empty($rusDomain[1])) {
            $lengthDomain = strlen($rusDomain[1]);
            $pos = strpos($email, $rusDomain[1]) + $lengthDomain;
            $email = substr($email, 0, $pos) . '.ru';
            $correctEmail[] = $email;
            unset($wrongEmail[$key]);
        }

        preg_match_all('#[\w+|_?|\-?]+\.?+\@([\w+|\.?|\-?]+)\.(\w+)?#', $email, $emailMached);
        foreach ($emailMached[0] as $em) {
            preg_match_all('#\@([\w+|\.?|\-?]+)\.(\w+)?#', $em, $match);

            $continue = false;

            for ($i = 0; $i < count($match[0]); $i++) {

                switch ($match[1][$i]) {
                    default: {
                            switch ($match[2][$i]) {
                                case "u":
                                case "r": {
                                        $replace = str_replace($match[0], '@' . $match[1][$i] . '.ru', $em);
                                        array_push($correctEmail, $replace);
                                        unset($wrongEmail[$key]);
                                        $continue = true;
                                        break;
                                    }
                                case "c":
                                case "co": {
                                        $replace = str_replace($match[0], '@' . $match[1][$i] . '.com', $em);
                                        array_push($correctEmail, $replace);
                                        $continue = true;
                                        unset($wrongEmail[$key]);
                                        break;
                                    }
                            }
                            break;
                        }
                }
            }
            if ($continue) {
                continue;
            }
        }
    }

    $wrongEmail = array_unique($wrongEmail);
    foreach ($wrongEmail as $key => &$item) {
        foreach ($correctEmail as $corEmail) {
            $pos = strpos($corEmail, $item);
            if ($pos !== false) {
                unset($wrongEmail[$key]);
            }
        }
    }

    // Очищение массива с неправильными Email от пустых элементов и перекодировка Punycode в домен РФ
    $wrongEmails = [];
    // $idn = new idna_convert();
    foreach ($wrongEmail as $item) {
        //     if ($item != null) {
        //         if (strrpos(idn_to_utf8($item), '.рф')) {
        //             $encoded = $idn->decode($item);
        //             $correctEmail[] = $encoded;
        //         } else {
        $wrongEmails[] = $item;
        // }
        // }
    }

    $wrongEmails = array_unique($wrongEmails);

    if (isset($fileName)) {
        $basename = basename($fileName, '.csv'); // Повторный вызов функции
    }

    // Запись в файл корректных Email
    file_put_contents(OutputPath . $basename . '_correct_emails.csv', chr(0xEF) . chr(0xBB) . chr(0xBF));
    if (isset($fileName)) {
        file_put_contents(OutputPath . $basename . '_correct_emails.csv', implode("\n", array_unique($correctEmail)));
    } else {
        // file_put_contents(OutputPath . 'all_files_correct_emails.csv', chr(0xEF) . chr(0xBB) . chr(0xBF));
        file_put_contents(OutputPath . 'all_files_correct_emails.csv', implode("\n", array_unique($correctEmail)));
    }
    // Запись в файл ошибочных Email
    if (isset($fileName)) {
        file_put_contents(OutputPath . $basename . '_incorrect_emails.csv', implode("\n", $wrongEmails));
    } else {
        file_put_contents(OutputPath . 'all_files_incorrect_emails.csv', implode("\n", $wrongEmails));
    }
?>
    <a class="px-10 py-4 cursor-pointer flex items-center" onclick="javascript:history.back(); return false;">
        <img src="../left_arrow.svg" alt=""><span class="text-red-400">Вернуться на главную</span>
    </a>
    <div class="px-10 py-6 flex flex-col justify-center">
        <span class="text-xl underline">Файл <?php echo $fileName ?> успешно проверен!</span>
        <div class="border-orange-400 bg-amber-300 rounded-md border-2 px-2 py-3 gap-4 flex flex-col w-1/3 my-4">
            <!-- Скачать Правильные Email -->
            <?php if (isset($checkAll)) { ?>
                <a class="border-2 p-3 border-orange-400" href="../output/all_files_correct_emails.csv" download>
                    Скачать файл с <b>корректными</b> Email
                </a>
            <?php } else { ?>
                <a class="border-2 p-3 border-orange-400" href="<?php echo OutputPath;
                                                                echo basename($fileName, '.csv') ?>_correct_emails.csv" download>
                    Скачать файл с <b>корректными</b> Email
                </a>
            <?php } ?>

            <!-- Скачать Неправильные Email -->
            <?php if (isset($checkAll)) { ?>
                <a class="border-2 p-3 border-orange-400" href="../output/all_files_incorrect_emails.csv" download>
                    Скачать файл с <b>неправильными</b> Email
                </a>
            <?php } else { ?>
                <a class="border-2 p-3 border-orange-400" href="<?php echo OutputPath;
                                                                echo basename($fileName, '.csv') ?>_incorrect_emails.csv" download>
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