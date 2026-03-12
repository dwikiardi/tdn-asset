<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Http\Controllers\Api\CustomerSyncController;
use Illuminate\Http\Request;

$controller = app(CustomerSyncController::class);

echo "--- CID: 0018-1118 (Amalfi) ---\n";
$resp1 = $controller->assetsByExternalId('0018-1118');
echo $resp1->getContent() . "\n\n";

echo "--- CID: 0016-1118 (Villa Azaya) ---\n";
$resp2 = $controller->assetsByExternalId('0016-1118');
echo $resp2->getContent() . "\n";
