<?php

namespace WillGriffin\MariaInterface;

class Connection
{
  public $conn;     /* the database connection */
  public $name;     /* database name */
  public $host;     /* server address */
  public $user;     /* user */
  public $pass;     /* password */
  public $port;     /* port */

  public $cache;    /* optional pointer to opcache */
  public $traceLog; /* optional tracelog filename */

  private $struct;  /* database structure */

  function __construct($data = null)
  {
    if ($data['conn'])
    {
      $this->conn = $data['conn'];
    } else {
      if (empty($data['host']))
      {
        $this->host = "localhost";
      } else {
        $this->host = $data['host'];
      }

      if (empty($data['name']))
      {
        $this->name = "";
      } else {
        $this->name = $data['name'];
      }

      if (empty($data['user']))
      {
        $this->user = "";
      } else {
        $this->user = $data['user'];
      }

      if (empty($data['pass']))
      {
        $this->pass = "";
      } else {
        $this->pass = $data['pass'];
      }

      if (empty($data['port']))
      {
        $this->port = "";
      } else {
        $this->port = $data['port'];
      }

      $this->connect();
    }

  }

  /**
  *
  * Executes a sql insert statment and returns created records ID
  *
  *
  * @param string sql sql to execute
  * @param array $prepArgs arguments to send to bind_param for prepared queries
  *
  * <code>
  * <?php
  *
  *
  * ?>
  * </code>
  *
  */
  public function insert($sql, $prepArgs = false)
  {
    if ($que = $this->query($sql, $prepArgs))
    {
      $result = $this->conn->insert_id;
      return $result;
    }
  }

  /**
  *
  * Executes a sql update statement and returns true if successfull
  *
  *
  * @param string sql sql to execute
  * @param array $prepArgs arguments to send to bind_param for prepared queries
  *
  * <code>
  * <?php
  *
  *
  * ?>
  * </code>
  *
  */
  public function update($sql, $prepArgs = false)
  {
    if ($que = $this->query($sql, $prepArgs))
    {
      return true;
    }
  }


  /**
  *
  * Alias to update for now
  *
  *
  * @param string sql sql to execute
  * @param array $prepArgs arguments to send to bind_param for prepared queries
  *
  * <code>
  * <?php
  *
  *
  * ?>
  * </code>
  *
  */

  public function delete($sql, $prepArgs = false)
  {
    return $this->update($sql, $prepArgs);
  }


  /**
  *
  * Single value query retrieval. Returns first column of first row
  *
  *
  * @param string sql sql to execute
  * @param array $prepArgs arguments to send to bind_param for prepared queries
  *
  * <code>
  * <?php
  *
  * $fooID = $db->value('select bar_id from bars where bar_id = 1');
  *
  * ?>
  * </code>
   */
  public function value($sql, $prepArgs = false)
  {
    if ($que = $this->query($sql, $prepArgs))
    {
      $row = $que->fetch_row();
      return $row[0];
    } else {
      $this->error("ERR dbObj::value [{$this->name}] :  {$this->conn->error}<br/>\n$sql", $this->conn->error);
    }
  }

  /**
  *
  * Retrieve an single dimensional array of values from a single column of a result set, default is the first column
  *
  *
  * @param string sql sql to execute
  * @param array $prepArgs arguments to send to bind_param for prepared queries
  *
  * <code>
  * <?php
  *
  * $fooIDs = $db->column('select foo_id from foos');
  *
  * ?>
  * </code>
   */
  public function column($sql, $index = 0, $prepArgs = false)
  {
    if ($que = $this->query($sql, $prepArgs))
    {
      while ($row = $que->fetch_row())
      {
        $result[] = $row[$index];
      }
      return $result;
    }
  }

  /**
  *
  * executes a query and runs a lambda on each results first value
  *
  *
  * @param string $sql sql to execute
  * @param array $prepArgs optional arguments to send to bind_param for prepared queries
  * @param object $method function to run on each result
  * @param object $scope optional scope
  *
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

  public function foreachValue()
  {
    $args = func_get_args();
    return $this->each('value', $args[0], $args[1], $args[2], $args[3]);
  }


  /**
  *
  * returns result of query as json
  *
  *
  * @param mixed key sql to execute
  * @param array $prepArgs arguments to send to bind_param for prepared queries
  *
  * <code>
  * <?php
  *
  * $jsonTxt = $db->json("select * from foos");
  *
  * ?>
  * </code>
  */
  public function json($sql, $prepArgs = false)
  {
    return json_encode($this->assocs($sql, $prepArgs));
  }

  /**
  *
  * returns result of query as an array of associative arrays
  *
  *
  * @param string $sql sql to execute
  * @param array $prepArgs arguments to send to bind_param for prepared queries
  *
  * <code>
  * <?php
  *
  * $fooArr = $db->assoc("select * from foos");
  *
  * //prepared
  * $foo = $db->assoc("select * from foos where foo_id > ?", ['i',0]);
  *
  * ?>
  * </code>
  */
  public function assocs($sql, $prepArgs = false)
  {
    if ($que = $this->query($sql, $prepArgs))
    {
      if ($que->num_rows > 0)
      {
        while($row = $que->fetch_assoc())
        {
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
  *
  * returns first result of query as associative array
  *
  *
  * @param string $sql sql to execute
  * @param array $prepArgs arguments to send to bind_param for prepared queries
  *
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
    if (count($results) > 0)
    {
      return $results[0];
    } else {
      return $results;
    }
  }

  /**
  *
  * executes a query and runs a lambda on each resulting row passed as an associative array
  *
  *
  * @param string $sql sql to execute
  * @param array $prepArgs optional arguments to send to bind_param for prepared queries
  * @param object $method function to run on each result
  * @param object $scope optional scope
  *
  * <code>
  * <?php
  *  $obj = ["foo" => "ID: "];
  *  $db->foreachAssoc(
  *    "select id from foobar where id > ?", ['i',1],
  *    function($result, $scope) {
  *      echo $scope['foo'].$result['id']."\n";
  *    },
  *    $obj);
  * ?>
  * </code>
  */

  public function foreachAssoc()
  {
    $args = func_get_args();
    return $this->each('assoc', $args[0], $args[1], $args[2], $args[3]);
  }


  /**
  *
  * returns first result of query as an object
  *
  *
  * @param string $sql sql to execute
  * @param array $prepArgs arguments to send to bind_param for prepared queries
  *
  * <code>
  * <?php
  *
  * $foo = $db->object("select * from foos where foo_id = 1");
  *
  * //prepared
  * $foo = $db->object("select * from foos where foo_id = ?", ['i',1]);
  *
  *
  * ?>
  * </code>
  */

  public function object($sql, $prepArgs = false)
  {
    if ($que = $this->query($sql, $prepArgs))
    {
      $result = $que->fetch_object();
      return $result;
    }
  }



  /**
  *
  * returns result of query as array of objects
  *
  *
  * @param string $sql sql to execute
  * @param array $prepArgs arguments to send to bind_param for prepared queries
  *
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
  public function objects($sql, $prepArgs = false)
  {
    if ($que = $this->query($sql, $prepArgs))
    {
      while ($result = $que->fetch_object())
      {
        $results[] = $result;
      }
      return $results;
    }
  }

  /**
  *
  * executes a query and runs a lambda on each resulting row passed as an object
  *
  *
  * @param string $sql sql to execute
  * @param array $prepArgs optional arguments to send to bind_param for prepared queries
  * @param object $method function to run on each result
  * @param object $scope optional scope
  *
  * <code>
  * <?php
  *  $obj = (object)["foo" => "ID: "];
  *  $db->foreachObject(
  *    "select id from foobar where id > ?", ['i',1],
  *    function($result, $scope) {
  *      echo $scope->foo.$result->id."\n";
  *    },
  *    $obj);
  * ?>
  * </code>
  */

  public function foreachObject()
  {
    $args = func_get_args();
    return $this->each('object', $args[0], $args[1], $args[2], $args[3]);
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
  * $db->each('select * from foo', function($result) {
  *   $row = $result->fetch_row();
  *   var_dump($row);
  * });
  *
  * //basic
  * $db->each(
  *   'object',
  *   "select id from foobar where id > 0",
  *   function($result) {
  *     echo $result->id."\n";
  *   });
  *
  * //prepared
  * $db->each(
  *   'assoc',
  *   "select id from foobar where id = ? or id = ?",
  *   ['ii',1,2],
  *   function($result) {
  *     echo $result['id']."\n";
  * });
  *
  * //prepared with scope
  *
  * $db->each(
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
  public function each()
  {

    $args = func_get_args();
    if (gettype($args[2]) == 'array') {
      $result = $this->query($args[1], $this->mkrefs($args[2]));
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

  //ugh
  //todo: find a better way
  public function mkrefs($arr) {
    $refs = array();
    foreach($arr as $key => $value)
      $refs[$key] = &$arr[$key];
    return $refs;
  }

  /**
  *
  * executes query returns result
  *
  *
  * @param string $sql sql to execute
  * @param array $prepArgs arguments to send to bind_param for prepared queries
  *
  * <code>
  * <?php
  *
  *
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
        if (!$stmt) {
          $this->error("Couldn't prepare statement");
        } else {
          $method = new \ReflectionMethod('mysqli_stmt', 'bind_param');
          $method->invokeArgs($stmt, $prepArgs); /* much love to jan kriedner */
          $stmt->execute();
          $result = $stmt->get_result();
        }

      } else {
        $result = $this->conn->query($sql);
      }
    } catch (Exception $e) {
        $this->error($e->getMessage());
    }
    return $result;
  }

  /**
  *
  * connect to database
  *
  *
  * <code>
  * <?php
  *
  *
  *
  * ?>
  * </code>
  */
  public function connect()
  {
    $this->conn = new \mysqli($this->host, $this->user, $this->pass, $this->name);
    if (mysqli_connect_errno()) {
      $this->error("Connect failed: {$this->conn->error}<br/>\n", mysqli_connect_error());
    }
  }


  /**
  *
  * cleans a string of baddies
  *
  *
  * <code>
  * <?php
  *
  *
  *
  * ?>
  * </code>
  */
  public function esc($str)
  {
    return $this->conn->real_escape_string($str);
  }

  public function cleanString($str)
  {
    return $this->esc($str);
  }

  /**
  * probably broken
  * loads structure of database. from opcache if initialized. (broken)
  *
  *
  * <code>
  * <?php
  *
  *
  *
  * ?>
  * </code>
  */
  function loadStructure()
  {

    $startTime = microtime(true);
    $cached = $this->cache->dbstruct;
    if (empty($cached))
    {
      $dbtables = $this->getlist("show tables");
      if (count($dbtables) > 0)
        foreach ($dbtables as $table)
        {
          $tables[$table]['name'] = $table;
          $columns = $this->getobjects("show columns from $table");
          foreach ($columns as $column)
          {
            $tables[$table]['columns'][] = $column->Field;
            /* mysql properties of the column Field,Type,Null,Key,Default,Extra */
            if ($column->Key == "PRI")
            {
              $tables[$table]['pk'] = $column->Field;
              $primary_keys["{$column->Field}"] = $table;
            }
          }
        }

      /* find relationships */
      if (count($tables) > 0)
        foreach ($tables as $tablename => $table)
        {
          foreach ($table['columns'] as $column)
          {
            if ($column != $table['pk'] && array_key_exists($column, $primary_keys))
            {
              $joined_table = $primary_keys[$column];
              $tables[$tablename]['linkedto'][] = $joined_table;
            }

            if ($column == $table['pk'])
            {
              foreach ($tables as $linktblkey => $linktbl)
              {
                if ($linktbl != $table)
                  foreach ($linktbl['columns'] as $lnkcol)
                  {
                    if ($lnkcol == $column)
                    {
                      $tables[$tablename]['linkedfrom'][] = $linktblkey;
                    }
                  }
              }
            }
          }
        }

      $this->tables = $tables;
      //$_SESSION['dbstruct'] = json_encode($this->tables);

      $this->cache->dbstruct = json_encode($this->tables);
    } else  {
      $this->tables = json_decode($cached);
    }
    $endTime = microtime(true);

    //echo "\nLoad took ".($endTime - $startTime)." seconds";

  }


  /**
  * probably broken
  * check to see that two records are related
  *
  * @param string $descendant_table descendant table
  * @param string $descendant_id descendant id
  * @param string $ancestor_table ancestor table
  * @param string $ancestor_id ancestor id
  *
  * <code>
  * <?php
  *
  *
  *
  * ?>
  * </code>
  */
  public function isDescendant($descendant_table, $descendant_id, $ancestor_table, $ancestor_id)
  {
    $joins = $this->findJoins($descendant_table, $ancestor_table);
    $sql = "select count(*) from $descendant_table ";

    if (is_array($joins) && count($joins) > 0)
    {

      if (!$lastjoin)
        $lastjoin = $descendant_table;

      for ($x = 1; $x < count($joins); $x++)
      {
        $jointable = $joins[$x];
        $sql .= "inner join $jointable on $jointable.".$this->tables->{$jointable}->pk." =  $lastjoin.".$this->tables->{$jointable}->pk." ";
        $lastjoin = $jointable;
      }

      $sql .= " where $descendant_table.".$this->tables->{$descendant_table}->pk." = $descendant_id and $ancestor_table.".$this->tables->{$ancestor_table}->pk." = $ancestor_id";
      if ($this->value($sql) > 0)
      {
        return true;
      } else {
        return false;
      }

    } else  {
      return false;
    }
    return $sql;
  }

  /**
  * probably broken
  * try and find how two tables are related. find the salemen at least one path, could get weird
  *
  *
  * @param string $descendant_table descendant table
  * @param string $descendant_id descendant id
  *
  * <code>
  * <?php
  *
  *
  *
  * ?>
  * </code>
  */
  public function findJoins($start, $end, $joins = false)
  {
    if (count($joins) > 100) /* catch runaways */
    {
      $this->error("runaway search aborted"); //todo: if this error is seen figure out why
    }

    if (isset($this->tables->{$start}) && isset($this->tables->{$end}))
    {
      if (count($this->tables->{$start}->linkedto) > 0 && in_array($end, $this->tables->{$start}->linkedto))
      {
        $endpointindex = array_search($end, $this->tables->{$start}->linkedto);
        $endpoint = $this->tables->{$start}->linkedto[$endpointindex];
        $joins[] = $endpoint;
        return $joins;
      } else {
        if ($joins[(count($joins) - 1)] != $start) /* why is this here */
          $joins[] = $start;

        if (count($this->tables->{$start}->linkedto) > 0)
        {
          foreach ($this->tables->{$start}->linkedto as $subtable)
          {
            if (!in_array($subtable, $joins)) // avoid getting caught in a loop
            {
              $check = $this->findJoins($subtable, $end, array_merge($joins, array($subtable)));
              if ($check)
              {
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

  private function trace($msg)
  {
    $this->writetolog("[".date("Y-m-d H:i:s")."] ".$msg);
  }

  private function warning($msg)
  {
    trigger_error($msg, E_USER_WARNING);
  }

  private function error($msg)
  {
    trigger_error($msg, E_USER_ERROR);
  }

  private function writetolog($entry)
  {
    if ($this->traceLog)
    {
      try {
        file_put_contents($this->traceLog, "$datestamp $entry\n", FILE_APPEND);
      } catch (Exception $e) {
        $this->error("tracelog file specified by not writeable");
      }
    }
  }
}

