<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Simulate admin login
$user = App\Models\User::first();
auth()->login($user);

$request = Illuminate\Http\Request::create('/salaries', 'GET');
$controller = new App\Http\Controllers\SalaryController(new App\Services\WorkerWageCalculationService());
$view = $controller->index($request);

$html = $view->render();
file_put_contents(__DIR__.'/salaries_output.html', $html);
echo "HTML dumped to salaries_output.html\n";
