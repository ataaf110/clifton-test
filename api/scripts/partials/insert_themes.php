<?php

if (!file_exists($json)) {
    die("❌ JSON file not found: $json\n");
}

$data = json_decode(file_get_contents($json), true);
if (!is_array($data)) {
    die("❌ Invalid JSON structure for themes\n");
}

$stmt = $db->prepare("
    INSERT INTO clifton_themes (
        themeKey, domain, name, icon, phrase,
        onDominanceDescription, dominanceSupportingDetails,
        positiveIdentifiers, negativeIdentifiers,
        howThemeHelpsYou, behavioralProfile,
        workplace, improvementMethods, books,
        onWeaknessDescription, onWeaknessObstacles,
        onWeaknessOpportunities, picture, maxDegree
    )
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE
        domain = VALUES(domain),
        name = VALUES(name),
        icon = VALUES(icon),
        phrase = VALUES(phrase),
        onDominanceDescription = VALUES(onDominanceDescription),
        dominanceSupportingDetails = VALUES(dominanceSupportingDetails),
        positiveIdentifiers = VALUES(positiveIdentifiers),
        negativeIdentifiers = VALUES(negativeIdentifiers),
        howThemeHelpsYou = VALUES(howThemeHelpsYou),
        behavioralProfile = VALUES(behavioralProfile),
        workplace = VALUES(workplace),
        improvementMethods = VALUES(improvementMethods),
        books = VALUES(books),
        onWeaknessDescription = VALUES(onWeaknessDescription),
        onWeaknessObstacles = VALUES(onWeaknessObstacles),
        onWeaknessOpportunities = VALUES(onWeaknessOpportunities),
        picture = VALUES(picture),
        maxDegree = VALUES(maxDegree),
        updatedAt = CURRENT_TIMESTAMP
");

if (!$stmt) {
    die("❌ Prepare failed: " . $db->error . "\n");
}

foreach ($data as $themeKey => $t) {

    // JSON fields
    $positive_identifiers      = json_encode($t["positiveIdentifiers"] ?? []);
    $negative_identifiers      = json_encode($t["negativeIdentifiers"] ?? []);
    $how_theme_helps_you       = json_encode($t["howThemeHelpsYou"] ?? []);
    $behavioral_profile        = json_encode($t["behavioralProfile"] ?? []);
    $workplace                 = json_encode($t["workplace"] ?? []);
    $improvement_methods       = json_encode($t["improvementMethods"] ?? []);
    $books                     = json_encode($t["books"] ?? []);
    $on_weakness_obstacles     = json_encode($t["onWeaknessObstacles"] ?? []);
    $on_weakness_opportunities = json_encode($t["onWeaknessOpportunities"] ?? []);

    // Normal fields – assign to variables
    $v_theme_key            = $themeKey;
    $v_domain               = $t["domain"] ?? null;
    $v_name                 = $t["name"] ?? null;
    $v_icon                 = $t["icon"] ?? null;
    $v_phrase               = $t["phrase"] ?? null;
    $v_on_dom_desc          = $t["onDominanceDescription"] ?? null;
    $v_dom_support          = $t["dominanceSupportingDetails"] ?? null;
    $v_positive             = $positive_identifiers;
    $v_negative             = $negative_identifiers;
    $v_how_help             = $how_theme_helps_you;
    $v_behavioral           = $behavioral_profile;
    $v_workplace            = $workplace;
    $v_improvement          = $improvement_methods;
    $v_books                = $books;
    $v_on_weak_desc         = $t["onWeaknessDescription"] ?? null;
    $v_on_weak_obstacles    = $on_weakness_obstacles;
    $v_on_weak_opportunities= $on_weakness_opportunities;
    $v_picture              = $t["picture"] ?? null;
    $v_max_degree           = $t["maxDegree"] ?? null;

    // Bind all variables
    $stmt->bind_param(
        "ssssssssssssssssssi",
        $v_theme_key,
        $v_domain,
        $v_name,
        $v_icon,
        $v_phrase,
        $v_on_dom_desc,
        $v_dom_support,
        $v_positive,
        $v_negative,
        $v_how_help,
        $v_behavioral,
        $v_workplace,
        $v_improvement,
        $v_books,
        $v_on_weak_desc,
        $v_on_weak_obstacles,
        $v_on_weak_opportunities,
        $v_picture,
        $v_max_degree
    );

    $stmt->execute();
    echo "✔ Theme processed: $themeKey\n";
}

$stmt->close();
