<?php
require __DIR__ . '/vendor/autoload.php';
$Arabic = new \Arphp\Glyphs();

$names = [
    'سجانه 35x75',
    'قفص 60×40',
    'قفص 47×30',
    '3 اكاله صغير',
];

foreach ($names as $name) {
    $clean = str_replace('×', 'x', $name);
    echo "Original: $name\n";
    echo "Fixed: " . $Arabic->utf8Glyphs($clean, 100, false) . "\n";
    echo "---\n";
}
