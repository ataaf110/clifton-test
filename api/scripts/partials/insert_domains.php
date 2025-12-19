<?php

if (!file_exists($json)) {
    die("❌ JSON file not found: $json\n");
}

$data = json_decode(file_get_contents($json), true);
if (!is_array($data)) {
    die("❌ Invalid JSON structure for domains\n");
}

$stmt = $db->prepare("
    INSERT INTO clifton_domains (
        domainKey, name, icon,
        descriptionLow, descriptionAverage, descriptionHigh,
        maxDegree,
        colorPrimary, colorBg, colorSep
    )
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE
        name = VALUES(name),
        icon = VALUES(icon),
        descriptionLow = VALUES(descriptionLow),
        descriptionAverage = VALUES(descriptionAverage),
        descriptionHigh = VALUES(descriptionHigh),
        maxDegree = VALUES(maxDegree),
        colorPrimary = VALUES(colorPrimary),
        colorBg = VALUES(colorBg),
        colorSep = VALUES(colorSep),
        updatedAt = CURRENT_TIMESTAMP
");

if (!$stmt) {
    die("❌ Prepare failed: " . $db->error . "\n");
}

foreach ($data as $key => $d) {

    $stmt->bind_param(
        "ssssssisss",
        $key,
        $d["name"],
        $d["icon"],
        $d["descriptions"]["low"],
        $d["descriptions"]["average"],
        $d["descriptions"]["high"],
        $d["maxDegree"],
        $d["colors"]["primary"],
        $d["colors"]["bg"],
        $d["colors"]["sep"]
    );

    $stmt->execute();
    echo "✔ Domain processed: $key\n";
}

$stmt->close();
