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

    function sql_die( $message ) {
        print "<font color=red><b>FATAL: </b></font>";
        DEBUG( D_SQL_ERROR, $message );
        print mysql_error();
        exit();
    }

    function sql_connect( $database=SQL_DB, $username=SQL_USER, $password=SQL_PASS ) {
        DEBUG( D_FUNCTION, "sql_connect('$database', '$username', '\$password')" );

        @mysql_connect( SQL_HOST, $username, $password )
            or sql_die( "mysql_connect(): Couldn't connect to the database" );
        @mysql_select_db( $database )
            or sql_die( "mysql_select_db(): Couldn't select the database" );
    }

    function sql_select_db( $database = SQL_DB ) {
        DEBUG( D_FUNCTION, "sql_select_db('$database')" );

        return @mysql_select_db( $database )
            or sql_die( "mysql_select_db(): Couldn't select the database" );
    }

    function sql_data_seek() {

        DEBUG( D_FUNCTION, "sql_data_seek(...)" );

        if ( func_num_args() == 1 ) {
            $result = $GLOBALS['sql_last_result'];
            $row_number = func_get_arg( 0 );
        } else {
            $result = func_get_arg( 0 );
            $row_number = func_get_arg( 1 );
        }

        return mysql_data_seek( $result, $row_number );
    }

    function sql_query( $query ) {

        DEBUG( D_FUNCTION, "sql_query('$query')" );

        ($GLOBALS['sql_last_result'] = @mysql_query( $query ))
            or sql_die( "mysql_query(): Unable to execute query: $query" );

        return  $GLOBALS['sql_last_result'];
    }

    function sql_num_rows() {

        DEBUG( D_FUNCTION, "sql_num_rows(...)" );

        if ( ! func_num_args() )
            $result = $GLOBALS['sql_last_result'];
        else
            $result = func_get_arg( 0 );

        return mysql_num_rows( $result );
    }

    function sql_affected_rows() {

        DEBUG( D_FUNCTION, "sql_affected_rows()" );

        return mysql_affected_rows();
    }

    function sql_insert_id() {

        DEBUG( D_FUNCTION, "sql_insert_id()" );

        return mysql_insert_id();
    }

    function sql_fetch_array() {

        DEBUG( D_FUNCTION, "sql_fetch_array(...)" );

        if ( ! func_num_args() )
            $result = $GLOBALS['sql_last_result'];
        else
            $result = func_get_arg( 0 );

        return mysql_fetch_array( $result );
    }

    function sql_fetch_variable() {

        DEBUG( D_FUNCTION, "sql_fetch_variable(...)" );

	if ( ! func_num_args() )
	    $result = $GLOBALS['sql_last_result'];
        else
            $result = func_get_arg( 0 );

        $arr = sql_fetch_array( $result );
        if ( $arr == false )
            return false;

        return $arr[0];
    }

    function sql_fetch_row() {

        DEBUG( D_FUNCTION, "sql_fetch_row(...)" );

        if ( ! func_num_args() )
            $result = $GLOBALS['sql_last_result'];
        else
            $result = func_get_arg( 0 );

        return mysql_fetch_row( $result );
    }

     // Can be used for caching. Usage: sql_export( $query, $filename );
    function sql_export( $query, $filename ) {

        $S_NEWLINE = "\n";   // New line
        $S_COMMENT = "#";    // Comment sign
        $S_DELIMITER = "\t"; // Fields delimiter


        DEBUG( D_FUNCTION, "sql_export('$query','$filename')" );
     
        if ( ( $fh = fopen( $filename, "w" ) ) === FALSE ) sql_die( "sql_export(): Permission denied" );
 
        $result = sql_query( $query );
        $f_count = mysql_num_fields( $result );
        fwrite( $fh, $S_COMMENT." " ); // Comment sign
        for ( $i = 0; $i < $f_count; $i++ ) fwrite( $fh, mysql_field_name( $result, $i ).$S_DELIMITER ); 
        fwrite( $fh, $S_NEWLINE );
 
        while ( $row = sql_fetch_array( $result ) ) {
            for ( $i = 0; $i < $f_count; $i++ ) {
                fwrite( $fh, str_replace(
                    array( "\\",   $S_COMMENT,      $S_NEWLINE, $S_DELIMITER ),
                    array( "\\\\", "\\".$S_COMMENT, "\\n",      "\\t" ),
                    $row[$i] )
                    .$S_DELIMITER );
            }
            fwrite( $fh, $S_NEWLINE );
        }
 
        fclose( $fh );
        return mysql_fetch_array( $result );
    }

    function sql_free_result() {

        DEBUG( D_FUNCTION, "sql_free_result(...)" );

        if ( ! func_num_args() )
            $result = $GLOBALS['sql_last_result'];
        else
            $result = func_get_arg( 0 );

        return @mysql_free_result( $result )
            or sql_die( "mysql_free_result(): Couldn't free result" );
    }

    function sql_close() {

        DEBUG( D_FUNCTION, "sql_close()" );

        mysql_close();
    }

    sql_connect();

?>
