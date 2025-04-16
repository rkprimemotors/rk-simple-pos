<?php
// File: spice-pos/lib/products_db.php
// Contains functions specifically for interacting with the products data (products.json).
// Handles CRUD operations for products and stock updates.

// Ensure required helper files are included
require_once __DIR__ . '/data_handler.php';
require_once __DIR__ . '/helpers.php';

// Define the path to the products JSON file relative to this file's directory
define('PRODUCTS_FILE', __DIR__ . '/../data/products.json');

/**
 * Retrieves all products from the JSON file.
 *
 * @return array An array of all product items. Returns empty array on failure or if no products exist.
 */
function getAllProducts(): array {
    return readJsonFile(PRODUCTS_FILE);
}

/**
 * Retrieves a single product by its unique ID.
 *
 * @param string $id The unique ID of the product to find.
 * @return ?array The product data as an array if found, otherwise null.
 */
function getProductById(string $id): ?array {
    $products = getAllProducts();
    foreach ($products as $product) {
        if (isset($product['id']) && $product['id'] === $id) {
            return $product;
        }
    }
    return null; // Not found
}

/**
 * Adds a new product to the JSON file.
 * Generates a unique ID for the new product.
 * Basic validation for required fields.
 *
 * @param array $productData Associative array containing product details (name, price_lkr, unit, stock, category[optional]).
 * 'id' should NOT be included, it will be generated.
 * @return bool True if the product was added successfully, false otherwise.
 */
function addProduct(array $productData): bool {
    // Basic validation
    if (empty($productData['name']) || !isset($productData['price_lkr']) || empty($productData['unit']) || !isset($productData['stock'])) {
        // error_log("Attempted to add product with missing required fields."); // Optional logging
        return false;
    }
     // Ensure numeric types where expected
    if (!is_numeric($productData['price_lkr']) || !is_numeric($productData['stock'])) {
        // error_log("Attempted to add product with non-numeric price or stock."); // Optional logging
        return false;
    }


    $products = getAllProducts();

    // Generate a unique ID
    $newId = generateUniqueId('prod_');

    // Prepare the new product entry
    $newProduct = [
        'id' => $newId,
        'name' => sanitize_input($productData['name']),
        'category' => isset($productData['category']) ? sanitize_input($productData['category']) : '',
        'price_lkr' => (float) $productData['price_lkr'], // Cast to float
        'unit' => sanitize_input($productData['unit']),
        'stock' => (float) $productData['stock'] // Cast to float (allows for kg/g decimals)
    ];

    // Add the new product to the array
    $products[] = $newProduct;

    // Write the updated array back to the file
    return writeJsonFile(PRODUCTS_FILE, $products);
}

/**
 * Updates an existing product's details by ID.
 * The 'id' field in $productData is ignored; the $id parameter is used to find the product.
 *
 * @param string $id The unique ID of the product to update.
 * @param array $productData Associative array containing the new details for the product.
 * Must include keys for fields being updated (e.g., name, price_lkr, unit, stock, category).
 * @return bool True if the product was found and updated successfully, false otherwise.
 */
function updateProduct(string $id, array $productData): bool {
    $products = getAllProducts();
    $productIndex = -1;

    // Find the index of the product to update
    foreach ($products as $index => $product) {
        if (isset($product['id']) && $product['id'] === $id) {
            $productIndex = $index;
            break;
        }
    }

    // If product found
    if ($productIndex !== -1) {
        // Update fields provided in $productData, keep existing ID
        $updatedProduct = $products[$productIndex]; // Start with existing data

        if (isset($productData['name'])) {
            $updatedProduct['name'] = sanitize_input($productData['name']);
        }
        if (isset($productData['category'])) {
            $updatedProduct['category'] = sanitize_input($productData['category']);
        }
        if (isset($productData['price_lkr']) && is_numeric($productData['price_lkr'])) {
            $updatedProduct['price_lkr'] = (float) $productData['price_lkr'];
        }
        if (isset($productData['unit'])) {
            $updatedProduct['unit'] = sanitize_input($productData['unit']);
        }
         // Note: Stock is typically updated via updateStock, but allow direct update if needed.
        if (isset($productData['stock']) && is_numeric($productData['stock'])) {
             $updatedProduct['stock'] = max(0, (float) $productData['stock']); // Prevent negative stock via direct edit
        }


        // Replace the old product data with the updated data
        $products[$productIndex] = $updatedProduct;

        // Write the updated array back to the file
        return writeJsonFile(PRODUCTS_FILE, $products);
    }

    // error_log("Attempted to update non-existent product ID: " . $id); // Optional logging
    return false; // Product not found
}

/**
 * Updates the stock level for a specific product.
 * Use positive quantityChange to add stock, negative to deduct.
 * Prevents stock from going below zero.
 *
 * @param string $id The unique ID of the product whose stock needs updating.
 * @param float $quantityChange The amount to change the stock by (positive or negative).
 * @return bool True if the stock was updated successfully, false if product not found or write failed.
 */
function updateStock(string $id, float $quantityChange): bool {
    $products = getAllProducts();
    $productIndex = -1;

    // Find the index of the product
    foreach ($products as $index => $product) {
        if (isset($product['id']) && $product['id'] === $id) {
            $productIndex = $index;
            break;
        }
    }

    // If product found
    if ($productIndex !== -1) {
        // Calculate the new stock level
        $currentStock = (float) $products[$productIndex]['stock'];
        $newStock = $currentStock + $quantityChange;

        // Prevent stock from going below zero
        $newStock = max(0, $newStock);

        // Update the stock level in the array
        $products[$productIndex]['stock'] = $newStock;

        // Write the updated array back to the file
        return writeJsonFile(PRODUCTS_FILE, $products);
    }

     // error_log("Attempted to update stock for non-existent product ID: " . $id); // Optional logging
    return false; // Product not found
}


/**
 * Deletes a product by its ID.
 * WARNING: Deleting products may affect historical sales reporting if product details
 * were not stored within the sales record itself. Use with caution.
 * (Our sales record structure DOES store name/price at sale time, mitigating this).
 *
 * @param string $id The unique ID of the product to delete.
 * @return bool True if the product was found and deleted successfully, false otherwise.
 */
function deleteProduct(string $id): bool {
    $products = getAllProducts();
    $initialCount = count($products);
    $productFound = false;

    // Filter out the product with the matching ID
    $updatedProducts = [];
    foreach ($products as $product) {
        if (isset($product['id']) && $product['id'] === $id) {
            $productFound = true; // Mark as found, but don't add to new array
        } else {
            $updatedProducts[] = $product; // Keep other products
        }
    }

    // If the product was found (meaning the array size potentially changed)
    if ($productFound) {
        // Write the potentially smaller array back to the file
        return writeJsonFile(PRODUCTS_FILE, $updatedProducts);
    } else {
        // error_log("Attempted to delete non-existent product ID: " . $id); // Optional logging
        return false; // Product not found, nothing to delete
    }
}

?>
