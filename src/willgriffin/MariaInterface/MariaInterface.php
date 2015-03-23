<?php
/**
 * Q&D interface to a maria database
 * @package MariaInterface
 * @version 0.1.0
 * @link https://github.com/willgriffin/mariainterface
 * @author willgriffin <https://github.com/willgriffin>
 * @license https://github.com/willgriffin/mariainterface/blob/master/LICENSE
 * @copyright Copyright (c) 2014, willgriffin
 */

namespace willgriffin\MariaInterface;

require(dirname(dirname(dirname(dirname(__FILE__)))) . '/vendor/autoload.php');

/**
 * The MariaInterface class
 * @author willgriffin <https://github.com/willgriffin>
 * @since 0.1.0
 */
class MariaInterface
{

  /**
  * Database Connection
  * @var mysqli $conn mysqli transport layer
  * @since 0.1.0
  */
  public $conn;

  /**
  * Database Name
  * @var str $name Name of the database
  * @since 0.1.0
  */
  public $name;     /* database name */

  /**
  * Database Host
  * @var str $host The ip address or hostname of the mariadb server
  * @since 0.1.0
  */
  public $host;

  /**
  * Database User
  * @var str $user The username to connect to the databse with
  * @since 0.1.0
  */
  public $user;

  /**
  * Database Password
  * @var str $pass The password to use for the datbase connection
  * @since 0.1.0
  */
  public $pass;

  /**
  * Port
  * @var int $port Port the database is running on.
  * @since 0.1.0
  */
  public $port;

  /**
  * OPCache reference
  * @var opcache $cache Originally intended to save queries for things like
  *                     database structure, probably broken under php-fpm.
  *                     TODO: evaulate worth of related functionality either
  *                     refactor to memcache or toast
  *
  * @since 0.1.0
  */
  public $cache;

  /**
  * Tracelog filename
  * @var str $traceLog Tracelog filename, deprecated in favour of hooks
  * @since 0.1.0
  */
  public $traceLog; /* optional tracelog filename */

  /**
  * Database Structure
  * @var array $_tables The database tables definition as loaded by loadStructure
  * @since 0.1.0
  */
  protected $_tables;

  /**
  * constructor
  * @name __construct
  * @param mysql|array $args Either a mysqli connection or credentials to create on
  * @since 0.1.0
  * @return object the MariaInterface object
  */
  function __construct($args = null)
  {
    if (isset($args['conn'])) {
      $this->conn = $args['conn'];
    } else {
      if (empty($args['host'])) {
        $this->host = "localhost";
      } else {
        $this->host = $args['host'];
      }

      if (empty($args['name'])) {
        $this->name = "";
      } else {
        $this->name = $args['name'];
      }

      if (empty($args['user'])) {
        $this->user = "";
      } else {
        $this->user = $args['user'];
      }

      if (empty($args['pass'])) {
        $this->pass = "";
      } else {
        $this->pass = $args['pass'];
      }

      if (empty($args['port'])) {
        $this->port = "";
      } else {
        $this->port = $args['port'];
      }

      $this->connect();
    }
  }

  /**
  * Executes a sql insert and returns the new records primary key
  * @name insert
  * @param string $sql sql to execute
  * @param array $prepArgs optional arguments to send to bind_param for prepared queries
  * @since 0.1.0
  * @return int primary key of the new record
  * <code>
  * <?php
  *
  * $foo_id = $db->update('insert into foo (name) values ("bar")');
  *
  * //prepared
  * $foo_id = $db->update('insert into foo (name) values (?)', ['s', 'bar']);
  *
  * ?>
  * </code>
  */
  public function insert($sql, $prepArgs = false)
  {
    if ($que = $this->query($sql, $prepArgs)) {
      $result = $this->conn->insert_id;
      return $result;
    } else {
      echo "Invalid query\n";
      echo "$sql\n";
      var_dump($prepArgs);
      die;
    }
  }

  /**
  * Executes a sql statement and returns true if successful
  * @name update
  * @param string $sql sql to execute
  * @param array $prepArgs optional arguments to send to bind_param for prepared queries
  * @since 0.1.0
  * @return bool true on success
  * <code>
  * <?php
  *
  * $isUpdated = $db->update('update foo set name = "bar" where foo_id = 1');
  *
  * //prepared
  * $isUpdated = $db->update('update foo set name = ? where foo_id = ?', ['si', 'bar', 1]);
  *
  * ?>
  * </code>
  */
  public function update($sql, $prepArgs = false)
  {
    if ($que = $this->query($sql, $prepArgs)) {
      return true;
    }
  }


  /**
  * Schematic alias to update
  * @name delete
  * @param string $sql sql to execute
  * @param array $prepArgs optional arguments to send to bind_param for prepared queries
  * @since 0.1.0
  * @return bool true on success
  * <code>
  * <?php
  *
  * $isDeleted = $db->delete('delete from foo where foo_id = 1');
  *
  * //prepared
  * $isDeleted = $db->delete('delete from foo where foo_id = ?', ['i', 1]);
  *
  * ?>
  * </code>
  */
  public function delete($sql, $prepArgs = false)
  {
    return $this->update($sql, $prepArgs);
  }



  /**
  * Exec sql and return first column of first row returned or false if empty
  * @name value
  * @param string $sql sql to execute
  * @param array $prepArgs optional arguments to send to bind_param for prepared queries
  * @since 0.1.0
  * @return mixed|bool first column of first row of sql or false if empty
  * <code>
  * <?php
  *
  * $fooID = $db->value('select bar_id from bars where bar_id = 1');
  *
  * //prepared
  * $fooID = $db->value('select bar_id from bars where bar_id = ?', ['i', $fooID]);
  *
  * ?>
  * </code>
  */
  public function value($sql, $prepArgs = false)
  {
    if ($que = $this->query($sql, $prepArgs)) {
      $row = $que->fetch_row();
      return $row[0];
    } else {
      $this->error("ERR dbObj::value [{$this->name}] :  {$this->conn->error}<br/>\n$sql", $this->conn->error);
    }
  }

  /**
  * Returns of single columns of $sql array as simple array, defaults to first column
  * @name column
  * @param string $sql sql to execute
  * @param int $index column index to return
  * @param array $prepArgs optional arguments to send to bind_param for prepared queries
  * @since 0.1.0
  * @return str|bool single column of $sql results or false if empty
  * <code>
  * <?php
  *
  * //returns array of 'name' values starting with 'a'
  * $foonames = $db->column("select foo_id, name from foos where name like ?", 1, ['s', 'a%']);
  *
  * ?>
  * </code>
  */
  public function column($sql, $index = 0, $prepArgs = false)
  {
    if ($que = $this->query($sql, $prepArgs)) {
      $result = [];
      while ($row = $que->fetch_row()) {
        $result[] = $row[$index];
      }
      return $result;
    }
  }


  /**
  * Returns results of $sql as json string
  * @name assocs
  * @param string $sql sql to execute
  * @param array $prepArgs optional arguments to send to bind_param for prepared queries
  * @since 0.1.0
  * @return str|bool results of $sql as a json
  * <code>
  * <?php
  *
  * $foo = $db->json("select * from foos where foo_id = 1");
  *
  * //prepared
  * $foo = $db->json("select * from foos where foo_id = ?", ['i',1]);
  *
  * ?>
  * </code>
  */
  public function json($sql, $prepArgs = false)
  {
    return json_encode($this->assocs($sql, $prepArgs));
  }

  /**
  * Returns results of $sql as an array of associative arrays or false if not found
  * @name assocs
  * @param string $sql sql to execute
  * @param array $prepArgs optional arguments to send to bind_param for prepared queries
  * @since 0.1.0
  * @return array|bool results of $sql as an array of associative arrays or false if empty
  * <code>
  * <?php
  *
  * $foo = $db->assocs("select * from foos where foo_id = 1");
  *
  * //prepared
  * $foo = $db->assocs("select * from foos where foo_id = ?", ['i',1]);
  *
  * ?>
  * </code>
  */
  public function assocs($sql, $prepArgs = false)
  {
    if ($que = $this->query($sql, $prepArgs)) {
      if ($que->num_rows > 0) {
        while($row = $que->fetch_assoc()) {
          $result[] = $row;
        }
      } else {
        $result = array();
      }

      return $result;
    } else {
      $this->error("ERR dbObj::hashes [{$this->name}] : {$this->conn->error}<br/>\n$sql", $this->conn->error);
    }
  }

    /**
    * Returns the first result of $sql as an associative array or false if not found
    * @name assoc
    * @param string $sql sql to execute
    * @param array $prepArgs optional arguments to send to bind_param for prepared queries
    * @since 0.1.0
    * @return object|bool first row after sql statement as associate array or false if failed
    * <code>
    * <?php
    *
    * $foo = $db->assoc("select * from foos where foo_id = 1");
    *
    * //prepared
    * $foo = $db->assoc("select * from foos where foo_id = ?", ['i',1]);
    *
    * ?>
    * </code>
    */
    function assoc($sql, $prepArgs = false)
    {
      $results = $this->assocs($sql, $prepArgs);
      if (count($results) > 0) {
        return $results[0];
      } else {
        return $results;
      }
    }

    /**
    * Executes $sql and returns the first found row as an object
    * @name object
    * @param string $sql sql to execute
    * @param array $prepArgs optional arguments to send to bind_param for prepared queries
    * @since 0.1.0
    * @return object|bool first row after sql statement as stdClass or false if empty
    * <code>
    * <?php
    *
    * $foos = $db->objects("select * from foos");
    *
    * //prepared
    * $foos = $db->objects("select * from foos where foo_id > 0", ['i',0]);
    *
    * ?>
    * </code>
    */
    public function object($sql, $prepArgs = false)
    {
      if ($que = $this->query($sql, $prepArgs)) {
        $result = $que->fetch_object();
        return $result;
      }
    }


    /**
    * Executes $sql and returns the results as array of objects
    * @name objects
    * @param string $sql sql to execute
    * @param array $prepArgs optional arguments to send to bind_param for prepared queries
    * @since 0.1.0
    * @return array|bool results of sql array of objects or false on empty
    * <code>
    * <?php
    *
    * $foos = $db->objects("select * from foos");
    *
    * //prepared
    * $foos = $db->objects("select * from foos where foo_id > 0", ['i',0]);
    *
    * ?>
    * </code>
    */
    public function objects($sql, $prepArgs = false) {
      if ($que = $this->query($sql, $prepArgs)) {
        while ($result = $que->fetch_object()) {
          $results[] = $result;
        }
        return $results;
      }
    }


    /**
    * Executes a query and runs a lambda on each resulting rows first value using
    * the scope if provided
    * @name mapValues
    * @param string $sql sql to execute
    * @param array $prepArgs optional arguments to send to bind_param for prepared queries
    * @param object $method function to run on each result
    * @param object $scope optional scope
    * @since 0.1.0
    * @return object the MariaInterface object
    * <code>
    * <?php
    *  $label = "ID: ";
    *  $db->foreachValue(
    *    "select id from foobar where id > ?", ['i',1],
    *    function($result, $scope) {
    *      echo $scope.$result."\n";
    *    },
    *    $label);
    * ?>
    * </code>
    */

    public function mapValues()
    {
      $args = func_get_args();
      return $this->map('value', $args[0], $args[1], $args[2], $args[3]);
    }




    /**
    * Executes a query and runs a lambda on each resulting row passed as an associative array
    * @name mapObjects
    * @param string $sql sql to execute
    * @param array $prepArgs optional arguments to send to bind_param for prepared queries
    * @param object $method function to run on each result
    * @param object $scope optional scope
    * @since 0.1.0
    * @return object the MariaInterface object
    * <code>
    * <?php
    *  $label = "ID: ";
    *  $db->foreachValue(
    *    "select id from foobar where id > ?", ['i',1],
    *    function($result, $scope) {
    *      echo $scope.$result."\n";
    *    },
    *    $label);
    * ?>
    * </code>
    */
    public function mapAssocs()
    {
      $args = func_get_args();
      return $this->map('assoc', $args[0], $args[1], $args[2], $args[3]);
    }

    /**
    * Executes a query and runs a lambda on each resulting row passed as an object
    * @name mapObjects
    * @param string $sql sql to execute
    * @param array $prepArgs optional arguments to send to bind_param for prepared queries
    * @param object $method function to run on each result
    * @param object $scope optional scope
    * @since 0.1.0
    * @return object the MariaInterface object
    * <code>
    * <?php
    *  $label = "ID: ";
    *  $db->foreachValue(
    *    "select id from foobar where id > ?", ['i',1],
    *    function($result, $scope) {
    *      echo $scope.$result."\n";
    *    },
    *    $label);
    * ?>
    * </code>
    */
    public function mapObjects() {
      $args = func_get_args();
      return $this->map('object', $args[0], $args[1], $args[2], $args[3]);
    }

  /**
  *
  * executes a query and runs a lambda on each resulting row passed as $type
  *
  *
  * @param string $type array/object/assoc/row/value
  * @param string $sql sql to execute
  * @param array $prepArgs bind_param arguments for prepared query
  * @param function $method function to run on each result
  * @param function $scope optional scope
  *
  * or
  *
  * @param string $type object/assoc/row
  * @param string $sql sql to execute
  * @param function $method function to run on each result
  * @param function $scope optional scope
  *
  *
  * <code>
  * <?php
  *
  * $db->map('select * from foo', function($result) {
  *   $row = $result->fetch_row();
  *   var_dump($row);
  * });
  *
  * //basic
  * $db->map(
  *   'object',
  *   "select id from foobar where id > 0",
  *   function($result) {
  *     echo $result->id."\n";
  *   });
  *
  * //prepared
  * $db->map(
  *   'assoc',
  *   "select id from foobar where id = ? or id = ?",
  *   ['ii',1,2],
  *   function($result) {
  *     echo $result['id']."\n";
  * });
  *
  * //prepared with scope
  *
  * $db->map(
  *   'object',
  *   "select * from foobar where id > ?",
  *   ['ii',0],
  *   function($result, $scope) {
  *     echo $scope->foo.$result[0]."\n";
  *   },
  *   $this);
  *
  * ?>
  * </code>
  */

  /**
  * Prepares a sql query optionally as a prepared statement if the prepArgs
  * array is specified
  * @name query
  * @param str $sql SQL to execute
  * @param str $prepArgs Arguments for prepared statement queries
  * @since 0.1.0
  * @return object query results
  * <code>
  * <?php
  * $query = $db->query("select * from foo")
  *
  * //prepared (safe from injection)
  * $query = $db->query("select * from foo where foo_id = ?", ['i', 1]);
  *
  * ?>
  * </code>
  */
  public function map()
  {

    $args = func_get_args();
    if (gettype($args[2]) == 'array') {
      $result = $this->query($args[1], $args[2]);
      list($method,$scope) = [3,4];
    } else if (gettype($args[2]) == 'object') {
      $result = $this->query($args[1]);
      list($method,$scope) = [2,3];
    }

    if ($args[0] != 'value') {
      $resultMethod = new \ReflectionMethod('mysqli_result', "fetch_".$args[0]);
      while ($row = $resultMethod->invoke($result)) {
        $args[$method]($row, $args[$scope]);
      }
    } else {
      $resultMethod = new \ReflectionMethod('mysqli_result', "fetch_row");
      while ($row = $resultMethod->invoke($result)) {
        $args[$method]($row[0], $args[$scope]);
      }
    }
  }


  /**
  * For whatever reason i don't remember atm, args have to be references for
  * reflection to play nice
  * @name _mkrefs
  * @param str $arr array of arguments
  * @since 0.1.0
  * @return array array of references to arguments
  */
  protected function _mkrefs($arr) {
    $refs = array();
    foreach($arr as $key => $value)
      $refs[$key] = &$arr[$key];
    return $refs;
  }


  /**
  * Prepares a sql query optionally as a prepared statement if the prepArgs
  * array is specified
  * @name query
  * @param str $sql SQL to execute
  * @param str $prepArgs Arguments for prepared statement queries
  * @since 0.1.0
  * @return object query results
  * <code>
  * <?php
  * $query = $db->query("select * from foo")
  *
  * //prepared (safe from injection)
  * $query = $db->query("select * from foo where foo_id = ?", ['i', 1]);
  *
  * ?>
  * </code>
  */
  public function query($sql, $prepArgs = false)
  {

    if (!$this->conn->ping()) {
      $this->conn->close();
      $this->connect();
    }

    try {
      if (is_array($prepArgs)) {
        $stmt = $this->conn->prepare($sql);
        if (false === $stmt) {
          $this->error("Couldn't prepare statement: ".$this->conn->error);
        } else {
          $method = new \ReflectionMethod('mysqli_stmt', 'bind_param');
          $method->invokeArgs($stmt, $this->_mkrefs($prepArgs)); /* much love to jan kriedner */
          $stmt->execute();

          if ($stmt->insert_id > 0) {
            $result = $stmt->insert_id;
          } else {
            $result = $stmt->get_result();
          }
        }
      } else {
        $result = $this->conn->query($sql);
      }
    } catch (Exception $e) {
      $this->error($e->getMessage()." SQL: $sql");
    }
    return $result;
  }

  /**
  * Connects to a maria database using the mysqli transport
  * @name connect
  * @param int $
  * @since 0.1.0
  * @return mysqli mysqli transport
  */
  public function connect()
  {
    $this->conn = new \mysqli($this->host, $this->user, $this->pass, $this->name);
    if (mysqli_connect_errno()) {
      $this->error("Connect failed: {$this->conn->error}<br/>\n", mysqli_connect_error());
    }
  }

  /**
  * Alias to mysqli->real_escape_string
  * @name esc
  * @param str $str string to escape
  * @since 0.1.0
  * @return str escaped string
  */
  public function esc($str)
  {
    return $this->conn->real_escape_string($str);
  }

  /**
  * Loads structure of database into $_tables. from opcache if availible.
  * @name loadStructure
  * @param int $
  * @since 0.1.0
  * @return stdC the MariaInterface object
  */
  private function loadStructure()
  {

    $startTime = microtime(true);
    $cached = $this->cache->dbstruct;
    if (empty($cached)) {
      $dbtables = $this->getlist("show tables");
      if (count($dbtables) > 0)
        foreach ($dbtables as $table) {
          $tables[$table]['name'] = $table;
          $columns = $this->getobjects("show columns from $table");
          foreach ($columns as $column) {
            $tables[$table]['columns'][] = $column->Field;
            /* mysql properties of the column Field,Type,Null,Key,Default,Extra */
            if ($column->Key == "PRI") {
              $tables[$table]['pk'] = $column->Field;
              $primary_keys["{$column->Field}"] = $table;
            }
          }
        }

      /* find relationships */
      if (count($tables) > 0)
        foreach ($tables as $tablename => $table) {
          foreach ($table['columns'] as $column) {
            if ($column != $table['pk'] && array_key_exists($column, $primary_keys)) {
              $joined_table = $primary_keys[$column];
              $tables[$tablename]['linkedto'][] = $joined_table;
            }

            if ($column == $table['pk']) {
              foreach ($tables as $linktblkey => $linktbl) {
                if ($linktbl != $table) {
                  foreach ($linktbl['columns'] as $lnkcol) {
                    if ($lnkcol == $column) {
                      $tables[$tablename]['linkedfrom'][] = $linktblkey;
                    }
                  }
                }
              }
            }
          }
        }

      $this->_tables = $tables;
      //$_SESSION['dbstruct'] = json_encode($this->tables);
      if ($this->cache) {
        $this->cache->dbstruct = json_encode($this->tables); //TODO: memcache
      }
    } else  {
      $this->_tables = json_decode($cached);
    }
    $endTime = microtime(true);
  }

  /**
  * Check to see if two records are related. Looks for a relation between the
  * descendant and ancestor records based on their repective primary ids and
  * the 'join paths' between the tables
  * @name isDescendant
  * @param str $descendant_table
  * @param int $descendant_id
  * @param str $ancestor_table
  * @param int $ancestor_id
  * @since 0.1.0
  * @return bool true if the decendant is related to the ancestor
  */
  public function isDescendant($descendant_table, $descendant_id, $ancestor_table, $ancestor_id)
  {
    $joins = $this->findJoins($descendant_table, $ancestor_table);
    $sql = "select count(*) from $descendant_table "; //todo: count(*) is slow

    if (is_array($joins) && count($joins) > 0) {

      if (!$lastjoin) {
        $lastjoin = $descendant_table;
      }

      for ($x = 1; $x < count($joins); $x++) {
        $jointable = $joins[$x];
        $sql .= "inner join $jointable on $jointable.".$this->tables->{$jointable}->pk." =  $lastjoin.".$this->tables->{$jointable}->pk." ";
        $lastjoin = $jointable;
      }

      $sql .= " where $descendant_table.".$this->tables->{$descendant_table}->pk." = $descendant_id and $ancestor_table.".$this->tables->{$ancestor_table}->pk." = $ancestor_id";

      if ($this->value($sql) > 0) {
        return true;
      } else {
        return false;
      }
    } else  {
      return false;
    }
  }

  /**
  * Try and find how two tables are related. Find the salemen at least one path,
  * could get weird with certain database structures. Requires that primary key
  * field names are unique and used for joins in related tables.
  * @name findJoins
  * @param str $start Table to find a path from
  * @param str $end Table to find a path to
  * @param str $joins Progress thus far
  * @since 0.1.0
  * @return object the MariaInterface object
  */
  public function findJoins($start, $end, $joins = false)
  {
    if (count($joins) > 100) { /* catch runaways */
      $this->error("runaway search aborted"); //todo: if this error is seen figure out why
    }

    if (isset($this->tables->{$start}) && isset($this->tables->{$end})) {
      if (count($this->tables->{$start}->linkedto) > 0 && in_array($end, $this->tables->{$start}->linkedto)) {
        $endpointindex = array_search($end, $this->tables->{$start}->linkedto);
        $endpoint = $this->tables->{$start}->linkedto[$endpointindex];
        $joins[] = $endpoint;
        return $joins;
      } else {
        if ($joins[(count($joins) - 1)] != $start) //todo: why is this here
          $joins[] = $start;

        if (count($this->tables->{$start}->linkedto) > 0) {
          foreach ($this->tables->{$start}->linkedto as $subtable) {
            if (!in_array($subtable, $joins)) {// avoid getting caught in a loop

              $check = $this->findJoins($subtable, $end, array_merge($joins, array($subtable)));
              if ($check) {
                return $check;
              }
            }
          }
        }
        return false;
      }
    } else {
      $this->error("invalid tables $start $end");
    }
  }

  /**
  * Close the database connection
  * @name close
  * @since 0.1.0
  * @return void
  */
  public function close()
  {
    $this->conn->close();
  }

  /**
  * Note something in the tracelog
  * @name trace
  * @param str $msg What to note
  * @since 0.1.0
  * @return void
  */
  private function trace($msg)
  {
    $this->writetolog("[".date("Y-m-d H:i:s")."] ".$msg);
  }

  /**
  * Warning Handler
  * @name warning
  * @param str $msg Warning message
  * @since 0.1.0
  * @return object the MariaInterface object
  */
  private function warning($msg)
  {
    trigger_error($msg, E_USER_WARNING);
  }

  /**
  * Error Handler
  * @name error
  * @param int $
  * @since 0.1.0
  * @return void
  */
  private function error($msg)
  {
    trigger_error($msg, E_USER_ERROR);
  }



  /**
  * write to log
  * @name writetolog
  * @param str $entry write to a log file, deprecated
  * @since 0.1.0
  * @return void
  */
  private function writetolog($entry)
  {
    if ($this->traceLog) {
      try {
        file_put_contents($this->traceLog, "$datestamp $entry\n", FILE_APPEND);
      } catch (Exception $e) {
        $this->error("tracelog file specified but not writeable");
      }
    }
  }
}
