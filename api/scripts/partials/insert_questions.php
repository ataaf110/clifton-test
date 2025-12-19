<?php

if (!file_exists($json)) {
    die("❌ JSON file not found: $json\n");
}

$data = json_decode(file_get_contents($json), true);
if (!is_array($data)) {
    die("❌ Invalid JSON structure for questions\n");
}

$stmt = $db->prepare("
    INSERT INTO clifton_questions (
        phrase_a_text,
        phrase_a_themes,
        phrase_b_text,
        phrase_b_themes
    )
    VALUES (?, ?, ?, ?)
");

if (!$stmt) {
    die("❌ Prepare failed: " . $db->error . "\n");
}

foreach ($data as $index => $pair) {

    if (!isset($pair[0], $pair[1])) {
        echo "⚠ Skipping invalid record at index $index\n";
        continue;
    }

    $a_text = $pair[0]["text"] ?? "";
    $a_themes = json_encode($pair[0]["themes"] ?? []);
    $b_text = $pair[1]["text"] ?? "";
    $b_themes = json_encode($pair[1]["themes"] ?? []);

    $stmt->bind_param(
        "ssss",
        $a_text,
        $a_themes,
        $b_text,
        $b_themes
    );

    $stmt->execute();
    echo "✔ Question inserted: #$index\n";
}

$stmt->close();
