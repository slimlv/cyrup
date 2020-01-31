<?php
/*
 * $RCSfile: admins.php,v $ $Revision: 1.3 $
 * $Author: slim_lv $ $Date: 2016/11/01 14:09:36 $
 * This file is part of CYRUP project
 * by Yuri Pimenov (up@msh.lv) & Deniss Gaplevsky (slim@msh.lv)
 */

    if ( !defined("INCLUDE_DIR") ) exit("Not for direct run");
    if ( $_SESSION['USER'] != ADMIN_USER ) {
	header( "Location: ".BASE_URL."/?admin" );
	exit;
    };

    print_header( TITLE."Admins" );
    print_top_menu();
    print "<script type=\"text/javascript\" src=\"".JS_URL."/functions.js\" language=\"JavaScript\"></script>\n";

    $order_by = get_order_by("admins_order_by");

    if ( (isset($_POST['confirm'])) AND (isset($_POST['action'])) ) {

	$sel = chks2sql();
        if ( $sel != "" )
	    sql_query( "DELETE FROM cyrup_admins WHERE username<>'".ADMIN_USER."' AND (".$sel.")" ); 
    };

    print "<form name=form method=POST action='".BASE_URL."/?admin&m=admins'>\n";
    print "<input type=hidden name=action value=''>\n";
    print "<table width='100%' border=0 cellpadding=0 cellspacing=0>\n";
    dotline( 5 );
    print "<tr>\n";
    print "<th width=1><input type=checkbox name=chkChangeAll onClick='check_boxes()'></th>\n";
    html_th( "username", "Username" );
    html_th( "rights", "Owned domains" );
    html_th( "info", "Info" );
    print "</tr>\n";
    dotline( 5 );
    print "<tr class=highlight><td colspan=5 align=center>";
    print "<a href='?admin&m=adminform' class=button>[ Add new ]</a></td></tr>\n";
    dotline( 5 );
    
    $result = sql_query( "SELECT * FROM cyrup_admins ORDER BY ".$order_by ); 
    $i = 0;
    while ( $row = sql_fetch_array($result) ) {
	$i++;
	print "<td width=1><input type=checkbox name='chks[".$i."]'>";
	print "<input type=hidden name='ids[".$i."]' value='".$row['id']."'></td>\n";
	print "<td>&nbsp;<a href=?admin&m=adminform&id=".$row['id'].">";
	print $row['username']."</a></td>\n";

	print "<td align=center>";
	if ( $row['username'] == ADMIN_USER ) {
	    print "<a href='?admin&m=domains'>Supervisor</a>";
	} else {
	    if ( trim($row['rights']) != "") {
		sql_query( "SELECT id,domain FROM cyrup_domains 
					WHERE id IN (".$row['rights'].") ORDER BY domain" );
		while ( $row_dom = sql_fetch_array() ) {
		    print "<a href='?admin&m=domainform&id=".intval($row_dom['id'])."'>";
		    print $row_dom['domain']."</a><br />";
		};
	    } else {
		print "&nbsp;";
	    };
	};
	print "</td>\n";

	print "<td align=right>&nbsp;".$row['info']."</td>\n";
	print "\n</tr>\n";
	dotline( 5 );
    };
    print "</table>\n";
    if ( $i >= 1 ) {
	print "<br><br>";
	delete_selected_box();
    };
    print "</form>";
    print_footer();

?>
