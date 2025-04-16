<?php
// File: spice-pos/lib/helpers.php
// Contains utility functions used throughout the application.

/**
 * Generates a unique ID string.
 * Uses a prefix, the current microtime, and a random element for uniqueness.
 *
 * @param string $prefix Optional prefix for the ID (e.g., 'prod_', 'sale_').
 * @return string A unique identifier string.
 */
function generateUniqueId(string $prefix = ''): string {
    // Get microseconds as part of the timestamp for better uniqueness
    $microtime = floor(microtime(true) * 1000); // Milliseconds
    // Add a random component to further decrease collision probability
    $random = bin2hex(random_bytes(4)); // 8 random hex characters
    return $prefix . $microtime . '_' . $random;
}

/**
 * Formats a floating-point number as Sri Lankan Rupees (LKR).
 * Displays with two decimal places, commas for thousands separator.
 *
 * @param float $amount The monetary amount.
 * @return string The formatted currency string (e.g., "LKR 1,500.00").
 */
function formatCurrencyLKR(float $amount): string {
    // Use number_format for thousands separator and decimal places
    $formattedAmount = number_format($amount, 2, '.', ',');
    return "LKR " . $formattedAmount;
}

/**
 * Gets the current timestamp in ISO 8601 format with timezone offset.
 * Uses the Asia/Colombo timezone for Sri Lanka.
 *
 * @return string The current timestamp (e.g., "2025-04-16T10:30:00+05:30").
 * @throws Exception If the timezone setting fails.
 */
function getCurrentTimestamp(): string {
    // Set timezone to Sri Lanka
    date_default_timezone_set('Asia/Colombo');
    // Get current time
    $now = new DateTime();
    // Format as ISO 8601 with timezone offset
    return $now->format(DateTime::ATOM); // ATOM is equivalent to ISO 8601 (Y-m-d\TH:i:sP)
}

/**
 * Basic sanitization for string input to prevent simple XSS.
 * Should be used on data before displaying it in HTML.
 * For database/JSON storage, prepare statements or proper encoding is key,
 * but for display, this offers basic protection.
 *
 * @param string|null $input The string to sanitize.
 * @return string The sanitized string.
 */
function sanitize_input(?string $input): string {
    if ($input === null) {
        return '';
    }
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

?>
