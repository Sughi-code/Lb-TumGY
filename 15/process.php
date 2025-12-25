<?php

// Read the HTML file
$htmlContent = file_get_contents('example.html');

// Create the answer.txt file
$answerFile = fopen('answer.txt', 'w');
if ($answerFile === false) {
    die("Could not create answer.txt");
}

// Looking for code blocks with light blue background based on CSS
// Elements with classes like "highlight" that are inside containers with light blue backgrounds
$dom = new DOMDocument();
libxml_use_internal_errors(true); // Suppress warnings for malformed HTML
$dom->loadHTML($htmlContent);

// Find spans with highlight class (which based on CSS would have colored font)
$xpath = new DOMXPath($dom);
$highlightElements = $xpath->query("//span[contains(@class, 'highlight')]");

foreach ($highlightElements as $element) {
    $text = $element->textContent;
    $decodedText = html_entity_decode($text);
    if (!empty(trim($decodedText))) {
        fwrite($answerFile, $decodedText . "\n");
    }
}

fclose($answerFile);

// Count occurrences of "?php" (case insensitive)
$fullText = file_get_contents('answer.txt');
$phpCount = substr_count(strtolower($fullText), '?php');

echo "Extraction completed. Content written to answer.txt\n";
echo "Number of '?php' occurrences: " . $phpCount . "\n";

?>