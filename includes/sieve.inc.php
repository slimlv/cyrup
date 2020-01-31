<?php
/*
 * $RCSfile: sieve.inc.php,v $ $Revision: 1.9 $
 * $Author: slim_lv $ $Date: 2016/11/01 14:09:36 $
 * This file is part of CYRUP project
 * by Deniss Gaplevsky (slim@msh.lv)
 */

  if ( !defined('INCLUDE_DIR') ) exit('Not for direct run');

  # do we have SIEVE for vacation support ?
  error_reporting(E_ALL & ~E_WARNING);
  define ( 'SIEVE', ( (include 'Net/Sieve.php') === FALSE ? 0 : 1 ) );
  error_reporting(E_ALL); 

  $vac_rfc = 'vacation\s+(?::days\s+(?P<days>\d+)\s+|)(?::from\s+"([^"]+)"\s+|)(?::addresses\s+\[(?P<addr>[^\]]+)\]\s+|)(?::subject\s+"(?P<subject>[^"]+)"\s+|)(?::mime\s+|)(?::handle\s+"[^"]+"|)(?:reason:\s+|)("(?P<msg>(?:[^\\\"]|\\\.)+)"|text:\s+(?P<msg1>.*?)\n\.(\r|)\n)\s*;'; # RFC ?

  $vac_regex = array( 
    '/(#[^\n]*(|\r){1,}\n)\s*if\s+[^\{]+\s*\{\s*'.$vac_rfc.'\s*(fileinto.+?;|)\s*\}/ims', # avelsieve/rc
    '/'.$vac_rfc.'/ims', # RFC ?
  );

  function removeVacation($account) {
    global $vac_regex;
    DEBUG( D_FUNCTION, 'removeVacation()' );

    if (empty($account)) return FALSE;

    list($sname,$script) = getSieveScript($account);

    $script = preg_replace($vac_regex,'', $script);

    setSieveScript($account,$sname,$script);
  }
  
  function setVacation($account, $msg, $aliases, $days = 7) {
    DEBUG( D_FUNCTION, 'setVacation()' );

    if ( !is_array($aliases) ) $aliases = explode(',',$aliases);
    foreach ($aliases as &$addr) 
        $addr = '"'.strtr( $addr, ['"' => '', "'" => ''] ).'"';

    $vacation = 'if anyof (true)'."\n".'{'."\n";
# FIXME: this is invalid usage of :addresses
    $vacation .= 'vacation :days '.$days.' :addresses ['.implode(',',$aliases).']';
    if ( strpos($msg,'"') === FALSE AND strpos($msg,'\\') === FALSE )
        $vacation .= ' "'.trim($msg).'";';
    else 
        $vacation .= ' text:'."\n".trim($msg)."\n.\n;";
    $vacation .= "\n".'}'."\r\n";

    removeVacation($account);

    list($sname,$script) = getSieveScript($account);

    # prepend require "vacation"; when missed
    if ( !preg_match('/require\s+(\[[^\]]*|[^;]*)"vacation"/mi',$script) )
       $script = 'require "vacation";'."\n".$script;

    # mimic crappy avelsieve
    $avel = array('cond' => array('kind' => 'all'),
                  'type' => 1,
                  'condition' => 'and',
                  'action' => 6,
                  'vac_addresses' => implode(',',$aliases),
                  'vac_subject' => '',
                  'vac_days' => $days,
                  'vac_message' => $msg );
    $avel_str = '#START_SIEVE_RULE'.urlencode(base64_encode(serialize($avel))).'END_SIEVE_RULE';
    if ( preg_match('/AVELSIEVE/',$script) )
        $script .= "\n".$avel_str;
    else 
        $script .= "\n".'# rule:[vacation]';

    $script .= "\n".$vacation;

    setSieveScript($account,$sname,$script);
  }

  function getVacation($account) {
    global $vac_regex;
    DEBUG( D_FUNCTION, 'getVacation()' );

    # will return empty list of message text, emails and days
    $ret = array('','', 0);

    list($sname,$script) = getSieveScript($account);

    # something like this but iam not shure ;)
    foreach ($vac_regex as $regex) 
        if ( preg_match($regex, $script, $matches) ) {
            $matches['msg'] = empty($matches['msg']) ? $matches['msg1'] : $matches['msg'];
            $ret[0] = strtr( $matches['msg'], array( "\n.." => "\n.", '\\\\' => '\\', '\"' => '"' ) );
            $ret[1] = strtr( $matches['addr'], array('"' => '', "'" => '') );
            $ret[2] = $matches['days'];
            break;
        }

    return $ret;
  }

  function getSieve($account) {
    DEBUG( D_FUNCTION, 'getSieve()' );

    if ( !SIEVE ) return FALSE;

    $sieve = @new Net_Sieve(CYRUS_USER,CYRUS_PASS,CYRUS_HOST,'2000','',$account,FALSE,FALSE,FALSE); 
    if ( PEAR::isError( $sieve )) sieve_die('getSieve(): '.print_r( $sieve, true ));
    return $sieve;

  }

  function getSieveScript($account) {
    DEBUG( D_FUNCTION, 'getSieveScript()' );

    if ( !SIEVE ) return FALSE;

    $sieve = getSieve($account);
    if ( PEAR::isError( $sieve )) sieve_die('getSieve(): '.print_r( $sieve, true ));
   
    $sname = $sieve->getActive();
    if ( PEAR::isError( $sname )) sieve_die('getActive(): '.print_r( $sname, true ));

    if (!empty($sname)) {

        $script = $sieve->getScript($sname);
        if ( PEAR::isError( $script )) sieve_die('getScript(): '.print_r( $script, true ));
    } else {
        $sname = $script = '';
    }

    $sieve->disconnect();

    return array($sname, trim($script) );
    
  }

  function setSieveScript($account,$sname,$script) {
    DEBUG( D_FUNCTION, 'setSieveScript()' );

    if ( !SIEVE ) return FALSE;

    $sieve = getSieve($account);
    if ( PEAR::isError( $sieve )) sieve_die('getSieve(): '.print_r( $sieve, true ));

# [DG]: cannot remove active script
#    if ( !empty($sname) ) {
#
#        $rv = $sieve->removeScript($sname);
#        if ( PEAR::isError( $rv )) sieve_die('removeScript(): '.print_r( $rv, true ));
#
#    } else {

    if ( empty($sname) ) {

        $sname = 'cyrup_sieve';

    }    
    $rv = $sieve->installScript($sname,$script,true);
    if ( PEAR::isError( $rv )) sieve_die('removeScript(): '.print_r( $rv, true ));
    
    $sieve->disconnect();

  }

  function sieve_die( $message ) {
    print '<font color=red><b>FATAL: </b></font>';
    $message = str_replace(CYRUS_PASS,'CYRUS_PASS',$message);
    DEBUG( D_IMAP_ERROR, '<pre>'.$message.'</pre>' );
    exit();
  }

