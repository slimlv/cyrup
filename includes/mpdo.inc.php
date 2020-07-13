<?php

    defined("INCLUDE_DIR") || exit("Not for direct run");
    DEBUG( D_INCLUDE, "sql.inc.php" );

    $GLOBALS['sql_last_result'] = null;
    $GLOBALS['dbconn'] = null;

    function sql_die( $message ) {
        global $dbconn;
        DEBUG( D_SQL_ERROR, $message );
        print "<font color=red><b>FATAL: </b></font><pre>";
        print_r($dbconn->errorInfo());
        die();
    }

    function sql_escape($str) {
       global $dbconn;
       return $dbconn->quote($str);
    }

    function sql_connect( $database=SQL_DB, $username=SQL_USER, $password=SQL_PASS ) {
        global $dbconn;
        DEBUG( D_FUNCTION, "sql_connect('$database', '$username', '\$password')" );

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ];

        try {
          $dbconn = new PDO( "mysql:host=".SQL_HOST.";dbname=$database", $username, $password, $options);
        } catch (\PDOException $e) {
          sql_die($e->getMessage());
        }
    }

    function sql_query( $query ) {
        global $dbconn;
        global $sql_last_result;

        DEBUG( D_FUNCTION, "sql_query('$query')" );

        try {
          $sql_last_result = $dbconn->query($query);
        } catch (\PDOException $e) {
          sql_die($e->getMessage());
        }
        return  $sql_last_result;
    }

    function sql_num_rows() {
        global $dbconn;
        global $sql_last_result;
        DEBUG( D_FUNCTION, "sql_num_rows(...)" );

        $stmt = func_num_args() ? func_get_arg(0) : $sql_last_result;

        return count($stmt->fetchAll());
    }

    function sql_affected_rows() {
        global $dbconn;
        global $sql_last_result;
        DEBUG( D_FUNCTION, "sql_affected_rows()" );

        return $sql_last_result->rowCount();
    }

    function sql_insert_id() {
        global $dbconn;
        global $sql_last_result;
        DEBUG( D_FUNCTION, "sql_insert_id()" );

        return $dbconn->lastInsertId();
    }

    function sql_fetch_array() {
        global $dbconn;
        global $sql_last_result;
        DEBUG( D_FUNCTION, "sql_fetch_array(...)" );
        $stmt = func_num_args() ? func_get_arg(0) : $sql_last_result;

        return $stmt->fetch();
    }

    function sql_fetch_variable() {
        global $dbconn;
        global $sql_last_result;
        DEBUG( D_FUNCTION, "sql_fetch_variable(...)" );

        $stmt = func_num_args() ? func_get_arg(0) : $sql_last_result;

        return $stmt->fetchColumn();
    }

    function sql_fetch_row() {
        global $dbconn;
        global $sql_last_result;
        DEBUG( D_FUNCTION, "sql_fetch_row(...)" );

        $stmt = func_num_args() ? func_get_arg(0) : $sql_last_result;

        return $stmt->fetch(PDO::FETCH_NUM);
    }

    function sql_fetch_all() {
        global $dbconn;
        global $sql_last_result;
        DEBUG( D_FUNCTION, "sql_fetch_all(...)" );

        $stmt = func_num_args() ? func_get_arg(0) : $sql_last_result;

        return $stmt->fetchAll();
    }

    function sql_field_names() {
        DEBUG( D_FUNCTION, "sql_field_names()" );
        $result = func_num_args() ? func_get_arg(0) : $GLOBALS['sql_last_result'];
        $out = [];

        $count = $result->columnCount();
        for ($i = 0; $i < $count; $i++) $out[$i] = $result->getColumnMeta($i)["name"];
        return $out;
    }

    function sql_close() {
        global $dbconn;
        DEBUG( D_FUNCTION, "sql_close()" );
        $dbconn = null;
    }

    sql_connect();

