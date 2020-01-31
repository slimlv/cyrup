<?php
/*
 * $RCSfile: functions.inc.php,v $ $Revision: 1.11 $
 * $Author: slim_lv $ $Date: 2013/07/18 09:16:38 $
 * This file is from CYRUP project
 * by Yuri Pimenov (up@msh.lv) & Deniss Gaplevsky (slim@msh.lv)
 */

    if ( !defined('INCLUDE_DIR') ) exit('Not for direct run');

    if ( !defined('DEFAULT_DOMAIN') ) define('DEFAULT_DOMAIN','');
    if ( !defined('SHOW_VACATION_LIST') ) define('SHOW_VACATION_LIST', 0);
    if ( !defined('IMAP_FOLDERS') ) const IMAP_FOLDERS = [];

    function chks2sql( $sql_field = "id" ) {

        $i = 0;
        $at_least_one = 0;
        $sel = "";
        while ( $i <= sizeof( $_POST['ids'] ) ) {
            if ( isset( $_POST['chks'][$i] ) AND ( $_POST['chks'][$i] == "on" ) ) {
                if ( $at_least_one )
                    $sel .= " OR ";
                $sel .= $sql_field."='".intval($_POST['ids'][$i])."'";
                $at_least_one++;
            }
            $i++;
        }
        return $sel;
    }

    function chks2array() {

        $ids = array();
		if ( empty($_POST['ids']) ) return $ids;
        $i = 0;
        while ( $i <= count( $_POST['ids'] ) ) {
            if ( isset( $_POST['chks'][$i] ) AND ( $_POST['chks'][$i] == "on" ) ) 
                $ids[] = intval($_POST['ids'][$i]);
            $i++;
        }
        return $ids;
    }

  function rights2sql( $put_where = 0 , $sql_field = "id" ) {

    if ( $_SESSION['USER'] == ADMIN_USER ) {
        return '';
    } else {
        if ( trim($_SESSION['RIGHTS']) == "" ) 
            return ( $put_where ? ' WHERE ' : ' AND ' ).' null ';
        else 
            return ( $put_where ? ' WHERE ' : ' AND ' ).$sql_field.' IN ('.addslashes($_SESSION['RIGHTS']).') ';
    }
    die('Incorrect function call: rights2sql()');
  }

  function sql_pager($field) {

    if ( isset($_GET['w']) ) {
        $str = strtolower($_GET['w']);
        $str = preg_replace('/[^\w\-\.]/','',$str);
        if ( empty($field) )
            return ' ';
        if ( $str == '0' )
            return 'AND '.$field.' NOT REGEXP "^[a-z]"';
        else 
            return 'AND '.$field.' LIKE "'.$str.'%"';
    } else { 
        return ' ';
    }
  }

    function verify_email( $email ) {

        return preg_match( "/^([\w.-])+\@([\w.-]+\.)[a-z]{2,7}$/i" , trim($email) );
    }

    function get_sql_crypt( $password ) {

        switch ( PASSWORD_CRYPT ) {
            case 0 : return "'".addslashes( $password )."'";
            case 1 : return "'".crypt(addslashes( $password ))."'";
            case 2 : return "PASSWORD('".addslashes( $password )."')";
            case 3 : return "'".md5(addslashes( $password ))."'";
            case 4 : return "'".sha1(addslashes( $password ))."'";
        }
    }

    function get_mailbox_local_part( $domain_row, $account ) {

        switch ( MAILBOX_STYLE ) {
        case "USERSUFFIX" :
            if ( preg_match( "/^(.+)".$domain_row["account_suffix"]."$/", $account, $matches ) )
                return $matches[1];
            else
                return $account;
        case "USER@DOMAIN.TLD" :
            if ( $domain_row['domain'] == DEFAULT_DOMAIN )
                return $account;
            else
                return substr( $account, 0, strpos( $account, "@" ) );
        }
    }

    function get_mailbox_suffix( $domain_row ) {

        switch ( MAILBOX_STYLE ) {
        case "USERSUFFIX" :
            return $domain_row['account_suffix'];
        case "USER@DOMAIN.TLD" :
            if ( $domain_row['domain'] == DEFAULT_DOMAIN )
                return '';
            else
                return "@".$domain_row['domain'];
        }
    }

    function get_mailbox( $username, $domain ) {

        return $username.get_mailbox_suffix( $domain );
    }

    function get_alias_local( $string ) {

        return substr( $string, 0, strpos( $string, "@" ) );
    }


    function get_domain_id() {

        sql_query( "SELECT id FROM cyrup_domains".rights2sql(1) );
        if ( 0 == sql_num_rows() )
            return 0;
        if ( 1 == sql_num_rows() ) {
            $row = sql_fetch_array();
            return $_SESSION['domain_id'] = $row[0];
        }
        if ( (!empty($_POST['domain_id'])) AND (!isset($_POST['action'])) ) {
            sql_query( "SELECT id FROM cyrup_domains WHERE id=".intval($_POST['domain_id']).rights2sql() );
            if ( 1 == sql_num_rows() )
                return $_SESSION['domain_id'] = intval($_POST['domain_id']);
        }
        if ( !empty($_SESSION['domain_id']) ) {
            sql_query( "SELECT id FROM cyrup_domains WHERE id=".$_SESSION['domain_id'].rights2sql() );
            if ( 1 == sql_num_rows() )
                return $_SESSION['domain_id'];
        }
        return $_SESSION['domain_id'] = 0;
    }

    function get_domain_owner( $domain_id ) {
	
        sql_query( "SELECT id, rights FROM cyrup_admins WHERE rights like '%".intval($domain_id)."%'" );
        while ( $row = sql_fetch_array() ) {
            $rights = explode( ",", $row['rights'] );
            if ( in_array( intval($domain_id), $rights ) )
            return $row['id'];
        }
        return false;
    }

   /*
    *  get_domain_info()
    *  returns hash: all columns of cyrup_domains table +
    *      default_rcpt, aliases_cur, accounts_cur, quota_cur
    */
    function get_domain_info( $domain_id ) {

        $domain_id = intval($domain_id);

        sql_query( "SELECT cd.*,cdr.aliased_to AS default_rcpt  FROM cyrup_domains cd LEFT JOIN cyrup_default_rcpt cdr  ON cd.id=cdr.domain_id WHERE cd.id=".$domain_id );
        $row = sql_fetch_array();
        sql_query( "SELECT alias FROM cyrup_aliases WHERE domain_id=".$domain_id."
                        UNION
                    SELECT alias FROM cyrup_maillists WHERE domain_id=".$domain_id );
        $row['aliases_cur'] = sql_num_rows();

        sql_query( "SELECT COUNT(*) AS '0', SUM(quota) AS '1' FROM cyrup_accounts WHERE domain_id=".$domain_id );
        list( $row['accounts_cur'], $row['quota_cur'] ) = sql_fetch_array();

        return $row;
    }

    function get_order_by($session_var) {

        $valid = array(
            "domains_order_by" => array ('domain','accounts_max','aliases_max','quota','account_suffix',
						'aliased_to','enabled','info'),
            "accounts_order_by"  => array ('account','quota','first_name','enabled','aliases_cur',
						'surname','phone','info','vacation'),
            "aliases_order_by"   => array ('alias','enabled','account_id','aliased_to'),
            "maillists_order_by" => array ('alias','enabled','aliased_to'),
		    "admins_order_by"   => array ('username','rights','info')
        );
	
        if ( !array_key_exists($session_var,$valid) )
            return '';
        if ( !empty( $_GET['order_by'] ) ) {
            if ( in_array($_GET['order_by'],$valid[$session_var]) )
                $order_by = $_GET['order_by'];
            else
                $order_by = $valid[$session_var][0];
        } else {
            if ( isset($_SESSION[$session_var]) )
                $order_by = $_SESSION[$session_var];
            else
                $order_by = $valid[$session_var][0];
        }
        $_SESSION[$session_var] = $order_by;
        return $order_by;
    }
    
  function print_maillist_list( $alias_id ) {
	
    $alias_id = intval( $alias_id );
    $domain_id = get_domain_id();
    if ( !$alias_id ) return false;

    sql_query( "SELECT alias FROM cyrup_aliases WHERE domain_id='".$domain_id."' AND id='".$alias_id ."'" );
    if ( 1 != sql_num_rows() ) return false;

    $row = sql_fetch_array();
    $alias = $row['alias'];
    sql_query( "SELECT * FROM cyrup_maillists
                          WHERE domain_id='".$domain_id."'
                                AND aliased_to LIKE '%".$alias."%' ORDER BY alias" );
    while ( $row = sql_fetch_array() ) {
      $aliased_to = explode( ",", $row['aliased_to'] );
      if ( in_array($alias,$aliased_to) ) 
        print "<a href='?admin&m=maillistform&id=".$row['id']."'>
              &nbsp;".$row['alias'].'</a> '.
              ( $row['enabled'] ? '(active)' : '(not active)' )."<br>\n";
    }
  }

  function remove_from_maillist( $alias_id ) {
	
    $alias_id = intval( $alias_id );
    if ( !$alias_id ) return false;

    sql_query( "SELECT alias FROM cyrup_aliases WHERE domain_id='".get_domain_id()."' 
                                                      AND id='".$alias_id ."'" );
    if ( 1 != sql_num_rows() ) return false;

    $alias = sql_fetch_variable();

    sql_query( "DELETE FROM cyrup_maillists WHERE domain_id='".get_domain_id()."'
                                                        AND aliased_to='".$alias."'" );

    $result = sql_query( "SELECT * FROM cyrup_maillists WHERE domain_id='".get_domain_id()."'
                                                        AND aliased_to LIKE '%".$alias."%'" );
    while ( $row = sql_fetch_array($result) ) {
      $aliased_to = explode( ",", $row['aliased_to'] );
      if ( in_array($alias,$aliased_to) ) {
        array_splice( $aliased_to, array_search($alias,$aliased_to), 1 );
        sql_query( "UPDATE cyrup_maillists SET aliased_to='".implode(",",$aliased_to)."' 
                            WHERE domain_id='".get_domain_id()."'
                                  AND id='".$row['id']."'" );
      }
    }
    return true;
	
  }

    function DEBUG( $level, $message = "" ) {
        if ( ! ( $level & DEBUG_LEVEL ) )
            return 0;
        switch ( $level ) {
        case D_INCLUDE :
            $head = "<font color='green'>INCLUDE</font>";
            break;
        case D_FUNCTION :
            $head = "<font color='blue'>FUNCTION</font>";
            break;
        case D_SQL_ERROR :
            $head = "<font color='orange'>SQL</font>";
            break;
        case D_IMAP_ERROR :
            $head = "<font color='orange'>IMAP</font>";
            break;
        }
        print "$head: $message<br>\n";
    }


    function mksysaliases($file) {
         DEBUG( D_FUNCTION, 'mksysaliases('.$file.')' );

         if ( ( $fh = fopen( $file, 'w') ) === FALSE )
             sql_die( 'mksysaliases('.$file.'): Permission denied' );

         $result = sql_query( 'SELECT domain FROM cyrup_domains WHERE enabled=1' );
         fwrite( $fh, "# Basic system aliases -- these MUST be present.\n" );

         while ( $row = sql_fetch_array( $result ) ) 
             fwrite( $fh, '/(postmaster|abuse)@'.str_replace('.','\.',$row[0]).'$/  root'."\n" );
         fclose( $fh );
    }

   /* Recursively strip all slashes from an array
    * Taken from squirel-mail
    */

    function arr_stripslashes( &$arr ) {
        if( count( $arr ) > 0 )
            foreach ( $arr as $index => $value ) {
                if ( is_array( $arr[$index] ) )
                    arr_stripslashes( $arr[$index] );
                else
                    $arr[$index] = stripslashes( $value );
            }
    }

    if ( get_magic_quotes_gpc() ) {
        arr_stripslashes( $_GET );
        arr_stripslashes( $_POST );
    }
?>
