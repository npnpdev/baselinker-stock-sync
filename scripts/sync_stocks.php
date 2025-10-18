<?php
/**
 * Autor: npnpdev@gmail.com
 * Synchronizacja stanów magazynowych
 * Aktualizuje stany w BaseLinker na podstawie master_products.csv
 * 
 * UWAGA: Ten skrypt MODYFIKUJE dane w BaseLinker!
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../classes/BaseLinkerAPI.php';

log_message("════════════════════════════════════════\n");
log_message("   SYNCHRONIZACJA STANÓW MAGAZYNOWYCH\n");
log_message("════════════════════════════════════════\n\n");

// --- KROK 1: Wczytaj MASTER CSV ---
log_message(" Krok 1: Wczytywanie master_products.csv...\n");

$masterFile = MASTER_CSV;
if (!file_exists($masterFile)) {
    log_message(" BŁĄD: Nie znaleziono pliku: $masterFile\n");
    log_message("   Uruchom najpierw: parse_xml_supplier.php\n");
    throw new Exception("Brak pliku master_products.csv");
}

$masterProducts = [];
$csvHandle = fopen($masterFile, 'r');

// Pomiń BOM jeśli istnieje
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
        'stock' => intval($row[3]),
        'price' => floatval($row[4]),
        'category' => $row[5],
        'kod_supplier' => $row[6],
        'last_update' => $row[7] ?? ''
    ];
}

fclose($csvHandle);

log_message(" Wczytano produktów: " . count($masterProducts) . "\n\n");

// --- KROK 2: Pobieramy produkty z BaseLinker ---
log_message(" Krok 2: Pobieranie produktów z BaseLinker...\n");

try {
    $api = new BaseLinkerAPI(
        BASELINKER_TOKEN,
        BASELINKER_API_URL
    );
    
    $blProducts = $api->getInventoryProducts(WAREHOUSE_ID);
    log_message(" Pobrano z BaseLinker: " . count($blProducts) . " produktów\n\n");
    
} catch (Exception $e) {
    log_message(" BŁĄD API: " . $e->getMessage() . "\n");
    throw $e;
}

// --- KROK 3: Indeksujemy produkty BL po EAN ---
log_message(" Krok 3: Indeksowanie produktów po EAN...\n");

$blByEan = [];
$duplicateEans = []; 

foreach ($blProducts as $p) {
    if (!empty($p['ean'])) {
        // Sprawdzamy czy EAN już istnieje (duplikat!)
        if (isset($blByEan[$p['ean']])) {
            $duplicateEans[$p['ean']] = true;
        } else {
            $blByEan[$p['ean']] = $p;
        }
    }
}

log_message(" Produkty w BL z EAN: " . count($blByEan) . "\n");
log_message("  Wykryto duplikatów EAN: " . count($duplicateEans) . "\n\n");

// --- KROK 4: Synchronizacja ---
log_message(" Krok 4: Synchronizacja stanów...\n");
log_message("════════════════════════════════════════\n\n");

$stats = [
    'found' => 0,
    'not_found' => 0,
    'up_to_date' => 0,
    'updated' => 0,
    'skipped_variants' => 0,
    'skipped_duplicates' => 0,
    'errors' => 0
];


$logEntries = [];

foreach ($masterProducts as $mp) {
    $ean = $mp['ean'];
    $newStock = $mp['stock'];
    
    // Sprawdzamy czy produkt istnieje w BL
    if (!isset($blByEan[$ean])) {
        $stats['not_found']++;
        $logEntries[] = "[BRAK] EAN: $ean | SKU: {$mp['sku']} - produkt nie istnieje w BaseLinker";
        continue;
    }
    
    $stats['found']++;
    $blProduct = $blByEan[$ean];
    
    // Pomijamy produkty z duplikatami EAN (nie możemy określić który aktualizować)
    if (isset($duplicateEans[$ean])) {
        $stats['skipped_duplicates']++;
        $logEntries[] = "[DUPLIKAT] EAN: $ean | SKU: {$mp['sku']} - pominięto (duplikat EAN w BL)";
        continue;
    }

    // Pobieramy aktualny stan z BL
    // Stan jest w Array: ['bl_xxxxx' => wartość]
    $blStock = 0;
    $warehouseKey = null;
    if (!empty($blProduct['stock']) && is_array($blProduct['stock'])) {
        // Pobieramy klucz magazynu (bl_xxxxx)
        $warehouseKey = key($blProduct['stock']);
        $blStock = intval($blProduct['stock'][$warehouseKey]);
    }

    // Sprawdzamy czy produkt ma warianty → POMIJAMY (zgodnie z ustaleniem)
    $fullProductCheck = $api->getInventoryProductData($blProduct['id'], WAREHOUSE_ID);
    usleep(API_DELAY_SECONDS * 1000000);  // Konwersja sekund na mikrosekundy
    
    if (!empty($fullProductCheck['variants'])) {
        log_message("   PRODUKT MA WARIANTY - POMIJAM\n\n");
        $stats['skipped_variants']++;
        continue; // Przejdź do następnego produktu
    }

    // Porównaj stany
    if ($blStock === $newStock) {
        $stats['up_to_date']++;
        // Nie logujemy produktów bez zmian
        continue;
    }

    // Stan wymaga aktualizacji
    log_message(" {$mp['sku']} | EAN: $ean\n");
    log_message("   Nazwa: {$mp['name']}\n");
    log_message("   Stan BL: $blStock → Nowy stan: $newStock (różnica: " . ($newStock - $blStock) . ")\n");

    try {
        // Aktualizujemy stan w BaseLinker
        $api->updateStock(
            $blProduct['id'],
            0,
            $newStock,
            WAREHOUSE_ID,
            $warehouseKey
        );
        
        $stats['updated']++;
        $logEntries[] = "[AKTUALIZACJA] EAN: $ean | SKU: {$mp['sku']} | Stary: $blStock → Nowy: $newStock";
        
        log_message("   Zaktualizowano w BaseLinker\n\n");
        
    } catch (Exception $e) {
        $stats['errors']++;
        $logEntries[] = "[BŁĄD] EAN: $ean | SKU: {$mp['sku']} | Error: " . $e->getMessage();
        log_message("   BŁĄD: " . $e->getMessage() . "\n\n");
    }
}

// --- Wypisujemy szczegóły zmian ---
if (!empty($logEntries)) {
    log_message("\n════════════════════════════════════════\n");
    log_message("SZCZEGÓŁY ZMIAN:\n");
    log_message("════════════════════════════════════════\n");
    foreach ($logEntries as $entry) {
        log_message($entry . "\n");
    }
}

// --- KROK 6: Podsumowanie ---
log_message("════════════════════════════════════════\n");
log_message(" PODSUMOWANIE SYNCHRONIZACJI\n");
log_message("════════════════════════════════════════\n");
log_message("Produkty do synchronizacji:  " . count($masterProducts) . "\n");
log_message("Znalezione w BaseLinker:     {$stats['found']}\n");
log_message("Nie znalezione w BL:         {$stats['not_found']}\n");
log_message("Aktualne (bez zmian):        {$stats['up_to_date']}\n");
log_message("Zaktualizowane:              {$stats['updated']}\n");
log_message("Pominięte (warianty):        {$stats['skipped_variants']}\n");
log_message("Pominięte (duplikaty EAN):   {$stats['skipped_duplicates']}\n");
log_message("Błędy:                       {$stats['errors']}\n");
log_message("════════════════════════════════════════\n");

log_message("\n[SUKCES] ETAP 3 ZAKOŃCZONY!\n");
log_message("════════════════════════════════════════\n");
?>