<?php

  defined('INCLUDE_DIR') || exit("Not for direct run");
  if ( $_SESSION['USER'] == ADMIN_USER && !empty($_POST['confirm']) ) {
    require_once( INCLUDE_DIR."/imap.inc.php" );

    $sel = chks2sql( 'domain_id' );
    if ( $sel ) {
      sql_query( "SELECT * FROM cyrup_accounts WHERE ".$sel );
      while ( $row = sql_fetch_array() ) cimap_deletemailbox( $row['account'] );
        sql_query( "DELETE FROM cyrup_accounts WHERE ".$sel );
        sql_query( "DELETE FROM cyrup_aliases WHERE ".$sel );
        sql_query( "DELETE FROM cyrup_default_rcpt WHERE ".$sel );
        sql_query( "DELETE FROM cyrup_maillists WHERE ".$sel );
    }

    $sel = chks2sql( 'id' );
    if ( !empty($sel) ) sql_query( 'DELETE FROM cyrup_domains WHERE '.$sel );
    if ( defined('DOMAIN_EXPORT_FILE') AND DOMAIN_EXPORT_FILE != '' )
      export2file( 'SELECT domain FROM cyrup_domains WHERE enabled=1' , DOMAIN_EXPORT_FILE );
    if ( defined('SYSTEM_ALIASES') && SYSTEM_ALIASES ) mksysaliases(SYSTEM_ALIASES);
  }


  print_header( TITLE."Domains" );
  print_top_menu();
  print "<script type='text/javascript' src='".JS_URL."/functions.js' language='JavaScript'></script>\n";

  $order_by = get_order_by("domains_order_by");
  $colspan = 9;

  if ( $_SESSION['USER'] == ADMIN_USER ) print "<form name=form method=POST action='?admin&m=domains'>\n";
  print "<table width=100% border=0 cellpadding=0 cellspacing=0>\n";
  dotline( $colspan );

  print "<tr>\n";
  print "<th width=1><input type=checkbox name=chkChangeAll onClick='check_boxes()'></th>\n";
  html_th( "domain", "Domain", "Domain name" );
  html_th( "enabled", "Active", "Permit to receive mails for the domain");
  html_th( "accounts_max", "Accounts / Max", 'Accounts in use / Maximum allowed in domain' );
  html_th( "aliases_max", "Aliases / Max", 'Aliases in use / Maximum allowed in domain' );
  html_th( "quota", "Quota current / Max ", 'Quota in use / Maximum allowed in domain' );
  if ( MAILBOX_STYLE == "USERSUFFIX" ) html_th( "account_suffix", "Acc. suffix" );
  html_th( "aliased_to", "Def. email", "Unknown recipient's catcher" );
  html_th( "owner", "Owner" );
  html_th( "info", "Info" );
  print "</tr>\n";

  dotline( $colspan );
  print "<tr class=highlight><td colspan=${colspan} align=center>";
  print $_SESSION['USER'] == ADMIN_USER ? "<a href='?admin&m=domainform' class=button>[ Add new ]</a>" : "&nbsp;";
  print "</td></tr>\n";
  dotline( $colspan );

  $query = "SELECT id FROM cyrup_domains a LEFT JOIN cyrup_default_rcpt b ON a.id=b.domain_id WHERE ".rights2sql('a.id')." ORDER BY ".$order_by;
  $domains_res = sql_query( $query );
  $i = 0;
  while ( $row = sql_fetch_array( $domains_res ) ) {
    $domain_row = get_domain_info($row['id']);
    $i++;
    print "<td><input type=checkbox ";
    if ( $_SESSION['USER'] == ADMIN_USER ) {
      print "name='ids[${row['id']}]' value=${row['id']}></td>\n";
      print "<td>&nbsp;<a href='?admin&m=domainform&id=${row['id']}'>".htmlspecialchars($domain_row['domain'])."</a>";
    } else {
      print "></td>\n<td>&nbsp;<a href='#'>".htmlspecialchars($domain_row['domain'])."</a>";
    }
    print "</td>\n<td align='center'>".($domain_row['enabled'] ? 'Y' : 'N')."</td>\n";
    print "<td align=center>&nbsp;${domain_row['accounts_cur']}/${domain_row['accounts_max']}</td>\n";
    print "<td align=center>&nbsp;${domain_row['aliases_cur']}/${domain_row['aliases_max']}</td>\n";
    print "<td align=center>&nbsp;".kb2mb( $domain_row['quota_cur'] )."/".($domain_row['quota'] ? kb2mb( $domain_row['quota'] ) : "no-quota" )."</td>\n";
    if ( MAILBOX_STYLE == "USERSUFFIX" ) print "<td align=center>&nbsp;".htmlspecialchars($domain_row['account_suffix'])."</td>\n";
    print "<td align=center>&nbsp;".htmlspecialchars($domain_row['default_rcpt'])."</td>\n";
    if ( $owner_id = get_domain_owner($row['id']) ) {
      sql_query( "SELECT username FROM cyrup_admins WHERE id = ".$owner_id );
      print "<td align=center>&nbsp;<a href='?admin&m=".($_SESSION['USER'] == ADMIN_USER ? "adminform&id=${owner_id}" : "service")."'>".sql_fetch_variable()."</a></td>\n";
    }
    print "<td align=right>&nbsp;".htmlspecialchars($domain_row['info'])."</td>\n";
    print "</tr>\n";
    dotline( $colspan );
  }
  print "</table>\n";
  if ( $_SESSION['USER'] == ADMIN_USER ) {
    if ( $i ) print "<br><br>\n".delete_selected_box();
    print "</form>\n";
  }
  print_footer();
