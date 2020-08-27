<?php

 // Time zone
 date_default_timezone_set('Europe/Helsinki');
 
 // Database Settings
 define('SITEDB_DATABASE','goodcall_main');
 define('SITEDB_USERNAME','toot');
 define('SITEDB_PASSWORD','toober');
 define('SITEDB_HOSTNAME','localhost');
  
 // Data connection
 require('../lib/php/dataConnection.php');
 $data = new dataConnection;
 
 // Load template engine + initialize
 require_once('../lib/smarty/libs/Smarty.class.php');
 $smarty = new Smarty;
 
 $smarty->setTemplateDir('../mirror/templates/');
 $smarty->setCompileDir('../templates_c/');
 $smarty->setConfigDir('../mirror/configs/');
 $smarty->setCacheDir('../cache/');
 

 
 //$smarty->force_compile = true;
 //$smarty->debugging = true;
 $smarty->caching = false;
 $smarty->cache_lifetime = 120;

 // Feedback variable
 $smarty->assign('feedback', '');


 // Site Key
 define('SITE_KEY', 'cSDKjsadlkJ_38293_25092308');
 
  // Admin email
 define('SYS_EMAIL', 'jason@goodcall.fi');
 
 
 
  $ffpage = "";
 
?>
