<?php
// File: spice-pos/manage_product.php
// Handles POST requests for adding, editing, and deleting products.

session_start(); // Required for session messages

// Include necessary library files
require_once 'lib/products_db.php';
require_once 'lib/helpers.php'; // May use sanitize_input here or rely on db functions

// --- Security Check: Ensure this script is accessed via POST method ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['message'] = 'Invalid access method.';
    $_SESSION['message_type'] = 'error';
    header('Location: products.php');
    exit;
}

// --- Determine the Action ---
$action = $_POST['action'] ?? '';

// --- Process Based on Action ---
switch ($action) {
    case 'add':
        // Get data from POST request
        $productData = [
            'name' => $_POST['name'] ?? null,
            'category' => $_POST['category'] ?? '', // Optional field
            'price_lkr' => $_POST['price_lkr'] ?? null,
            'unit' => $_POST['unit'] ?? null,
            'stock' => $_POST['stock'] ?? null
        ];

        // Call the addProduct function
        if (addProduct($productData)) {
            $_SESSION['message'] = 'Product added successfully!';
            $_SESSION['message_type'] = 'success';
        } else {
            $_SESSION['message'] = 'Failed to add product. Please check your input.';
             // You could add more specific error checking/logging in addProduct if needed
            $_SESSION['message_type'] = 'error';
        }
        break;

    case 'edit':
        // Get product ID and updated data
        $productId = $_POST['product_id'] ?? null;
        $productData = [
            'name' => $_POST['name'] ?? null,
            'category' => $_POST['category'] ?? '',
            'price_lkr' => $_POST['price_lkr'] ?? null,
            'unit' => $_POST['unit'] ?? null,
            'stock' => $_POST['stock'] ?? null
            // Note: ID is passed separately to updateProduct function
        ];

        // Basic validation for ID
        if (!$productId) {
             $_SESSION['message'] = 'Product ID missing for update.';
             $_SESSION['message_type'] = 'error';
        } else {
            // Call the updateProduct function
            if (updateProduct($productId, $productData)) {
                $_SESSION['message'] = 'Product updated successfully!';
                $_SESSION['message_type'] = 'success';
            } else {
                $_SESSION['message'] = 'Failed to update product. Please check your input or ensure the product exists.';
                 // More specific error possibilities: product not found, write error
                $_SESSION['message_type'] = 'error';
            }
        }
        break;

    case 'delete':
        // Get product ID
        $productId = $_POST['product_id'] ?? null;

        if (!$productId) {
             $_SESSION['message'] = 'Product ID missing for delete action.';
             $_SESSION['message_type'] = 'error';
        } else {
             // Call the deleteProduct function
            if (deleteProduct($productId)) {
                $_SESSION['message'] = 'Product deleted successfully!';
                $_SESSION['message_type'] = 'success';
            } else {
                $_SESSION['message'] = 'Failed to delete product. It might have already been removed or an error occurred.';
                $_SESSION['message_type'] = 'error';
            }
        }
        break;

    default:
        // Handle unknown action
        $_SESSION['message'] = 'Unknown product action requested.';
        $_SESSION['message_type'] = 'error';
        break;
}

// --- Redirect back to the product management page ---
header('Location: products.php');
exit; // Ensure no further code execution after redirect

?>
