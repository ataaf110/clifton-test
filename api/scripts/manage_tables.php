<?php

/***************************************
 * CONFIG
 ***************************************/
const DB_HOST = 'localhost';
const DB_NAME = '<DB_NAME>';
const DB_USER = '<DB_NAME>';

/***************************************
 * CLI OPTIONS
 ***************************************/
$options = getopt("", [
    "db-pass:",
    "create-table",
    "add-data",
    "themes",
    "domains",
    "questions"
]);

if (!isset($options['db-pass'])) {
    exit("❌ ERROR: --db-pass is required\n");
}

$DB_PASS = $options['db-pass'];

/***************************************
 * ACTION VALIDATION
 ***************************************/
if (!isset($options['create-table']) && !isset($options['add-data'])) {
    exit("❌ ERROR: Specify at least --create-table or --add-data\n");
}

/***************************************
 * TABLE SELECTION
 ***************************************/
$tablesRequested = array_intersect(
    ['themes', 'domains', 'questions'],
    array_keys($options)
);

if (count($tablesRequested) > 1) {
    exit("❌ ERROR: Only one table can be specified at a time\n");
}

$targetTables = $tablesRequested ?: ['themes', 'domains', 'questions'];

/***************************************
 * DB CONNECTION
 ***************************************/
$mysqli = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if ($mysqli->connect_errno) {
    exit("❌ DB Connection failed: {$mysqli->connect_error}\n");
}
$mysqli->set_charset("utf8mb4");

/***************************************
 * TABLE SCHEMAS
 ***************************************/
$themes_schema = "
        CREATE TABLE IF NOT EXISTS clifton_themes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            themeKey VARCHAR(50) UNIQUE,
            domain VARCHAR(50),
            name VARCHAR(255),
            icon VARCHAR(100),
            phrase TEXT,
            onDominanceDescription LONGTEXT,
            dominanceSupportingDetails LONGTEXT,
            positiveIdentifiers JSON,
            negativeIdentifiers JSON,
            howThemeHelpsYou JSON,
            behavioralProfile JSON,
            workplace JSON,
            improvementMethods JSON,
            books JSON,
            onWeaknessDescription LONGTEXT,
            onWeaknessObstacles JSON,
            onWeaknessOpportunities JSON,
            picture VARCHAR(255),
            maxDegree INT,
            createdAt DATETIME DEFAULT CURRENT_TIMESTAMP,
            updatedAt DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";

$domains_schema = "
        CREATE TABLE IF NOT EXISTS clifton_domains (
            id INT AUTO_INCREMENT PRIMARY KEY,
            domainKey VARCHAR(100) UNIQUE,
            name VARCHAR(255),
            icon VARCHAR(100),

            descriptionLow LONGTEXT,
            descriptionAverage LONGTEXT,
            descriptionHigh LONGTEXT,

            maxDegree INT,

            colorPrimary VARCHAR(20),
            colorBg VARCHAR(20),
            colorSep VARCHAR(20),

            createdAt DATETIME DEFAULT CURRENT_TIMESTAMP,
            updatedAt DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";
    
$questions_schema = "
        CREATE TABLE IF NOT EXISTS clifton_questions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            phrase_a_text TEXT NOT NULL,
            phrase_a_themes JSON NOT NULL,
            phrase_b_text TEXT NOT NULL,
            phrase_b_themes JSON NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";


/***************************************
 * TABLE DEFINITIONS
 ***************************************/
$tables = [

    'themes' => [
        'json' => __DIR__ . '/../resources/themes.json',
        'create' => function($db) {
            $db->query($GLOBALS['themes_schema']);
            echo "✔ themes table ready\n";
        },
        'insert' => function($db, $json) {
            require __DIR__ . '/partials/insert_themes.php';
        }
    ],

    'domains' => [
        'json' => __DIR__ . '/../resources/domains.json',
        'create' => function($db) {
            $db->query($GLOBALS['domains_schema']);
            echo "✔ domains table ready\n";
        },
        'insert' => function($db, $json) {
            require __DIR__ . '/partials/insert_domains.php';
        }
    ],

    'questions' => [
        'json' => __DIR__ . '/../resources/questions.json',
        'create' => function($db) {
            $db->query($GLOBALS['questions_schema']);
            echo "✔ questions table ready\n";
        },
        'insert' => function($db, $json) {
            require __DIR__ . '/partials/insert_questions.php';
        }
    ]
];

/***************************************
 * EXECUTION
 ***************************************/
foreach ($targetTables as $table) {

    echo "\n▶ Processing: $table\n";

    if (isset($options['create-table'])) {
        $tables[$table]['create']($mysqli);
    }

    if (isset($options['add-data'])) {
        $tables[$table]['insert']($mysqli, $tables[$table]['json']);
    }
}

$mysqli->close();
echo "\n✅ Done.\n";
