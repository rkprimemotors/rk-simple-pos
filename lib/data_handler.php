<?php
// File: spice-pos/lib/data_handler.php
// Contains generic functions for reading from and writing to JSON files.
// Includes basic file locking to mitigate concurrency issues in simple scenarios.

/**
 * Reads data from a JSON file.
 *
 * @param string $filePath The full path to the JSON file.
 * @return array Returns the decoded JSON data as an associative array,
 * or an empty array if the file doesn't exist or is invalid/empty.
 */
function readJsonFile(string $filePath): array {
    // Check if the file exists and is readable
    if (!file_exists($filePath) || !is_readable($filePath)) {
        // error_log("Data file not found or not readable: " . $filePath); // Optional logging
        return []; // Return empty array if file doesn't exist or isn't readable
    }

    // Attempt to read the file content
    $jsonContent = file_get_contents($filePath);
    if ($jsonContent === false) {
        // error_log("Failed to read data file: " . $filePath); // Optional logging
        return []; // Return empty array on read failure
    }

    // If the file is empty, return an empty array
    if (trim($jsonContent) === '') {
        return [];
    }

    // Attempt to decode the JSON content
    $data = json_decode($jsonContent, true); // true for associative array

    // Check for JSON decoding errors
    if (json_last_error() !== JSON_ERROR_NONE) {
        // error_log("JSON decode error in file " . $filePath . ": " . json_last_error_msg()); // Optional logging
        return []; // Return empty array if JSON is invalid
    }

    // Ensure it's an array (JSON root should be an array for products/sales)
    if (!is_array($data)) {
       // error_log("JSON root is not an array in file: " . $filePath); // Optional logging
       return [];
    }


    return $data;
}

/**
 * Writes data to a JSON file with basic file locking.
 * Encodes the data array into a pretty-printed JSON string and saves it.
 * This will overwrite the existing file content.
 *
 * @param string $filePath The full path to the JSON file.
 * @param array $data The array data to encode and write.
 * @return bool Returns true on success, false on failure (e.g., write error, lock failure).
 */
function writeJsonFile(string $filePath, array $data): bool {
    // Ensure the directory exists, attempt to create if not (optional, adjust permissions as needed)
    $dir = dirname($filePath);
    if (!is_dir($dir)) {
        if (!mkdir($dir, 0775, true)) { // Adjust permissions (0775 is common)
             // error_log("Failed to create directory: " . $dir); // Optional logging
            return false;
        }
    }
     if (!is_writable($dir)) {
         // error_log("Directory not writable: " . $dir); // Optional logging
        return false;
     }


    // Attempt to encode the data to JSON
    // JSON_PRETTY_PRINT for readability
    // JSON_UNESCAPED_SLASHES to avoid escaping slashes in URLs/paths if any
    $jsonContent = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

    if ($jsonContent === false) {
        // error_log("JSON encode error: " . json_last_error_msg()); // Optional logging
        return false; // Return false on encoding failure
    }

    // Attempt to open the file for writing ('c' mode: create if not exists, truncate, place pointer at start)
    $fileHandle = fopen($filePath, 'c');
    if ($fileHandle === false) {
        // error_log("Failed to open file for writing: " . $filePath); // Optional logging
        return false;
    }

    // Attempt to get an exclusive lock (prevents others writing simultaneously)
    if (flock($fileHandle, LOCK_EX)) {
        // Truncate the file to zero length BEFORE writing new content
        if (!ftruncate($fileHandle, 0)) {
            // error_log("Failed to truncate file: " . $filePath); // Optional logging
            flock($fileHandle, LOCK_UN); // Release lock
            fclose($fileHandle);
            return false;
        }

        // Write the JSON string to the file
        $bytesWritten = fwrite($fileHandle, $jsonContent);

        // Ensure data is written to disk before releasing lock
        fflush($fileHandle);

        // Release the lock
        flock($fileHandle, LOCK_UN);

        // Close the file handle
        fclose($fileHandle);

        // Check if writing was successful (fwrite returns false on error)
        if ($bytesWritten === false) {
             // error_log("Failed to write to file: " . $filePath); // Optional logging
            return false;
        }

        return true; // Success!

    } else {
        // error_log("Could not acquire file lock for writing: " . $filePath); // Optional logging
        fclose($fileHandle); // Close the handle even if lock failed
        return false; // Failed to get lock
    }
}

?>
