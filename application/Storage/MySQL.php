<?php

namespace Serenity\Storage;

use {PDO, PDOException, Serenity\App};

use PDOStatement;
use RuntimeException;
use function \
{
    count,
    implode,
    array_fill,
};

/**
 * Class MySQL
 * @package Serenity\Storage
 */
class MySQL
{
    public const ERROR_ENCOUNTERED = 1;
    public const CONNECTION_DROPPED = 2;
    public const CONNECTION_RESTORED = 3;

    protected ?PDO $dbh;
    protected int $error;
    protected array $credentials;
    protected string $connectionName;

    /** @var PDOStatement[] */
    protected array $statements = [];

    /**
     * MySQL constructor.
     * @param string $connectionName
     */
    public function __construct(string $connectionName)
    {
        $connectionsList = App::config()->get('mysql');
        $credentials = $connectionsList[$connectionName] ?? null;

        if ($credentials && $this->validateCredentials($credentials))
        {
            $this->connectionName = $connectionName;
            $this->credentials = $connectionsList[$connectionName];
        }
        else {
            throw new RuntimeException("Invalid MySQL configuration for connection {$connectionName}");
        }
    }

    /**
     * @param array $credentials
     * @return bool
     */
    protected function validateCredentials(array $credentials): bool
    {
        return isset(
            $credentials['host'],
            $credentials['port'],
            $credentials['database'],
            $credentials['username'],
            $credentials['password']);
    }

    /**
     * @return PDO
     */
    protected function connect(): PDO
    {
        $credentials = $this->credentials;

        return new PDO(
            "mysql:host={$credentials['host']};port={$credentials['port']};dbname={$credentials['database']};charset=utf8",
            $credentials['username'],
            $credentials['password'],
            $credentials['options'] ?? null);
    }

    /**
     * @return bool
     */
    public function disconnect() : bool
    {
        $connection	= $this->getConnection();

        if ($connection->getAttribute(PDO::ATTR_PERSISTENT))
        {
            try
            {
                $connection->exec('KILL CONNECTION CONNECTION_ID()');
            }
            catch (PDOException $e)
            {
                return false;
            }
        }

        $this->dbh = null;
        return true;
    }

    /**
     * @return PDO
     */
    protected function getConnection() : PDO
    {
        $connection = $this->dbh;

        if (!$connection)
        {
            $connection = $this->dbh = $this->connect();
        }
        return $connection;
    }

    /**
     * @return int
     */
    protected function getLastErrorCode() : int
    {
        return $this->error;
    }

    /**
     * If the connection was dropped, attempts to re-establish it.
     * Otherwise, it simply records the error encountered.
     *
     * @param $errorCode
     * @return int
     */
    protected function processError($errorCode) : int
    {
        if ($errorCode !== 'HY000')
        {
            $this->error = $errorCode;
            return static::ERROR_ENCOUNTERED;
        }
        return $this->connect() ? static::CONNECTION_RESTORED : static::CONNECTION_DROPPED;
    }

    /**
     * @param array $values
     * @return string
     */
    protected function generateWildcards(array $values): string
    {
        return implode(',', array_fill(0, count($values) - 1, '?'));
    }

    /**
     * Creates a prepared statement from query or retrieves a cached statement,
     * if available, and executes it.
     *
     * @param string $query
     * @param array $params
     * @return bool|null|PDOStatement
     */
    public function query(string $query, array $params)
    {
        $statement = $this->createStatement($query);

        if (!$statement) {
            return null;
        }
        if ($statement->execute($params)) {
            return $statement;
        }
        if ($this->processError($statement->errorCode()) === self::CONNECTION_RESTORED) {
            return $this->query($query, $params);
        }
        return null;
    }

    protected function createStatement(string $query)
    {
        if (!isset($$this->statements[$query]))
        {
            $statement = $this->getConnection()->prepare($query);

            if ($statement)
            {
                $this->statements[$query] = $statement;
            }
            else {
                throw new RuntimeException("Could not create prepared statement for query: $query");
            }
        }
        return $this->statements[$query];
    }
}