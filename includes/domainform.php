<?php

  defined("INCLUDE_DIR") || exit("Not for direct run");
  if ( $_SESSION['USER'] != ADMIN_USER ) {
    header( "Location: ".BASE_URL."?admin" );
    exit;
  }

  $errors = [];
  $owner_id = empty($_POST['owner_id']) ? '' : intval($_POST['owner_id']);
  $domain = empty($_POST['domain']) ? '' : trim($_POST['domain']);
  $domain_id = empty($_POST['id']) ? '' : intval($_POST['id']);
  $quota = empty($_POST['quota_mb']) ? 0 : intval($_POST['quota_mb'] << 10);
  $accounts_max = empty($_POST['accounts_max']) ? 0 : intval($_POST['accounts_max']);
  $aliases_max = empty($_POST['aliases_max']) ? 0 : intval($_POST['aliases_max']);
  $default_rcpt = empty($_POST['default_rcpt']) ? '' : trim($_POST['default_rcpt']);
  $account_suffix = empty($_POST['account_suffix']) ? '' : trim($_POST['account_suffix']);
  $info = empty($_POST['info']) ? '' : trim($_POST['info']);

  // Huge checks on alter
  if ( !empty($_POST['domain']) || !empty($_POST['id']) ) {

    # Check for allowed symbols in account_suffix
    if ( $account_suffix ) {
      $account_suffix = strtolower(preg_replace('/[^\w]/i','',$account_suffix));
    }

    # Check $owner_id to exist
    if ( $owner_id ) {
      sql_query("SELECT * FROM cyrup_admins WHERE id=".$owner_id);
      if ( ! sql_num_rows() ) {
        $errors[] = "Specified admin does not exist";
      }
    }

    # Check for allowed symbols in default_rcpt
    if ( $default_rcpt && !verify_email($default_rcpt) ) {
      $errors[] = "Recipient of undelivered mail is incorrect";
    }

    if ( $quota > 2147483647 || $quota < 0 ) {
      $errors[] = "Quota exceed maximum of 2147483647Kb";
    }

    if ( $domain_id ) {
      # This is update of existing domain, let's make some checks...
      sql_query("SELECT * FROM cyrup_domains WHERE id=".$domain_id);
      if ( 1 == sql_num_rows() ) {
        $row = sql_fetch_array();
        $domain = $row['domain'];
        sql_query( "SELECT count(*),sum(quota) FROM cyrup_accounts WHERE domain_id=".$domain_id );
        list( $accounts_cur, $quota_cur ) = sql_fetch_array();
        sql_query( "SELECT count(*) FROM cyrup_aliases WHERE domain_id=".$domain_id );
        $aliases_cur = sql_fetch_variable();
        if ( $accounts_max && $accounts_cur > $accounts_max ) {
          $errors[] = "There are already ${accounts_cur} accounts in this domain.<br>Delete some accounts if you need to reduce the maximum.";
        }
        if ( $aliases_max && $aliases_cur > $aliases_max ) {
          $errors[] = "There are already ${row['aliases_cur']} aliases in this domain.<br>Delete some aliases if you need to reduce the maximum.";
        }
        if ( $quota && $quota_cur > $quota ) {
          $errors[] = "Can't set quota less then already used";
        }
      } else {
        $errors[] = "This domain does not exist";
      }
    } else {
      # This is a new domain, domain must be set
      if ( $domain && preg_match("/^([\w\.\-]+\.)[a-z]{2,7}$/i", $domain) ) {
        $domain = strtolower(preg_replace('/[^\w\-\.]/','',$domain));
        # Check new domain to be already exist
        sql_query( "SELECT * FROM  cyrup_domains WHERE  domain=".sql_escape($domain) );
        if ( sql_fetch_array() ) {
          $errors[] = "Domain already exist";
        }
      } else {
        $errors[] = "Domain name is incorrect";
      }

      # Each domain must have unique suffix if MAILBOX_STYLE !="USER@DOMAIN.TLD" 
      if ( MAILBOX_STYLE != "USER@DOMAIN.TLD" ) {
        sql_query( "SELECT * FROM cyrup_domains WHERE account_suffix=".sql_escape($account_suffix) );
        if ( sql_num_rows() ) $errors[] = "Domain suffix already in use";
      }
    }

    if ( !$errors ) {
      if ( !empty($domain_id) ) {
        $query = "UPDATE cyrup_domains SET accounts_max=${accounts_max},aliases_max=${aliases_max}, quota=${quota},info=".sql_escape($info).", enabled=".( empty($_POST['enabled']) ? 0 : 1 )." WHERE id=".$domain_id;;
        sql_query( $query );
      } else {
        $query = "INSERT INTO cyrup_domains (domain, account_suffix, accounts_max, aliases_max, quota, info, enabled)
                   VALUES (".sql_escape($domain).",".sql_escape($account_suffix).",${accounts_max}, ${aliases_max}, ${quota}, ".sql_escape($info).", 1)";
        sql_query( $query );
        $domain_id = sql_insert_id('cyrup_domains', 'id');
      }

      sql_query( "DELETE FROM cyrup_default_rcpt WHERE domain_id=".$domain_id );
      if ( $default_rcpt ) {
         sql_query( "INSERT INTO cyrup_default_rcpt (domain_id, alias, aliased_to) VALUES (${domain_id}, ".sql_escape('@'.$domain).", ".sql_escape($default_rcpt).")" );
      }

      if ( $domain_owner_id = get_domain_owner($domain_id) ) {
        sql_query( "SELECT rights FROM cyrup_admins WHERE id=".$domain_owner_id);
        $rights = sql_fetch_variable();
        $rights = explode( ",", $rights );
        $key = array_search( $domain_id, $rights );
        unset($rights[$key]);
        sql_query( "UPDATE cyrup_admins SET rights=".sql_escape(implode( ',', $rights ))." WHERE id=".$domain_owner_id );
      }
      if ( $owner_id && $domain_id ) {
        sql_query( "SELECT rights FROM cyrup_admins WHERE id=".$owner_id );
        $rights = sql_fetch_variable();
        $rights = trim($rights) == '' ? [] : explode( ",", $rights );
        $rights[] = $domain_id;
        sql_query( "UPDATE cyrup_admins SET rights=".sql_escape(implode( ',' , $rights ))." WHERE id=".$owner_id );
      }

      if ( defined('DOMAIN_EXPORT_FILE') && DOMAIN_EXPORT_FILE != '' ) {
        export2file( "SELECT domain FROM cyrup_domains WHERE enabled=1", DOMAIN_EXPORT_FILE );
      }
      if ( defined('SYSTEM_ALIASES') && SYSTEM_ALIASES != '' ) {
        mksysaliases(SYSTEM_ALIASES);
      }

      header( "Location: ".BASE_URL."?admin&m=domains" );
      exit;

    }
  }

// Domain form comes here
  print_header( TITLE."Domain form" );
  print_top_menu();

  $domain_id = empty($_GET['id']) ? '' : intval($_GET['id']);

  print "<center><form action='?admin&m=domainform&id=${domain_id}' method='POST'>\n";

  if ( $domain_id ) {
    print "<input type='hidden' name='id' value='${domain_id}'>\n";
    $row = get_domain_info( $domain_id );
  } else {
    $row['account_suffix'] = $account_suffix;
    $row['domain'] = $domain;
    $row['info'] = $info;
    $row['accounts_max'] = $accounts_max;
    $row['aliases_max'] = $aliases_max;
    $row['quota'] = $quota;
    $row['default_rcpt'] = $default_rcpt;
  }

  print "<table align=center border=0 cellpadding=0 cellspacing=0>\n";
  dotline( 2 );
  print "<tr class=highlight>\n<td colspan=2 align=center>\n";
  print $domain_id ? "<b>Edit domain</b>" : "<b>Add domain</b>";
  print "</tr>\n";
  dotline( 2 );
  print "<tr>\n<td>&nbsp; Domain &nbsp;</td>\n<td>\n";

  print $domain_id ? htmlspecialchars($row['domain']) : "<input type=text name=domain size=30 value='".htmlspecialchars($row['domain'])."'>";
  print "</td>\n</tr>\n";
  dotline( 2 );
  print '<tr><td>&nbsp; Active &nbsp;</td><td><input name="enabled" type="checkbox" '.(!empty($row['enabled']) ? 'checked' : '')."></td></tr>\n";
  dotline( 2 );
  html_input_text( "accounts_max", "Max accounts", $row['accounts_max'], "", 5 );
  dotline( 2 );
  html_input_text( "aliases_max", "Max aliases", $row['aliases_max'], "", 5 );
  dotline( 2 );
  html_input_text( "quota_mb", "Quota", ( $row['quota'] >>10), "Mb", 5 );
  if ( MAILBOX_STYLE == "USERSUFFIX" ) {
    dotline( 2 );
    print "<tr>\n<td>&nbsp; Account suffix &nbsp;</td>\n<td>\n";
    print $domain_id ? htmlspecialchars($row['account_suffix']) : "<input type=text name=account_suffix size=20 value='".htmlspecialchars($row['account_suffix'])."'>";
    print "</td>\n</tr>\n";
  }
  dotline( 2 );
  html_input_text( "default_rcpt", "Recipient of undelivered mail", $row['default_rcpt'], "(E-mail address or blank for default delivery)", 20 );
  dotline( 2 );
  print "<tr>\n<td>&nbsp; Owner &nbsp;</td>\n";
  print "<td><select name='owner_id'>\n";
  print "<option value='0'>- none -\n";
  $result = sql_query( "SELECT * FROM cyrup_admins ORDER BY username" );
  $domain_owner_id =  get_domain_owner($domain_id);
  while ( $admin_row = sql_fetch_array($result) ) {
    if ( $admin_row['username'] == ADMIN_USER ) continue;
    print "<option value='".$admin_row['id'];
    print $domain_id && $admin_row['id'] == $domain_owner_id ? "' selected >" : "' >";
    print htmlspecialchars($admin_row['username'])."</option>\n";
  }
  print "</select></td></tr>\n";
  dotline( 2 );
  print "<tr>\n<td>&nbsp; Info &nbsp;</td>\n";
  print "<td><textarea name=info cols=24 rows=4>".htmlspecialchars($row['info'])."</textarea></td></tr>\n";
  dotline( 2 );
  if ( $errors ) {
    print "<tr class=highlight><td colspan=2 align=center>";
    print_errors( $errors );
    print "</tr>";
    dotline( 2 );
  }
  print "<tr>\n<td>&nbsp;</td>\n<td>";
  print "<input type=submit value='".($domain_id ? 'Update': 'Add new')."'>";
  print "</td>\n</tr>\n</table>\n</center>\n</form>\n";
  print_footer();
