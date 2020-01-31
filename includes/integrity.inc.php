<?php
/*
 * $RCSfile: integrity.inc.php,v $ $Revision: 1.10 $
 * $Author: slim_lv $ $Date: 2012/06/26 12:16:02 $
 * This file is part of CYRUP project
 * by Yuri Pimenov (up@msh.lv) & Deniss Gaplevsky (slim@msh.lv)
 */

    if ( !defined('INCLUDE_DIR') ) exit('Not for direct run');
    if ( $_SESSION['USER'] != ADMIN_USER ) {
        header( 'Location: '.BASE_URL.'/?admin' );
        exit;
    }

    print "<br /><br />\n";

    require_once( INCLUDE_DIR.'/imap.inc.php' );

    if (MAILBOX_STYLE == 'USER@DOMAIN.TLD' ) {
    	sql_query( "SELECT account FROM cyrup_accounts WHERE domain_id='0' AND account LIKE '".CYRUS_USER."@%'" );
      	$cyrus = sql_fetch_variable();
    }

    if ( !empty($cyrus) ) {
        $defdomain = strstr( $cyrus, '@' );
    } else {
        $cyrus = CYRUS_USER;
        $defdomain = "";
    }

    // Check integrity
    global $_imap_result;

    $result = sql_query( "SELECT * FROM cyrup_accounts" );
    while ( $row = sql_fetch_array($result) ) {
        cimap_command( 'LIST "" "user'.CYRUS_DELIMITER.$row['account'].'"' );
        if ( (count($_imap_result) == 0) AND ($row['account'] != $cyrus) ) {
            echo "Account ".$row['account']." has no imap account<br>\n";
            if ( isset($_GET['fixdb']) ) {
                sql_query( "SELECT id FROM cyrup_aliases WHERE account_id = ".$row['id']." AND domain_id='".$row['domain_id']."'" );
                while ( $row1 = sql_fetch_array() ) remove_from_maillist( $row1['id'] );
                sql_query( "DELETE FROM cyrup_aliases WHERE account_id = ".$row['id']." AND domain_id='".$row['domain_id']."'" );
                sql_query( "DELETE FROM cyrup_accounts WHERE id = ".$row['id']." AND domain_id='".$row['domain_id']."'" );
                print "Account '".$row['account']."' was deleted<br>\n";
            }
            if ( isset($_GET['fiximap']) ) {
                cimap_createmailbox( $row['account'] );
                cimap_createfolders( $row['account'] );
                if ( $row['quota'] > 0 ) cimap_setquota( $row['account'], $row['quota'] ); 
                print "Account '".$row['account']."' was created. Quota is ".$row['quota']."<br />\n";
            }
        }
    }
    print "\t---->To remove invalid account(s) from database, append '&fixdb' to URL<br>\n";

    cimap_command('LIST "*" "user'.CYRUS_DELIMITER.'*"');
    for ($i = 0 ; $i < count($_imap_result) ; $i++) {
        $mystring = trim($_imap_result[$i]);
        list(,,,$mystring) = explode( ' ' , $mystring );
        if ( substr_count($mystring,CYRUS_DELIMITER) > 1 ) continue;
        list(,$mystring) = explode( CYRUS_DELIMITER , $mystring );
        $mystring = trim($mystring,'"');
        if ( (MAILBOX_STYLE == 'USER@DOMAIN.TLD') AND ( strrpos( $mystring, '@') === false ) ) $mystring = $mystring.$defdomain;
	    $query = "SELECT * FROM cyrup_accounts where account='".$mystring."'";
	    if ( 1 > sql_num_rows(sql_query($query)) ) {
		    echo "Imap account ".$mystring." has no database entry<br>\n";
		    if ( isset($_GET['fiximap']) ) {
			    cimap_deletemailbox( $mystring );
			    print "FIXED<br>\n";	
	    	}
	    }
    }
    print "\t---->To remove invalid account(s) from imap, append '&fiximap' to URL<br>\n";
	
    sql_query( "SELECT id FROM cyrup_domains;" );
    $domain_ids = array();
    while ( $row = sql_fetch_array() ) $domain_ids[] = $row['id'];
    $domain_sql = ( count($domain_ids) ? ' domain_id NOT IN ('.implode(',',$domain_ids).') ' : ' TRUE ' );

    sql_query( "SELECT * FROM cyrup_accounts WHERE ".$domain_sql." AND account<>'".$cyrus."'" );
    while ( $row = sql_fetch_array() ) echo "Account ".$row['account']." is assigned to not existing domain<br>\n";

    sql_query( "SELECT * FROM cyrup_accounts WHERE account='' or password=''" );
    while ( $row = sql_fetch_array() ) echo "Account ".$row['account']."(".$row['id'].") has no password<br>\n";

    sql_query( "SELECT * FROM cyrup_aliases WHERE ".$domain_sql );
    while ( $row = sql_fetch_array() ) echo "Alias ".$row['alias']." is assigned to not existing domain<br>\n";

    sql_query( "SELECT id FROM cyrup_accounts" );
    $account_ids = array();
    while ( $row = sql_fetch_array() ) $account_ids[] = $row['id'];
    array_push ( $account_ids, "0");
    $account_sql = " account_id NOT IN (".implode(",",$account_ids).") ";

    sql_query( "SELECT * FROM cyrup_aliases WHERE ".$account_sql );
    while ( $row = sql_fetch_array() ) echo "Alias ".$row['alias']." is assigned to not existing account<br>\n";
    
    sql_query( "SELECT * FROM cyrup_aliases WHERE alias='' or aliased_to=''" );
    while ( $row = sql_fetch_array() ) echo "Alias ".$row['alias']."(".$row['id'].") has empty target<br>\n";

    sql_query( "SELECT * FROM cyrup_maillists WHERE ".$domain_sql );
    while ( $row = sql_fetch_array() ) echo "Maillist ".$row['alias']." is assigned to not existing domain<br>\n";

    sql_query( "SELECT * FROM cyrup_maillists WHERE alias='' or aliased_to=''" );
    while ( $row = sql_fetch_array() ) echo "Maillist ".$row['alias']."(".$row['id'].") has empty target<br>\n"; 

    print "\t---->To fix invalid entries in database, please call to administrator<br>\n";

?>      
