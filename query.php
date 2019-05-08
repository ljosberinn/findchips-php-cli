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

$columns = [
    str_pad('Chip', 20, ' ', STR_PAD_BOTH),
    str_pad('Total Stock', 20, ' ', STR_PAD_BOTH),
    'Available Distributors > ' . Parser::$STOCK_SIZE_THRESHOLD,
    'EOL',
];

$stringThresholdLength = strlen((string) Parser::$STOCK_SIZE_THRESHOLD);

$head = implode(' | ', $columns) . "\r\n";
echo $head . str_pad('', strlen($head), '-') . "\r\n";

foreach($database->getNonEOLChips() as $row) {
    $html                        = $parser->get($row['partname']);
    $amountDistributorsWithStock = $parser->parse($html);

    $setToEOL = false;
    if($amountDistributorsWithStock < 3) {
        $setToEOL = $database->setToEOL((int) $row['id']);
    }

    echo implode(' | ', [
            str_pad($row['partname'], 20),
            str_pad(number_format($parser->getTotalStock()), 20, ' ', STR_PAD_LEFT),
            str_pad(number_format($amountDistributorsWithStock), 25 + $stringThresholdLength, ' ', STR_PAD_LEFT),
            $setToEOL ? 'yes' : 'no',
        ]) . "\r\n";
}

$database->logExecutionTime();
