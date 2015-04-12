<?php
namespace ShinyDeploy\Core;

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
     * @var string Holds a mysql statement.
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
     * Use instead of constructor to get instance of class.
     *
     * @param string $host DB-Server hostname.
     * @param string $user DB-Server authentication user.
     * @param string $pass DB-Server authentication password.
     * @param string $db Database to use.
     * @param bool $persistent True to open persistent connection.
     * @throws \Exception
     */
    public function __construct($host, $user, $pass, $db, $persistent = false)
    {
        if ($this->connect($host, $user, $pass, $db, $persistent) === false) {
            throw new \Exception('Could not connect to database.');
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
    public function connect($host = null, $user = null, $pass = null, $db = null, $persistent = false)
    {
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
     */
    public function disconnect()
    {
        if ($this->mysqli !== null) {
            $this->mysqli->close();
        }
    }

    /**
     * Replaces placeholders in an sql-statement with according values.
     * Supported placeholders are:
     * %d	= Numeric value. Not quoted.
     * %s	= Quoted string.
     * %S	= Unquoted string, e.g. 1,2,3 in IN statement (WHERE foo IN(%S))
     *
     * @param string $statement The query string.
     * @return Db Instance of this class.
     */
    public function prepare($statement = null)
    {
        if (empty($statement)) {
            trigger_error('No query given', E_USER_ERROR);
        }

        // mask escaped placeholders:
        $statement = str_replace('\%', '{#}', $statement);

        // get values and check count:
        $values = func_get_args();
        array_shift($values);
        if (substr_count($statement, '%s') + substr_count($statement, '%S') +
            substr_count($statement, '%d') != count($values)) {
            trigger_error('Passed value-count does not match placeholder-count.', E_USER_ERROR);
        }

        // sanitize query:
        $statement = str_replace("'%s'", '%s', $statement);
        $statement = str_replace('"%s"', '%s', $statement);
        $statement = str_replace("'%d'", '%d', $statement);
        $statement = str_replace('"%d"', '%d', $statement);

        // quote strings (%S is placeholder for unqouted strings):
        $statement = str_replace('%s', "'%s'", $statement);
        $statement = str_replace('%S', '%s', $statement);


        // prepare values for use in sql statement:
        foreach (array_keys($values) as $key) {
            $values[$key] = $this->mysqli->real_escape_string($values[$key]);
        }

        // replace placeholders with passed values:
        $statement = vsprintf($statement, $values);

        // unmask:
        $this->statement = str_replace('{#}', '%', $statement);

        return $this;
    }

    /**
     * Executes an sql-statement and returns result as array.
     *
     * @param bool $pop Removes first layer in result array if only one result.
     * @return array Result of executed sql statement.
     */
    public function getResult($pop = true)
    {
        if ($this->executeStatement() === false) {
            return false;
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
     * @return array|bool
     */
    public function getColumn($columnName, $key = '')
    {
        if ($this->executeStatement() === false) {
            return false;
        }
        $result = [];
        while ($row = $this->result->fetch_array(MYSQLI_ASSOC)) {
            if (!isset($row[$columnName])) {
                return false;
            }
            if (!empty($key) && !isset($row[$key])) {
                return false;
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
     * Exexutes an sql statement and return first column of first row only.
     *
     * @return mixed
     */
    public function getValue()
    {
        if ($this->executeStatement() === false) {
            return false;
        }
        $row = $this->result->fetch_array(MYSQLI_NUM);
        return $row[0];
    }

    /**
     * Executes an sql-statement.
     *
     * @return bool True if statement could be executed, false if an error occoured.
     */
    public function execute()
    {
        return $this->executeStatement();
    }

    /**
     * Returns number of result rows.
     *
     * @return int Number of results.
     */
    public function getResultCount()
    {
        return $this->result->num_rows;
    }

    /**
     * Returns mysqli error message.
     *
     * @return string Error message.
     */
    public function getError()
    {
        return $this->mysqli->error;
    }

    /**
     * Returns id of last insert operation.
     *
     * @return int Id of last insert operation.
     */
    public function getInsertId()
    {
        return $this->mysqli->insert_id;
    }

    /**
     * Pings the database.
     *
     * @return bool
     */
    public function ping()
    {
        return $this->mysqli->ping();
    }

    /**
     * Executes an mysql-statement.
     *
     * @return bool True is statement could be executed, false otherwise.
     */
    private function executeStatement()
    {
        if (empty($this->statement)) {
            trigger_error('No query given.', E_USER_ERROR);
        }
        if ($this->ping() === false) {
            $this->connect($this->host, $this->user, $this->pass, $this->db);
        }
        $this->result = $this->mysqli->query($this->statement);
        $this->statement = null;

        return ($this->result === false) ? false : true;
    }
}
