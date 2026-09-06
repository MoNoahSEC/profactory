<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = \App\Models\User::where('name', 'like', '%محمد%')->first();
if ($user) {
    $user->password = bcrypt('12345678');
    $user->save();
    echo "Password updated successfully for: " . $user->name . "\n";
} else {
    echo "User not found.\n";
}
