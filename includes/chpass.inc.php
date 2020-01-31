<?php
/*
 * $RCSfile: chpass.inc.php,v $ $Revision: 1.4 $
 * $Author: slim_lv $ $Date: 2016/11/01 14:09:36 $
 * This file is part of CYRUP project
 * by Yuri Pimenov (up@msh.lv) & Deniss Gaplevsky (slim@msh.lv)
 */

    if ( !defined("INCLUDE_DIR") ) exit("Not for direct run");

    // Change admins passwords
    if ( isset($_GET['chpass']) ) {
	$account = $_SESSION['USER'];
        $row = sql_fetch_array( 
	    sql_query( "SELECT id FROM cyrup_admins 
			WHERE username='".$account."' AND password="
			.get_sql_crypt( $_POST['old_password'] ) ) );
        $errors = array();
        if ( (!$row) OR ( CYRUS_USER == $account ) )
            array_push( $errors, "Wrong login name or password" );
        if ( $_POST['new_password'] != $_POST['new_password_retype'] )
            array_push( $errors, "New password and retyped new password do not match" );
        if ( strlen( $_POST['new_password'] ) < MIN_PASSWORD_LENGTH )
            array_push( $errors, "Minimal password length is ".MIN_PASSWORD_LENGTH );
        if ( sizeof($errors) == 0 ) {
            sql_query( "UPDATE cyrup_admins SET password="
                .get_sql_crypt( $_POST['new_password'] )." WHERE id='".$row['id']."'");
            array_push( $errors, "Password successfully changed" );
        }
    };

?>
<br /><br /><center>
<form action='<?=BASE_URL?>/?admin&m=service&chpass' method='POST'>
<table width="0%" border=0 cellpadding=0 cellspacing=0>
<?php dotline( 2, "./" ); ?>
<tr class="highlight">
    <td colspan=2 align="center">
    <b>Change admin's password</b>
</tr>
<?php dotline( 2 ); ?>
<tr>
    <td>&nbsp; Login &nbsp; </td>
    <td><input type=text name=account disabled
         <?php print "value='".$_SESSION['USER']."'"; ?>
    ></td>
</tr>
<?php dotline( 2 ); ?>
<tr>
    <td>&nbsp; Old password &nbsp; </td>
    <td><input type=password name=old_password></td>
</tr>
<?php dotline( 2 ); ?>
<tr>
    <td>&nbsp; New password &nbsp; </td>
    <td><input type=password name=new_password></td>
</tr>
<?php dotline( 2 ); ?>
<tr>
    <td>&nbsp; Retype new password &nbsp; </td>
    <td><input type=password name=new_password_retype></td>
</tr>
<?php dotline( 2 ); 
  if ( ( isset($errors) ) AND ( sizeof($errors) ) ) {
    print "<tr class=highlight>
        <td colspan=2 align=center>";
        print_errors( $errors ); 
    print "</tr>";
    dotline( 2 ); 
  }; 
?>
<tr>
    <td> &nbsp; </td>
    <td><input type=submit value='  Ok  '></td>
</tr>
</table>
</form>
<br />
