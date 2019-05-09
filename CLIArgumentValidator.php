<?php declare(strict_types=1);

class CLIArgumentValidator {

    /* @var array $requiredArguments */
    private $requiredArguments;
    /* @var int $requiredArgumentAmount */
    private $requiredArgumentAmount;


    /* @var array $requiredArguments */
    private $givenArguments;
    /* @var int $givenArgumentAmount */
    private $givenArgumentAmount;


    public function __construct() {
        $this->givenArguments      = $this->setGivenArguments();
        $this->givenArgumentAmount = $this->setGivenArgumentAmount();
    }

    /**
     * @param string ...$arguments
     */
    public function setRequiredArguments(...$arguments): void {
        $this->requiredArguments      = $arguments;
        $this->requiredArgumentAmount = count($arguments);
    }

    /**
     * Validates given arguments against expected arguments
     *
     * @return array
     * @throws Error
     */
    public function validate(): array {
        if($this->givenArgumentAmount < $this->requiredArgumentAmount) {
            die('Arguments missing, got ' . $this->givenArgumentAmount . ', but required are: ' . implode(', ', $this->requiredArguments));
        }

        $sanitizedArguments = [];

        // parses CLI arguments provided via "-arg value" to [arg => value]
        for($i = 1; $limit = $this->requiredArgumentAmount * 2 - 1, $i <= $limit; $i += 2) {
            $name  = str_replace('-', '', $this->givenArguments[$i]);
            $value = $this->givenArguments[$i + 1];

            $sanitizedArguments[$name] = $value;
        }

        foreach($this->requiredArguments as $argument) {
            if(!array_key_exists($argument, $sanitizedArguments)) {
                die('Required argument missing: ' . $argument);
            }
        }

        if(!file_exists($sanitizedArguments['dbPath'])) {
            die('Access database not found under ' . $sanitizedArguments['dbPath']);
        }

        return $sanitizedArguments;
    }

    private function setGivenArguments(): array {
        return $_SERVER['argv'];
    }

    private function setGivenArgumentAmount(): int {
        // first argument provided is the script itself
        return $_SERVER['argc'] - 1;
    }


}
