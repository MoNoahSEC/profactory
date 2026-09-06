<?php
$dirs = [
    __DIR__ . '/resources/views',
    __DIR__ . '/app/Http/Controllers',
    __DIR__ . '/app/Models',
    __DIR__ . '/app/Services'
];

$replacements = [
    'العامل' => 'الموظف',
    'العمال' => 'الموظفين',
    'عامل' => 'موظف',
    'عمال' => 'موظفين',
    'العاملين' => 'الموظفين',
    'عاملين' => 'موظفين',
    'للعامل' => 'للموظف',
    'للعمال' => 'للموظفين',
    'بعامل' => 'بموظف',
    'بعمال' => 'بموظفين',
];

$modifiedCount = 0;

function processDir($dir) {
    global $replacements, $modifiedCount;
    $files = scandir($dir);
    foreach ($files as $file) {
        if ($file == '.' || $file == '..') continue;
        $path = $dir . '/' . $file;
        if (is_dir($path)) {
            processDir($path);
        } else if (pathinfo($path, PATHINFO_EXTENSION) == 'php') {
            $content = file_get_contents($path);
            $newContent = strtr($content, $replacements);
            if ($newContent !== $content) {
                file_put_contents($path, $newContent);
                echo "Modified: $path\n";
                $modifiedCount++;
            }
        }
    }
}

foreach ($dirs as $dir) {
    if (is_dir($dir)) {
        processDir($dir);
    }
}

echo "Total files modified: $modifiedCount\n";
