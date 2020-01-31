<?php
/*
 * $RCSfile: domains.php,v $ $Revision: 1.7 $
 * $Author: slim_lv $ $Date: 2016/11/01 14:09:36 $ 
 * This file is part of CYRUP project
 * by Yuri Pimenov (up@msh.lv) & Deniss Gaplevsky (slim@msh.lv)
 */

    if ( !defined('INCLUDE_DIR') ) exit("Not for direct run");
    if ( ($_SESSION['USER'] == ADMIN_USER) AND (isset($_POST['confirm']) ) ) {
        require_once( INCLUDE_DIR."/imap.inc.php" );

        $sel = chks2sql( 'domain_id' );
        if ( $sel != "" ) {
            sql_query( "SELECT * FROM cyrup_accounts WHERE ".$sel );
            while ( $row = sql_fetch_array() )
            	cimap_deletemailbox( $row['account'] );
            sql_query( "DELETE FROM cyrup_accounts WHERE ".$sel );
            sql_query( "DELETE FROM cyrup_aliases WHERE ".$sel );
            sql_query( "DELETE FROM cyrup_default_rcpt WHERE ".$sel );
            sql_query( "DELETE FROM cyrup_maillists WHERE ".$sel );
        }

        $sel = chks2sql( 'id' );
        if ( !empty($sel) )
            sql_query( 'DELETE FROM cyrup_domains WHERE '.$sel );
        if ( defined('DOMAIN_EXPORT_FILE') AND DOMAIN_EXPORT_FILE != '' )
            sql_export( 'SELECT domain FROM cyrup_domains WHERE enabled=1' , DOMAIN_EXPORT_FILE );
        if ( defined('SYSTEM_ALIASES') AND SYSTEM_ALIASES != '' ) 
            mksysaliases(SYSTEM_ALIASES);
    }


    print_header( TITLE."Domains" );
    print_top_menu();
    print "<script type=\"text/javascript\" src='".JS_URL."/functions.js' language=\"JavaScript\"></script>\n";

    $order_by = get_order_by("domains_order_by");
    $colspan = 9;

    if ( $_SESSION['USER'] == ADMIN_USER ) {
	print "<form name=form method=POST action='".BASE_URL."/?admin&m=domains'>\n";
	print "<input type=hidden name=action value=''>\n";
    };
    print "<table width=100% border=0 cellpadding=0 cellspacing=0>\n";
    dotline( $colspan );

    print "<tr>\n";
    print "<th width=1><input type=checkbox name=chkChangeAll onClick='check_boxes()'></th>\n";
    html_th( "domain", "Domain", "Domain name" );
    html_th( "enabled", "Active", "Permit to receive mails for the domain");
    html_th( "accounts_max", "Accounts / Max", 'Accounts in use / Maximum allowed in domain' );
    html_th( "aliases_max", "Aliases / Max", 'Aliases in use / Maximum allowed in domain' );
    html_th( "quota", "Quota current / Max ", 'Quota in use / Maximum allowed in domain' );
    if ( MAILBOX_STYLE == "USERSUFFIX" )
        html_th( "account_suffix", "Acc. suffix" );
    html_th( "aliased_to", "Def. email", "Unknown recipient's catcher" );
    html_th( "owner", "Owner" );
    html_th( "info", "Info" );
    print "</tr>\n";

    dotline( $colspan );
    print "<tr class=highlight><td colspan=".$colspan." align=center>";
    if ( $_SESSION['USER'] == ADMIN_USER ) 
	print "<a href='?admin&m=domainform' class=button>[ Add new ]</a>";
    else
	 print "&nbsp;";
    print "</td></tr>\n";
    dotline( $colspan );

    $query = "SELECT id FROM cyrup_domains a 
			    LEFT JOIN cyrup_default_rcpt b ON a.id=b.domain_id ".
			    rights2sql(1,"a.id"). 
			   " ORDER BY ".$order_by;
    $domains_res = sql_query( $query );
    $i = 0;
    while ( $row = sql_fetch_array( $domains_res ) ) {
	$domain_row = get_domain_info($row['id']);
        $i++;
	print "<td><input type=checkbox name='chks[".$i."]'>\n";
	if ( $_SESSION['USER'] == ADMIN_USER ) {
	    print "<input type=hidden name='ids[".$i."]' value='".$row['id']."'></td>\n";
	    print "<td>&nbsp;<a href='?admin&m=domainform&id=".intval($row['id'])."'>".$domain_row['domain']."</a>";
	} else {
	    print "</td>\n<td>&nbsp;<a href='#'>".$domain_row['domain']."</a>";
	}
	print "</td>\n<td align='center'>".($domain_row['enabled'] ? 'Y' : 'N')."</td>\n";
        print "<td align=center>&nbsp;".$domain_row['accounts_cur']."/".$domain_row['accounts_max']."</td>\n";
	print "<td align=center>&nbsp;".$domain_row['aliases_cur']."/".$domain_row['aliases_max']."</td>\n";
        print "<td align=center>&nbsp;".kb2mb( $domain_row['quota_cur'] )."/"
            .( $domain_row['quota'] ? kb2mb( $domain_row['quota'] ) : "no-quota" )."</td>\n";
        if ( MAILBOX_STYLE == "USERSUFFIX" )
            print "<td align=center>&nbsp;".$domain_row['account_suffix']."</td>\n";
        print "<td align=center>&nbsp;".$domain_row['default_rcpt']."</td>\n";
	if ( $owner = get_domain_owner($row['id']) ) {
	    sql_query( "SELECT username FROM cyrup_admins WHERE id = '".$owner."'" );
	    if ( $_SESSION['USER'] == ADMIN_USER )
		$owner = "<a href='?admin&m=adminform&id=".$owner."'>".sql_fetch_variable()."</a>";
	    else
		$owner = "<a href='?admin&m=service'>".sql_fetch_variable()."</a>";
	};
	print "<td align=center>&nbsp;".$owner."</td>\n";
        print "<td align=right>&nbsp;".$domain_row['info']."</td>\n";
        print "</tr>\n";
        dotline( $colspan );
    };
    print "</table>\n";
    if ( $_SESSION['USER'] == ADMIN_USER ) {
	if ( $i >= 1 ) 
	    print "<br><br>\n".delete_selected_box();
	print "</form>\n";
    };
    print_footer();

?>
