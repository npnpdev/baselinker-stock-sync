# System Synchronizacji Supplier → BaseLinker

Automatyczna synchronizacja stanów magazynowych produktów STAIRS.

Autor: npnpdev@gmail.com | Wersja: 1.0 | Data: 2025-10-19

---

## Opis

System automatycznie:
1. Pobiera dane z XML Supplier
2. Łączy z kodami EAN (data/products_ean.csv)
3. Synchronizuje stany do BaseLinker przez API
4. Generuje logi i raport CSV (plik zgodny z BL)

Funkcje:
- Automatyczna synchronizacja stanów
- Wykrywanie wariantów i duplikatów EAN
- Szczegółowe logowanie
- Export CSV dla ręcznego importu

---

## Wymagania

- PHP 7.4+ z rozszerzeniami: curl, dom, libxml
- Token API BaseLinker
- ID magazynu BaseLinker
- Plik data/products_ean.csv z mapowaniem Symbol → EAN

---

## Instalacja

1. Upload plików na serwer (FTP/SFTP)
2. Ustaw uprawnienia: chmod 755 na foldery, chmod 644 na pliki PHP
3. Edytuj config.php:
   define('BASELINKER_TOKEN', 'TUTAJ_WKLEJ_TOKEN');
   define('WAREHOUSE_ID', 12345);

Gdzie znaleźć:
- Token API: Panel BaseLinker → Integracje → API
- ID magazynu: Panel → Magazyny → URL: inventory_id=XXXXX

---

## Uruchomienie

Ręcznie (test):
php run_daily_sync.php

Automatycznie (CRON) - codziennie o 2:00:
0 2 * * * cd /sciezka/do/projektu && /usr/bin/php run_daily_sync.php

---

## Struktura

baselinker_sync/
├── README.md
├── config.php                  # Konfiguracja (TOKEN, WAREHOUSE_ID)
├── run_daily_sync.php          # GŁÓWNY SKRYPT
├── classes/
│   └── BaseLinkerAPI.php
├── scripts/
│   ├── parse_xml_supplier.php
│   ├── export_for_baselinker.php
│   └── sync_stocks.php
├── data/
│   └── products_ean.csv        # Symbol → EAN (WAŻNE!)
├── output/
│   ├── master_products.csv
│   └── products_baselinker_format.csv
└── logs/
    └── run_YYYY-MM-DD.log

---

## Logi

Lokalizacja: logs/run_YYYY-MM-DD_HH-MM-SS.log

Przykład podsumowania:
Produkty do synchronizacji:  554
Zaktualizowane:              1
Aktualne (bez zmian):        496
Pominięte (warianty):        49
Pominięte (duplikaty EAN):   8
Błędy:                       0

---

## Import CSV (opcjonalny)

Plik output/products_baselinker_format.csv można zaimportować ręcznie w BaseLinker:
1. Panel → Magazyn → Import
2. Wybierz opcję "Aktualizuj istniejące"
3. Mapowanie po EAN

UWAGA: Produkty z wariantami i duplikatami EAN mogą być błędnie zaktualizowane. Zalecamy automatyczną synchronizację przez API.

---

## Rozwiązywanie problemów

HTTP 502: Zwiększ API_DELAY_SECONDS w config.php (z 0.2 na 0.5)

Brak pliku CSV: Sprawdź data/products_ean.csv (format: Symbol,EAN)

Invalid token: Wygeneruj nowy token w BaseLinker → Integracje → API

Produkty nie synchronizują się: Sprawdź logi - możliwe warianty/duplikaty/brak EAN

---

## Kontakt

Developer: npnpdev@gmail.com