<?php declare(strict_types=1);

class Database {

    /* @var PDO $pdo */
    private $pdo;
    /* @var float $start */
    private $start;
    /* @var string $table */
    private $table;

    private const QUERIES = [
        'verifyTableExists' => 'SELECT `id` FROM `%TABLENAME%`',
        'getNonEOLChips'    => 'SELECT `id`, `partname` FROM `%TABLENAME%` WHERE `state` NOT LIKE \'EOL\'',
        'setToEOL'          => 'UPDATE `%TABLENAME%` SET `state` = :state WHERE `id` = :id',
    ];

    /**
     * @param string $odbc     [system defined odbc DSN]
     * @param string $table    [table to update]
     * @param string $user     [optionally required username for db access]
     * @param string $password [optionally required pw for db access]
     */
    public function __construct(string $odbc, string $table, string $user = '', string $password = '') {
        try {
            $this->pdo   = new PDO('odbc:' . $odbc, $user, $password);
            $this->start = microtime(true);

            $this->verifyTableExists($table);
        } catch(PDOException $exception) {
            die($exception->getMessage());
        }
    }

    /**
     * Returns all non-EOL chip datasets
     *
     * @return array
     */
    public function getNonEOLChips(): array {
        $stmt = $this->pdo->query(str_replace('%TABLENAME%', $this->table, self::QUERIES['getNonEOLChips']));
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Updates chip in DB to EOL
     *
     * @param int $id
     *
     * @return bool
     */
    public function setToEOL(int $id): bool {
        $stmt = $this->pdo->prepare(str_replace('%TABLENAME%', $this->table, self::QUERIES['setToEOL']));
        return $stmt->execute([
            'state' => 'EOL',
            'id'    => $id,
        ]);
    }

    /**
     * Just metrics
     */
    public function logExecutionTime(): void {
        echo microtime(true) - $this->start . 's execution time';
    }

    /**
     * Verifies the given table actually exists
     *
     * @param string $table
     */
    private function verifyTableExists(string $table) {
        $stmt = $this->pdo->query(str_replace('%TABLENAME%', $table, self::QUERIES['verifyTableExists']));

        if($stmt->rowCount() === 0) {
            die('Connection established, but couldn\'t find table "' . $table . '"');
        }

        $this->table = $table;
    }
}
