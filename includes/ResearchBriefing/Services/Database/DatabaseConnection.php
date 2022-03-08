<?php

namespace ResearchBriefing\Services\Database;

/**
 * Class DatabaseConnection
 * @package ResearchBriefing\Services\Database
 */
class DatabaseConnection
{
    /**
     * @property \PDO;
     */
    public $connection;

    /**
     * S24Database constructor.
     */
    public function __construct()
    {
        $this->connect();
    }

    /**
     * Connect to the database using PDO
     */
    protected function connect()
    {
        $host = DB_HOST;
        $dbname = DB_NAME;
        $username = DB_USER;
        $password = DB_PASSWORD; 

        $this->connection = new \PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    }
}
