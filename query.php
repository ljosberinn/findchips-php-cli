#!/usr/bin/php -q
<?php declare(strict_types=1);

// only show actual errors, as DOMDocument->loadHTML seems to validate HTML and throws warnings on invalid HTML
error_reporting(E_ERROR);

// first argument provided is the script itself
$amountArgs = $_SERVER['argc'] - 1;
$args       = $_SERVER['argv'];

$requiredArgs = [
    'dbPath',
    'table',
    'odbc',
];

$amountRequiredArgs = count($requiredArgs);

if($amountArgs < $amountRequiredArgs) {
    die('arguments missing, got ' . $amountArgs . ', required are: ' . implode(', ', $requiredArgs));
}

$sanitizedArgs = [];
for($i = 1; $limit = $amountRequiredArgs * 2 - 1, $i <= $limit; $i += 2) {
    // parses CLI arguments provided via "-arg value" to [arg => value]
    $sanitizedArgs[str_replace('-', '', $args[$i])] = $args[$i + 1];
}

foreach($requiredArgs as $arg) {
    if(!array_key_exists($arg, $sanitizedArgs)) {
        die('argument missing: ' . $arg);
    }
}

if(!file_exists($sanitizedArgs['dbPath'])) {
    die('Access database not found under ' . $sanitizedArgs['dbPath']);
}

require_once 'Database.php';
require_once 'Parser.php';

$database = new Database($sanitizedArgs['odbc'], $sanitizedArgs['table']);
$parser   = new Parser();

foreach($database->getNonEOLChips() as $row) {
    $html                        = $parser->get($row['partname']);
    $amountDistributorsWithStock = $parser->parse($html);

    $setToEOL = false;
    if($amountDistributorsWithStock < 3) {
        $setToEOL = $database->setToEOL((int) $row['id']);
    }

    $result = [
        str_pad($row['partname'], 20),
        'Total Stock:' . str_pad(number_format($parser->getTotalStock()), 19, ' ', STR_PAD_LEFT),
        'Available Distributors > 1000: ' . str_pad(number_format($amountDistributorsWithStock), 10, ' ', STR_PAD_LEFT),
        'EOL: ' . ($setToEOL ? 'yes' : 'no'),
    ];

    echo implode(' | ', $result) . "\r\n";
}

$database->logExecutionTime();
