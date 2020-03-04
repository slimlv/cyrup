<?php

    if ( !defined("INCLUDE_DIR") ) exit("Not for direct run");
    $errors = [];

    if ( !empty($_POST['login']) ) {
        sql_query( "SELECT username,rights FROM cyrup_admins WHERE username=".sql_escape(trim($_POST['login']))." AND password=".get_sql_crypt($_POST['password']) );
        if ( $row = sql_fetch_array() ) {
	    if ( $row['username'] != ADMIN_USER && empty(trim($row['rights'])) ) {
		$errors[] = "Not enough rights";
	    } else {
		$_SESSION['USER'] = $row['username'];
		$_SESSION['RIGHTS'] = $row['rights'];
		session_write_close();
		header( "Location: ".BASE_URL."?admin" );
		exit;
	    }
	} else {
	    $errors[] = "Wrong account name or password";
	}
    }

    print_header( "Please authorize yourself" );

?>
<br /><br /><br />
<br /><br /><br />
<form action='<?=BASE_URL?>?admin' method=POST>
<center>
<img src='<?=IMAGES_URL?>/logo.gif'><br>
<table align=center border=0 cellpadding=0 cellspacing=0>
    <?php dotline( 2 ); ?>
    <tr>
	<td><b>Login</b> &nbsp; </td>
	<td><input type=text name=login></td>
    </tr>
    <?php dotline( 2 ); ?>
    <tr>
	<td><b>Password</b> &nbsp; </td>
	<td><input type=password name=password></td>
    </tr>
<?php	dotline( 2 ); 
        if ( !empty($errors) ) {
            print "<tr class=highlight><td colspan=2 align=center>";
            print_errors( $errors ); 
            print "</tr>";
            dotline( 2 ); 
        }
?>
    <tr><td> &nbsp; </td><td><input type=submit value='  Ok  '></td></tr>
</table>
</center>
</form>

<?php 
    print_footer();
