<?php
/*
 * $RCSfile: mysql.inc.php,v $ $Revision: 1.5 $
 * $Author: slim_lv $ $Date: 2016/11/01 14:09:36 $
 * This file is part of CYRUP project
 * by Yuri Pimenov (up@msh.lv) & Deniss Gaplevsky (slim@msh.lv)
 */

    if ( !defined("INCLUDE_DIR") ) exit("Not for direct run");
    

    DEBUG( D_INCLUDE, "sql.inc.php" );

    $GLOBALS['sql_last_result'] = null;
    $GLOBALS['dbconn'] = null;

    function sql_die( $message ) {
        global $dbconn;
        DEBUG( D_SQL_ERROR, $message );
        print "<font color=red><b>FATAL: </b></font><pre>";
        print_r(PDO::errorInfo);
        exit();
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

        return $stmt->fetch();
    }

     // Can be used for caching. Usage: sql_export( $query, $filename );
    function sql_export( $query, $filename ) {
        global $dbconn;
        global $sql_last_result;
        DEBUG( D_FUNCTION, "sql_export('$query','$filename')" );

        $S_NEWLINE = "\n";   // New line
        $S_COMMENT = "#";    // Comment sign
        $S_DELIMITER = "\t"; // Fields delimiter

        if ( ( $fh = fopen( $filename, "w" ) ) === FALSE ) sql_die( "sql_export(): Permission denied" );
 
        $result = sql_query( $query );
        $f_count = $result->columnCount();
        fwrite( $fh, $S_COMMENT." " ); // Comment sign
        for ( $i = 0; $i < $f_count; $i++ ) fwrite( $fh, $result->getColumnMeta( $i )["name"].$S_DELIMITER ); 
        fwrite( $fh, $S_NEWLINE );
 
        while ( $row = sql_fetch_array( $result ) ) {
            foreach ($row as $k => $v) {
                fwrite( $fh, str_replace(
                    [ "\\",   $S_COMMENT,      $S_NEWLINE, $S_DELIMITER ],
                    [ "\\\\", "\\".$S_COMMENT, "\\n",      "\\t" ],
                    $v ).$S_DELIMITER );
            }
            fwrite( $fh, $S_NEWLINE );
        }
 
        fclose( $fh );
        return $result;
    }

    function sql_close() {
        global $dbconn;
        DEBUG( D_FUNCTION, "sql_close()" );
        $dbconn = null;
    }

    sql_connect();

