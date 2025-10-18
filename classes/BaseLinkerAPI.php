<?php
/**
 * Autor: npnpdev@gmail.com
 * BaseLinker API - Klasa do komunikacji z API
 * Obsługuje: pobieranie produktów, aktualizację stanów
 */

class BaseLinkerAPI {
    private $token;
    private $apiUrl;
    
    public function __construct($token, $apiUrl) {
    $this->token = $token;
    $this->apiUrl = $apiUrl;
    }
    
    /**
     * Uniwersalna metoda do wywołań API
     */
    private function call($method, $parameters = []) {
        $data = [
            'token' => $this->token,
            'method' => $method,
            'parameters' => json_encode($parameters)
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->apiUrl);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            if ($httpCode === 502) {
                // 502 = przeciążenie, czekaj i retry
                log_message("HTTP 502 otrzymany od BaseLinker API. Czekam 3 sekundy i ponawiam próbę...\n");
                sleep(3);
                
                // Powtórz request
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $this->apiUrl);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                
                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                
                if ($httpCode !== 200) {
                    throw new Exception("HTTP Error: $httpCode (po ponownej próbie)");
                }
                
                $result = json_decode($response, true);
            } else {
                throw new Exception("HTTP Error: $httpCode");
            }
        } else {
            $result = json_decode($response, true);
        }

        if (!$result || $result['status'] !== 'SUCCESS') {
            $error = $result['error_message'] ?? 'Unknown error';
            throw new Exception("API Error: $error");
        }

        return $result;
    }
    
    /**
     * Pobiera listę magazynów (inventories)
     */
    public function getInventories() {
        log_message("Pobieram listę magazynów...\n");
        return $this->call('getInventories');
    }
    
    /**
     * Pobiera wszystkie produkty z danego magazynu
     */
    public function getInventoryProducts($inventoryId) {
        log_message("Pobieram produkty z magazynu ID: $inventoryId...\n");
        
        $allProducts = [];
        $page = 1;
        
        do {
            $result = $this->call('getInventoryProductsList', [
                'inventory_id' => $inventoryId,
                'page' => $page
            ]);
            
            if (!empty($result['products'])) {
                $allProducts = array_merge($allProducts, $result['products']);
                log_message("   └─ Strona $page: " . count($result['products']) . " produktów\n");
            }
            
            $page++;
        } while (!empty($result['products']));
        
        log_message("Łącznie pobrano " . count($allProducts) . " produktów z magazynu ID: $inventoryId\n");
        return $allProducts;
    }
    
    /**
     * Aktualizuje stan magazynowy produktu
     * MODYFIKUJE DANE W BASELINKER!
     */
    public function updateStock($productId, $variantId, $newStock, $inventoryId, $warehouseKey) {        
        log_message("AKTUALIZACJA W BASELINKER:\n");
        log_message("Product ID: $productId, Warehouse: $warehouseKey, Nowy stan: $newStock\n");
        
        // Format dla BaseLinker:
        // Produkt główny: products[PRODUCT_ID][WAREHOUSE_KEY] = STOCK
        $products = [];
        
        if ($variantId > 0) {
            // WARIANT!
            $productKey = $productId . ':' . $variantId;
            $products[$productKey] = [
                $warehouseKey => $newStock
            ];
        } else {
            // Produkt główny (bez wariantów)
            $products[$productId] = [
                $warehouseKey => $newStock
            ];
        }
        
        return $this->call('updateInventoryProductsStock', [
            'inventory_id' => $inventoryId,
            'products' => $products
        ]);
    }


    /**
     * Pobiera PEŁNE dane produktu (z wariantami)
     */
    public function getInventoryProductData($productId, $inventoryId) {
        log_message("Pobieram pełne dane produktu ID: $productId...\n");
        
        $result = $this->call('getInventoryProductsData', [
            'inventory_id' => $inventoryId,
            'products' => [$productId]
        ]);
        
        if (!empty($result['products'][$productId])) {
            return $result['products'][$productId];
        }
        
        return null;
    }
}
?>
