<?php
/*
 * $RCSfile: accountform.php,v $ $Revision: 1.12 $
 * $Author: slim_lv $ $Date: 2016/11/01 14:09:36 $
 * This file is part of CYRUP project
 * by Yuri Pimenov (up@msh.lv) & Deniss Gaplevsky (slim@msh.lv)
 */

    if ( !defined('INCLUDE_DIR') ) exit('Not for direct run'); 
    if (!isset($_SESSION['domain_id'])){
        header( "Location: ".BASE_URL."/?admin" );
        exit;
    };

    require_once( INCLUDE_DIR.'/imap.inc.php' );
    require_once( INCLUDE_DIR.'/sieve.inc.php' );

    $domain_id = intval($_SESSION['domain_id']);
    $domain_row = get_domain_info( $domain_id );

    if ( isset($_POST['action']) ) {
        $errors = array();

        if ( isset($_POST['id']) AND (0 !== intval($_POST['id'])) ) {
            sql_query( "SELECT * FROM cyrup_accounts WHERE id=".intval($_POST['id'])." AND domain_id=".$domain_id );
            $account_row = sql_fetch_array();
            if ( $account_row ) {
                $account_id = $account_row['id'];
            } else { 
                array_push( $errors, "This account absent" );
            }
        }

        $first_name = addslashes(htmlspecialchars( $_POST['first_name'],ENT_QUOTES ));
        $surname    = addslashes(htmlspecialchars( $_POST['surname'],ENT_QUOTES ));
        $phone	    = addslashes(htmlspecialchars( $_POST['phone'],ENT_QUOTES ));
        $other_email = addslashes(htmlspecialchars( $_POST['other_email'],ENT_QUOTES ));
        $info	    = addslashes(htmlspecialchars( $_POST['info'],ENT_QUOTES ));
        $enabled    = ( isset($_POST['enabled']) ? 1 : 0 );
        $quota	    = intval($_POST['quota_mb'] << 10);
        $quota_inc  = ( isset($account_id) ? $quota - $account_row['quota'] : $quota );
        if ( CYRUS_DELIMITER == '/')
            $account = strtolower(trim(preg_replace('/[^\w\-\.]/','',$_POST['account'])));
        else
            $account = strtolower(trim(preg_replace('/[^\w\-]/','',$_POST['account'])));
        $imap_account = get_mailbox( $account, $domain_row );

        // Check account name
        if ( strlen($imap_account) >100 ) array_push( $errors, "Account name contains more than 100 symbols" );
        if ( CYRUS_DELIMITER == '/') {
            if  ( !preg_match('/^([\w\-\.])+$/',trim($_POST['account'])) ) array_push( $errors, "Account name empty or  contains illegal symbols" );
        } else {
            if  ( !preg_match('/^([\w\-])+$/',trim($_POST['account'])) ) array_push( $errors, "Account name empty or  contains illegal symbols" );
        }

        // Check password
        $change_password = true;
        if ( empty($_POST['password_']) AND ((!isset($account_id)) OR (SHOW_PASSWORD)) ) {
            array_push( $errors, "Empty password" );
        } else {
            if ( ($_POST['password_'] != $_POST['password_retype']) AND (!SHOW_PASSWORD) ) array_push( $errors, "Passwords do not match" );
            if ( $_POST['password_'] == "" ) $change_password = false;
        }

	// Check overwriting of a account
	$query = "SELECT 1 FROM cyrup_accounts WHERE account='".$imap_account."'";
	if ( isset( $account_id ) ) $query .= " AND id<> ".$account_id;
	if ( sql_fetch_array( sql_query( $query ) ) ) array_push( $errors, "Account with this name already exists" );
	if ( !isset($account_id) AND $domain_row['accounts_max'] AND $domain_row['accounts_cur'] >= $domain_row['accounts_max'] ) array_push( $errors, "Maximum number of accounts for the domain reached" );

	// Check quotas
	if ( !ALLOW_NO_QUOTA  AND !$quota ) array_push( $errors, "Zero quota not allowed" );
	if ( $domain_row['quota'] AND  ($domain_row['quota_cur']+$quota_inc) > $domain_row['quota'] ) array_push( $errors, "Domain quota reached (".$domain_row['quota_cur']."+".$quota_inc." > ".$domain_row['quota'].")" );
	if ( $quota > 1000000000 ) array_push( $errors, "Quota exceed maximum of 1Tb" );

	if ( sizeof($errors) == 0 ) {
	    $password = get_sql_crypt( $_POST['password_'] );
	    $sel = " cyrup_accounts SET account='".$imap_account."'"
                    .( $change_password ? ", password=".$password : "" )
                    .", domain_id='".$domain_id."', quota='".$quota."'"
                    .", enabled='".$enabled."'"
                    .", first_name='".$first_name."', surname='".$surname."'"
                    .", phone='".$phone."', other_email='".$other_email."', info='".$info."'";

            if ( isset($account_id) ) {
                sql_query( "UPDATE ".$sel." WHERE id=".$account_id );
                if ( $account_row['account'] != $imap_account ) {
                    sql_query( "UPDATE cyrup_aliases SET aliased_to='".$imap_account."' WHERE account_id=".$account_id );
                    cimap_renamemailbox( $account_row['account'], $imap_account );
                }
                if ( $account_row['quota'] != $quota ) {
                    if ( $quota ) cimap_setquota( $imap_account, $quota );
                    else cimap_delquota( $imap_account );
                }
            } else {
                sql_query( "INSERT INTO cyrup_accounts 
                             (account, password, domain_id, quota, enabled, first_name,
                              surname, phone, other_email, info)
                            VALUES 
                             ('".$imap_account."',".$password.",'".$domain_id."',
                              '".$quota."','".$enabled."','".$first_name."',
                              '".$surname."','".$phone."','".$other_email."','".$info."')" );
                $account_id = sql_insert_id('cyrup_accounts', 'id');
                cimap_createmailbox( $imap_account );
                cimap_createfolders( $imap_account );
                if ( $quota ) cimap_setquota( $imap_account, $quota );
                if ( isset($_POST['autoalias']) ) {
                    $alias = $account."@".$domain_row['domain'];
                    if ( $domain_row['aliases_max'] AND $domain_row['aliases_max'] <= $domain_row['aliases_cur'] ) $cannot_create_aliases = 1;
                    sql_query( "SELECT 1 from cyrup_aliases WHERE domain_id='".$domain_id."'
                                    AND alias='".$alias."'
                                  UNION
                                SELECT 1 from cyrup_maillists WHERE domain_id='".$domain_id."'
                                    AND alias='".$alias."'");
                    if ( 0 == sql_num_rows() AND !isset($cannot_create_aliases) )
                        sql_query ( "INSERT INTO cyrup_aliases 
                                        (account_id,domain_id,enabled,alias,aliased_to) 
                                      VALUES 
                                        ('".$account_id."','".$domain_id."','1',
                                         '".$alias."','".$imap_account."')" );
                }
            }
	    header( "Location: ".BASE_URL."/?admin&m=accounts" );
	    exit;
	}
    }

    print_header(VERSION.": Account form");
    print_top_menu();
    print "<script type=\"text/javascript\" src='".JS_URL."/functions.js' language=\"JavaScript\"></script>\n";

    $mailbox_suffix = get_mailbox_suffix( $domain_row );

    if ( (isset( $_GET['id'])) AND (0 != intval($_GET['id'])) ) {
        sql_query( "SELECT * FROM cyrup_accounts WHERE id=".intval($_GET['id'])." AND domain_id='".$domain_id."'"  );
        $row = sql_fetch_array(); 
        if ( $row ) $account_id = $row['id'];
    }

    print "<center>\n";
    print "<form action='".BASE_URL."/?admin&m=accountform".(isset($account_id)?"&id=".$account_id:"");
    print "' name=form method=POST onSubmit='javascript:document.form.account.disabled=false;'>";
    print "<input type=hidden name='action' value='action'>";

    if ( isset( $account_id ) ) {
        print "<input type=hidden name=id value=".$account_id.">\n";
        $account_real = $row['account'];
        $row['account'] = get_mailbox_local_part( $domain_row, $row['account'] );
        $row['quota'] >>= 10;
    } else {
        ( isset($account) ? $row['account'] = $account : $row['account'] = '' );
        if ( isset($_POST['quota_mb']) )
            $row['quota'] = intval($_POST['quota_mb']);
    	else
            $row['quota'] = DEFAULT_QUOTA;
        ( isset($first_name) ? $row['first_name'] = $first_name : $row['first_name']="" );
        ( isset($surname) ? $row['surname'] = $surname : $row['surname'] = "" );
        ( isset($phone) ? $row['phone'] = $phone : $row['phone'] = "" );
        ( isset($other_email) ? $row['other_email']=$other_email:$row['other_email']="" );
        ( isset($enabled) ? $row['enabled'] = $enabled : $row['enabled'] = 1 );
        ( isset($info) ? $row['info'] = $info : $row['info'] = "" );
        $row['password'] = "";
    }

    print "<table align=center border=0 cellpadding=0 cellspacing=0>\n";
    dotline( 2 );
    print "<tr class=highlight>\n<td colspan=2 align=center>";
    print (isset($account_id) ? "<b>Edit account</b>" : "<b>Add account</b>"); 
    print "</td></tr>\n";
    dotline( 2 );
    print "<tr>\n<td>&nbsp; Active? &nbsp;</td>\n<td>\n";
    print "<input type=checkbox name='enabled' ".( $row['enabled'] == 1 ? " checked" : "" );
    print "></td>\n</tr>\n";
    dotline( 2 );
    print "<tr>\n<td>&nbsp; Domain &nbsp;</td>\n";
    print "<td>&nbsp;".$domain_row['domain']."</td>\n</tr>\n";
    dotline( 2 );
    print "<tr>\n<td>&nbsp; Account name &nbsp;</td>\n<td>\n";
    print "<input type=hidden name=old_account value='".$row['account']."'>\n";
    print "<input type=text name=account size=15";
    if ( isset($account_id) ) 
        print " disabled onBlur='javascript:checkInput()' value='".$row['account']."'";
    print ">".$mailbox_suffix."<br>" ;
    if ( isset($account_id) ) 
       	print "<input type=button value='Rename' onClick='javascript:enableRename();'>";
    print "</td>\n</tr>\n";
    dotline( 2 );
    print "<tr>\n<td>&nbsp; Password ".(SHOW_PASSWORD ? "":"(twice)")."&nbsp;</td>\n<td>";
    if ( SHOW_PASSWORD ) {
        print "<input type='text' name='password_' size=10 value='".$row['password']."'>";
        print "<input type='hidden' name='password_retype'>";
    } else {
        print "<input type='password' name='password_' size=10><br>\n";
        print "<input type='password' name='password_retype' size=10>&nbsp;";
        print ( isset( $account_id ) ? "(Leave empty if no change) " : "" );
    }
    print "</td>\n</tr>\n";
    dotline( 2 ); 
    html_input_text( "quota_mb", "Quota", $row['quota'], "Mb", 5 );
    dotline( 2 );
    html_input_text( "first_name", "First name", $row['first_name'] );
    dotline( 2 );
    html_input_text( "surname", "Surname", $row['surname'] );
    dotline( 2 );
    html_input_text( "phone", "Phone", $row['phone'] );
    dotline( 2 );
    html_input_text( "other_email", "Other email", $row['other_email'] );
    dotline( 2 );
    print "<tr>\n<td>&nbsp; Info &nbsp;</td>\n<td>";
    print "<textarea name='info' cols='24' rows='4'>".$row['info']."</textarea></td>\n";
    print "</tr>\n";
    dotline( 2 );
    if ( isset($account_id) ) {
        print "<tr>\n<td>&nbsp; Aliases &nbsp;</td>\n<td>";
        sql_query( "SELECT id,alias,enabled FROM cyrup_aliases 
						WHERE domain_id='".$domain_id."' 
						    AND account_id='".$account_id."'" );
        while ( $row = sql_fetch_array() ) {
            print "<a href='?admin&m=aliasform&id=".$row['id']."&account_id=".$account_id."'";
            print ">&nbsp;".$row['alias']."</a> ";
            print ( $row['enabled'] ? "(active)" : "(not active)" );
            print "<br>\n";
        }
        print "&nbsp;<a href='?admin&m=aliasform&account_id=".$account_id."' ";
        print "class='button'>[ Add new ]</a><br>\n";
        print "</td>\n</tr>\n";
        dotline( 2 );
        print "<tr>\n\t<td>&nbsp; Maillists &nbsp;</td>\n\t<td>";
        $res = sql_query( "SELECT id FROM cyrup_aliases WHERE domain_id='".$domain_id."'
                                                    AND account_id='".$account_id."'" );
        while ( $row = sql_fetch_array($res) )
            print_maillist_list( $row['id'] );
        print "\t</td>\n</tr>\n"; 
        if ( SIEVE ) {
            dotline( 2 );
            print "<tr>\n\t<td>&nbsp; Autoreply &nbsp;</td>\n\t<td>";
            print "<a href='?admin&m=vacationform&account_id=".$account_id."'>";
            $vacation = getVacation($account_real);
            print ( empty($vacation[0]) ? 'set' : 'edit')."</a></td></tr>\n";
        }
    } else {
        print "<tr>\n<td>&nbsp; Autocreate alias? &nbsp;</td>\n<td>\n";
        print "<input type=checkbox name=autoalias checked>\n</td>\n</tr>\n";
    }
    dotline( 2 );
    if ( (isset($errors)) AND (sizeof($errors)) ) {
        print "<tr class=highlight>
        <td colspan=2 align=center>";
        print_errors( $errors ); 
        print "</tr>";
        dotline( 2 ); 
    } 
    print "<tr>\n<td>&nbsp;</td>\n<td>";
    if ( isset($account_id) ) 
	print "<input type='submit' value='Update'>";
    else
	print "<input type='submit' value='Add new'>"; 
    print "</td>\n</tr>\n</table>\n</form><br />\n";
    print "<form name='generator'>\nGenerate password:";
    print "<input type='text' name='password' size='12'>\n";
    print "<input type=button value='Generate' onClick='document.forms[1].password.value=getPassword(8,\"\",true,true,true,false,true,true,true,false);'>";
    print "</form>\n";
    print_footer();

?>
