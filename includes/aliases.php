<?php
/*
 * $RCSfile: aliases.php,v $ $Revision: 1.6 $
 * $Author: slim_lv $ $Date: 2016/11/01 14:09:36 $
 * This file is part of CYRUP project
 * by Yuri Pimenov (up@msh.lv) & Deniss Gaplevsky (slim@msh.lv)
 */

    if ( !defined("INCLUDE_DIR") ) exit("Not for direct run");
    print_header( TITLE."Aliases" );
    print_top_menu();
    print "<script type=\"text/javascript\" src=\"".JS_URL."/functions.js\" language=\"JavaScript\"></script>\n";

    $order_by = get_order_by("aliases_order_by");
    $domain_id = get_domain_id();

    if ( (isset($_POST['confirm'])) AND (isset($_POST['action'])) AND ($domain_id) ) {

	$sel = chks2array();
	foreach ($sel as $val)
	   remove_from_maillist($val);
	$sel = chks2sql();
        if ( $sel != "" )
	    sql_query( "DELETE FROM cyrup_aliases WHERE ".$sel." 
						    AND domain_id='".$domain_id."'" );
    };

    print_domain_selection( $domain_id );

    if ( $domain_id ) {
        print "<form name=form method=POST action='".BASE_URL."/?admin&m=aliases'>\n";
        print "<input type=hidden name=action value=''>\n";
        print "<table width='100%' border=0 cellpadding=0 cellspacing=0>\n";
        dotline( 5 );
        print "<tr>\n";
        print "<th width=1><input type=checkbox name=chkChangeAll onClick='check_boxes()'></th>\n";
        html_th( "alias", "Alias" );
        html_th( "enabled", "SMTP", "Alias active?" );
        html_th( "account_id", "Email or account?" );
        html_th( "aliased_to", "Destination", "Aliased to" );
        print "</tr>\n";
        dotline( 5 );
        print "<tr class=highlight><td colspan=5 align=center>";
        print "<a href='?admin&m=aliasform' class=button>[ Add new ]</a></td></tr>\n";
        dotline( 5 );
   
 
        $query = "SELECT * FROM cyrup_aliases WHERE domain_id='".$domain_id."' ".sql_pager('alias').' ORDER BY '.$order_by;
        sql_query($query);
        $i = 0;
        while ( $row = sql_fetch_array() ) {
            $i++;
            print "<td width=1><input type=checkbox name='chks[".$i."]'>";
            print "<input type=hidden name='ids[".$i."]' value='".$row['id']."'></td>\n";
            print "<td>&nbsp;<a href=?admin&m=aliasform&id=".$row['id'].">";
            print $row['alias']."</a></td>\n";
            print "<td align=center>&nbsp;".( $row['enabled'] == 1 ? "Y" : "N")."</td>\n";
            print "<td align=center>&nbsp;".( $row['account_id'] ? "Account" : "Email" )."</td>\n";
            print "<td align=center>&nbsp;";
            print ( $row['account_id'] ? $row['aliased_to'] : $row['aliased_to'] );
            print "\t</td>\n</tr>\n";
            dotline( 5 );
        };
        print "</table>\n";
        if ( $i >= 1 ) {
            print "<br><br>";
            delete_selected_box();
        };
        print "</form>";
    };

    print_footer();

?>
