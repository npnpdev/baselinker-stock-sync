<?php
/**
 * Autor: npnpdev@gmail.com
 * MAIN SCRIPT - Codzienna synchronizacja Supplier → BaseLinker
 */

require_once __DIR__ . '/config.php';

// Tworzymy plik log
$logFile = __DIR__ . '/logs/run_' . date('Y-m-d_H-i-s') . '.log';
$GLOBALS['logFile'] = $logFile;

if (!is_dir(__DIR__ . '/logs')) {
    mkdir(__DIR__ . '/logs', 0755, true);
}

log_message("═══════════════════════════════════════════\n");
log_message("   CODZIENNA SYNCHRONIZACJA Supplier → BL\n");
log_message("   " . date('Y-m-d H:i:s') . "\n");
log_message("═══════════════════════════════════════════\n\n");

// KROK 1: Parse XML + Excel → master CSV
log_message("- KROK 1/3: Parsowanie XML + Excel...\n");
require_once __DIR__ . '/scripts/parse_xml_supplier.php';
log_message("\n");

// KROK 2: Export do formatu BaseLinker
log_message("- KROK 2/3: Export do formatu BaseLinker...\n");
require_once __DIR__ . '/scripts/export_for_baselinker.php';
log_message("\n");

// KROK 3: Sync stanów do BaseLinker (API)
log_message("- KROK 3/3: Synchronizacja z BaseLinker...\n");
require_once __DIR__ . '/scripts/sync_stocks.php';

log_message("\n═══════════════════════════════════════════\n");
log_message(" SYNCHRONIZACJA ZAKOŃCZONA!\n");
log_message("═══════════════════════════════════════════\n");
