<?php declare(strict_types=1);

class Parser {

    private const URI = 'https://www.findchips.com/search/';
    private const DISTRIBUTOR_RESULTS_CLASS = 'distributor-results';
    private const INSTOCK_DATASET = 'data-instock';

    public static $STOCK_SIZE_THRESHOLD = 1000;

    /* @var DOMDocument $DOM */
    private $DOM;

    public function __construct() {
        $this->DOM = new DOMDocument();
    }

    /**
     * Curls findchips.com's search HTML
     *
     * @param string $chip
     */
    public function get(string $chip): string {
        return $this->curl($chip);
    }

    /**
     * Extracts the amount of distributors having a higher
     * stock than STOCK_SIZE_THRESHOLD available
     *
     * @param string $html
     *
     * @return array
     */
    public function parse(string $html): array {
        $this->DOM->loadHTML($html);

        $distributors = $this->extractDistributors();

        $totalStock = $amountDistributorsWithStock = 0;

        if(count($distributors) === 0) {
            return [$amountDistributorsWithStock, $totalStock];
        }

        foreach($distributors as $div) {
            $currentStock = $this->countStock($div);

            if($currentStock > self::$STOCK_SIZE_THRESHOLD) {
                ++$amountDistributorsWithStock;
            }

            $totalStock += $currentStock;
        }

        return [$amountDistributorsWithStock, $totalStock];
    }

    /**
     * @param DOMElement $div
     *
     * @return int
     */
    private function countStock(DOMElement $div): int {
        $currentStock = 0;

        foreach($div->getElementsByTagName('tbody') as $tbody) {
            /* @var DOMDocument|DOMElement $tbody */
            foreach($tbody->getElementsByTagName('tr') as $tr) {
                /* @var DOMDocument|DOMElement $tr */
                $inStock = $tr->getAttribute(self::INSTOCK_DATASET);

                // verify it's not empty for whatever reason
                if($inStock !== NULL) {
                    $currentStock += (int) $inStock;
                }
            }
        }

        return $currentStock;
    }

    /**
     * Parses the DOM for div.distributor-results
     *
     * @return array
     */
    private function extractDistributors(): array {
        $divs = [];

        foreach($this->DOM->getElementsByTagName('div') as $div) {
            $divs[] = $div;
        }

        return array_filter($divs, static function($div) {
            /* @var DOMDocument|DOMElement $div */
            return $div->getAttribute('class') === self::DISTRIBUTOR_RESULTS_CLASS;
        });
    }

    private function curl(string $chip): ?string {
        try {
            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL            => self::URI . $chip,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYPEER => true,
            ]);
            $response = curl_exec($curl);
            curl_close($curl);

            return $response;
        } catch(Error $error) {
            die($error->getMessage());
        }
    }
}
