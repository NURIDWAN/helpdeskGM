<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Auth;

// Login the user who owns the request (user_id = 4)
$user = User::find(4);
Auth::setUser($user);
Auth::shouldUse('sanctum'); // Ensure sanctum guard is active

try {
    $controller = app(\App\Http\Controllers\FormPermintaanController::class);
    $response = $controller->downloadPdf(1);
    
    $content = $response->getContent();
    file_put_contents(__DIR__ . '/test.pdf', $content);
    echo "PDF successfully generated and saved to test.pdf. Size: " . strlen($content) . " bytes\n";
} catch (\Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
