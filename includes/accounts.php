<?php
  defined('INCLUDE_DIR') || exit('Not for direct run');
  require_once( INCLUDE_DIR.'/imap.inc.php' );

  # fetching sieve scripts slow down things
  if ( SHOW_VACATION_LIST ) {
    require_once( INCLUDE_DIR.'/sieve.inc.php' );
  } else {
    define('SIEVE', 0);
  }

  print_header( VERSION.": Accounts" );
  print_top_menu();
  print "<script type='text/javascript' src='".JS_URL."/functions.js' language='JavaScript'></script>\n";

  $order_by = get_order_by("accounts_order_by");
  $domain_id = get_domain_id();

  if ( isset($_POST['confirm']) && isset($_POST['action']) && $domain_id ) {
    if ($sel = chks2sql('account_id') ) {
      sql_query( "SELECT id FROM cyrup_aliases WHERE ${sel} AND domain_id=".$domain_id );
      while ( $row = sql_fetch_array() ) remove_from_maillist($row['id']);
      sql_query( "DELETE FROM cyrup_aliases WHERE ${sel} AND domain_id=".$domain_id );
    }
    if ($sel = chks2sql()) {
      sql_query( "SELECT * FROM cyrup_accounts WHERE ${sel} AND domain_id=".$domain_id );
      $quota_sum = 0;
      while ( $row = sql_fetch_array() ) {
        $quota_sum += $row['quota'];
        cimap_deletemailbox( $row['account'] );
      }
      sql_query( "DELETE FROM cyrup_accounts WHERE ${sel} AND domain_id=".$domain_id );
    }
  }

  print_domain_selection( $domain_id );

  if ( $domain_id ) {
    print "<form name='form' method=POST action='?admin&m=accounts'>\n";
    print "<input type=hidden name=action value='action'>\n";
    print "<table width=100% border=0 cellpadding=0 cellspacing=0>\n";
    dotline( 10 );

    print "<tr>\n";
    print "<th width=1><input type=checkbox name=chkChangeAll onClick='check_boxes()'></th>\n";
    html_th( "account", "Account" );
    html_th( "enabled", "Active", "Can user connect to his mailbox?" );
    html_th( "aliases_cur", "Aliases", "Current number of aliases" );
    if ( SIEVE ) html_th( 'vacation', 'V', 'Current status of autoreply' );
    html_th( "quota", "Quota Used/Max (%)" );
    html_th( "first_name", "First name" );
    html_th( "surname", "Surname" );
    html_th( "phone", "Phone" );
    html_th( "info", "Info" );
    print "</tr>\n";

    dotline( 10 );
    print "<tr class=highlight><td colspan=10 align=center><a href='?admin&m=accountform' class=button>[ Add new ]</a></td></tr>\n";
    dotline( 10 );

    $query = "SELECT * FROM cyrup_accounts WHERE domain_id=${domain_id} ".filter2sql('account').' ORDER BY '.$order_by;
    
    sql_query( $query );

    $rows = sql_fetch_all();
    if ( $rows ) {
      foreach ($rows as $row) {
        if ( $row["account"] == CYRUS_USER ) continue;
        print "<td width=1><input type=checkbox name='ids[${row['id']}]' value='${row['id']}'></td>\n";
        print "<td>&nbsp;<a href='?admin&m=accountform&id=${row['id']}'>".htmlspecialchars($row['account'])."</a></td>\n";
        print "<td align=center>&nbsp;".( $row['enabled'] == 1 ? "Y" : "N")."</td>\n";
        print "<td align=center>&nbsp;".count(get_aliases_list($row['id']))."</td>\n";
        if ( SIEVE) {
          $vacation = getVacation($row['account']);
          print '<td align=center><a href="?admin&m=vacationform&account_id='.$row['id'].'">'.( empty($vacation[0]) ? '-' : 'V')."</a></td>\n";
        }
        $quota = cimap_getquota( $row['account'] );
        print "<td align=center>&nbsp;";
        print is_array($quota) ? kb2mb( $quota['used'] )."/".kb2mb( $quota['max'] )."&nbsp;(".percents( $quota['used'], $quota['max'] ).") " : "no-quota";
        print "</td>\n";
        print "<td align=center>&nbsp;".htmlspecialchars($row['first_name'])."</td>\n";
        print "<td align=center>&nbsp;".htmlspecialchars($row['surname'])."</td>\n";
        print "<td align=center>&nbsp;".htmlspecialchars($row['phone'])."</td>\n";
        print "<td align=right>&nbsp;".htmlspecialchars($row['info'])."</td>\n";
        print "</tr>\n";
        dotline( 10 );
      }
      print "</table>\n";
      print "<br><br>\n";
      delete_selected_box(); 
    } else {
      print "</table>\n";
    }
    print "</form>\n";
  }
  print_footer();
