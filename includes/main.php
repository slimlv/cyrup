<?php

    if ( !defined("INCLUDE_DIR") ) exit("Not for direct run");

    print_header( TITLE."Password Change" );

    if ( isset($_GET['chpass']) ) {
        $account = trim($_POST['account']);
        sql_query( "SELECT id FROM cyrup_accounts WHERE account=".sql_escape($account)." AND password=".get_sql_crypt($_POST['old_password']) );
	$row = sql_fetch_array();
        $errors = [];
        if ( !$row || CYRUS_USER == $account ) $errors[] = "Wrong account name or password";
        if ( $_POST['new_password'] != $_POST['new_password_retype'] ) $errors[] = "New password and retyped new password do not match";
        if ( strlen( $_POST['new_password'] ) < MIN_PASSWORD_LENGTH ) $errors[] = "Minimal password length is ".MIN_PASSWORD_LENGTH;
        if ( empty($errors) ) {
            sql_query( "UPDATE cyrup_accounts SET password=".get_sql_crypt($_POST['new_password'])." WHERE id=".$row['id']);
           $errors[] = "Password successfully changed";
        }
    }

?>
<br /><br /><br /><br />
<form action="?chpass" method="POST">
<center>
<img src='<?=IMAGES_URL;?>/logo.gif'>
<br /><br />
<table align=center width="0%" border=0 cellpadding=0 cellspacing=0>
    <?php dotline( 2, "./" ); ?>
    <tr class=highlight><td colspan=2 align=center><b>Password Change</b><td></tr>
    <?php dotline( 2 ); ?>
    <tr>
	<td>&nbsp; Account &nbsp; </td>
	<td><input type=text name=account
	    <?php print (isset($_POST['account']) ? "value='".htmlspecialchars($_POST['account'])."'":""); ?>
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
	if ( !empty($errors) ) {
	    print "<tr class=highlight>
	    <td colspan=2 align=center>";
	    print_errors( $errors ); 
	    print "</tr>";
	    dotline( 2 ); 
	}
    ?>
    <tr><td> &nbsp; </td><td><input type=submit value='  Ok  '></td></tr>
    <tr><td colspan=2 align=center>Admins press <a href="?admin">here</a><td></tr>
</table>
</center>
</form>

<?php
    print_footer();
