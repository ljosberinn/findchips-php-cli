#!/usr/bin/php -q
<?php declare(strict_types=1);

// only show actual errors, as DOMDocument->loadHTML seems to validate HTML and throws warnings on invalid HTML
error_reporting(E_ERROR);

require_once 'CLIArgumentValidator.php';
require_once 'Database.php';
require_once 'Parser.php';
require_once 'Logger.php';

$CLIArgumentValidator = new CLIArgumentValidator();
$CLIArgumentValidator->setRequiredArguments('dbPath', 'table', 'odbc');
$sanitizedArguments = $CLIArgumentValidator->validate();

$database = new Database($sanitizedArguments['odbc'], $sanitizedArguments['table']);
$parser   = new Parser();
$logger   = new Logger();

echo $logger->showHead();

foreach($database->getNonEOLChips() as $row) {
    $html = $parser->get($row['partname']);

    [$amountDistributorsWithStock, $totalStock] = $parser->parse($html);

    $setToEOL = $amountDistributorsWithStock < 3 ? $database->setToEOL((int) $row['id']) : false;

    echo $logger->logResult($row['partname'], $totalStock, $amountDistributorsWithStock, $setToEOL);
}

$database->logExecutionTime();
