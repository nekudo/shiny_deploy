<?php
namespace ShinyDeploy\Core;

use ShinyDeploy\Exceptions\DatabaseException;

/**
 * class Db
 *
 * Simple MySQLi Wrapper.
 *
 * @author Simon Samtleben <simon@nekudo.com>
 * @license MIT
 */

class Db
{
    /**
     * @var \mysqli A mysqli instance.
     */
    private $mysqli = null;

    /**
     * @var \mysqli_result Mysqli results.
     */
    private $result = null;

    /**
     * @var \mysqli_stmt Holds a mysql statement.
     */
    private $statement = null;

    /**
     * @var string $host Hostname of db server.
     */
    private $host;

    /**
     * @var string $user Username of db server.
     */
    private $user;

    /**
     * @var string $pass Password of db server.
     */
    private $pass;

    /**
     * @var string $db Database to use.
     */
    private $db;


    /**
     * @param string $host DB-Server hostname.
     * @param string $user DB-Server authentication user.
     * @param string $pass DB-Server authentication password.
     * @param string $db Database to use.
     * @param bool $persistent True to open persistent connection.
     * @throws DatabaseException
     */
    public function __construct(string $host, string $user, string $pass, string $db, bool $persistent = false)
    {
        if ($this->connect($host, $user, $pass, $db, $persistent) === false) {
            throw new DatabaseException('Could not connect to database.');
        }
    }

    /**
     * Closes database connection.
     */
    public function __destruct()
    {
        $this->disconnect();
    }

    /**
     * Connect to a mysql database using mysqli.
     *
     * @param string $host DB-Server hostname.
     * @param string $user DB-Server authentication user.
     * @param string $pass DB-Server authentication password.
     * @param string $db Database to use.
     * @param bool $persistent True to open persistent connection.
     * @return bool True if connected successfully false otherwise.
     */
    public function connect(
        string $host = '',
        string $user = '',
        string $pass = '',
        string $db = '',
        bool $persistent = false
    ) : bool {
        $this->mysqli = mysqli_init();
        if ($persistent === true) {
            $host = 'p:' . $host;
        }
        $connectResult = $this->mysqli->real_connect($host, $user, $pass, $db);
        if ($connectResult === false) {
            return false;
        }
        $this->host = $host;
        $this->user = $user;
        $this->pass = $pass;
        $this->db = $db;
        $this->mysqli->set_charset("utf8");
        $this->mysqli->query("SET time_zone = '+00:00'");
        return true;
    }

    /**
     * Close database connection.
     *
     * @return void
     */
    public function disconnect() : void
    {
        if ($this->mysqli !== null) {
            $this->mysqli->close();
        }
    }

    /**
     * Replaces placeholders in an sql-statement with according values.
     * Supported placeholders are:
     * %i = Integer value.
     * %d = Double value.
     * %s = String value.
     * %b = Blob value.
     *
     * @param string $statement The query string.
     * @throws DatabaseException
     * @return Db Instance of this class.
     */
    public function prepare(string $statement = '') : Db
    {
        if (empty($statement)) {
            throw new DatabaseException('No query given.');
        }

        // mask escaped placeholders:
        $statement = str_replace('\%', '{#}', $statement);

        $possibleType = ['%i', '%d', '%s', '%b'];

        // sanitize query:
        foreach ($possibleType as $type) {
            $statement = str_replace("'".$type."'", $type, $statement);
            $statement = str_replace('"'.$type.'"', $type, $statement);
        }

        // check if type and value counts match:
        $values = func_get_args();
        array_shift($values);
        $valueCount = count($values);
        $typeCount = preg_match_all('/%i|%d|%s|%b/sU', $statement, $types);
        if ($valueCount !== $typeCount) {
            throw new DatabaseException('Type count does not match value count.');
        }

        // replace type-placeholders with question-marks for usage in prepared statements:
        $statement = preg_replace('/%i|%d|%s|%b/sU', '?', $statement);

        // unmask:
        $statement = str_replace('{#}', '%', $statement);

        // set statement
        $this->statement = $this->mysqli->prepare($statement);
        if ($this->statement === false) {
            throw new DatabaseException($this->mysqli->error);
        }

        // bind values:
        if ($valueCount > 0) {
            $typeChars = str_replace('%', '', $types[0]);
            $typeChars = implode('', $typeChars);
            $valuesToBind = [];
            $valuesToBind[0] = &$typeChars;
            foreach ($values as &$value) {
                $valuesToBind[] = &$value;
            }
            call_user_func_array([$this->statement, 'bind_param'], $valuesToBind);
        }

        return $this;
    }

    /**
     * Executes an sql-statement and returns result as array.
     *
     * @param bool $pop Removes first layer in result array if only one result.
     * @throws DatabaseException
     * @return array Result of executed sql statement.
     */
    public function getResult(bool $pop = true) : array
    {
        if ($this->executeStatement() === false) {
            throw new DatabaseException('Could not execute statement.');
        }

        $result = array();
        while ($row = $this->result->fetch_array(MYSQLI_ASSOC)) {
            $result[] = $row;
        }

        if ($this->result->num_rows == 1 && $pop === true) {
            $result = $result[0];
        }
        return $result;
    }

    /**
     * Executes sql statement and returns result as one dimensional key value array.
     *
     * @param string $columnName Name of column to be used as array value.
     * @param string $key Name of column to be used as array key.
     * @throws DatabaseException
     * @return array
     */
    public function getColumn(string $columnName, string $key = '') : array
    {
        if ($this->executeStatement() === false) {
            throw new DatabaseException('Could not execute statement.');
        }
        $result = [];
        while ($row = $this->result->fetch_array(MYSQLI_ASSOC)) {
            if (!isset($row[$columnName])) {
                throw new DatabaseException('Invalid column name.');
            }
            if (!empty($key) && !isset($row[$key])) {
                throw new DatabaseException('Invalid key name.');
            }
            if (!empty($key)) {
                $result[$row[$key]] = $row[$columnName];
            } else {
                $result[] = $row[$columnName];
            }
        }
        return $result;
    }

    /**
     * Executes an sql statement and return first column of first row only.
     *
     * @throws DatabaseException
     * @return mixed
     */
    public function getValue()
    {
        if ($this->executeStatement() === false) {
            throw new DatabaseException('Could not execute statement.');
        }
        $row = $this->result->fetch_array(MYSQLI_NUM);
        return $row[0];
    }

    /**
     * Executes an sql-statement.
     *
     * @return bool True if statement could be executed, false on error.
     * @throws DatabaseException
     */
    public function execute() : bool
    {
        return $this->executeStatement();
    }

    /**
     * Returns number of result rows.
     *
     * @return int Number of results.
     */
    public function getResultCount() : int
    {
        return $this->result->num_rows;
    }

    /**
     * Returns mysqli error message.
     *
     * @return string Error message.
     */
    public function getError() : string
    {
        return $this->mysqli->error;
    }

    /**
     * Returns id of last insert operation.
     *
     * @return int Id of last insert operation.
     */
    public function getInsertId() : int
    {
        return $this->mysqli->insert_id;
    }

    /**
     * Pings the database.
     *
     * @return bool
     */
    public function ping() : bool
    {
        return $this->mysqli->ping();
    }

    /**
     * Executes an mysql-statement.
     *
     * @return bool True is statement could be executed, false otherwise.
     * @throws DatabaseException
     */
    private function executeStatement() : bool
    {
        if (empty($this->statement)) {
            throw new DatabaseException('No query given.');
        }
        if ($this->ping() === false) {
            $this->connect($this->host, $this->user, $this->pass, $this->db);
        }
        $execResult = $this->statement->execute();
        $this->result = $this->statement->get_result();
        $this->statement = null;

        return $execResult;
    }
}
