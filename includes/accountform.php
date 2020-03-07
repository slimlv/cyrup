<?php

  defined('INCLUDE_DIR') || exit('Not for direct run'); 
  if ( empty($_SESSION['domain_id'])) {
    header( "Location: ".BASE_URL."?admin" );
    exit;
  }

  require_once( INCLUDE_DIR.'/imap.inc.php' );
  require_once( INCLUDE_DIR.'/sieve.inc.php' );

  $domain_id = intval($_SESSION['domain_id']);
  $domain_row = get_domain_info( $domain_id );
  $account_id = '';
  $account = '';
  $errors = [];

  if ( !empty($_POST['account']) ) {

    if ( !empty($_POST['id']) && $_POST['id'] == intval($_POST['id']) ) {
      sql_query( "SELECT * FROM cyrup_accounts WHERE id=${_POST['id']} AND domain_id=".$domain_id );
      if ($account_row = sql_fetch_array() ) {
        $account_id = $account_row['id'];
      } else { 
        $errors[] = "This account absent";
      }
    }

    foreach (['first_name','surname','phone','other_email','info'] as $key) {
      $$key = empty($_POST[$key]) ? '' : trim($_POST[$key]);
    }
    $enabled    = isset($_POST['enabled']) ? 1 : 0;
    $quota      = empty($_POST['quota_mb']) ? 0 : intval($_POST['quota_mb'] << 10);
    $quota_inc  = isset($account_id) ? $quota - $account_row['quota'] : $quota;
    $regex =  CYRUS_DELIMITER == '/' ? '\w.-' : '\w-';
    $account = strtolower(trim(preg_replace("/[^${regex}]/",'',$_POST['account'])));
    $imap_account = get_mailbox( $account, $domain_row );

    // Check account name
    if ( strlen($imap_account) > MAX_ACCOUNT_LENGTH ) $errors[] = "Account name contains more than 100 symbols";
    if ( !preg_match("/^[${regex}]+$/",trim($_POST['account'])) ) $errors[] = "Account name empty or  contains illegal symbols";

    // Check password
    $change_password = true;
    if ( !isset($_POST['password_']) && (!$account_id || SHOW_PASSWORD) ) {
      $errors[] = "Empty password";
    } else {
      if ( !isset($_POST['password_retype']) || $_POST['password_'] != $_POST['password_retype'] && !SHOW_PASSWORD ) $errors[] = "Passwords do not match";
      if ( $_POST['password_'] == "" ) $change_password = false;
    }

    // Check overwriting of a account
    $query = "SELECT 1 FROM cyrup_accounts WHERE account=".sql_escape($imap_account);
    if ( $account_id ) $query .= " AND id<> ".$account_id;
    if ( sql_num_rows( sql_query($query) ) ) $errors[] = "Account with this name already exists";
    if ( !$account_id && $domain_row['accounts_max'] && $domain_row['accounts_cur'] >= $domain_row['accounts_max'] )
      $errors[] = "Maximum number of accounts for the domain already reached";
    if ( !empty($_POST['autoalias']) && $domain_row['aliases_max'] && $domain_row['aliases_max'] <= $domain_row['aliases_cur'] )
      $errors[] = "Maximum number of aliases for the domain already reached";

    // Check quotas
    if ( !ALLOW_NO_QUOTA && !$quota ) $errors[] = "Empty quota is not allowed";
    if ( $domain_row['quota'] && ($domain_row['quota_cur']+$quota_inc) > $domain_row['quota'] ) $errors[] = "Domain quota reached (${domain_row['quota_cur']}+${quota_inc} > ${domain_row['quota']})";
    if ( $quota > MAX_QUOTA ) $errors[] = "Quota exceed maximum of ".(MAX_QUOTA >>20)."Gb";

    if ( !$errors ) {
      $password = get_sql_crypt( $_POST['password_'] );
      if ( $account_id ) {
        $query = "UPDATE cyrup_accounts SET account=".sql_escape($imap_account)
                    .( $change_password ? ", password=".$password : "" )
                    .", domain_id=${domain_id}, quota=${quota}"
                    .", enabled=${enabled}"
                    .", first_name=".sql_escape($first_name).", surname=".sql_escape($surname)
                    .", phone=".sql_escape($phone).", other_email=".sql_escape($other_email).", info=".sql_escape($info)." WHERE id=".$account_id;

        sql_query( $query );
        if ( $account_row['account'] != $imap_account ) {
          sql_query( "UPDATE cyrup_aliases SET aliased_to=".sql_escape($imap_account)." WHERE account_id=".$account_id );
          cimap_renamemailbox( $account_row['account'], $imap_account );
        }
        if ( $account_row['quota'] != $quota ) {
          if ( $quota ) {
            cimap_setquota( $imap_account, $quota );
          } else {
            cimap_delquota( $imap_account );
          }
        }
      } else {
        sql_query( "INSERT INTO cyrup_accounts (account, password, domain_id, quota, enabled, first_name, surname, phone, other_email, info)
                     VALUES (".sql_escape($imap_account).",${password},${domain_id}, ${quota},${enabled},".sql_escape($first_name).",
                             ".sql_escape($surname).",".sql_escape($phone).",".sql_escape($other_email).",".sql_escape($info).")" );
        $account_id = sql_insert_id('cyrup_accounts', 'id');
        cimap_createmailbox( $imap_account );
        cimap_createfolders( $imap_account );
        if ( $quota ) cimap_setquota( $imap_account, $quota );
        if ( !empty($_POST['autoalias']) ) {
          $alias = $account."@".$domain_row['domain'];
          sql_query( "SELECT 1 from cyrup_aliases WHERE domain_id=${domain_id} AND alias=".sql_escape($alias)."
                         UNION
                      SELECT 1 from cyrup_maillists WHERE domain_id=${domain_id} AND alias=".sql_escape($alias));
          if ( !sql_num_rows() ) {
            sql_query ( "INSERT INTO cyrup_aliases (account_id,domain_id,enabled,alias,aliased_to) 
                             VALUES (${account_id},${domain_id},1, ".sql_escape($alias).",".sql_escape($imap_account).")" );
          }
        }
      }
    }
    header( "Location: ".BASE_URL."?admin&m=accounts" );
    exit;
  }

  print_header(VERSION.": Account form");
  print_top_menu();
  print "<script type='text/javascript' src='".JS_URL."/functions.js' language='JavaScript'></script>\n";

  $mailbox_suffix = get_mailbox_suffix( $domain_row );

  if ( !empty($_GET['id']) && $_GET['id'] == intval($_GET['id']) ) {
    sql_query( "SELECT * FROM cyrup_accounts WHERE id=".intval($_GET['id'])." AND domain_id=".$domain_id );
    if ( $account_row = sql_fetch_array() ) $account_id = $account_row['id'];
  }

  print "<center>\n";
  print "<form action='?admin&m=accountform&id=${account_id}' name='form' method='POST' onSubmit='javascript:document.form.account.disabled=false;'>";
  print "<input type=hidden name='action' value='action'>";

  if ( $account_id ) {
    print "<input type='hidden' name='id' value=${account_id}>\n";
    $account = $account_row['account'];
    $account_row['account'] = get_mailbox_local_part( $domain_row, $account_row['account'] );
    $account_row['quota'] >>= 10;
  } else {
    $account_row['quota'] = empty($_POST['quota_mb']) ? DEFAULT_QUOTA : intval($_POST['quota_mb']);
    $account_row['enabled'] = isset($enabled) ? $enabled : 1;
    $account_row['account'] = '';
    $account_row['password'] = '';
    foreach (['first_name','surname','phone','other_email','info'] as $key) {
      $account_row[$key] = empty($$key) ? '' : $$key;
    }
  }

  print "<table align=center border=0 cellpadding=0 cellspacing=0>\n";
  dotline( 2 );
  print "<tr class=highlight>\n<td colspan=2 align=center>";
  print $account_id ? "<b>Edit account</b>" : "<b>Add account</b>"; 
  print "</td></tr>\n";
  dotline( 2 );
  print "<tr>\n<td>&nbsp; Active? &nbsp;</td>\n<td>\n";
  print "<input type=checkbox name='enabled' ".( $account_row['enabled'] == 1 ? " checked" : "" );
  print "></td>\n</tr>\n";
  dotline( 2 );
  print "<tr>\n<td>&nbsp; Domain &nbsp;</td>\n";
  print "<td>&nbsp;".htmlspecialchars($domain_row['domain'])."</td>\n</tr>\n";
  dotline( 2 );
  print "<tr>\n<td>&nbsp; Account name &nbsp;</td>\n<td>\n";
  print "<input type=hidden name=old_account value='".htmlspecialchars($account_row['account'])."'>\n";
  print "<input type=text name=account size=15";
  if ( $account_id ) print " disabled onBlur='javascript:checkInput()' value='".htmlspecialchars($account_row['account'])."'";
  print ">".htmlspecialchars($mailbox_suffix)."<br>" ;
  if ( $account_id) print "<input type=button value='Rename' onClick='javascript:enableRename();'>";
  print "</td>\n</tr>\n";
  dotline( 2 );
  print "<tr>\n<td>&nbsp; Password ".(SHOW_PASSWORD ? "":"(twice)")."&nbsp;</td>\n<td>";
  if ( SHOW_PASSWORD ) {
    print "<input type='text' name='password_' size=10 value='".htmlspecialchars($account_row['password'])."'>";
    print "<input type='hidden' name='password_retype'>";
  } else {
    print "<input type='password' name='password_' size=10><br>\n";
    print "<input type='password' name='password_retype' size=10>&nbsp;";
    print ( $account_id ? "(Leave empty if no change) " : "" );
  }
  print "</td>\n</tr>\n";
  dotline( 2 ); 
  html_input_text( "quota_mb", "Quota", $account_row['quota'], "Mb", 5 );
  dotline( 2 );
  html_input_text( "first_name", "First name", $account_row['first_name'] );
  dotline( 2 );
  html_input_text( "surname", "Surname", $account_row['surname'] );
  dotline( 2 );
  html_input_text( "phone", "Phone", $account_row['phone'] );
  dotline( 2 );
  html_input_text( "other_email", "Other email", $account_row['other_email'] );
  dotline( 2 );
  print "<tr>\n<td>&nbsp; Info &nbsp;</td>\n<td>";
  print "<textarea name='info' cols='24' rows='4'>".$account_row['info']."</textarea></td>\n";
  print "</tr>\n";
  dotline( 2 );
  if ( $account_id ) {
    print "<tr>\n<td>&nbsp; Aliases &nbsp;</td>\n<td>";
    sql_query( "SELECT id,alias,enabled FROM cyrup_aliases WHERE domain_id=${domain_id} AND account_id=".$account_id );
    while ( $row = sql_fetch_array() ) {
      print "<a href='?admin&m=aliasform&id=${row['id']}&account_id=${account_id}'>&nbsp;".htmlspecialchars($row['alias'])."</a> ";
      print ($row['enabled'] ? "(active)" : "(not active)")."<br>\n";
    }
    print "&nbsp;<a href='?admin&m=aliasform&account_id=${account_id}' class='button'>[ Add new ]</a><br>\n";
    print "</td>\n</tr>\n";
    dotline( 2 );
    print "<tr>\n\t<td>&nbsp; Maillists &nbsp;</td>\n\t<td>";
    sql_query( "SELECT id FROM cyrup_aliases WHERE domain_id=${domain_id} AND account_id=".$account_id );
    while ( $row = sql_fetch_array() )
      print_maillist_list( $row['id'] );
      print "\t</td>\n</tr>\n"; 
      if ( SIEVE ) {
        dotline( 2 );
        print "<tr>\n\t<td>&nbsp; Autoreply &nbsp;</td>\n\t<td>";
        print "<a href='?admin&m=vacationform&account_id=${account_id}'>";
        $vacation = getVacation($account);
        print ( empty($vacation[0]) ? 'set' : 'edit')."</a></td></tr>\n";
      }
  } else {
    print "<tr>\n<td>&nbsp; Autocreate alias? &nbsp;</td>\n<td>\n";
    print "<input type=checkbox name=autoalias checked>\n</td>\n</tr>\n";
  }
  dotline( 2 );
  if ( $errors ) {
    print "<tr class=highlight><td colspan=2 align=center>";
    print_errors( $errors ); 
    print "</tr>";
    dotline( 2 ); 
  } 
  print "<tr>\n<td>&nbsp;</td>\n<td>";
  print "<input type='submit' value='".($account_id ? 'Update' : 'Add new')."'>";
  print "</td>\n</tr>\n</table>\n</form><br />\n";
  print "<form name='generator'>\nGenerate password:";
  print "<input type='text' name='password' size='12'>\n";
  print "<input type=button value='Generate' onClick='document.forms[1].password.value=getPassword(8,\"\",true,true,true,false,true,true,true,false);'>";
  print "</form>\n";
  print_footer();

