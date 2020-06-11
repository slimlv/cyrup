<?php

  defined("INCLUDE_DIR") || exit("Not for direct run"); 
  if ( $_SESSION['USER'] != ADMIN_USER ) {
    header( "Location: ".BASE_URL."?admin" );
    exit;
  }

  $errors = [];

  // use in form later
  $info = !empty($_POST['info']) ? trim($_POST['info']) : '';
  $username = !empty($_POST['username']) ? strtolower(trim($_POST['username'])) : '';

  if ( !empty($_POST['action']) ) {

       if ( !empty($_POST['id']) && $_POST['id'] == intval($_POST['id']) ) {
	    sql_query( "SELECT * FROM cyrup_admins WHERE id=".$_POST['id']." AND username=".sql_escape($username) );
	    if ( $admin_row = sql_fetch_array() ) {
		$admin_id = $admin_row['id'];
	    } else { 
		$errors[] = "This admin is absent";
	    }
	}

	// Check admins name
        if  ( empty($username) || !preg_match("/^([\w-])+$/",$username) ) {
           $errors[] = "Admin's name is empty or contains illegal symbols";
	}

	// Check overwriting of the admin
	if ( !isset($admin_id) && empty($errors) ) {
	    $query = "SELECT 1 FROM cyrup_admins WHERE username=".sql_escape($username);
            if ( sql_fetch_array( sql_query($query) ) ) $errors[] = "Admin with this name already exists";
	}

        // Check password
        $change_password = true;
        if ( empty($_POST['password_a']) && (!isset($admin_id) || SHOW_PASSWORD) ) {
	    $errors[] = "Empty password";
	} else {
	    if ( $_POST['password_a'] != $_POST['password_retype'] && !SHOW_PASSWORD )
		$errors[] = "Passwords do not match";
	    if ( empty($_POST['password_a']) )
		$change_password = false;
	}


        if ( empty($errors) ) {
            $password = get_sql_crypt( $_POST['password_a'] );

            if ( isset($admin_id) ) 
                sql_query( "UPDATE cyrup_admins SET rights='".implode( ",", chks2array() )."', info=".sql_escape($info)
					.( $change_password ? ", password=".$password : "" )
					." WHERE id=".$admin_id );
            else 
                sql_query( "INSERT INTO cyrup_admins
				(username, password, rights, info)
				VALUES 
				(".sql_escape($username).",".$password.",'"
				.implode( ",", chks2array() )."',".sql_escape($info).")" );

	    header( "Location: ".BASE_URL."?admin&m=admins" );
	    exit;
	}
  }

  print_header(VERSION.": Admins form");
  print_top_menu();
  print "<script type='text/javascript' src='".JS_URL."/functions.js' language='JavaScript'></script>\n";

  if ( !empty($_GET['id']) && $_GET['id'] == intval($_GET['id']) ) {
    sql_query( "SELECT * FROM cyrup_admins WHERE id=".$_GET['id']);
    if ( $row = sql_fetch_array() ) $admin_id = $row['id'];
  }

  print "<center>\n";
  print "<form action='?admin&m=adminform".(isset($admin_id)?"&id=".$admin_id:"");
  print "' name=form method=POST onSubmit='javascript:document.form.account.disabled=false;'>\n";
  print "<input type=hidden name='action' value='action'>\n";

  if ( isset( $admin_id ) ) {
    print "<input type=hidden name=id value='".$admin_id."'>\n";
    print "<input type=hidden name=username value='".$row['username']."'>\n";
  } else {
    $row['username'] = $username;
    $row['info'] = $info;
    $row['password'] = "";
  }

  print "<table align=center border=0 cellpadding=0 cellspacing=0>\n";
  dotline( 2 );
  print "<tr class=highlight>\n<td colspan=2 align=center>";
  print (isset($admin_id) ? "<b>Edit the Admin's Profile</b>" : "<b>Add admin</b>"); 
  print "</td></tr>\n";
  dotline( 2 );
  print "<tr>\n<td>&nbsp; Admin's name &nbsp;</td>\n<td>\n";
  print isset($admin_id) ? htmlspecialchars($row['username']) : "<input type=text name=username size=15";
  print "</td>\n</tr>\n";
  dotline( 2 );
  print "<tr>\n<td>&nbsp; Password ".(SHOW_PASSWORD ? "":"(twice)")."&nbsp;</td>\n<td>";
  if ( SHOW_PASSWORD ) {
    print "<input type='text' name='password_a' size=10 value='".htmlspecialchars($row['password'])."'>";
    print "<input type='hidden' name='password_retype'>";
  } else {
    print "<input type='password' name='password_a' size=10><br>\n";
    print "<input type='password' name='password_retype' size=10>&nbsp;";
    print ( isset( $admin_id ) ? "(Leave empty if no change) " : "" );
  }
  print "</td>\n</tr>\n";
  dotline( 2 ); 
  print "<tr>\n<td>&nbsp; Info &nbsp;</td>\n<td>";
  print "<textarea name='info' cols='24' rows='4'>".htmlspecialchars($row['info'])."</textarea></td>\n";
  print "</tr>\n";
  dotline( 2 );
  print "<tr>\n<td>&nbsp; Owned domains &nbsp;</td>\n<td>";
  if ( isset($admin_id) && $row['username'] == ADMIN_USER ) {
    print "<a href='?admin&m=domains'>all domains</a>";
  } else {
    $result = sql_query( "SELECT id,domain FROM cyrup_domains ORDER BY domain" );
    while ( $row_dom = sql_fetch_array($result) ) {
      print "<input type=checkbox name='ids[${row_dom['id']}]' value='${row_dom['id']}'";
      if ( $owner = get_domain_owner($row_dom['id']) ) {
        print (isset($admin_id) && $owner == $admin_id ? 'checked' : "disabled"); 
      }
      print ">\n<a href='?admin&m=domainform&id=${row_dom['id']}'>${row_dom['domain']}</a><br />\n";
    }
  }
  dotline( 2 );
  if ( $errors ) {
    print "<tr class=highlight><td colspan=2 align=center>";
    print_errors( $errors ); 
    print "</tr>";
    dotline( 2 ); 
  }
  print "<tr>\n<td>&nbsp;</td>\n<td>";
  print "<input type='submit' value='".(isset($admin_id) ? 'Update' : 'Add new' )."'>"; 
  print "</td>\n</tr>\n</table>\n</form><br />\n";
  print "<form name='generator'>\nGenerate password:";
  print "<input type='text' name='password' size='12'>\n";
  print "<input type=button value='Generate' onClick='document.forms[1].password.value=getPassword(8,\"\",true,true,true,false,true,true,true,false);'>";
  print "</form>\n";
  print_footer();

