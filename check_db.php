<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

$columns = Schema::getColumnListing('orders');
echo "Columns in orders table:\n";
print_r($columns);

$transactions = Schema::hasTable('transactions');
echo "\nTransactions table exists: " . ($transactions ? 'Yes' : 'No') . "\n";
