<?php
/*
 * $RCSfile: sessions.inc.php,v $ $Revision: 1.3 $
 * $Author: slim_lv $ $Date: 2007/05/13 17:29:27 $
 * This file is part of CYRUP project
 * by Yuri Pimenov (up@msh.lv) & Deniss Gaplevsky (slim@msh.lv)
 */

    if ( !defined("INCLUDE_DIR") ) exit("Not for direct run");

    DEBUG( D_INCLUDE, "sessions.inc.php" );

    function sess_open( $save_path, $session_name ) {
        DEBUG( D_FUNCTION, "sess_open('$save_path', '$session_name')" );
        sess_gc(0);
        return true;
    }

    function sess_close() {
        DEBUG( D_FUNCTION, "sess_close()" );
        return true;
    }

    function sess_read( $session_id ) {
        DEBUG( D_FUNCTION, "sess_read('$session_id')" );

        $query = "SELECT value FROM cyrup_sessions WHERE sesskey=".sql_escape($session_id);
	sql_query( $query );
        if ( $val = sql_fetch_variable() ) { return $val; }
        return "";
    }

    function sess_write( $session_id, $val ) {
        DEBUG( D_FUNCTION, "sess_write('$session_id', '$val')" );

        $expiry = time() + SESS_LIFE;
        $query = "DELETE FROM cyrup_sessions WHERE sesskey=".sql_escape($session_id);
        sql_query( $query );
        $query = "INSERT INTO cyrup_sessions (sesskey,expiry,value) VALUES (".sql_escape($session_id).",".sql_escape($expiry).",".sql_escape($val).")";
        $ecode = sql_query( $query );
        return true;
    }

    function sess_destroy( $session_id ) {
        DEBUG( D_FUNCTION, "sess_destroy('$session_id')" );
        $query = "DELETE FROM cyrup_sessions WHERE sesskey=".sql_escape($session_id);
        sql_query( $query );
	return true;
    }

    function sess_gc( $maxlifetime ) {
        DEBUG( D_FUNCTION, "sess_gc($maxlifetime)" );

        $query = "DELETE FROM cyrup_sessions WHERE expiry<'".time()."'";
        sql_query( $query );

        return true;
    }

    session_set_save_handler( "sess_open", "sess_close", "sess_read", "sess_write", "sess_destroy", "sess_gc" );
    session_start();
