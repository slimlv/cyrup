<?php
/*
 * $RCSfile: pgsql.inc.php,v $ $Revision: 1.3 $
 * $Author: slim_lv $ $Date: 2007/05/13 17:29:26 $
 * This file is part of CYRUP project
 * by Yuri Pimenov (up@msh.lv) & Deniss Gaplevsky (slim@msh.lv)
 * Thanks for postgresql code goes to Brett Van Sprewenburg <brett(at)ataxxia.com>
 */

    if ( !defined("INCLUDE_DIR") ) exit("Not for direct run");

    DEBUG( D_INCLUDE, "pgsql.inc.php" );

// definejam mainigo un pieskiram vertibu NULL
    $GLOBALS['sql_last_result'] = null;
    $GLOBALS['dbconn'] = "";

    function sql_die( $message ) {
        print "<font color=red><b>FATAL: </b></font>";
        DEBUG( D_SQL_ERROR, $message );
        print pg_last_error(); //mysql_error()
        exit();
    }

    function sql_connect( $database=SQL_DB, $username=SQL_USER, $password=SQL_PASS ) {
        DEBUG( D_FUNCTION, "sql_connect('$database', '$username', '\$password')" );

        @pg_connect("dbname=".$database." user=".$username." password=".$password."" ) or sql_die( "pg_connect(): Couldn't connect to the database" );

    }

    function sql_query( $query ) {

        DEBUG( D_FUNCTION, "sql_query('$query')" );

	($GLOBALS['sql_last_result'] = @pg_query($query))
            or sql_die( "sql_query(): Unable to execute query: ".$query );

        return  $GLOBALS['sql_last_result'];
    }

    function sql_num_rows() {

        DEBUG( D_FUNCTION, "sql_num_rows(...)" );

        if ( ! func_num_args() )
            $result = $GLOBALS['sql_last_result'];
        else
            $result = func_get_arg( 0 );

        return pg_num_rows( $result );
    }

    function sql_affected_rows() {

        DEBUG( D_FUNCTION, "sql_affected_rows()" );

        return pg_affected_rows();
    }

    function sql_insert_id( $tablename , $fieldname ) {

        DEBUG( D_FUNCTION, "sql_insert_id()" );

	$seq_name = "${tablename}_${fieldname}_seq";
	$cval = pg_fetch_row( pg_query( "SELECT currval('".$seq_name."')" ) );

        return $cval[0];
    }

    function sql_fetch_array() {

        DEBUG( D_FUNCTION, "sql_fetch_array(...)" );

        if ( ! func_num_args() )
            $result = $GLOBALS['sql_last_result'];
        else
            $result = func_get_arg( 0 );

        return pg_fetch_array($result);
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

        return pg_fetch_row( $result ); 
    }

     // Can be used for caching. Usage: sql_export( $query, $filename );
    function sql_export( $query, $filename ) {

        $S_NEWLINE = "\n";   // New line
        $S_COMMENT = "#";    // Comment sign
        $S_DELIMITER = "\t"; // Fields delimiter


         DEBUG( D_FUNCTION, "sql_export('$query','$filename')" );

         if ( ( $fh = fopen( $filename, "w" ) ) == FALSE )
             sql_die( "sql_export(): Permission denied" );

         $result = sql_query( $query );
         $f_count = pg_field_num( $result );
         fwrite( $fh, $S_COMMENT." " ); // Comment sign
         for ( $i = 0; $i < $f_count; $i++ )
            fwrite( $fh, pg_field_name($result, $i). $S_DELIMITER);
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
         return pg_fetch_array($result);
    }

    function sql_free_result() {

        DEBUG( D_FUNCTION, "sql_free_result(...)" );

        if ( ! func_num_args() )
            $result = $GLOBALS['sql_last_result'];
        else
            $result = func_get_arg( 0 );

        return @pg_free_result($result)
            or sql_die( "mysql_free_result(): Couldn't free result" );
    }

    function sql_close() {

        DEBUG( D_FUNCTION, "sql_close()" );

        pg_close();
    }

    sql_connect();

?>
