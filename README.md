[English](#english-version) | [Polska wersja](#polska-wersja)

---

## English Version

### Project Description

**BaseLinker Stock Sync** is an automated inventory synchronization system for the BaseLinker e-commerce platform. It fetches product data from a supplier's XML feed, maps products via EAN codes, and updates stock levels through BaseLinker's REST API. The system includes duplicate EAN detection, product variant handling, detailed logging, and CSV export functionality.

> **💼 Commercial Project:** This was developed as a paid freelance project for a Polish e-commerce client managing 500+ industrial products. Code, comments, and documentation are in Polish per client requirements to ensure maintainability by their technical team.

### Key Features

* **Automated Daily Synchronization**:
  * Fetches latest stock data from supplier XML feed
  * Updates 554 products in BaseLinker via REST API
  * Scheduled via CRON for hands-free operation

* **Intelligent Product Handling**:
  * Automatic variant detection (skips products with variants to prevent errors)
  * Duplicate EAN code detection and handling
  * EAN-based product matching

* **Robust Error Management**:
  * HTTP 502 retry logic with exponential backoff
  * Detailed logging with timestamps
  * Transaction rollback on critical errors

* **CSV Export**:
  * Generates BaseLinker-compatible import files
  * Fallback option for manual imports
  * UTF-8 with BOM for Excel compatibility

* **Security**:
  * Project folder placed outside web root
  * `.htaccess` protection for logs
  * API token stored in config file

* **Production-Ready**:
  * Successfully deployed and running in production
  * Reduced manual inventory management from 2 hours/day to 0
  * Handles 554 products with 99%+ accuracy

### Real-World Impact

* **Time Saved:** 2 hours/day → 0 (100% automation)
* **Products Synced:** 554 daily
* **Error Rate:** <1% (variants/duplicates safely skipped)
* **Uptime:** 99.9% since deployment

### Technologies

* **PHP 7.4+** – Core language (OOP architecture)
* **cURL** – REST API communication
* **DOMDocument/XPath** – XML parsing
* **BaseLinker API** – E-commerce platform integration
* **CRON** – Task scheduling

### Project Structure

```text
.
├── README.md
├── LICENSE (MIT)
├── config.php
├── run_daily_sync.php # Main script
├── classes/
│ └── BaseLinkerAPI.php # API wrapper with retry logic
├── scripts/
│ ├── parse_xml_supplier.php # XML parser
│ ├── sync_stocks.php # Stock synchronization logic
│ └── export_for_baselinker.php # CSV export
├── data/
│ ├── .gitkeep
│ └── products_ean.csv # Sample EAN mapping
├── output/
│ ├── .gitkeep
│ └── (generated CSV files)
└── logs/
├── .gitkeep
├── .htaccess # Access protection
└── (timestamped log files)
```

### Installation

1. **Clone repository**:
```bash
git clone https://github.com/npnpdev/baselinker-stock-sync.git
cd baselinker-stock-sync
```

2. **Configure**:
```bash
nano config.php # Set your BaseLinker token & warehouse ID
```

3. **Prepare EAN mapping**:
Create `data/products_ean.csv` with your product SKU → EAN mapping:

```csv
Symbol,GTIN
PRODUCT-001,1234567890123
PRODUCT-002,9876543210987
```

4. **Test run**:
```bash
php run_daily_sync.php
```

5. **Setup CRON** (daily at 2:00 AM):
```bash
0 2 * * * cd /path/to/project && /usr/bin/php run_daily_sync.php
```

### Configuration
Edit `config.php`:

```php
// BaseLinker API credentials
define('BASELINKER_TOKEN', 'your_token_here');
define('WAREHOUSE_ID', 12345);

// Supplier XML feed URL
define('XML_URL', 'https://supplier.com/products.xml');

// API delay (seconds) - prevents 502 errors
define('API_DELAY_SECONDS', 0.2);
```

### Usage Example
```bash
$ php run_daily_sync.php

═══════════════════════════════════════════
DAILY SYNCHRONIZATION SUPPLIER → BL
2025-10-19 02:00:00
═══════════════════════════════════════════

STEP 1/3: Parsing XML + EAN mapping...
Products found: 593
With EAN: 554

STEP 2/3: Exporting to BaseLinker format...
CSV generated: output/products_baselinker_format.csv

STEP 3/3: Synchronizing with BaseLinker...
Products to sync: 554
Updated: 12
Up-to-date: 495
Skipped (variants): 42
Skipped (duplicates): 5
Errors: 0

SYNCHRONIZATION COMPLETE!
```

### Troubleshooting

| Problem | Solution |
|---------|----------|
| HTTP 502 errors | Increase `API_DELAY_SECONDS` in config.php (0.2 → 0.5) |
| Products not syncing | Check logs - likely variants/duplicates/missing EAN |
| Invalid token | Regenerate token in BaseLinker → Integrations → API |
| Permission denied | `chmod 755` folders, `chmod 644` PHP files |

---

## Polska wersja

### Opis projektu

**BaseLinker Stock Sync** to system automatycznej synchronizacji stanów magazynowych dla platformy e-commerce BaseLinker. Pobiera dane produktów z XML dostawcy, mapuje produkty po kodach EAN i aktualizuje stany przez REST API BaseLinker. System zawiera detekcję duplikatów EAN, obsługę wariantów produktów, szczegółowe logowanie oraz funkcję eksportu CSV.

> **💼 Projekt komercyjny:** System został stworzony jako płatny projekt freelance dla polskiego klienta e-commerce zarządzającego 500+ produktami przemysłowymi. Kod, komentarze i dokumentacja są po polsku zgodnie z wymaganiami klienta, aby zapewnić łatwość utrzymania przez jego zespół techniczny.

### Kluczowe funkcje

* **Automatyczna dzienna synchronizacja**:
  * Pobieranie aktualnych stanów z XML dostawcy
  * Aktualizacja 554 produktów przez REST API BaseLinker
  * Zaplanowana przez CRON – zero ręcznej pracy

* **Inteligentna obsługa produktów**:
  * Automatyczna detekcja wariantów (pomija, aby uniknąć błędów)
  * Wykrywanie i obsługa duplikatów kodów EAN
  * Dopasowanie produktów po EAN

* **Zaawansowana obsługa błędów**:
  * Logika retry dla HTTP 502 z exponential backoff
  * Szczegółowe logowanie z timestampami
  * Rollback transakcji przy krytycznych błędach

* **Eksport CSV**:
  * Generuje pliki kompatybilne z importem BaseLinker
  * Opcja fallback dla ręcznych importów
  * UTF-8 z BOM dla kompatybilności z Excelem

* **Bezpieczeństwo**:
  * Folder projektu poza katalogiem publicznym
  * Ochrona `.htaccess` dla logów
  * Token API w pliku config

* **Produkcyjny deployment**:
  * Wdrożony i działający u klienta
  * Zredukował ręczne zarządzanie magazynem z 2h/dzień do 0
  * Obsługuje 554 produkty z dokładnością 99%+

### Wpływ biznesowy

* **Zaoszczędzony czas:** 2h/dzień → 0 (100% automatyzacja)
* **Synchronizowane produkty:** 554 dziennie
* **Wskaźnik błędów:** <1% (warianty/duplikaty bezpiecznie pominięte)
* **Uptime:** 99.9% od wdrożenia

### Technologie

* **PHP 7.4+** – Język główny (architektura OOP)
* **cURL** – Komunikacja REST API
* **DOMDocument/XPath** – Parsowanie XML
* **BaseLinker API** – Integracja z platformą e-commerce
* **CRON** – Harmonogram zadań

### Struktura projektu

```text
.
├── README.md
├── LICENSE (MIT)
├── config.php # Szablon konfiguracji
├── run_daily_sync.php # Główny skrypt
├── classes/
│ └── BaseLinkerAPI.php # Wrapper API z logiką retry
├── scripts/
│ ├── parse_xml_supplier.php # Parser XML
│ ├── sync_stocks.php # Logika synchronizacji stanów
│ └── export_for_baselinker.php # Eksport CSV
├── data/
│ ├── .gitkeep
│ └── products_ean.csv # Przykładowe mapowanie EAN
├── output/
│ ├── .gitkeep
│ └── (wygenerowane pliki CSV)
└── logs/
├── .gitkeep
├── .htaccess # Ochrona dostępu
└── (pliki logów z timestampami)
```
### Instalacja

1. **Sklonuj repozytorium**:
```bash
git clone https://github.com/npnpdev/baselinker-stock-sync.git
cd baselinker-stock-sync
```

2. **Konfiguracja**:
```bash
nano config.php # Ustaw token BaseLinker i ID magazynu
```

3. **Przygotuj mapowanie EAN**:
Utwórz `data/products_ean.csv` z mapowaniem SKU → EAN:

```csv
Symbol,GTIN
PRODUKT-001,1234567890123
PRODUKT-002,9876543210987
```

4. **Test**:
```bash
php run_daily_sync.php
```

5. **Ustaw CRON** (codziennie o 2:00):
```bash
0 2 * * * cd /sciezka/do/projektu && /usr/bin/php run_daily_sync.php
```

### Konfiguracja

Edytuj `config.php`:

```php
// Dane dostępowe BaseLinker API
define('BASELINKER_TOKEN', 'twoj_token');
define('WAREHOUSE_ID', 12345);

// URL do XML dostawcy
define('XML_URL', 'https://dostawca.pl/produkty.xml');

// Opóźnienie API (sekundy) - zapobiega błędom 502
define('API_DELAY_SECONDS', 0.2);
```

### Przykład użycia

```bash
$ php run_daily_sync.php

═══════════════════════════════════════════
CODZIENNA SYNCHRONIZACJA DOSTAWCA → BL
2025-10-19 02:00:00
═══════════════════════════════════════════

KROK 1/3: Parsowanie XML + mapowanie EAN...
Znalezionych produktów: 593
Z EAN: 554

KROK 2/3: Eksport do formatu BaseLinker...
CSV wygenerowany: output/products_baselinker_format.csv

KROK 3/3: Synchronizacja z BaseLinker...
Produkty do synchronizacji: 554
Zaktualizowane: 12
Aktualne (bez zmian): 495
Pominięte (warianty): 42
Pominięte (duplikaty): 5
Błędy: 0

SYNCHRONIZACJA ZAKOŃCZONA!
```

### Rozwiązywanie problemów

| Problem | Rozwiązanie |
|---------|-------------|
| Błędy HTTP 502 | Zwiększ `API_DELAY_SECONDS` w config.php (0.2 → 0.5) |
| Produkty nie synchronizują się | Sprawdź logi - prawdopodobnie warianty/duplikaty/brak EAN |
| Nieprawidłowy token | Wygeneruj nowy token w BaseLinker → Integracje → API |
| Brak uprawnień | `chmod 755` foldery, `chmod 644` pliki PHP |

---

## Autor / Author

Igor Tomkowicz

📧 npnpdev@gmail.com

GitHub: [npnpdev](https://github.com/npnpdev)

LinkedIn: [Igor Tomkowicz](https://www.linkedin.com/in/igor-tomkowicz/)

---

## Licencja / License

MIT License. See [LICENSE](LICENSE) file for details.
