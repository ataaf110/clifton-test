<?php

echo "=== Max Degree Calculator Script Started ===\n";

// -----------------------------------------
// Hardcoded input JSON path (questions list)
// -----------------------------------------
$inputFile = __DIR__ . "/../resources/questions.json";
echo "Input file: $inputFile\n";

// -----------------------------------------
// Hardcoded output JSON path
// -----------------------------------------
$outputFile = __DIR__ . "/../resources/secondary-data/themes-max-degrees.json";
echo "Output file will be: $outputFile\n";

// ------------------------------------------------
// Load input data
// ------------------------------------------------
if (!file_exists($inputFile)) {
    die("[ERROR] Input JSON file NOT found: $inputFile\n");
}

echo "Loading input file...\n";
$jsonData = file_get_contents($inputFile);

if ($jsonData === false) {
    die("[ERROR] Failed to read file: $inputFile\n");
}

echo "File loaded. Size: " . strlen($jsonData) . " bytes\n";

$questions = json_decode($jsonData, true);

if ($questions === null) {
    die("[ERROR] Invalid JSON format: " . json_last_error_msg() . "\n");
}

echo "JSON decoded successfully. Total questions: " . count($questions) . "\n\n";

// ------------------------------------------------
// Compute max degrees
// ------------------------------------------------
$themeMaxDegrees = [];

echo "=== Processing Questions ===\n";

foreach ($questions as $qIndex => $question) {

    echo "Question #" . ($qIndex + 1) . "\n";
    
    foreach ($question as $pIndex => $phrase) {
        echo "\tphrase #" . ($pIndex + 1) . "\n";
        echo "\tText: " . (isset($phrase['text']) ? $phrase['text'] : "[NO TEXT]") . "\n";

        if (!isset($phrase['themes'])) {
            echo "\t -> No themes found for this phrase. Skipping.\n\n";
            continue;
        }

        foreach ($phrase['themes'] as $themeData) {

            $theme = $themeData['theme'];
            $weight = $themeData['weight'];

            // Maximum = weight × 2
            $maxContribution = $weight * 2;

            echo "  -> Theme: $theme | Weight: $weight | +$maxContribution\n";

            if (!isset($themeMaxDegrees[$theme])) {
                $themeMaxDegrees[$theme] = 0;
            }

            $themeMaxDegrees[$theme] += $maxContribution;
        }

        echo "\n";
    }
}

echo "=== Themes Computed ===\n";
foreach ($themeMaxDegrees as $theme => $degree) {
    echo "  $theme => $degree\n";
}
echo "\n";

// ------------------------------------------------
// Build output format
// ------------------------------------------------
$output = [
    "themes" => []
];

foreach ($themeMaxDegrees as $theme => $degree) {
    $output["themes"][$theme] = [
        "maxDegree" => $degree
    ];
}

echo "Output structure created.\n";

// ------------------------------------------------
// Save to output JSON file
// ------------------------------------------------
file_put_contents(
    $outputFile,
    json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
);

echo "JSON saved successfully.\n";
echo "=== DONE ===\n";
