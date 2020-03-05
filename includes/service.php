<?php

  defined("INCLUDE_DIR") || exit("Not for direct run");

  print_header( TITLE."Service" );
  print_top_menu();

  if ( $_SESSION['USER'] != ADMIN_USER ) 
    include_once( INCLUDE_DIR."/chpass.inc.php" );
  else 
    include_once( INCLUDE_DIR."/integrity.inc.php" );

  print_footer();
