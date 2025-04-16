<?php
// File: spice-pos/process_sale.php
// Handles the submission of the cart data from index.php.
// Records the sale, updates stock levels, and redirects back with a message.

session_start(); // Required to store success/error messages

// Include necessary library files
require_once 'lib/sales_db.php';
require_once 'lib/products_db.php';
require_once 'lib/helpers.php'; // Might need for sanitization or other helpers later

// --- Security Check: Ensure this script is accessed via POST method ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // If not POST, redirect to index or show an error
    $_SESSION['message'] = 'Invalid access method.';
    $_SESSION['message_type'] = 'error';
    header('Location: index.php');
    exit;
}

// --- Get Submitted Data ---
$cartDataJson = $_POST['cart_data'] ?? '';
$cartTotal = $_POST['cart_total'] ?? 0.0;

// --- Basic Validation ---
$errors = [];
$cartItems = json_decode($cartDataJson, true); // Decode JSON into PHP array

if (json_last_error() !== JSON_ERROR_NONE) {
    $errors[] = 'Invalid cart data received.';
}

if (empty($cartItems) || !is_array($cartItems)) {
    $errors[] = 'Cart is empty or data is corrupted.';
}

if (!is_numeric($cartTotal) || $cartTotal <= 0) {
    $errors[] = 'Invalid cart total amount.';
}

// --- Server-Side Stock Check (Important Note) ---
// For robust systems, you should re-verify stock here before processing.
// This prevents issues if stock changed between page load and checkout, or if client-side checks are bypassed.
// Example check (simplified, assumes getProductById exists):
/*
foreach ($cartItems as $item) {
    $product = getProductById($item['product_id']);
    if (!$product || $product['stock'] < $item['quantity']) {
        $errors[] = "Insufficient stock for item: " . sanitize_input($item['name']);
    }
}
*/
// For this simple implementation, we are currently SKIPPING the server-side re-check
// and relying on the client-side checks and the subsequent updateStock call.
// This is a simplification and potential weakness in high-concurrency scenarios.


// --- Process Sale if No Errors ---
if (empty($errors)) {
    // Prepare data for recordSale function
    $saleData = [
        'items' => $cartItems, // Already decoded from JSON
        'total_lkr' => (float) $cartTotal
    ];

    // 1. Record the Sale
    $saleRecorded = recordSale($saleData);

    if ($saleRecorded) {
        // 2. Update Stock Levels for each item sold
        $stockUpdateSuccess = true; // Assume success initially
        foreach ($cartItems as $item) {
            if (!isset($item['product_id']) || !isset($item['quantity'])) {
                 // error_log("Missing product_id or quantity in item during stock update."); // Optional logging
                 $stockUpdateSuccess = false;
                 break; // Stop processing stock if item data is bad
            }
            // Deduct stock (quantity is positive, so make it negative for updateStock)
            $quantityToDeduct = -abs((float)$item['quantity']); // Ensure it's negative float
            if (!updateStock($item['product_id'], $quantityToDeduct)) {
                // Log error if stock update fails for an item
                // This indicates a potential inconsistency (sale recorded but stock not updated)
                error_log("Failed to update stock for product ID: " . $item['product_id'] . " during sale processing.");
                // In this simple system, we might continue, but flag that there was an issue.
                // A more robust system might try to roll back the sale record.
                $stockUpdateSuccess = false; // Mark that at least one update failed
                 // Maybe collect specific errors: $errors[] = "Stock update failed for " . sanitize_input($item['name']);
            }
        }

        // Set final success/error message based on outcomes
        if ($stockUpdateSuccess) {
            $_SESSION['message'] = 'Sale recorded successfully!';
            $_SESSION['message_type'] = 'success';
        } else {
            $_SESSION['message'] = 'Sale recorded, but there was an issue updating stock levels. Please check inventory.';
            $_SESSION['message_type'] = 'warning'; // Use a warning type
        }

    } else {
        // Sale recording failed
        $_SESSION['message'] = 'Failed to record the sale. Please try again.';
        $_SESSION['message_type'] = 'error';
        // Optional: Log detailed error from writeJsonFile if possible
        error_log("recordSale function returned false.");
    }

} else {
    // If there were validation errors initially
    $_SESSION['message'] = 'Could not process sale due to errors: ' . implode(' ', $errors);
    $_SESSION['message_type'] = 'error';
}

// --- Redirect back to the main sales page ---
header('Location: index.php');
exit; // Ensure no further code execution after redirect

?>
