<?php
/*
 * $RCSfile: aliasform.php,v $ $Revision: 1.8 $
 * $Author: slim_lv $ $Date: 2016/11/01 14:09:36 $
 * This file is part of CYRUP project
 * by Yuri Pimenov (up@msh.lv) & Deniss Gaplevsky (slim@msh.lv)
 */

    if ( !defined('INCLUDE_DIR') ) exit('Not for direct run');
    if ( !isset($_SESSION['domain_id']) ) {
        header( "Location: ".BASE_URL."/?admin" );
        exit;
    }

    $domain_id = intval($_SESSION['domain_id']);
    $domain_row = get_domain_info( $domain_id );

    if ( isset($_POST['action']) ) {

      $errors = [];
      if ( (isset( $_POST['id'])) AND (0 != intval($_POST['id'])) ) {
        sql_query( "SELECT * FROM cyrup_aliases WHERE id='".intval($_POST['id'])."' AND domain_id=".$domain_id );
        if ( 1 == sql_num_rows() ) $alias_id = intval($_POST['id']);
        else array_push( $errors, "This alias is absent" );
      }
      $enabled = isset($_POST['enabled']) ? 1 : 0;

      // Check alias name
      if ( isset($_POST['alias']) AND !preg_match('/^([\w\-\.])+$/',$_POST['alias']) ) array_push( $errors, "Alias empty or contains illegal symbols" );
      else $alias = strtolower( trim(preg_replace('/[^\w\-\.]/','',$_POST['alias'])) )."@".$domain_row['domain'];

      // Check new alias to be already exist
      if ( isset($alias) ) {
        $query = "SELECT 1 FROM cyrup_aliases WHERE alias='".$alias."'";
        if ( isset($alias_id) ) $query .= " AND id<>".$alias_id;
        if ( 0 < sql_num_rows(sql_query($query)) ) array_push( $errors, "Alias with this name already exists" );
        $query = "SELECT 1 FROM cyrup_maillists WHERE alias='".$alias."'";
        if ( 0 < sql_num_rows(sql_query($query)) ) array_push( $errors, "Maillist with this alias already exist" );
      }

      // Check aliased to
      if ( $_POST['dest'] == "email" ) {
        $members = explode( "," , trim($_POST['aliased_to']) );
   	    if ( count(array_filter($members,"verify_email")) ) $aliased_to = implode( ",", array_filter($members,"verify_email") );
        else array_push( $errors, "No valid email adresses (emails must be separated by ',')" );
      } else {
        if ( !$_POST['account_id'] OR (0 == intval($_POST['account_id'])) ) {
	      array_push( $errors, "Destination account not selected" );
          $account_id = 0;
        } else {
          $account_id = intval($_POST['account_id']);
          sql_query( "SELECT id FROM cyrup_accounts WHERE id=".$account_id." AND domain_id=".$domain_id );
          if ( 1 != sql_num_rows() ) array_push( $errors, "Destination account absent" );
        }
      }

      if ( !isset($alias_id) AND $domain_row['aliases_max'] AND $domain_row['aliases_cur'] >= $domain_row['aliases_max'] ) array_push( $errors, "Maximum number of aliases for the domain reached" );

      if ( sizeof($errors) == 0 ) {

          if ( $_POST['dest'] == "account" ) {
		    sql_query( "SELECT account FROM cyrup_accounts WHERE id=".$account_id." AND domain_id=".$domain_id );
            $aliased_to = sql_fetch_variable();
          } else {
            $account_id = 0;
          }

          if ( isset( $alias_id ) )
                $query = "UPDATE cyrup_aliases SET account_id=".$account_id.", domain_id=".$domain_id.",
                                        enabled='".$enabled."',
                                        alias='".$alias."',
                                        aliased_to='".$aliased_to."'
			 	WHERE id=".$alias_id;
          else 
                $query = "INSERT INTO cyrup_aliases 
				(account_id,domain_id,enabled,alias, aliased_to)
				VALUES 
				(".$account_id.", ".$domain_id.", '".$enabled."', '".$alias."', '".$aliased_to."')";
          sql_query( $query );
          if ( !isset( $alias_id ) ) $alias_id = sql_insert_id('cyrup_aliases','id');
          if ( !$enabled ) remove_from_maillist($alias_id);
          header( "Location: ".BASE_URL."/?admin&m=aliases" );
          exit;
	   }
    }
    
    if ( isset( $_GET['id']) AND (0 != intval($_GET['id'])) ) {
      sql_query( "SELECT * FROM cyrup_aliases WHERE id=".intval($_GET['id'])." AND domain_id=".$domain_id );
      $row = sql_fetch_array();
      if ( $row ) $alias_id = $row['id'];
    }

    print_header( TITLE."Alias form" );
    print_top_menu();
    print "<script type=\"text/javascript\" src=\"".JS_URL."/functions.js\" language=\"JavaScript\"></script>\n";
    print "<center><form name=form method='POST' ";
    print " action='".BASE_URL."/?admin&m=aliasform".(isset($alias_id)?"&id=".$alias_id:"")."'>\n";
    print "<input type=hidden name=action>\n";

    if ( isset($alias_id) ) {
      print "<input type=hidden name=id value='".$alias_id."'>\n";
    } else {
      $row['alias'] = isset($alias) ? $alias : "";
      $row['aliased_to'] = isset($aliased_to) ? $aliased_to : "";
      $row['enabled'] = isset($enabled) ? $enabled : 1;
      if ( isset($_GET['account_id']) ) $row['account_id'] = intval($_GET['account_id']);
      else $row['account_id'] = "";
      if ( isset($account_id) ) $row['account_id'] = $account_id;
    }

    print "<table align=center border=0 cellpadding=0 cellspacing=0>\n";
    dotline( 2 ); 
    print "<tr class=highlight>\n<td colspan=2 align=center>\n";
    print ( isset($alias_id) ? "<b>Edit alias</b>" : "<b>Add alias</b>" ); 
    print "</tr>\n";
    dotline( 2 ); 
    print "<tr>\n<td>&nbsp; Active? &nbsp;</td>\n";
    print "<td><input type=checkbox name='enabled'";
    print ( $row['enabled'] == 1 ? " checked" : "" )."></td>\n</tr>\n";
    dotline( 2 );
    html_input_text("alias","Alias (source)",get_alias_local($row['alias']),"@".$domain_row["domain"],20);
    dotline( 2 );
    print "<tr>\n<td>&nbsp; Redirection to (destination) &nbsp;</td>\n<td>\n";
    print "<input type='radio' name='dest' value='email' onClick='javascript:switchInputField(0);'";
    print ( $row['account_id'] ? "" : " checked" ).">";
    print "&nbsp; Email: &nbsp;<input type='text' name='aliased_to' size='22' value='";
    print ( $row['account_id'] ? "' disabled" : $row['aliased_to']."'" )."><br>\n";
    print "<input type='radio' name='dest' value='account' onClick='javascript:switchInputField(1);'";
    print ( $row['account_id'] ? " checked" : "" ).">";
    print "&nbsp; Account: &nbsp;\n";
    print "<select name='account_id'".( $row['account_id'] ? "" : " disabled" ).">\n";
    print "<option value='0'>--- Select account here ---\n";

    sql_query( "SELECT * FROM cyrup_accounts WHERE domain_id=".$domain_id." ORDER BY account" );
    while ( $account_row = sql_fetch_array() ) {
      print "<option value='".$account_row['id'];
      print ( $row['account_id'] == $account_row['id'] ? "' selected" : "'" ).">";
      print $account_row['account']."</option>\n";
    }
    print "</select>\n";

    print "\t</td>\n</tr>";
    dotline( 2 );

    if ( isset($alias_id) ) {
      print "<tr>\n\t<td>&nbsp; Maillists &nbsp;</td>\n\t<td>";
      print_maillist_list( $alias_id );
      print "\t</td>\n</tr>\n"; 
      dotline( 2 );
    }
    if ( !empty($errors) ) {
        print "<tr class=highlight>
        <td colspan=2 align=center>";
        print_errors( $errors ); 
        print "</tr>";
        dotline( 2 ); 
    }

    print "<tr>\n<td>&nbsp;</td>\n<td>";
    print "<input type=submit value=";
    print ( isset( $alias_id ) ? "'Update'>" : "'Add new'>" ); 
    print "</td>\n</tr>\n</table>\n<br></form>\n";

    print_footer();
