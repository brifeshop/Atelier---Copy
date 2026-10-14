<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Warehouse\Location;

// Buka output
$fp = fopen('php://output', 'w');

// Header CSV
fputcsv($fp, ['kode', 'nama', 'warehouse', 'capacity', 'notes', 'is_active']);

// Data
$locations = Location::with('warehouse')->orderBy('kode')->get();

foreach ($locations as $loc) {
    fputcsv($fp, [
        $loc->kode,
        $loc->nama,
        $loc->warehouse->nama ?? '',
        $loc->capacity ?? '',
        $loc->notes ?? '',
        $loc->is_active ? '1' : '0',
    ]);
}

fclose($fp);