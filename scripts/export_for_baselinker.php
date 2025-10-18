<?php
/**
 * Autor: npnpdev@gmail.com
 * Export produktów do formatu CSV zgodnego z BaseLinker
 * Konwertuje master_products.csv → products_baselinker_format.csv
 */

require_once __DIR__ . '/../config.php';

log_message("════════════════════════════════════════\n");
log_message("   EXPORT DO FORMATU BASELINKER\n");
log_message("════════════════════════════════════════\n");

$masterFile = MASTER_CSV;
$outputFile = BASELINKER_CSV;

if (!file_exists($masterFile)) {
    log_message("BŁĄD: Nie znaleziono master_products.csv\n");
    log_message("   Uruchom najpierw: parse_xml_supplier.php\n");
    throw new Exception("Brak pliku master_products.csv");
}

log_message("Wczytywanie master_products.csv...\n");

$masterProducts = [];
$csvHandle = fopen($masterFile, 'r');

// Pomiń BOM
$bom = fread($csvHandle, 3);
if ($bom !== "\xEF\xBB\xBF") {
    rewind($csvHandle);
}

// Pomiń nagłówek
$header = fgetcsv($csvHandle);

while (($row = fgetcsv($csvHandle)) !== false) {
    $masterProducts[] = [
        'sku' => $row[0],
        'ean' => $row[1],
        'name' => $row[2],
        'stock' => $row[3],
        'price' => $row[4]
    ];
}

fclose($csvHandle);

log_message("Wczytano: " . count($masterProducts) . " produktów\n\n");

// Zapisz w formacie BaseLinker
log_message("Zapisywanie products_baselinker_format.csv...\n");

$output = fopen($outputFile, 'w');

// BOM dla UTF-8 (Excel compatibility)
fwrite($output, "\xEF\xBB\xBF");

// Nagłówek zgodny z BaseLinker
fputcsv($output, [
    'product_id',
    'ean',
    'name',
    'stock',
    'price_brutto',
    'tax_rate',
    'weight',
    'sku'
]);

foreach ($masterProducts as $p) {
    fputcsv($output, [
        '',                    // product_id - puste (BL wypełni)
        $p['ean'],
        $p['name'],
        $p['stock'],
        $p['price'],
        '23',                  // VAT 23%
        '0',                  
        $p['sku']
    ]);
}

fclose($output);

log_message("Zapisano: $outputFile\n");
log_message("   Produktów: " . count($masterProducts) . "\n\n");

log_message("════════════════════════════════════════\n");
log_message("EXPORT ZAKOŃCZONY!\n");
log_message("════════════════════════════════════════\n");
log_message("\nPlik gotowy do importu w BaseLinker:\n");
log_message("-> products_baselinker_format.csv\n\n");
log_message("[OSTRZEŻENIE]  UWAGA PRZED IMPORTEM:\n");
log_message("════════════════════════════════════════\n");
log_message("Ten plik zawiera WSZYSTKIE produkty z XML.\n");
log_message("Podczas importu przez panel BaseLinker:\n");
log_message("  1. Wybierz opcję UPDATE (nie dodawanie nowych)\n");
log_message("  2. Mapuj produkty po EAN\n");
log_message("  3. BaseLinker zaktualizuje istniejące produkty\n\n");
log_message("ZALECENIE:\n");
log_message("  - Automatyczna synchronizacja przez API (CRON)\n");
log_message("    działa bezpieczniej i pomija produkty z wariantami\n");
log_message("    oraz duplikaty EAN.\n");
log_message("  - Import CSV używaj tylko w przypadku problemów\n");
log_message("    z automatyczną synchronizacją.\n");
log_message("════════════════════════════════════════\n\n");