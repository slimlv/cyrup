<?php
/*
 * $RCSfile: login.php,v $ $Revision: 1.4 $
 * $Author: slim_lv $ $Date: 2016/11/01 14:09:36 $
 * This file is part of CYRUP project
 * by Yuri Pimenov (up@msh.lv) & Deniss Gaplevsky (slim@msh.lv)
 */

    if ( !defined("INCLUDE_DIR") ) exit("Not for direct run");

    if ( isset( $_POST['login'] ) ) {
        $login    = addslashes( $_POST['login'] );
        $password = addslashes( $_POST['password'] );
        sql_query( "SELECT rights FROM cyrup_admins WHERE username='".$login."' 
				AND password=".get_sql_crypt($password) );
        if ( $row = sql_fetch_array() ) {
	    if ( ( $login != ADMIN_USER ) AND ( trim($row['rights']) == "" ) ) {
		$errors = array();
		array_push( $errors, "Not enough rights" );
	    } else {
		$_SESSION['USER'] = $login;
		$_SESSION['RIGHTS'] = $row['rights'];
		session_write_close();
		header( "Location: ".BASE_URL."/?admin" );
		exit;
	    };
	} else {
	    $errors = array();
	    array_push( $errors, "Wrong account name or password" );
	};
    };

    print_header( "Please authorize yourself" );

?>
<br /><br /><br />
<br /><br /><br />
<form action='<?=BASE_URL?>/?admin' method=POST>
<center>
<img src='<?php print IMAGES_URL; ?>/logo.gif'><br>
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
        if ( isset($errors) ) {
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
</center>
</form>

<?php print_footer(); ?>
