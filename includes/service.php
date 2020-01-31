<?php
/*
 * $RCSfile: service.php,v $ $Revision: 1.3 $
 * $Author: slim_lv $ $Date: 2007/05/13 17:29:26 $
 * This file is part of CYRUP project
 * by Yuri Pimenov (up@msh.lv) & Deniss Gaplevsky (slim@msh.lv)
 */

    if ( !defined("INCLUDE_DIR") ) exit("Not for direct run");

    print_header( TITLE."Service" );
    print_top_menu();

    if ( $_SESSION['USER'] != ADMIN_USER ) 
	include_once( INCLUDE_DIR."/chpass.inc.php" );
    else 
	include_once( INCLUDE_DIR."/integrity.inc.php" );

    print_footer();
?>
