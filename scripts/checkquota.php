<?php
/*
 * $RCSfile: checkquota.php,v $ $Revision: 1.2 $
 * $Author: slim_lv $ $Date: 2008/08/28 12:58:41 $
 * This file is part of CYRUP project
 * by Yuri Pimenov (up@msh.lv) & Deniss Gaplevsky (slim@msh.lv)
 * This script send notifications about quota exeeder's
 */

    define( "SENDMAIL2", "admin@domain.tld");
    define( "OWN_PATH", "../" );
    $ahosts = array ('127.0.0.1');

    if ( ! in_array($_SERVER['REMOTE_ADDR'],$ahosts) ) 
        die('nothing here');

    require_once( OWN_PATH.'/config.inc.php' );
    require_once( OWN_PATH.INCLUDE_DIR.'/functions.inc.php' );

    if ( DB_TYPE == "pgsql")
        require_once( INCLUDE_DIR."/pgsql.inc.php" );
    else
        require_once( INCLUDE_DIR."/mysql.inc.php" );

    require_once( OWN_PATH.INCLUDE_DIR.'/imap.inc.php' );

    $query = "SELECT acc.*,dom.domain,dom.info as domaininfo 
				FROM cyrup_accounts acc, cyrup_domains dom
                                WHERE acc.domain_id=dom.id AND acc.quota>0
                                        ORDER BY dom.domain";

    sql_query( $query );
    while ( $row = sql_fetch_array() ) {
        $quota = cimap_getquota( $row['account'] );
        if ( $quota['used'] > $quota['max'] ) {
            if ( empty($message) ) 
                $message = "Following accounts have IMAP overquote:\n\r";
            $message .= $row['account']." (".$row['first_name']." ".$row['surname'];
            if ( trim($row['phone']) != '' ) 
                $message .= "/".$row['phone'];
            if ( trim($row['info']) != '' )
                $message .= "/".$row['info'];
            $message .= ") in domain ".$row['domain']." (".$row['domaininfo'].") \n" ;	
        }
    }
    if ( !empty($message) ) {
        mail(SENDMAIL2,
            "IMAP overquote on ".$_SERVER['SERVER_NAME'],
            $message,
            "From: cyrup <".$_SERVER['SERVER_ADMIN'].">\r\nReply-To: ".$_SERVER['SERVER_ADMIN']."\r\n");
    }
?>
