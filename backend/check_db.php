<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$total = \App\Models\Data::count();
echo "Total data di tabel 'data': $total\n\n";

$sample = \App\Models\Data::take(5)->get();
echo "5 Data pertama:\n";
echo str_repeat('-', 100) . "\n";
foreach ($sample as $d) {
    echo "ID: {$d->id}\n";
    echo "  Hostname : {$d->title}\n";
    echo "  Lokasi   : {$d->category}\n";
    echo "  RAM      : {$d->value} MB\n";
    echo "  Status   : {$d->status}\n";
    $meta = $d->metadata;
    if ($meta) {
        echo "  Device ID    : " . ($meta['device_id'] ?? '-') . "\n";
        echo "  Manufacturer : " . ($meta['manufacturer'] ?? '-') . "\n";
        echo "  Model        : " . ($meta['model'] ?? '-') . "\n";
        echo "  OS           : " . ($meta['os'] ?? '-') . "\n";
        echo "  Serial       : " . ($meta['serial_number'] ?? '-') . "\n";
        echo "  Last Checkin : " . ($meta['last_checkin'] ?? '-') . "\n";
    }
    echo str_repeat('-', 100) . "\n";
}

echo "\nImport History:\n";
$histories = \App\Models\ImportHistory::latest()->take(5)->get();
foreach ($histories as $h) {
    echo "  [{$h->created_at}] {$h->filename} - Berhasil: {$h->success_count}, Gagal: {$h->error_count}, Peringatan: {$h->warning_count}\n";
}
