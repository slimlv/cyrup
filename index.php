<?php
/*
 * $RCSfile: index.php,v $ $Revision: 1.2 $
 * $Author: slim_lv $ $Date: 2007/01/05 13:17:23 $ 
 * This file is part of CYRUP project
 * by Yuri Pimenov (up@msh.lv) & Deniss Gaplevsky (slim@msh.lv)
 */

    require_once( "config.inc.php" );
    require_once( INCLUDE_DIR."/functions.inc.php" );
    require_once( INCLUDE_DIR."/html.inc.php" );

    if ( is_readable(INCLUDE_DIR.'/'.DB_TYPE.'.inc.php') ) {
      require_once( INCLUDE_DIR.'/'.DB_TYPE.'.inc.php' );
    } else {
	  require_once( INCLUDE_DIR."/mysql.inc.php" );
    }

    if ( isset($_GET['admin']) ) {
        require_once( INCLUDE_DIR."/sessions.inc.php" );
        if ( ! isset( $_SESSION['USER'] ) ) {
           require( INCLUDE_DIR."/login.php" );
           exit;
        }
        if ( !isset($_GET['m']) ) $_GET['m'] = "domains";
        $file2include = INCLUDE_DIR."/".preg_replace('/[^a-z]*/','',$_GET['m']).".php";
        $file2include = is_readable($file2include) ? $file2include : INCLUDE_DIR.'/domains.php';
        require( $file2include );
    } else {
        require( INCLUDE_DIR."/main.php" );	
    }
