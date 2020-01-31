<?php

    require_once( "config.inc.php" );
    require_once( INCLUDE_DIR."/functions.inc.php" );
    require_once( INCLUDE_DIR."/html.inc.php" );

    if ( is_readable(INCLUDE_DIR.'/'.DB_TYPE.'.inc.php') ) {
      require_once( INCLUDE_DIR.'/'.DB_TYPE.'.inc.php' );
    } else {
      require_once( INCLUDE_DIR."/mpdo.inc.php" );
    }

    function pc( $level ) {
        if ($level == 1)       return "<font color='green'>OK</font>";
        else if ($level == 2 ) return "<font color='orange'>RISKY</font>";
        else if ($level == 3 ) return "<font color='orange'>BAD</font>";
    }

    print_header( TITLE.": Check script");
?>
    <table with=600>
    <tr>
	<td>This is "first run, one time" script for <a href=cyrup.sf.net>Cyrup</a>.<br>
	    Lets assume that http server with php support is running, cyrus imap and database are configured as described in INSTALL file.<br>
	    Please be sure to set correct values of "SQL_PASS", "CYRUS_PASS", "PASSWORD_CRYPT" and "MAILBOX_STYLE" in "config.inc.php" file. <br>
	    This script checks for some php variables and set "cyrus" users password to database<br>
	    You may safely delete this file after successeful run
	</td>
    </tr>
    <tr>
	<td>Checking PHP variables:
	</td>
    </tr>
    <tr>
        <td>display_errors <?=( ini_get('display_errors') ? "ON: ".pc(2) : "OFF: ".pc(1) )?><br>
	    register_globals <?=( ini_get('register_globals') ? "ON: ".pc(2) : "OFF: ".pc(1) )?><br>
	    magic_quotes_gpc <?=( ini_get('magic_quotes_gpc') ? "ON: ".pc(3) : "OFF: ".pc(1) )?><br>
	    magic_quotes_runtime <?=( ini_get('magic_quotes_runtime') ? "ON: ".pc(3) : "OFF: ".pc(1) )?><br>
	    allow_url_fopen <?=( ini_get('allow_url_fopen') ? "ON: ".pc(2) : "OFF: ".pc(1) ); ?><br>
	    sql.safe_mode <?=( ini_get('sql.safe_mode') ? "ON: ".pc(3) : "OFF: ".pc(1) ); ?><br>
        </td>
    </tr>
    <tr>
        <td>Setting "cyrus" users password according to CYRUS_PASS variable:
<?php
    
    sql_query( "UPDATE cyrup_accounts SET password=".get_sql_crypt( CYRUS_PASS )." WHERE account='cyrus'");
    if ( sql_affected_rows() == 1 )
	print (  sql_affected_rows() == 1 ? pc(1) : "Already set");
    print " </td> </tr> </table>";
    print_footer();

