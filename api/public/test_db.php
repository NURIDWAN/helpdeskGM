<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$attachments = \App\Models\FormPermintaanAttachment::all()->toArray();
echo json_encode($attachments, JSON_PRETTY_PRINT);
