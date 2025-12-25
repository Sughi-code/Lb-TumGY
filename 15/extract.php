<?php
// Читаем содержимое HTML-файла
$htmlContent = file_get_contents('index.html');

// Используем регулярные выражения для поиска элементов с классом "highlight"
// Сначала находим все div-элементы с классом "highlight"
preg_match_all('/<div class="highlight">(.*?)<\/div>/s', $htmlContent, $matches);

$extractedTextLines = [];

if (isset($matches[1])) {
    foreach ($matches[1] as $match) {
        // Заменяем <br> теги на символы новой строки
        $contentWithLineBreaks = preg_replace('/<br\s*\/?>/i', "\n", $match);
        
        // Заменяем &nbsp; на обычные пробелы перед удалением тегов
        $contentWithLineBreaks = str_replace('&nbsp;', ' ', $contentWithLineBreaks);
        
        // Извлекаем текст из span-элементов, сохраняя цветовую разметку
        // Убираем все HTML-теги, кроме перевода строк
        $textOnly = strip_tags($contentWithLineBreaks);
        
        // Декодируем HTML-сущности
        $decodedText = html_entity_decode($textOnly, ENT_HTML5, 'UTF-8');
        
        // Разбиваем на строки по символу новой строки
        $lines = explode("\n", $decodedText);
        
        foreach ($lines as $line) {
            // Заменяем множественные пробелы на один, но оставляем символы новой строки
            $normalizedLine = preg_replace('/[ \t]+/', ' ', $line);
            $trimmedLine = trim($normalizedLine);
            if (!empty($trimmedLine)) {
                $extractedTextLines[] = $trimmedLine;
            }
        }
    }
}

// Записываем извлеченные строки в файл answer.txt
file_put_contents('answer.txt', implode("\n", $extractedTextLines), LOCK_EX);

// Подсчитываем количество вхождений строки "?php"
$searchText = "?php";
$count = 0;
foreach ($extractedTextLines as $line) {
    // Подсчитываем количество вхождений в каждой строке
    $count += substr_count(strtolower($line), strtolower($searchText));
}

echo "Извлечение завершено. Файл answer.txt создан.\n";
echo "Количество вхождений строки '?php': " . $count . "\n";
?>