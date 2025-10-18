<?php
/**
 * Autor: npnpdev@gmail.com
 * Parser XML Supplier → MASTER CSV
 * Pobiera dane z XML, łączy z EAN, generuje plik gotowy dla skryptu
 * NIE MODYFIKUJE BaseLinker - tylko tworzy plik CSV
 */

require_once __DIR__ . '/../config.php';

log_message("════════════════════════════════════════\n");
log_message("   PARSER XML SUPPLIER → MASTER CSV\n");
log_message("════════════════════════════════════════\n\n");

// --- KROK 1: Pobieramy XML Supplier ---
log_message("Krok 1: Pobieranie XML Supplier...\n");

$xmlContent = @file_get_contents(XML_URL);
if (!$xmlContent) {
    log_message("BŁĄD: Nie można pobrać XML z: " . XML_URL . "\n");
    throw new Exception("Nie można pobrać XML z Supplier");
}

log_message("XML pobrany (" . strlen($xmlContent) . " bajtów)\n\n");

// --- KROK 2: Parsowanie HTML (to jest tabela HTML) ---
log_message("Krok 2: Parsowanie tabeli...\n");

$dom = new DOMDocument();
@$dom->loadHTML($xmlContent);
$xpath = new DOMXPath($dom);

// Znajdź wszystkie wiersze tabeli
$rows = $xpath->query('//tr');

$xmlProducts = [];
$rowCount = 0;

foreach ($rows as $row) {
    $cols = $row->getElementsByTagName('td');
    
    // Pomiń wiersz jeśli ma mniej niż 6 kolumn - to nie jest produkt
    if ($cols->length < 6) continue;
    
    $kodProduktu = trim($cols->item(0)->textContent);
    $symbol = trim($cols->item(1)->textContent); // Indeks naz.skrócona
    $nazwaPelna = trim($cols->item(2)->textContent);
    $kategoria = trim($cols->item(3)->textContent);
    $stan = trim($cols->item(4)->textContent);
    
    // Sprawdź czy to produkt STAIRS (symbol zaczyna się od ST-)
    if (stripos($symbol, 'ST-') !== 0) continue;
    
    // Cena = 0 (zgodnie z wymaganiami)
    $cena = 0;
    
    $xmlProducts[$symbol] = [
        'kod' => $kodProduktu,
        'symbol' => $symbol,
        'nazwa' => $nazwaPelna,
        'kategoria' => $kategoria,
        'stan' => intval($stan),
        'cena' => $cena
    ];
    
    $rowCount++;
}

log_message("Znaleziono produktów STAIRS: $rowCount\n\n");

// --- KROK 3: Wczytujemy plik EAN ---
log_message("Krok 3: Wczytywanie kodów EAN...\n");

if (!file_exists(CSV_FILE)) {
    log_message("BŁĄD: Nie znaleziono pliku: " . CSV_FILE . "\n");
    throw new Exception("Nie znaleziono pliku CSV z kodami EAN");
}

$eanData = [];
$csvHandle = fopen(CSV_FILE, 'r');

// Pomijamy nagłówek
$header = fgetcsv($csvHandle);

while (($row = fgetcsv($csvHandle)) !== false) {
    $symbol = trim($row[0]);
    $ean = trim($row[1]);
    $eanData[$symbol] = $ean;
}

fclose($csvHandle);

log_message("Wczytano kodów EAN: " . count($eanData) . "\n\n");

// --- KROK 4: Łączymy dane ---
log_message("Krok 4: Łączenie danych (XML + EAN)...\n");

$masterProducts = [];
$withEan = 0;
$withoutEan = 0;

foreach ($xmlProducts as $symbol => $product) {
    if (isset($eanData[$symbol])) {
        $product['ean'] = $eanData[$symbol];
        $withEan++;
    } else {
        $product['ean'] = '';
        $withoutEan++;
    }
    
    $masterProducts[] = $product;
}

log_message("Produkty z EAN: $withEan\n");
log_message("Produkty bez EAN: $withoutEan\n\n");

// --- KROK 5: Generujemy MASTER CSV (format dla skryptu) ---
log_message("Krok 5: Generowanie MASTER CSV...\n");

$outputFile = MASTER_CSV;
$csvOut = fopen($outputFile, 'w');

// BOM dla UTF-8 (żeby Excel poprawnie odczytał polskie znaki)
fprintf($csvOut, "\xEF\xBB\xBF");

// Nagłówki CSV 
fputcsv($csvOut, [
    'sku',           // Symbol produktu (ST-xxx)
    'ean',           // Kod EAN
    'name',          // Nazwa pełna
    'stock',         // Stan magazynowy
    'price',         // Cena (ustawiona na 0)
    'category',      // Kategoria
    'kod_supplier',    // KOD z XML (dla referencji)
    'last_update'    // Data ostatniej aktualizacji
]);

// Dane produktów (TYLKO te z EAN!)
$savedCount = 0;
foreach ($masterProducts as $p) {
    // Pomijamy produkty bez EAN
    if (empty($p['ean'])) continue;
    
    fputcsv($csvOut, [
        $p['symbol'],
        $p['ean'],
        $p['nazwa'],
        $p['stan'],
        0,  // Cena ustawiona na 0
        $p['kategoria'],
        $p['kod'],
        date('Y-m-d H:i:s')
    ]);
    
    $savedCount++;
}

fclose($csvOut);

log_message("Plik zapisany: $outputFile\n");
log_message("   - Zapisano produktów: $savedCount (tylko z EAN)\n");
log_message("   - Pominięto bez EAN: $withoutEan\n\n");

// --- KROK 6: Statystyki ---
log_message("════════════════════════════════════════\n");
log_message(" PODSUMOWANIE:\n");
log_message("════════════════════════════════════════\n");
log_message("   - Produkty STAIRS w XML: $rowCount\n");
log_message("   - Kody EAN w CSV: " . count($eanData) . "\n");
log_message("   - Produkty z EAN: $withEan\n");
log_message("   - Produkty bez EAN: $withoutEan\n");
log_message("   - Wygenerowany plik: master_products.csv\n");
log_message("   - Ceny: WSZYSTKIE = 0\n");
log_message("════════════════════════════════════════\n");
log_message(" ETAP 2 ZAKOŃCZONY!\n");
log_message("════════════════════════════════════════\n");

?>
