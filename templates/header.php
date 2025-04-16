<?php
// File: spice-pos/templates/header.php
// Sets up the top part of the HTML page, includes CSS, and navigation.

// Set a default title if the specific page doesn't provide one
if (!isset($pageTitle)) {
    $pageTitle = 'Spice Shop POS';
}

// Include helper for sanitization if needed (though title is usually safe)
// require_once __DIR__ . '/../lib/helpers.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); // Sanitize title output ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        // Optional: You can configure Tailwind defaults here if needed
        // tailwind.config = {
        //   theme: {
        //     extend: {
        //       colors: {
        //         primary: '#yourColor',
        //       }
        //     }
        //   }
        // }
    </script>
    <style type="text/tailwindcss">
        /* Optional: You can add custom CSS using Tailwind's @apply directive here */
        /* For example: */
        /* .btn-primary {
            @apply bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded;
           } */
           body {
            font-family: sans-serif; /* Basic font stack */
           }
    </style>
</head>
<body class="bg-gray-100 text-gray-900 min-h-screen flex flex-col">

    <header class="bg-green-700 text-white shadow-md">
        <nav class="container mx-auto px-4 py-3 flex justify-between items-center">
            <div class="text-xl font-bold">
                <a href="index.php">RK Spice Paradise</a>
            </div>
            <ul class="flex space-x-4 md:space-x-6">
                <li><a href="index.php" class="hover:text-green-200 transition duration-150 ease-in-out">Sales</a></li>
                <li><a href="products.php" class="hover:text-green-200 transition duration-150 ease-in-out">Products</a></li>
                <li><a href="reports.php" class="hover:text-green-200 transition duration-150 ease-in-out">Reports</a></li>
            </ul>
        </nav>
    </header>

    <main class="container mx-auto px-4 py-6 flex-grow">
     
