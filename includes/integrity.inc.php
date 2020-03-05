<?php

  defined('INCLUDE_DIR') || exit('Not for direct run');
  if ( $_SESSION['USER'] != ADMIN_USER ) {
    header( 'Location: '.BASE_URL.'?admin' );
    exit;
  }

  print "<br /><br />\n";

  require_once( INCLUDE_DIR.'/imap.inc.php' );

  if ( MAILBOX_STYLE == 'USER@DOMAIN.TLD' ) {
    sql_query( "SELECT account FROM cyrup_accounts WHERE domain_id='0' AND account LIKE ".sql_escape(CYRUS_USER.'@%') );
    $cyrus = sql_fetch_variable();
  }

  if ( !empty($cyrus) ) {
    $default_domain = strstr( $cyrus, '@' );
  } else {
    $default_domain = '';
    $cyrus = CYRUS_USER;
  }

  // Check integrity
  $result = sql_query( "SELECT * FROM cyrup_accounts" );
  while ( $row = sql_fetch_array($result) ) {
    if ( $row['account'] == $cyrus ) continue;
    cimap_command( 'LIST "" "user'.CYRUS_DELIMITER.$row['account'].'"' );
    if ( !$_imap_result ) {
      print "Account ${row['account']} has no imap account<br>\n";
      if ( isset($_GET['fixdb']) ) {
        sql_query( "SELECT id FROM cyrup_aliases WHERE account_id = ${row['id']} AND domain_id=".$row['domain_id'] );
        while ( $row1 = sql_fetch_array() ) remove_from_maillist( $row1['id'] );
        sql_query( "DELETE FROM cyrup_aliases WHERE account_id = ${row['id']} AND domain_id=".$row['domain_id'] );
        sql_query( "DELETE FROM cyrup_accounts WHERE id = ${row['id']} AND domain_id=".$row['domain_id'] );
        print "Account '${row['account']}' was deleted<br>\n";
      }
      if ( isset($_GET['fiximap']) ) {
        cimap_createmailbox( $row['account'] );
        cimap_createfolders( $row['account'] );
        if ( $row['quota'] > 0 ) cimap_setquota( $row['account'], $row['quota'] ); 
        print "Account '${row['account']}' was created. Quota is ${row['quota']}<br />\n";
      }
    }
  }
  print "\t---->To remove invalid account(s) from database, append '&fixdb' to URL<br>\n";

  cimap_command('LIST "*" "user'.CYRUS_DELIMITER.'*"');
  # * LIST (\HasChildren) "/" user/test@casino777.lv
  foreach ($_imap_result as $line) {
    preg_match('@ "?user'.CYRUS_DELIMITER.'([^'.CYRUS_DELIMITER.']+)"?$@',trim($line),$m);
    if ( empty($m[1]) ) continue;
    $imap_account = $m[1];
    if ( MAILBOX_STYLE == 'USER@DOMAIN.TLD' && strrpos($imap_account, '@') === false ) {
      $imap_account .= $default_domain;
    }
    sql_query( "SELECT * FROM cyrup_accounts WHERE account=".sql_escape($imap_account) );
    if ( ! sql_num_rows() ) {
      echo "Imap account ${imap_account} has no database entry<br>\n";
      if ( isset($_GET['fiximap']) ) {
        cimap_deletemailbox( $imap_account ) && print "FIXED<br>\n";	
      }
    }
  }
  print "\t---->To remove invalid account(s) from imap, append '&fiximap' to URL<br>\n";
	
  sql_query( "SELECT id FROM cyrup_domains;" );
  $domain_ids = [];
  while ( $row = sql_fetch_array() ) $domain_ids[] = $row['id'];
  $domain_sql = $domain_ids ? ' domain_id NOT IN ('.implode(',',$domain_ids).') ' : ' TRUE ';

  sql_query( "SELECT * FROM cyrup_accounts WHERE ${domain_sql} AND account<>".sql_escape($cyrus) );
  while ( $row = sql_fetch_array() ) echo "Account ${row['account']} is assigned to not existing domain<br>\n";

  sql_query( "SELECT * FROM cyrup_aliases WHERE ".$domain_sql );
  while ( $row = sql_fetch_array() ) echo "Alias ${row['alias']} is assigned to not existing domain<br>\n";

  sql_query( "SELECT * FROM cyrup_maillists WHERE ".$domain_sql );
  while ( $row = sql_fetch_array() ) echo "Maillist ${row['alias']} is assigned to not existing domain<br>\n";

  sql_query( "SELECT * FROM cyrup_accounts WHERE account='' OR password=''" );
  while ( $row = sql_fetch_array() ) echo "Account ${row['account']} (${row['id']}) has no password<br>\n";

  sql_query( "SELECT * FROM cyrup_aliases WHERE alias='' OR aliased_to=''" );
  while ( $row = sql_fetch_array() ) echo "Alias ${row['alias']} (${row['id']}) has empty target<br>\n";

  sql_query( "SELECT * FROM cyrup_maillists WHERE alias='' or aliased_to=''" );
  while ( $row = sql_fetch_array() ) echo "Maillist ${row['alias']} (${row['id']}) has empty target<br>\n"; 

  sql_query( "SELECT id FROM cyrup_accounts" );
  $account_ids = [ 0 ];
  while ( $row = sql_fetch_array() ) $account_ids[] = $row['id'];
  $account_sql = " account_id NOT IN (".implode(",",$account_ids).") ";

  sql_query( "SELECT * FROM cyrup_aliases WHERE ".$account_sql );
  while ( $row = sql_fetch_array() ) echo "Alias ${row['alias']} is assigned to not existing account<br>\n";
    
  print "\t---->To fix invalid entries in database, please call to administrator<br>\n";

