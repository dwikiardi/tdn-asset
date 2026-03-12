<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Customer;
use App\Models\AssetUnit;

$results = [];
$customers = Customer::all();
foreach ($customers as $c) {
    $count = AssetUnit::where('customer_id', $c->id)->where('status', 'deployed')->count();
    $results[] = [
        'id' => $c->id,
        'external_id' => $c->external_id,
        'name' => $c->name,
        'asset_count' => $count
    ];
}

echo json_encode($results, JSON_PRETTY_PRINT) . PHP_EOL;
