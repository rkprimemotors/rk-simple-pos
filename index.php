<?php
// File: spice-pos/index.php
// Main Sales/Checkout interface.

session_start(); // Start session for flash messages (optional)

$pageTitle = 'Sales / Checkout';

// Include necessary files
require_once 'lib/products_db.php';
require_once 'lib/helpers.php'; // For formatting currency

// Fetch all products available for sale
$products = getAllProducts();

// Include the header template
require_once 'templates/header.php';

// Display success/error messages if redirected here after processing a sale
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $message_type = $_SESSION['message_type'] ?? 'success'; // Default to success
    echo '<div class="mb-4 p-3 rounded ' . ($message_type === 'success' ? 'bg-green-200 text-green-800' : 'bg-red-200 text-red-800') . '">' . sanitize_input($message) . '</div>';
    // Clear the message after displaying
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
}

?>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6" x-data="posApp()">
    <div class="md:col-span-2">
        <h1 class="text-2xl font-semibold mb-4">Available Spices</h1>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php if (empty($products)): ?>
                <p class="text-gray-600">No products found. Please add products in the 'Products' section.</p>
            <?php else: ?>
                <?php foreach ($products as $product): ?>
                    <div class="bg-white p-4 rounded shadow hover:shadow-lg transition-shadow duration-150 ease-in-out border border-gray-200 flex flex-col justify-between">
                        <div>
                            <h2 class="text-lg font-bold text-green-800"><?php echo sanitize_input($product['name']); ?></h2>
                            <p class="text-sm text-gray-600 mb-1"><?php echo sanitize_input($product['category'] ?? ''); ?></p>
                            <p class="text-md font-semibold mb-1"><?php echo formatCurrencyLKR($product['price_lkr']); ?> / <?php echo sanitize_input($product['unit']); ?></p>
                            <p class="text-sm <?php echo ($product['stock'] <= 0) ? 'text-red-600 font-semibold' : 'text-gray-700'; ?>">
                                Stock: <?php echo $product['stock']; ?> <?php echo sanitize_input($product['unit']); ?>
                            </p>
                        </div>
                        <button
                            type="button"
                            @click="addToCart(<?php echo htmlspecialchars(json_encode($product), ENT_QUOTES, 'UTF-8'); ?>)"
                            :disabled="<?php echo ($product['stock'] <= 0) ? 'true' : 'false'; ?>"
                            class="mt-3 w-full bg-green-600 text-white py-1 px-3 rounded hover:bg-green-700 disabled:bg-gray-400 disabled:cursor-not-allowed transition duration-150 ease-in-out text-sm"
                        >
                            Add to Cart
                        </button>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="md:col-span-1 bg-white p-4 rounded shadow border border-gray-200">
        <h1 class="text-2xl font-semibold mb-4 border-b pb-2">Current Sale</h1>

        <div class="mb-4 max-h-96 overflow-y-auto">
            <template x-if="cartItems.length === 0">
                <p class="text-gray-500 italic">Cart is empty.</p>
            </template>

            <template x-for="item in cartItems" :key="item.id">
                <div class="flex justify-between items-center border-b py-2 last:border-b-0">
                    <div>
                        <p class="font-semibold text-sm" x-text="item.name"></p>
                        <p class="text-xs text-gray-600" x-text="formatCurrencyAlpine(item.price) + ' / ' + item.unit"></p>
                        <div class="flex items-center mt-1">
                             <button @click="updateQuantity(item.id, -1)" class="bg-gray-200 px-2 py-0.5 rounded-l text-sm hover:bg-gray-300">-</button>
                             <input type="number" step="any" min="0.1" :max="item.maxStock" x-model.number="item.quantity" @change="validateAndUpdateQuantity(item.id)" class="w-12 text-center border-t border-b text-sm outline-none focus:ring-1 focus:ring-green-500">
                             <button @click="updateQuantity(item.id, 1)" class="bg-gray-200 px-2 py-0.5 rounded-r text-sm hover:bg-gray-300">+</button>
                         </div>

                    </div>
                    <div class="text-right">
                        <p class="font-semibold text-sm" x-text="formatCurrencyAlpine(item.price * item.quantity)"></p>
                        <button @click="removeFromCart(item.id)" class="text-red-500 hover:text-red-700 text-xs mt-1">Remove</button>
                    </div>
                </div>
            </template>
        </div>

        <div class="border-t pt-4 mt-auto">
             <div class="flex justify-between items-center font-bold text-lg mb-4">
                 <span>Total:</span>
                 <span x-text="formatCurrencyAlpine(cartTotal)">LKR 0.00</span>
             </div>

             <form method="POST" action="process_sale.php" @submit="prepareCheckout">
                 <input type="hidden" name="cart_data" x-model="cartDataJson">
                 <input type="hidden" name="cart_total" x-model="cartTotal">

                 <button
                     type="submit"
                     :disabled="cartItems.length === 0"
                     class="w-full bg-blue-600 text-white py-2 px-4 rounded hover:bg-blue-700 disabled:bg-gray-400 disabled:cursor-not-allowed transition duration-150 ease-in-out font-semibold"
                 >
                     Complete Sale
                 </button>
             </form>
              <button
                  type="button"
                  @click="clearCart()"
                  :disabled="cartItems.length === 0"
                  class="w-full mt-2 bg-red-500 text-white py-1 px-3 rounded hover:bg-red-600 disabled:bg-gray-400 disabled:cursor-not-allowed transition duration-150 ease-in-out text-sm"
              >
                  Clear Cart
              </button>
        </div>
    </div>
</div>

<script>
    function posApp() {
        return {
            // searchTerm: '', // For search functionality if added
            cartItems: [], // Array to hold { id, name, price, unit, quantity, maxStock }
            cartTotal: 0.0,
            cartDataJson: '', // To hold stringified cart data for form submission

            // Initialize (e.g., load cart from local storage if implementing persistence)
            // init() { console.log('POS App Initialized'); },

            addToCart(product) {
                // Find if item already exists in cart
                const existingItem = this.cartItems.find(item => item.id === product.id);

                if (existingItem) {
                    // Increment quantity if stock allows
                    if (existingItem.quantity < product.stock) {
                        existingItem.quantity++;
                    } else {
                        alert('Cannot add more than available stock.');
                    }
                } else {
                    // Add new item if stock > 0
                    if (product.stock > 0) {
                        this.cartItems.push({
                            id: product.id,
                            name: product.name,
                            price: parseFloat(product.price_lkr), // Ensure price is float
                            unit: product.unit,
                            quantity: 1, // Start with quantity 1
                            maxStock: parseFloat(product.stock) // Store max stock for validation
                        });
                    } else {
                         alert('Product is out of stock.');
                    }
                }
                this.calculateTotal();
            },

            updateQuantity(itemId, change) {
                const item = this.cartItems.find(item => item.id === itemId);
                if (item) {
                    const newQuantity = item.quantity + change;
                    // Prevent going below 1 or above stock
                    if (newQuantity >= 1 && newQuantity <= item.maxStock) {
                        item.quantity = newQuantity;
                    } else if (newQuantity > item.maxStock) {
                        item.quantity = item.maxStock; // Set to max stock if exceeding
                        alert('Quantity cannot exceed available stock.');
                    } else if (newQuantity < 1) {
                        // Optionally remove if quantity becomes 0 or less, or just keep at 1
                        this.removeFromCart(itemId); // Remove if quantity goes below 1
                        // item.quantity = 1; // Or enforce minimum 1
                    }
                    this.calculateTotal();
                }
            },

             validateAndUpdateQuantity(itemId) {
                // This is called when the input field value changes directly
                const item = this.cartItems.find(item => item.id === itemId);
                 if (item) {
                     // Ensure quantity is numeric and within bounds
                     if (isNaN(item.quantity) || item.quantity < 0.1) {
                        item.quantity = 1; // Reset to 1 if invalid or less than minimum
                     } else if (item.quantity > item.maxStock) {
                        item.quantity = item.maxStock; // Cap at max stock
                        alert('Quantity cannot exceed available stock.');
                     } else {
                        // Round to reasonable decimal places if needed (e.g., for kg/g)
                        // item.quantity = Math.round(item.quantity * 100) / 100; // Example: 2 decimal places
                     }

                     this.calculateTotal();
                 }
             },


            removeFromCart(itemId) {
                this.cartItems = this.cartItems.filter(item => item.id !== itemId);
                this.calculateTotal();
            },

            calculateTotal() {
                this.cartTotal = this.cartItems.reduce((total, item) => {
                    return total + (item.price * item.quantity);
                }, 0);
                // Round total to 2 decimal places
                this.cartTotal = Math.round(this.cartTotal * 100) / 100;
            },

            clearCart() {
                if(confirm('Are you sure you want to clear the cart?')) {
                    this.cartItems = [];
                    this.calculateTotal();
                }
            },

            prepareCheckout() {
                // Stringify the cart items to be sent in the hidden input
                // Only send essential data needed by the backend
                const itemsToSubmit = this.cartItems.map(item => ({
                    product_id: item.id,
                    name: item.name, // Send name/unit/price for record keeping
                    unit: item.unit,
                    price_at_sale_lkr: item.price,
                    quantity: item.quantity
                }));
                this.cartDataJson = JSON.stringify(itemsToSubmit);
                // The cartTotal is already bound via x-model to its hidden input
                // Optional: final validation before submit
                 return this.cartItems.length > 0;
            },

            // Helper to format currency within Alpine component
            formatCurrencyAlpine(amount) {
                 if (isNaN(amount)) return "LKR 0.00";
                 return "LKR " + amount.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
            }
        }
    }
</script>

<?php
// Include the footer template
require_once 'templates/footer.php';
?>
