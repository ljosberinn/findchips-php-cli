<?php declare(strict_types=1);

class Logger {

    /* @var int $stringThresholdLength */
    private $stringThresholdLength;

    private $currentStockSizeThreshold;

    private const COLUMN_SEPARATOR = ' | ';

    public function __construct() {
        $this->currentStockSizeThreshold = Parser::$STOCK_SIZE_THRESHOLD;
    }

    public function showHead(): string {
        $columns = [
            str_pad('Chip', 20, ' ', STR_PAD_BOTH),
            str_pad('Total Stock', 20, ' ', STR_PAD_BOTH),
            'Available Distributors > ' . $this->currentStockSizeThreshold,
            'EOL',
        ];

        $this->stringThresholdLength = strlen((string) $this->currentStockSizeThreshold);

        $head = implode(self::COLUMN_SEPARATOR, $columns) . "\r\n";
        return $head . str_pad('', strlen($head), '-') . "\r\n";
    }

    public function logResult(string $chip, int $totalStock, int $distributorsWithStock, bool $setToEOL): string {
        return implode(self::COLUMN_SEPARATOR, [
                str_pad($chip, 20),
                str_pad(number_format($totalStock), 20, ' ', STR_PAD_LEFT),
                str_pad(number_format($distributorsWithStock), 25 + $this->stringThresholdLength, ' ', STR_PAD_LEFT),
                $setToEOL ? 'yes' : 'no',
            ]) . "\r\n";
    }
}
