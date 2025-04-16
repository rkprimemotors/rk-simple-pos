<?php
// File: spice-pos/lib/sales_db.php
// Contains functions specifically for interacting with the sales data (sales.json).
// Handles recording sales and retrieving sales information for reporting.

// Ensure required helper files are included
require_once __DIR__ . '/data_handler.php';
require_once __DIR__ . '/helpers.php'; // For generateUniqueId, getCurrentTimestamp, formatCurrencyLKR maybe

// Define the path to the sales JSON file relative to this file's directory
define('SALES_FILE', __DIR__ . '/../data/sales.json');

/**
 * Records a new sale transaction in the JSON file.
 *
 * @param array $saleData Associative array containing sale details. Expected keys:
 * - 'items' (array): List of items sold. Each item should be an array with
 * 'product_id', 'name', 'quantity', 'unit', 'price_at_sale_lkr'.
 * - 'total_lkr' (float): The total amount for the sale.
 * @return bool True if the sale was recorded successfully, false otherwise (e.g., validation fail, write error).
 */
function recordSale(array $saleData): bool {
    // Basic validation for required sale data structure
    if (empty($saleData['items']) || !is_array($saleData['items']) || !isset($saleData['total_lkr']) || !is_numeric($saleData['total_lkr'])) {
        // error_log("Attempted to record sale with invalid data structure."); // Optional logging
        return false;
    }
    // Basic validation for items structure (check first item as sample)
    $firstItem = reset($saleData['items']); // Get the first item
    if (!isset($firstItem['product_id']) || !isset($firstItem['name']) || !isset($firstItem['quantity']) || !isset($firstItem['unit']) || !isset($firstItem['price_at_sale_lkr'])) {
         // error_log("Attempted to record sale with invalid item structure."); // Optional logging
        return false;
    }


    $sales = readJsonFile(SALES_FILE); // Use readJsonFile to get current sales

    // Generate unique ID and timestamp for the sale
    $saleId = generateUniqueId('sale_');
    $timestamp = getCurrentTimestamp(); // Gets ISO 8601 timestamp

    // Prepare the complete sale record
    $newSaleRecord = [
        'sale_id' => $saleId,
        'timestamp' => $timestamp,
        'items' => $saleData['items'], // Assume items are already structured correctly by calling code
        'total_lkr' => (float) $saleData['total_lkr']
    ];

    // Add the new sale record to the array
    $sales[] = $newSaleRecord;

    // Write the updated sales array back to the file
    return writeJsonFile(SALES_FILE, $sales);
}

/**
 * Retrieves all sales records from the JSON file.
 *
 * @return array An array of all sale records. Returns empty array on failure or if no sales exist.
 */
function getAllSales(): array {
    return readJsonFile(SALES_FILE);
}

/**
 * Retrieves sales records for a specific date.
 * Compares against the date part of the ISO 8601 timestamp.
 *
 * @param string $date The date string in 'YYYY-MM-DD' format.
 * @return array An array of sale records that occurred on the specified date.
 */
function getSalesByDate(string $date): array {
    $allSales = getAllSales();
    $filteredSales = [];

    // Validate the date format roughly
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        // error_log("Invalid date format provided to getSalesByDate: " . $date); // Optional logging
        return []; // Return empty if date format is invalid
    }

    foreach ($allSales as $sale) {
        // Extract the date part (YYYY-MM-DD) from the ISO 8601 timestamp
        if (isset($sale['timestamp'])) {
            $saleDate = substr($sale['timestamp'], 0, 10);
            if ($saleDate === $date) {
                $filteredSales[] = $sale;
            }
        }
    }

    return $filteredSales;
}

/**
 * Generates a simple daily sales report summary.
 * Calculates total revenue and number of sales for a specific date.
 *
 * @param string $date The date string in 'YYYY-MM-DD' format.
 * @return array An associative array containing 'totalRevenue' (float) and 'numberOfSales' (int).
 */
function getDailySalesReport(string $date): array {
    $salesOnDate = getSalesByDate($date); // Reuse the filtering function

    $totalRevenue = 0.0;
    $numberOfSales = count($salesOnDate); // Simple count of sales records

    foreach ($salesOnDate as $sale) {
        if (isset($sale['total_lkr']) && is_numeric($sale['total_lkr'])) {
            $totalRevenue += (float) $sale['total_lkr'];
        }
    }

    return [
        'totalRevenue' => $totalRevenue,
        'numberOfSales' => $numberOfSales
    ];
}

?>
