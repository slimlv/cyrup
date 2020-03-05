<?php
  defined("INCLUDE_DIR") || exit("Not for direct run");
  $errors = [];

  // Change admins passwords
  if ( !empty($_POST['old_password']) && !empty($_POST['new_password']) && !empty($_POST['new_password_retype']) ) {
    $account = $_SESSION['USER'];
    sql_query( "SELECT id FROM cyrup_admins WHERE username=".sql_escape($account)." AND password=" .get_sql_crypt($_POST['old_password']) ) );
    $row = sql_fetch_array(); 
    if ( !$row || CYRUS_USER == $account ) $errors[] = "Wrong login name or password";
    if ( $_POST['new_password'] != $_POST['new_password_retype'] ) $errors[] = "New password and retyped new password do not match";
    if ( strlen($_POST['new_password']) < MIN_PASSWORD_LENGTH ) $errors[] = "Minimal password length is ".MIN_PASSWORD_LENGTH;

    if ( !$errors ) {
      sql_query( "UPDATE cyrup_admins SET password=".get_sql_crypt($_POST['new_password'])." WHERE id=".$row['id']);
      $errors[] = "Password successfully changed";
    }
  }

?>
<br /><br /><center>
<form action='<?=BASE_URL?>?admin&m=service&chpass' method='POST'>
<table width="0%" border=0 cellpadding=0 cellspacing=0>
<?php dotline(2); ?>
<tr class="highlight"><td colspan=2 align="center"><b>Change admin's password</b></tr>
<?php dotline( 2 ); ?>
<tr><td>&nbsp; Login &nbsp; </td><td><input type=text name=account disabled value='<?=$_SESSION['USER']?>'></td></tr>
<?php dotline( 2 ); ?>
<tr><td>&nbsp; Old password &nbsp; </td><td><input type=password name=old_password></td></tr>
<?php dotline( 2 ); ?>
<tr><td>&nbsp; New password &nbsp; </td><td><input type=password name=new_password></td> </tr>
<?php dotline( 2 ); ?>
<tr><td>&nbsp; Retype new password &nbsp; </td><td><input type=password name=new_password_retype></td></tr>
<?php 
  dotline( 2 ); 
  if ( $errors ) {
    print "<tr class=highlight><td colspan=2 align=center>";
    print_errors( $errors ); 
    print "</tr>";
    dotline( 2 ); 
  } 
?>
<tr><td> &nbsp; </td><td><input type=submit value='  Ok  '></td></tr>
</table>
</form>
<br />
