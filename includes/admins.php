<?php
  defined("INCLUDE_DIR") || exit("Not for direct run");
  if ( $_SESSION['USER'] != ADMIN_USER ) {
    header( "Location: ".BASE_URL."?admin" );
    exit;
  }

  print_header( TITLE."Admins" );
  print_top_menu();
  print "<script type='text/javascript' src='".JS_URL."/functions.js' language='JavaScript'></script>\n";

  $order_by = get_order_by("admins_order_by");

  if ( isset($_POST['confirm']) && isset($_POST['action']) && ($sel = chks2sql()) ) {
    sql_query( "DELETE FROM cyrup_admins WHERE username<>".sql_escape(ADMIN_USER)." AND (".$sel.")" ); 
  }

  print "<form name='form' method='POST' action='?admin&m=admins'>\n";
  print "<input type='hidden' name='action' value=''>\n";
  print "<table width='100%' border=0 cellpadding=0 cellspacing=0>\n";
  dotline( 5 );
  print "<tr>\n";
  print "<th width=1><input type='checkbox' name='chkChangeAll' onClick='check_boxes()'></th>\n";
  html_th( "username", "Username" );
  html_th( "rights", "Owned domains" );
  html_th( "info", "Info" );
  print "</tr>\n";
  dotline( 5 );
  print "<tr class=highlight><td colspan=5 align='center'>";
  print "<a href='?admin&m=adminform' class='button'>[ Add new ]</a></td></tr>\n";
  dotline( 5 );
    
  $result = sql_query( "SELECT * FROM cyrup_admins ORDER BY ".$order_by ); 
  $i = 0;
  while ( $row = sql_fetch_array($result) ) {
    $i++;
    print "<td width=1><input type=checkbox name='chks[${i}]'>";
    print "<input type=hidden name='ids[${i}]' value='${row['id']}'></td>\n";
    print "<td>&nbsp;<a href='?admin&m=adminform&id=${row['id']}'>".htmlspecialchars($row['username'])."</a></td>\n";

    print "<td align=center>";
    if ( $row['username'] == ADMIN_USER ) {
      print "<a href='?admin&m=domains'>Supervisor</a>";
    } else {
      if ( strlen(trim($row['rights'])) ) {
        sql_query( "SELECT id,domain FROM cyrup_domains WHERE id IN (".$row['rights'].") ORDER BY domain" );
        while ( $domain_row = sql_fetch_array() ) {
          print "<a href='?admin&m=domainform&id=${domain_row['id']}'>${domain_row['domain']}</a><br />";
        }
      } else {
        print "&nbsp;";
      }
    }
    print "</td>\n";

    print "<td align=right>&nbsp;${row['info']}</td>\n</tr>\n";
    dotline( 5 );
  }
  print "</table>\n";
  if ( $i ) {
    print "<br><br>";
    delete_selected_box();
  }
  print "</form>";
  print_footer();

