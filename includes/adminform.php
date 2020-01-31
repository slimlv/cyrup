<?php
/*
 * $RCSfile: adminform.php,v $ $Revision: 1.4 $
 * $Author: slim_lv $ $Date: 2016/11/01 14:09:36 $
 * This file is part of CYRUP project
 * by Yuri Pimenov (up@msh.lv) & Deniss Gaplevsky (slim@msh.lv)
 */

    if ( !defined("INCLUDE_DIR") ) exit("Not for direct run"); 
    if ( $_SESSION['USER'] != ADMIN_USER ) {
	header( "Location: ".BASE_URL."/?admin" );
	exit;
    };

    if ( isset($_POST['action']) ) {
	$errors = array();

	if ( (isset( $_POST['id'])) AND (0 != intval($_POST['id'])) ) {
	    sql_query( "SELECT * FROM cyrup_admins WHERE id='".intval($_POST['id'])."'
					    AND username='".addslashes($_POST['username'])."'");
	    if ( 1 == sql_num_rows() ) {
		$admin_id = intval($_POST['id']);
		$admin_row = sql_fetch_array();
	    } else { 
		array_push( $errors, "This admin absent" );
	    };
	};

	$username    = strtolower(trim(preg_replace("/[^\w-]/","",$_POST['username'])));
	$info       = addslashes(htmlspecialchars( $_POST['info'],ENT_QUOTES ));

	// Check admins name
	if  ( !preg_match("/^([\w-])+$/",trim($_POST['username'])) )
	    array_push( $errors, "Admin's name empty or  contains illegal symbols" );

	// Check overwriting of the admin
	if ( !isset( $admin_id ) ) {
	    $query = "SELECT 1 FROM cyrup_admins WHERE username='".$username."'";
	    if ( sql_fetch_array( sql_query( $query ) ) )
		array_push( $errors, "Admin with this name already exists" );
	};

        // Check password
        $change_password = true;
        if ( empty($_POST['password_a']) AND ((!isset($admin_id)) OR (SHOW_PASSWORD)) ) {
	    array_push( $errors, "Empty password" );
	} else {
	    if ( ($_POST['password_a'] != $_POST['password_retype']) AND (!SHOW_PASSWORD) )
		array_push( $errors, "Passwords do not match" );
	    if ( $_POST['password_a'] == "" )
		$change_password = false;
	};


	if ( sizeof($errors) == 0 ) {

	    $password = get_sql_crypt( $_POST['password_a'] );

            if ( isset($admin_id) ) 
                sql_query( "UPDATE cyrup_admins SET rights='".implode( ",", chks2array() )."',
					info='".$info."'"
					.( $change_password ? ", password=".$password : "" )
					." WHERE id='".$admin_id."'" );
            else 
		sql_query( "INSERT INTO cyrup_admins
				(username, password, rights, info)
				VALUES 
				('".$username."',".$password.",'"
				.implode( ",", chks2array() )."','".$info."')" );

	    header( "Location: ".BASE_URL."/?admin&m=admins" );
	    exit;
	};
    };

    print_header(VERSION.": Admins form");
    print_top_menu();
    print "<script type=\"text/javascript\" src='".JS_URL."/functions.js' language=\"JavaScript\"></script>\n";

    if ( (isset( $_GET['id'])) AND (0 != intval($_GET['id'])) ) {
	sql_query( "SELECT * FROM cyrup_admins WHERE id='".intval($_GET['id'])."'");
	if ( 1 == sql_num_rows() ) {
	    $admin_id = intval($_GET['id']);
            $row = sql_fetch_array();
	};
    };

    print "<center>\n";
    print "<form action='".BASE_URL."/?admin&m=adminform".(isset($admin_id)?"&id=".$admin_id:"");
    print "' name=form method=POST onSubmit='javascript:document.form.account.disabled=false;'>\n";
    print "<input type=hidden name='action' value='action'>\n";

    if ( isset( $admin_id ) ) {
        print "<input type=hidden name=id value='".$admin_id."'>\n";
	print "<input type=hidden name=username value='".$row['username']."'>\n";
    } else {
	( isset($username) ? $row['username'] = $username : $row['username'] = "" );
	( isset($info) ? $row['info'] = $info : $row['info'] = "" );
	$row['password'] = "";
    };

    print "<table align=center border=0 cellpadding=0 cellspacing=0>\n";
    dotline( 2 );
    print "<tr class=highlight>\n<td colspan=2 align=center>";
    print (isset($admin_id) ? "<b>Edit the Admin's Profile</b>" : "<b>Add admin</b>"); 
    print "</td></tr>\n";
    dotline( 2 );
    print "<tr>\n<td>&nbsp; Admin's name &nbsp;</td>\n<td>\n";
    if ( isset($admin_id) )
	print $row['username'];
    else 
	print "<input type=text name=username size=15";
    print "</td>\n</tr>\n";
    dotline( 2 );
    print "<tr>\n<td>&nbsp; Password ".(SHOW_PASSWORD ? "":"(twice)")."&nbsp;</td>\n<td>";
    if ( SHOW_PASSWORD ) {
	print "<input type='text' name='password_a' size=10 value='".$row['password']."'>";
	print "<input type='hidden' name='password_retype'>";
    } else {
	print "<input type='password' name='password_a' size=10><br>\n";
	print "<input type='password' name='password_retype' size=10>&nbsp;";
        print ( isset( $admin_id ) ? "(Leave empty if no change) " : "" );
    };
    print "</td>\n</tr>\n";
    dotline( 2 ); 
    print "<tr>\n<td>&nbsp; Info &nbsp;</td>\n<td>";
    print "<textarea name='info' cols='24' rows='4'>".$row['info']."</textarea></td>\n";
    print "</tr>\n";
    dotline( 2 );
    print "<tr>\n<td>&nbsp; Owned domains &nbsp;</td>\n<td>";
    if ( (isset($admin_id)) AND ( $row['username'] == ADMIN_USER) ) {
	    print "<a href='?admin&m=domains'>all domains</a>";
    } else {
	$result = sql_query( "SELECT id,domain FROM cyrup_domains ORDER BY domain" );
	$i = 0;
	while ( $row_dom = sql_fetch_array($result) ) {
	    $i++;
	    print "<input type=hidden name='ids[".$i."]' value='".$row_dom['id']."'>\n";
	    print "<input type=checkbox name='chks[".$i."]' ";
	    if ( $owner = get_domain_owner($row_dom['id']) ) {
		if ( (isset($admin_id)) AND ($owner == $admin_id) ) 
		    print "checked";
		else
		    print "disabled";
	    };
	    print ">\n<a href='?admin&m=domainform&id=".intval($row_dom['id'])."'>";
	    print $row_dom['domain']."</a><br />\n";
	};
    };
    dotline( 2 );
    if ( (isset($errors)) AND (sizeof($errors)) ) {
        print "<tr class=highlight>
        <td colspan=2 align=center>";
        print_errors( $errors ); 
        print "</tr>";
        dotline( 2 ); 
    }; 
    print "<tr>\n<td>&nbsp;</td>\n<td>";
    if ( isset($admin_id) ) 
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
