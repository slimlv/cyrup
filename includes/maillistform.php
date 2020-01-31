<?php
/*
 * $RCSfile: maillistform.php,v $ $Revision: 1.7 $
 * $Author: slim_lv $ $Date: 2016/11/01 14:09:36 $
 * This file is part of CYRUP project
 * by Yuri Pimenov (up@msh.lv) & Deniss Gaplevsky (slim@msh.lv)
 */

    if ( !defined("INCLUDE_DIR") ) exit("Not for direct run");

    if (!isset($_SESSION['domain_id'])){
        header( "Location: ".BASE_URL."/?admin" );
        exit;
    };

    $domain_id = $_SESSION['domain_id'];
    $domain_row = get_domain_info( $domain_id );

    if ( isset($_POST['action']) ) {
	
	$errors = array();
	settype( $members, "array" );
	$enabled  = ( isset($_POST['enabled']) ? 1 : 0 );
	if ( (isset( $_POST['id'])) AND (0 != intval($_POST['id'])) ) {
	    sql_query( "SELECT * FROM cyrup_maillists WHERE id='".intval($_POST['id'])."'
                                                                AND domain_id='".$domain_id."'"  );
	    if ( 1 == sql_num_rows() ) 
		$maillist_id = intval($_POST['id']);
	    else 
		array_push( $errors, "This maillist absent" );
        };
        // Check maillist name
	if ( isset($_POST['alias']) AND !preg_match('/^([\w\-\.])+$/',$_POST['alias']) )
            array_push( $errors, "Alias empty or contains illegal symbols" );
        else
            $alias = strtolower( trim(preg_replace('/[^\w\-\.]/','',$_POST['alias'])) )."@".$domain_row['domain'];

	// Check new alias to be already exist
	$query = "SELECT 1 FROM cyrup_maillists WHERE alias='".$alias."'";
	if ( isset($maillist_id) ) 
	    $query .= " AND id<>'".$maillist_id."'";
	if ( 0 < sql_num_rows(sql_query($query)) )
	    array_push( $errors, "Maillist with this name already exists" );
	$query = "SELECT 1 FROM cyrup_aliases WHERE alias='".$alias."'";
	if ( 0 < sql_num_rows(sql_query($query)) )
	    array_push( $errors, "Alias with this name already exists" );

	// Check for correct members
	if ( (!isset($_POST['members'])) OR (0 == count($_POST['members'])) ) {
	    array_push( $errors, "No members" );
	} else {
	    if ( count(array_filter($_POST['members'],"verify_email")) )
		$members = implode( ",", array_filter($_POST['members'],"verify_email") );
	    else 
		array_push( $errors, "No valid members" );
	};

	if ( (!isset($maillist_id)) AND ($domain_row['aliases_max'])
        AND ($domain_row['aliases_cur'] >= $domain_row['aliases_max']) )
	    array_push( $errors, "Maximum number of aliases for the domain reached" );

	if ( sizeof($errors) == 0 ) {

	    $query = "cyrup_maillists SET domain_id='".$domain_id."', enabled='".$enabled."',
                                                alias='".$alias."', aliased_to='".addslashes($members)."'";

	    if ( isset($maillist_id) )
		$query = "UPDATE ".$query." WHERE id='".$maillist_id."'";
	    else
		$query = "INSERT INTO cyrup_maillists
				(domain_id, enabled, alias, aliased_to)
				VALUES
				('".$domain_id."','".$enabled."','".$alias."',
				'".addslashes($members)."')";

            sql_query( $query );
	    header( "Location: ".BASE_URL."/?admin&m=maillists" );
	    exit;
	};

    };

    if ( (isset( $_GET['id'])) AND (0 != intval($_GET['id'])) ) {
	sql_query( "SELECT * FROM cyrup_maillists WHERE id='".intval($_GET['id'])."'
						AND domain_id='".$domain_id."'"  );
	if ( 1 == sql_num_rows() ) {
	    $maillist_id = intval($_GET['id']);
	    $row = sql_fetch_array();
	};
    };

    print_header(TITLE."Maillist form");
    print_top_menu();
    print "<script type=\"text/javascript\" src='".JS_URL."/functions.js' language=\"JavaScript\"></script>\n";
    print "<script type=\"text/javascript\" src='".JS_URL."/checkemail.js' language=\"JavaScript\"></script>\n";
    print "<center>\n<form name=form ";
    print "action='".BASE_URL."/?admin&m=maillistform".(isset($maillist_id)?"&id=".$maillist_id:"")."' method=POST>\n";
    print "<input type=hidden name=action>\n";

    if ( isset( $maillist_id ) ) {
	print "<input type=hidden name=id value='".$maillist_id."'>\n";
    } else {
	( isset($alias) ? $row['alias'] = $alias : $row['alias'] = "" );
        ( isset($aliased_to) ? $row['aliased_to'] = $aliased_to : $row['aliased_to'] = "" );
        ( isset($enabled) ?  $row['enabled'] = $enabled : $row['enabled'] = 1 );
    };

    print "<table align=center border=0 cellpadding=0 cellspacing=0>\n";
    dotline( 2 ); 
    print "<tr class=highlight>\n<td colspan=2 align=center>";
    print ( isset($maillist_id) ? "<b>Edit maillist</b>" : "<b>Add maillist</b>" );
    print "</tr>\n";
    dotline( 2 );
    print "<tr>\n<td>&nbsp; Active? &nbsp;</td>\n<td>";
    print "<input type=checkbox name='enabled'".( $row['enabled'] == 1 ? " checked" : "" ).">";
    print "</td>\n</tr>\n";
    dotline( 2 );
    html_input_text( "alias", "Alias (source)", get_alias_local($row['alias']), "@".$domain_row['domain'], 20 );
    dotline( 2 );
    print "<tr>\n<td>&nbsp; Members &nbsp;</td>\n<td>Current members of the list:<br>\n";
    print "<select name='members[]' style='width:200px;height:120px' multiple>\n";
    print "</select><br>\n";
    print "<input type=button value='Remove selected' onClick='javascript: removeSelectedMembers();return false;'>\n";
    print "<br>Add from existing e-mail addresses:<br>\n";
    print "<select style='width:150px' name='aliases'>\n";
    print "</select>\n";
    print "<input type=button value=Add onClick='javascript: moveToMembers();return false;'>\n";
    print "<br>Or enter another e-mail address:<br>\n";
    print "<input name=email type=text style='width:150px'>\n";
    print "<input type=button value=Add onClick='javascript:addEmailToMembers(document.forms[\"form\"].elements[\"email\"]);return false;'>\n";
    print "</td>\n</tr>\n";
    dotline( 2 );
    if ( (isset($errors)) AND (sizeof($errors)) ) {
        print "<tr class=highlight>
        <td colspan=2 align=center>";
        print_errors( $errors ); 
        print "</tr>";
        dotline( 2 ); 
    }; 
    print "<tr>\n<td>&nbsp;</td>\n<td>\n";
    print "<input type='submit' onClick='javascript:markAll(document.forms[\"form\"].elements[\"members[]\"])' value='";
    print ( isset($maillist_id) ? "Update" : "Add new" )."'></td>\n";
    print "</tr>\n</table>\n<br></form></center>\n";
    print "<script language='JavaScript'>\n//<!--\n";
    print "var cur_members = new Array("
        .( isset($maillist_id) ? "'".str_replace(",", "',\n\t\t\t'", $row['aliased_to'])."'" : "" ).");\n";

    $aliases_arr = array();
    sql_query( "SELECT * FROM cyrup_aliases WHERE enabled='1' AND domain_id='".$domain_id."'
						ORDER BY alias" );
    while ( $row = sql_fetch_array() )
        array_push( $aliases_arr, "'".$row['alias']."'" );

    print "var domain_aliases = new Array(".join( ",\n\t\t\t", $aliases_arr ).");\n";
    print "var all_members = domain_aliases;\n";
    print "init(document.forms['form'].elements['members[]'],document.forms['form'].elements['aliases']);\n";
    print "//--></script>\n";
    print_footer();

?>
