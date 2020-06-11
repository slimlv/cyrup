<?php

  if ( !defined('INCLUDE_DIR') ) exit('Not for direct run');

  if ( !defined('DEFAULT_DOMAIN') ) define('DEFAULT_DOMAIN','');
  if ( !defined('SHOW_VACATION_LIST') ) define('SHOW_VACATION_LIST', 0);

  function chks2sql( $sql_field = "id" ) {
    $sel = "";

    if (!empty($_POST['ids']) AND is_array($_POST['ids'])) {
      $sel = "{$sql_field} IN (".implode(',',array_map('intval',$_POST['ids'])).")";
    }

    return $sel;
  }

  function chks2array() {
    $ids = [];
    if ( !empty($_POST['ids']) AND is_array($_POST['ids']) ) {
      $ids = array_map('intval',$_POST['ids']);
    }
    return $ids;
  }

  function rights2sql( $sql_field = "id" ) {
    DEBUG( D_FUNCTION, "rights2sql($sql_field)" );
    $out = ' TRUE '; 
    if ( $_SESSION['USER'] != ADMIN_USER && !empty(trim($_SESSION['RIGHTS'])) ) {
      $out =  "${sql_field} IN (${_SESSION['RIGHTS']} "; 
    }
    return $out;
  }

  function filter2sql($field) {
    DEBUG( D_FUNCTION, "filter2sql($field)" );
    $out = ' ';
    if ( !empty($_GET['w']) && !empty($field) ) {
      $str = strtolower($_GET['w']);
      $str = preg_replace('/[^\w\-\.]/','',$str);
      $out = "AND $field ".($str == '0' ? "NOT REGEXP '^[a-z]'" : "LIKE '${str}%'");
    }
    return $out;
  }

  function verify_email( $email ) {
    DEBUG( D_FUNCTION, "verify_email($email)" );
    return preg_match( "/^([\w.-])+\@([\w.-]+\.)[a-z]{2,7}$/i" , trim($email) );
  }

  function get_sql_crypt( $password = '' ) {
    switch ( PASSWORD_CRYPT ) {
      case 1 : return sql_escape(crypt($password));
      case 2 : return "PASSWORD(".sql_escape($password).")";
      case 3 : return sql_escape(md5($password));
      case 4 : return sql_escape(sha1($password));
    }
    return sql_escape($password);
  }

  function get_mailbox_local_part( $domain_row, $account ) {
    DEBUG( D_FUNCTION, "get_mailbox_local_part(\$domain_row,$account)" );
    $out = false;
    if ( MAILBOX_STYLE == "USERSUFFIX" ) {
      $out = ( preg_match("/^(.+)".$domain_row["account_suffix"]."$/", $account, $m) ? $m[1] : $account );
    } elseif ( MAILBOX_STYLE == "USER@DOMAIN.TLD" ) {
      # FIXME handle local@local@domain.tld
      $out = ( $domain_row['domain'] == DEFAULT_DOMAIN ? $account : substr($account, 0, strpos($account, '@')) );
    } else {
      die('MAILBOX_STYLE is unknown: '.MAILBOX_STYLE);
    }
    return $out;
  }

  function get_mailbox_suffix( $domain_row ) {
    DEBUG( D_FUNCTION, "get_mailbox_suffix(\$domain_row)" );
    $out = false;
    if ( MAILBOX_STYLE == "USERSUFFIX" ) {
      $out = $domain_row['account_suffix'];
    } elseif ( MAILBOX_STYLE == "USER@DOMAIN.TLD" ) {
      $out = ( $domain_row['domain'] == DEFAULT_DOMAIN ? '' : "@${domain_row['domain']}" );
    } else {
      die('MAILBOX_STYLE is unknown: '.MAILBOX_STYLE);
    }
    return $out;
  }

  function get_mailbox( $username, $domain_row ) {
    DEBUG( D_FUNCTION, "get_mailbox($username,\$domain_row)" );
    return $username.get_mailbox_suffix($domain_row);
  }

  function get_alias_local( $email ) {
    DEBUG( D_FUNCTION, "get_alias_local($email)" );
    return substr( $email, 0, strpos($email, "@") );
  }

  function get_domain_id() {
    DEBUG( D_FUNCTION, "get_domain_id()" );

    sql_query( "SELECT id FROM cyrup_domains WHERE ".rights2sql() );
    if ( 0 == sql_num_rows() ) return 0;
    if ( 1 == sql_num_rows() ) return $_SESSION['domain_id'] = intval(sql_fetch_variable());
    if ( !empty($_POST['domain_id']) && !isset($_POST['action']) ) {
      sql_query( "SELECT id FROM cyrup_domains WHERE id=".intval($_POST['domain_id'])." AND ".rights2sql() );
      if ( 1 == sql_num_rows() ) return $_SESSION['domain_id'] = intval($_POST['domain_id']);
    }
    if ( !empty($_SESSION['domain_id']) ) {
      sql_query( "SELECT id FROM cyrup_domains WHERE id=${_SESSION['domain_id']} AND ".rights2sql() );
      if ( 1 == sql_num_rows() ) return $_SESSION['domain_id'];
    }
    return $_SESSION['domain_id'] = 0;
  }

  function get_domain_owner( $domain_id ) {
    DEBUG( D_FUNCTION, "get_domain_owner($domain_id)" );
    $domain_id = intval($domain_id);
    sql_query( "SELECT id, rights FROM cyrup_admins WHERE rights like '%${domain_id}%'" );
    while ( $row = sql_fetch_array() ) {
      $rights = explode( ",", $row['rights'] );
      if ( in_array($domain_id, $rights) ) return $row['id'];
    }
    return false;
  }

  /*
   *  get_domain_info()
   *  returns hash: all columns of cyrup_domains table +
   *      default_rcpt, aliases_cur, accounts_cur, quota_cur
  */
  function get_domain_info( $domain_id ) {
    DEBUG( D_FUNCTION, "get_domain_info($domain_id)" );
    $domain_id = intval($domain_id);

    sql_query( "SELECT cd.*,cdr.aliased_to AS default_rcpt FROM cyrup_domains cd LEFT JOIN cyrup_default_rcpt cdr ON cd.id=cdr.domain_id WHERE cd.id=".$domain_id );
    $row = sql_fetch_array();
    sql_query( "SELECT alias FROM cyrup_aliases WHERE domain_id=".$domain_id." UNION
                    SELECT alias FROM cyrup_maillists WHERE domain_id=".$domain_id );
    $row['aliases_cur'] = sql_num_rows();

    sql_query( "SELECT COUNT(*) AS accounts_cur, SUM(quota) AS quota_cur FROM cyrup_accounts WHERE domain_id=".$domain_id );
    list( $row['accounts_cur'], $row['quota_cur'] ) = sql_fetch_array();

    return $row;
  }

  function get_order_by($session_var) {
    DEBUG( D_FUNCTION, "get_order_by($session_var)" );

    $order_by = 'id';

    $valid = [ 
      "domains_order_by"  => [ 'domain','accounts_max','aliases_max','quota','account_suffix','aliased_to','enabled','info'],
      "accounts_order_by" => [ 'account','quota','first_name','enabled','surname','phone','info','vacation'],
      "aliases_order_by"  => [ 'alias','enabled','account_id','aliased_to'],
      "maillists_order_by"=> [ 'alias','enabled','aliased_to'],
      "admins_order_by"   => [ 'username','rights','info']
    ];
	
    if ( !array_key_exists($session_var,$valid) ) return '';

    if ( !empty($_GET['order_by']) && !empty($valid[$session_var]) ) {
      $order_by  =  in_array($_GET['order_by'],$valid[$session_var]) ? $_GET['order_by'] : $valid[$session_var][0];
    } else {
      $order_by = isset($_SESSION[$session_var]) ? $_SESSION[$session_var] : $valid[$session_var][0];
    }
    $_SESSION[$session_var] = $order_by;
    return $order_by;
  }
    
  function print_maillist_list( $alias_id ) {
    DEBUG( D_FUNCTION, "print_maillist_list($alias_id)" );
	
    $alias_id = intval($alias_id);
    $domain_id = get_domain_id();
    if ( !$alias_id ) return false;

    sql_query( "SELECT alias FROM cyrup_aliases WHERE domain_id=${domain_id} AND id=".$alias_id );
    if ( 1 != sql_num_rows() ) return false;

    $row = sql_fetch_array();
    $alias = $row['alias'];
    sql_query( "SELECT * FROM cyrup_maillists WHERE domain_id=${domain_id} AND aliased_to LIKE ".sql_escape("%${alias}%")." ORDER BY alias" );
    while ( $row = sql_fetch_array() ) {
      $aliased_to = explode( ",", $row['aliased_to'] );
      if ( in_array($alias,$aliased_to) ) {
        print "<a href='?admin&m=maillistform&id=${row['id']}'>&nbsp;${row['alias']}</a> ".( $row['enabled'] ? '(active)' : '(inactive)' )."<br>\n";
      }
    }
  }

  function get_aliases_list( $account_id ) {
    DEBUG( D_FUNCTION, "print_alias_list($account_id)" );

    $aliases = [];

    $account_id = intval($account_id);
    $domain_id = get_domain_id();
    if ( !$account_id ) return $aliases;

    sql_query( "SELECT account FROM cyrup_accounts WHERE domain_id=${domain_id} AND id=".$account_id);
    if ( 1 != sql_num_rows() ) return $aliases;
    $account = sql_fetch_variable();

    sql_query( "SELECT id, alias, enabled, aliased_to FROM cyrup_aliases WHERE domain_id=${domain_id} AND account_id = 0 AND aliased_to LIKE ".sql_escape("%${account}%")." ORDER BY alias" );
    while ( $row = sql_fetch_array() ) {
      $aliased_to = explode( ",", $row['aliased_to'] );
      if ( in_array($account,$aliased_to) ) $aliases[] = $row;
    }

    sql_query( "SELECT id, alias, enabled FROM cyrup_aliases WHERE domain_id=${domain_id} AND account_id=".$account_id );
    if ( !sql_num_rows() ) return $aliases;
    while ( $row = sql_fetch_array() ) $aliases[] = $row;

    return $aliases;
  }

  function remove_from_maillist( $alias_id ) {
    DEBUG( D_FUNCTION, "remove_from_maillist($alias_id)" );
	
    $alias_id = intval( $alias_id );
    if ( !$alias_id ) return false;
    $domain_id = get_domain_id();
    if ( !$domain_id ) return false;

    sql_query( "SELECT alias FROM cyrup_aliases WHERE domain_id=${domain_id} AND id=".$alias_id );
    if ( 1 != sql_num_rows() ) return false;
    $alias = sql_fetch_variable();

    sql_query( "DELETE FROM cyrup_maillists WHERE domain_id=${domain_id} AND aliased_to = ".sql_escape($alias) );

    sql_query( "SELECT * FROM cyrup_maillists WHERE domain_id=${domain_id} AND aliased_to LIKE ".sql_escape("%${alias}%") );
    while ( $row = sql_fetch_array() ) {
      $aliased_to = explode( ",", $row['aliased_to'] );
      if ( in_array($alias,$aliased_to) ) {
        array_splice( $aliased_to, array_search($alias,$aliased_to), 1 );
        sql_query( "UPDATE cyrup_maillists SET aliased_to=".sql_escape(implode(",",$aliased_to))." WHERE domain_id=${domain_id} AND id=".$row['id'] );
      }
    }
    return true;
  }

  function DEBUG( $level, $message = "" ) {
    if ( !($level & DEBUG_LEVEL) ) return false;
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
    print "${head}: ${message}<br>\n";
  }

  function export2file( $query, $filename ) {
    DEBUG( D_FUNCTION, "export2file('$query','$filename')" );

    $NEWLINE = "\n";   // New line
    $COMMENT = '#';    // Comment sign
    $DELIMITER = "\t"; // Fields delimiter

    if ( ( $fh = fopen( $filename, "w" ) ) == FALSE ) sql_die( "export2file(): Permission denied" );

    $result = sql_query( $query );
    fwrite( $fh, "${COMMENT} ".implode($DELIMITER, array_values(sql_field_names())).$NEWLINE  ); // Comment 

    while ( $row = sql_fetch_row( $result ) ) {
      foreach ($row as $field) {
        $line = strtr($field, [ "\\" => "\\\\", $COMMENT => "\\${COMMENT}", $NEWLINE => "\\n", $DELIMITER => "\\t" ]);
        fwrite( $fh, $line.$DELIMITER );
      }
      fwrite( $fh, $NEWLINE );
    }

    fclose( $fh );
    return sql_fetch_array($result);
  }

  function mksysaliases($file) {
    DEBUG( D_FUNCTION, "mksysaliases($file)" );

    if ( ( $fh = fopen( $file, 'w') ) === FALSE ) {
      sql_die( 'mksysaliases('.$file.'): Permission denied' );
    }
    $result = sql_query( 'SELECT domain FROM cyrup_domains WHERE enabled=1' );
    fwrite( $fh, "# Basic system aliases -- these MUST be present.\n" );

    while ( $row = sql_fetch_array($result) ) {
      fwrite( $fh, '/(postmaster|abuse)@'.str_replace('.','\.',$row[0]).'$/  root'."\n" );
    }
    fclose($fh);
  }
