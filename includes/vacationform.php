<?php
  defined('INCLUDE_DIR') || exit('Not for direct run');
  require_once INCLUDE_DIR.'/sieve.inc.php'; 
  $errors = [];
  $account_id = '';

  if ( empty($_SESSION['domain_id']) ) {
    header( 'Location: '.BASE_URL.'?admin' );
    exit;
  }
  $domain_id = intval($_SESSION['domain_id']);

  if ( isset($_GET['account_id']) && intval($_GET['account_id']) ) {
    $account_id = intval($_GET['account_id']);
    sql_query( "SELECT account FROM cyrup_accounts WHERE id=${account_id} AND domain_id=".$domain_id );
    $account = sql_fetch_variable();
  }

  if ( empty($account) ) {
    header( 'Location: '.BASE_URL.'?admin&m=accounts' );
    exit;
  }

  if ( SIEVE && isset($_POST['submit']) && $account_id) {
    if ( $_POST['submit'] == 'Remove' ) {

      removeVacation($account);
      header( 'Location: '.BASE_URL.'?admin&m=accountform&id='.$account_id );
      exit;

    } elseif ( $_POST['submit'] == 'Set' ) {
      # Check for aliases
      if ( empty($_POST['members']) || !is_array($_POST['members']) ) {
        $errors[] = 'No emails to append';
      } else {
        if ( !($members = implode( ',', array_filter($_POST['members'],'verify_email')) ) {
	  $errors[] = "No valid emails";
        }
      }

      # Check for days - some hardcoded values here...
      $days = ( empty($_POST['days']) || intval($_POST['days']) < 1 || intval($_POST['days']) > 30 ) ? 7 : intval($_POST['days']);

      # Check for message
      if ( empty($_POST['msg']) || strlen(trim($_POST['msg'])) < 2 ) {
        $errors[] = 'Message is too short';
      } else {
        $msg = trim($_POST['msg']);
      }

      if ( !$errors ) {
        setVacation($account,$msg,$members,$days);
        header( 'Location: '.BASE_URL.'?admin&m=accountform&id='.$account_id );
        exit;
      }
    }
  }

  # Get active vacation 
  if ( SIEVE ) list($msg,$members,$days) = getVacation($account);

  print_header(TITLE.' Manage auto-repay message');
  print_top_menu();

  if ( !SIEVE ) {
    print '<center>PEAR Net/Sieve.php class is required for Autoreply feature</center>';
    print_footer();
    exit;
  }
  print '<script type="text/javascript" src="'.JS_URL.'/functions.js" language="JavaScript"></script>'."\n";
  print '<script type="text/javascript" src="'.JS_URL.'/checkemail.js" language="JavaScript"></script>'."\n";
  print "<center><form name='form' action='?admin&m=vacationform&account_id=${account_id}' method=POST>\n";
  print '<input type=hidden name=action>'."\n";
  print "<input type=hidden name=accoun_id value=${account_id}>\n";
  print '<table align=center border=0 cellpadding=0 cellspacing=0>'."\n";
  dotline( 2 ); 
  print "<tr class=highlight>\n<td colspan=2 align=center>";
  print "<b>Current vacation message for account '".htmlspecialchars($account)."'</b></td></tr>\n";
  print '<tr><td>Message text:</td><td><textarea name="msg" rows=5 cols=50>'.htmlspecialchars($msg).'</textarea></td></tr>'."\n";
  dotline( 2 );
  html_input_text('days', 'Send once in days', (empty($days) ? 7 : $days), '', 1);
  dotline( 2 );
  print "<tr>\n<td>&nbsp; Mail addresses to reply &nbsp;</td>\n<td>";
  print "<select name='members[]' style='width:200px;height:60px' multiple>\n";
  print "</select><br>\n";
  print '<input type=button value="Remove selected" onClick="javascript:removeSelectedMembers();return false;">'."\n";
  print "<br>Add from existing e-mail addresses:<br>\n";
  print "<select style='width:150px' name='aliases'>\n";
  print "</select>\n";
  print "<input type=button value=Add onClick='javascript: moveToMembers();return false;'>\n";
  print "<br>Or enter another e-mail address:<br>\n";
  print "<input name=email type=text style='width:150px'>\n";
  print "<input type=button value=Add onClick='javascript:addEmailToMembers(document.forms[\"form\"].elements[\"email\"]);return false;'>\n";
  print "</td>\n</tr>\n";
  dotline( 2 );
  if ( $errors ) {
      print "<tr class=highlight>
      <td colspan=2 align=center>";
      print_errors( $errors ); 
      print "</tr>";
      dotline( 2 ); 
  }
  print "<tr>\n<td>&nbsp;</td>\n<td>\n";
  print "<input type='submit' onClick='javascript:markAll(document.forms[\"form\"].elements[\"members[]\"])' ";
  print 'name="submit" value="Set">&nbsp;<input type="submit" name="submit" value="Remove"></td>';
  print "</tr>\n</table>\n<br></form></center>\n";

  print '<script language="JavaScript"><!--'."\n";

  $aliases_arr = [];
  sql_query( "SELECT * FROM cyrup_aliases WHERE enabled='1' AND domain_id=${domain_id} AND account_id=${account_id} ORDER BY alias" );
  while ( $row = sql_fetch_array() ) $aliases_arr[] = $row['alias'];

  print 'var cur_members = new Array(\''.htmlspecialchars( !empty($members) ? str_replace(',', "',\n\t'", $members) : join( "',\n\t'", $aliases_arr) ).'\');'."\n";
  print 'var domain_aliases = new Array(\''.htmlspecialchars(join( "',\n\t'", $aliases_arr )).'\');'."\n";
  print 'var all_members = domain_aliases;'."\n";
  print 'init(document.forms["form"].elements["members[]"],document.forms["form"].elements["aliases"]);'."\n";
  print "//--></script>\n";
  print_footer();

