<?php
require __DIR__ . '/vendor/autoload.php';
$Arabic = new \Arphp\Glyphs();

$names = [
    'سجانه 35x75',
    'قفص 60×40',
    '3 اكاله صغير',
];

foreach ($names as $name) {
    echo "Original: $name\n";
    echo "utf8Glyphs (default): " . $Arabic->utf8Glyphs($name) . "\n";
    echo "utf8Glyphs (hindo=false): " . $Arabic->utf8Glyphs($name, 50, false) . "\n";
    echo "---\n";
}
