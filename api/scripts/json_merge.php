<?php

// -----------------------------------------------------------
// Validate arguments
// -----------------------------------------------------------

if ($argc < 3) {
    exit("❌ ERROR: Usage: php merge.php <database.json> <input.json|folder>\n");
}

$databasePath = $argv[1];
$inputPath    = $argv[2];


// -----------------------------------------------------------
// Ensure main database exists
// -----------------------------------------------------------

if (!file_exists($databasePath)) {
    exit("❌ ERROR: Database JSON file not found at: $databasePath\n");
}

$mainData = json_decode(file_get_contents($databasePath), true);
if (!is_array($mainData)) {
    exit("❌ ERROR: Invalid JSON inside the main database.\n");
}


// -----------------------------------------------------------
// Collect JSON files to merge
// -----------------------------------------------------------

$jsonFiles = [];

if (is_file($inputPath)) {

    $jsonFiles[] = $inputPath;

} elseif (is_dir($inputPath)) {

    foreach (scandir($inputPath) as $file) {
        if (strtolower(pathinfo($file, PATHINFO_EXTENSION)) === 'json') {
            $jsonFiles[] = rtrim($inputPath, DIRECTORY_SEPARATOR)
                         . DIRECTORY_SEPARATOR
                         . $file;
        }
    }

} else {
    exit("❌ ERROR: Input path is not a file or directory.\n");
}

if (empty($jsonFiles)) {
    exit("❌ ERROR: No JSON files found to merge.\n");
}

echo "📁 Files to merge:\n";
foreach ($jsonFiles as $file) {
    echo "  → $file\n";
}


// -----------------------------------------------------------
// Recursive deep merge function
// -----------------------------------------------------------

function deepMerge(array &$base, array $update, string $path = ''): void
{
    foreach ($update as $key => $value) {

        $fullKey = $path === '' ? $key : "$path.$key";

        // New key → add
        if (!array_key_exists($key, $base)) {
            $base[$key] = $value;
            continue;
        }

        // Both arrays → recursive merge
        if (is_array($base[$key]) && is_array($value)) {
            deepMerge($base[$key], $value, $fullKey);
            continue;
        }

        // Scalar overwrite
        echo "⚠️ WARNING: Duplicate key overridden → $fullKey\n";
        $base[$key] = $value;
    }
}


// -----------------------------------------------------------
// Apply merges
// -----------------------------------------------------------

foreach ($jsonFiles as $file) {

    echo "\n🔄 Merging: $file\n";

    $json = json_decode(file_get_contents($file), true);

    if (!is_array($json)) {
        echo "❌ Skipping invalid JSON file: $file\n";
        continue;
    }

    deepMerge($mainData, $json);
}


// -----------------------------------------------------------
// Save updated database
// -----------------------------------------------------------

file_put_contents(
    $databasePath,
    json_encode($mainData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
);

echo "\n✅ Merge completed and saved to database:\n→ $databasePath\n";
