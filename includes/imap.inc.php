<?php
/*
 * $RCSfile: imap.inc.php,v $ $Revision: 1.14 $
 * $Author: slim_lv $ $Date: 2016/12/28 17:43:41 $ 
 * This file is part of CYRUP project
 * by Yuri Pimenov (up@msh.lv) & Deniss Gaplevsky (slim@msh.lv)
 */

    if ( !defined("INCLUDE_DIR") ) exit("Not for direct run");

    DEBUG( D_INCLUDE, "imap.inc.php" );

    $_imap_stream = 0;
    $_imap_command = "";
    $_imap_result = array();
    $_imap_answer = "";

    define( "ACL_ALL", "lrswipcda" );

    function cimap_die( $message ) {
        print "<font color=red><b>FATAL: </b></font>";
        DEBUG( D_IMAP_ERROR, $message );
        print cimap_last_error();
        exit();
    }

    function cimap_last_error() {
        global $_imap_command, $_imap_answer;

        #comment this out to see cleartext cyrus pass
        $_imap_command = str_replace(CYRUS_USER.' '.CYRUS_PASS,'CYRUS_USER CYRUS_PASS',$_imap_command); 

        return ">> $_imap_command<br>\n<< $_imap_answer<br>\n";
    }

    function cimap_open() {
        global $_imap_stream;
        DEBUG( D_FUNCTION, "cimap_open()" );

        $_imap_stream = fsockopen( CYRUS_HOST, CYRUS_PORT, $errno, $errstr );
        if ( ! $_imap_stream )
            cimap_die( "cimap_open(): fsockopen() failed ($errno: $errstr)" );
        
        if ( ! cimap_command( "login ".CYRUS_USER." ".CYRUS_PASS ) )
            cimap_die( "cimap_open(): login failed" );

        return true;
    }

    function cimap_command( $command ) {
        global $_imap_stream, $_imap_command, $_imap_result, $_imap_answer;
        DEBUG( D_FUNCTION, "cimap_command('$command')" );

        $_imap_result = array();

        $_imap_command = ". $command";
        fputs( $_imap_stream, $_imap_command."\n" );
        while ( ! feof( $_imap_stream ) ) {
            $line = fgets( $_imap_stream, 2048 );
            if ( ! $line )
                cimap_die( "cimap_command(): fgets() answer read error" );
            if ( $line{0} == "." ) {
                $rval = false;
                if ( substr( $line, 2, 2 ) == "OK" )
                    $rval = true;
                $_imap_answer = $line;
                return $rval;
            }
            else {
                array_push( $_imap_result, substr( $line, 2 ) );
            }
        }

        return true;
    }

    function cimap_createmailbox( $mbox_name ) {
        DEBUG( D_FUNCTION, "cimap_createmailbox('$mbox_name')" );

        if ( ! cimap_command( "create user".CYRUS_DELIMITER.$mbox_name ) )
            cimap_die( "cimap_createmailbox(): creating of mailbox failed" );

        return true;
    }

    function cimap_createfolders( $mbox_name ) {
        DEBUG( D_FUNCTION, "cimap_createfolders('$mbox_name')" );

        foreach ( IMAP_FOLDERS as $folder ) {
            $folder = trim($folder);
    	    $path = $mbox_name.CYRUS_DELIMITER.$folder;
            if ( MAILBOX_STYLE == "USER@DOMAIN.TLD" AND strpos($mbox_name, '@') ) {
                list( $muser, $mdomain ) = explode('@', $mbox_name);
                $path = $muser.CYRUS_DELIMITER.$folder.'@'.$mdomain;
            }
            cimap_command('create user'.CYRUS_DELIMITER.$path);
        }
    }

    function cimap_deletemailbox( $mbox_name ) {
        DEBUG( D_FUNCTION, "cimap_deletemailbox('$mbox_name')" );

        cimap_setacl( $mbox_name, CYRUS_USER, "c" );
        if ( ! cimap_command( "delete user".CYRUS_DELIMITER.$mbox_name ) )
            cimap_die( "cimap_deletemailbox(): deleting of mailbox failed" );

        return true;
    }

    function cimap_renamemailbox( $mbox_name, $new_mbox_name ) {
        DEBUG( D_FUNCTION, "cimap_renamemailbox('$mbox_name','$new_mbox_name')" );

        if (MAILBOX_STYLE == "USER@DOMAIN.TLD" )
            if ( cimap_command('rename "user'.CYRUS_DELIMITER.$mbox_name.'" "user'.CYRUS_DELIMITER.$new_mbox_name.'"' ) )
            	return true;

        $folders = array();
        $split_res = array();
     
        cimap_createmailbox( $new_mbox_name );
        cimap_setacl( $mbox_name, CYRUS_USER, ACL_ALL );

        cimap_copymailbox( $mbox_name, $new_mbox_name );

        $quota = cimap_getquota( $mbox_name );

        if ( is_array( $quota ) )
            cimap_setquota( $new_mbox_name, $quota["max"] );

        $folders = cimap_getfolders( $mbox_name );
     
        for ( $i = 0; $i < count( $folders ); $i++ ) {
            # hack for MAILBOX_STYLE == "USER@DOMAIN.TLD"
            if (MAILBOX_STYLE == "USER@DOMAIN.TLD" ) {
                $_mbox_name = preg_replace('/@/',CYRUS_DELIMITER.$folders[$i].'@',$mbox_name);
                $_new_mbox_name = preg_replace('/@/',CYRUS_DELIMITER.$folders[$i].'@',$new_mbox_name);
            } else {
                $_mbox_name = $mbox_name.CYRUS_DELIMITER.$folders[$i];
                $_new_mbox_name = $new_mbox_name.CYRUS_DELIMITER.$folders[$i];
            }

            _cimap_renamemailbox( $_mbox_name, $_new_mbox_name );
            cimap_setacl( $_new_mbox_name, $new_mbox_name, ACL_ALL );
            cimap_deleteacl( $_new_mbox_name, $mbox_name );
        }
        cimap_deleteacl( $new_mbox_name, CYRUS_USER );
        cimap_deletemailbox( $mbox_name );

        return true;
    }

    function _cimap_renamemailbox( $mbox_name, $new_mbox_name ) {
        DEBUG( D_FUNCTION, "_cimap_renamemailbox('$mbox_name','$new_mbox_name')" );

        cimap_setacl( $mbox_name, CYRUS_USER, ACL_ALL );
        if ( ! cimap_command('rename "user'.CYRUS_DELIMITER.$mbox_name.'" "user'.CYRUS_DELIMITER.$new_mbox_name.'"' ) )
            cimap_die( "_cimap_renamemailbox(): renaming failed" );
        cimap_deleteacl( $new_mbox_name, CYRUS_USER );

        return true;
    }

    function cimap_copymailbox( $src_mbox_name, $dst_mbox_name ) {
        global $_imap_result;
        DEBUG( D_FUNCTION, "cimap_copymailbox('$src_mbox_name','$dst_mbox_name')" );

        $find_out = array();
        $mails = 0;

        cimap_setacl( $src_mbox_name, CYRUS_USER, ACL_ALL );
        if ( ! cimap_command( "select user".CYRUS_DELIMITER.$src_mbox_name ) )
            cimap_die( "cimap_copymailbox(): selecting of mailbox failed" );

        for ( $i = 0; $i < count( $_imap_result ); $i++ ) {
            if ( strstr( $_imap_result[$i], "EXISTS" ) ) {
                $findout = explode( " ", $_imap_result[$i] );
                $mails = $findout[0];
            }
        }
        if ( $mails != 0 ) {
            cimap_setacl( $dst_mbox_name, CYRUS_USER, ACL_ALL );
            if ( ! cimap_command( "copy 1:$mails user".CYRUS_DELIMITER.$dst_mbox_name) ) 
                cimap_die( "cimap_copymailbox(): copying of mails failed" );
            for ( $i = 0; $i < count( $_imap_result ); $i++ )
                print "<font color=red>$_imap_result[$i]</font><br>";
            cimap_deleteacl( $dst_mbox_name, CYRUS_USER );
        }
        cimap_deleteacl( $src_mbox_name, CYRUS_USER );

        return true;
    }

    function cimap_getfolders( $mbox_name ) {
        global $_imap_result;
        DEBUG( D_FUNCTION, "cimap_getfolders('$mbox_name')" );
        if ( MAILBOX_STYLE == "USER@DOMAIN.TLD" ) {
            list( $muser, $mdomain ) = explode("@", $mbox_name);
            if ( $mdomain != "" ) $mdomain = "@".$mdomain;
        } else {
            $muser = $mbox_name;
            $mdomain = "";
        };
        if ( ! cimap_command('list "" "user'.CYRUS_DELIMITER.$muser.CYRUS_DELIMITER.'*'.$mdomain.'"' ) )
            cimap_die( "cimap_getfolders(): getting list of folders failed" );

        $result = array();
        for ( $i = 0; $i < count( $_imap_result ); $i++ ) {
            $splitfolder = split( '"', $_imap_result[$i] );
            $folder = substr( $splitfolder[3], strlen( "user".CYRUS_DELIMITER ) );
            $folder = substr( strstr( $folder, CYRUS_DELIMITER ), 1 ) ;
            if ( $mdomain != "" ) 
                $folder = substr( $folder,0, strpos( $folder,'@') );
            if ( $folder != "" )
                array_push( $result, $folder );
        }

        return $result;
    }

    function cimap_setquota( $mbox_name, $quota ) {
        DEBUG( D_FUNCTION, "cimap_setquota('$mbox_name', $quota)" );

        if ( ! cimap_command( "setquota user".CYRUS_DELIMITER.$mbox_name." (STORAGE $quota)" ) )
            cimap_die( "cimap_setquota(): setting quota of mailbox '$mbox_name' failed" );

        return true;
    }

    function cimap_delquota( $mbox_name ) {
        DEBUG( D_FUNCTION, "cimap_delquota('$mbox_name')" );

        if ( ! cimap_command( "setquota user".CYRUS_DELIMITER.$mbox_name." ()" ) )
            cimap_die( "cimap_setquota(): setting quota of mailbox '$mbox_name' failed" );

        return true;
    }

    function cimap_getquota( $mbox_name ) {
        global $_imap_result;
        DEBUG( D_FUNCTION, "cimap_getquota('$mbox_name')" );

        $rval = false;

        if ( ! cimap_command( "getquota user".CYRUS_DELIMITER.$mbox_name ) ) return $rval;

        if ( !preg_match( "/STORAGE (\d+) (\d+)/", $_imap_result[0], $quota ) ) return $rval;

        return array( "used" => $quota[1], "max" => $quota[2] );
    }

    function cimap_setacl( $mbox_name, $user, $acl ) {
        DEBUG( D_FUNCTION, "cimap_setacl('$mbox_name', '$acl')" );

        if ( ! cimap_command( "setacl user".CYRUS_DELIMITER.$mbox_name." $user $acl" ) )
            cimap_die( "cimap_setacl(): set of acl failed" );

        return true;
    }

    function cimap_deleteacl( $mbox_name, $user ) {
        DEBUG( D_FUNCTION, "cimap_deleteacl('$mbox_name')" );

        if ( ! cimap_command( "deleteacl user".CYRUS_DELIMITER.$mbox_name." $user" ) )
            cimap_die( "cimap_deleteacl(): deleting of acl failed" );

        return true;
    }

    function cimap_close() {
        global $_imap_stream;
        DEBUG( D_FUNCTION, "cimap_close()" );

        cimap_command( "logout" );
        fclose( $_imap_stream );

        return true;
    }

    cimap_open();

?>
