<?php
/*
 * $RCSfile: logout.php,v $ $Revision: 1.2 $
 * $Author: slim_lv $ $Date: 2007/05/13 17:29:26 $ 
 * This file is part of CYRUP project
 * by Yuri Pimenov (up@msh.lv) & Deniss Gaplevsky (slim@msh.lv)
 */

    if ( !defined("INCLUDE_DIR") ) exit("Not for direct run");

    unset( $_SESSION["USER"] );
    session_destroy();

    header( "Location: ".BASE_URL );
    exit;
?>
