<?php
// File: spice-pos/products.php
// Interface for managing products (View, Add, Edit).

session_start(); // Start session for flash messages

$pageTitle = 'Product Management';

// Include necessary files
require_once 'lib/products_db.php';
require_once 'lib/helpers.php';

// --- Handle Edit Request ---
$editMode = false;
$productToEdit = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $productIdToEdit = $_GET['id'];
    $productToEdit = getProductById($productIdToEdit);
    if ($productToEdit) {
        $editMode = true;
        $pageTitle = 'Edit Product: ' . sanitize_input($productToEdit['name']);
    } else {
        // Product ID provided but not found
        $_SESSION['message'] = 'Product with ID ' . sanitize_input($productIdToEdit) . ' not found.';
        $_SESSION['message_type'] = 'error';
        // Redirect or just show message and don't enter edit mode
        // For simplicity, we'll just not enter edit mode.
    }
}

// --- Fetch Product List ---
$products = getAllProducts();

// Include the header template
require_once 'templates/header.php';

// Display success/error messages if redirected here after processing
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $message_type = $_SESSION['message_type'] ?? 'success'; // Default to success
    echo '<div class="mb-6 p-3 rounded text-center ' . ($message_type === 'success' ? 'bg-green-100 text-green-800 border border-green-300' : 'bg-red-100 text-red-800 border border-red-300') . '">' . sanitize_input($message) . '</div>';
    // Clear the message after displaying
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
}
?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

    <div class="lg:col-span-1 bg-white p-6 rounded-lg shadow border border-gray-200">
        <h1 class="text-2xl font-semibold mb-5 border-b pb-2">
            <?php echo $editMode ? 'Edit Product' : 'Add New Product'; ?>
        </h1>

        <form action="manage_product.php" method="POST">
            <input type="hidden" name="action" value="<?php echo $editMode ? 'edit' : 'add'; ?>">
            <?php if ($editMode): ?>
                <input type="hidden" name="product_id" value="<?php echo sanitize_input($productToEdit['id']); ?>">
            <?php endif; ?>

            <div class="mb-4">
                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Product Name:</label>
                <input type="text" id="name" name="name" value="<?php echo $editMode ? sanitize_input($productToEdit['name']) : ''; ?>" required
                       class="w-full p-2 border border-gray-300 rounded focus:ring-2 focus:ring-green-500 focus:border-transparent outline-none">
            </div>

            <div class="mb-4">
                <label for="category" class="block text-sm font-medium text-gray-700 mb-1">Category:</label>
                <input type="text" id="category" name="category" value="<?php echo $editMode ? sanitize_input($productToEdit['category']) : ''; ?>"
                       class="w-full p-2 border border-gray-300 rounded focus:ring-2 focus:ring-green-500 focus:border-transparent outline-none" placeholder="e.g., Whole Spices, Ground Spices">
            </div>

             <div class="grid grid-cols-2 gap-4 mb-4">
                 <div>
                    <label for="price_lkr" class="block text-sm font-medium text-gray-700 mb-1">Price (LKR):</label>
                    <input type="number" id="price_lkr" name="price_lkr" value="<?php echo $editMode ? $productToEdit['price_lkr'] : ''; ?>" required step="0.01" min="0"
                           class="w-full p-2 border border-gray-300 rounded focus:ring-2 focus:ring-green-500 focus:border-transparent outline-none">
                </div>
                 <div>
                    <label for="unit" class="block text-sm font-medium text-gray-700 mb-1">Unit:</label>
                    <input type="text" id="unit" name="unit" value="<?php echo $editMode ? sanitize_input($productToEdit['unit']) : ''; ?>" required
                           class="w-full p-2 border border-gray-300 rounded focus:ring-2 focus:ring-green-500 focus:border-transparent outline-none" placeholder="e.g., kg, 100g, pack">
                 </div>
            </div>


            <div class="mb-6">
                <label for="stock" class="block text-sm font-medium text-gray-700 mb-1">Initial Stock / Current Stock:</label>
                <input type="number" id="stock" name="stock" value="<?php echo $editMode ? $productToEdit['stock'] : ''; ?>" required step="any" min="0"
                       class="w-full p-2 border border-gray-300 rounded focus:ring-2 focus:ring-green-500 focus:border-transparent outline-none" placeholder="Quantity in units specified above">
                 <p class="text-xs text-gray-500 mt-1">Enter the current stock level.</p>
            </div>

            <div class="flex items-center space-x-3">
                <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-5 rounded focus:outline-none focus:shadow-outline transition duration-150 ease-in-out">
                    <?php echo $editMode ? 'Update Product' : 'Add Product'; ?>
                </button>
                <?php if ($editMode): ?>
                    <a href="products.php" class="text-gray-600 hover:text-gray-800 text-sm">Cancel Edit</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <div class="lg:col-span-2">
        <h1 class="text-2xl font-semibold mb-5">Existing Products</h1>
        <div class="bg-white p-4 rounded-lg shadow border border-gray-200 overflow-x-auto">
            <?php if (empty($products)): ?>
                <p class="text-center text-gray-500 py-4">No products have been added yet.</p>
            <?php else: ?>
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price / Unit</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stock</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($products as $product): ?>
                            <tr>
                                <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo sanitize_input($product['name']); ?></td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600"><?php echo sanitize_input($product['category'] ?? ''); ?></td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600">
                                    <?php echo formatCurrencyLKR($product['price_lkr']); ?> / <?php echo sanitize_input($product['unit']); ?>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm <?php echo ($product['stock'] <= 0) ? 'text-red-600 font-bold' : 'text-gray-600'; ?>">
                                    <?php echo $product['stock']; ?>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm font-medium space-x-3">
                                    <a href="products.php?action=edit&id=<?php echo sanitize_input($product['id']); ?>" class="text-indigo-600 hover:text-indigo-900">Edit</a>
                                    <form method="post" action="manage_product.php" onsubmit="return confirm('Are you sure you want to delete this product? This cannot be undone.');" style="display: inline;">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="product_id" value="<?php echo sanitize_input($product['id']); ?>">
                                        <button type="submit" class="text-red-600 hover:text-red-800">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

</div>

<?php
// Include the footer template
require_once 'templates/footer.php';
?>
