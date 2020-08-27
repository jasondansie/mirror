<?php

require_once("lib/conf/config.php");

$HOME_DIR = $_SERVER['DOCUMENT_ROOT'];

$projectresults = $data->getReportCount('05');


 // Page 
 $page = "projectresults";
 if(isset($_REQUEST["page"])){
  if(preg_match('/[a-z]+/', $_REQUEST["page"]))
   $page = $_REQUEST["page"];
 }
 $pageload = "home";
 if(isset($_REQUEST["load"])){
  if(preg_match('/[a-z]+/', $_REQUEST["load"]))
   $pageload = $_REQUEST["load"];
 }
 $smarty->assign('pageload', $pageload);
 
 // Verify that page code exists
 if(is_file('lib/pages/' . $page . '.php'))
    require('lib/pages/' . $page . '.php');
 
 // Verify that page exists
 if(is_file('templates/page_' . $page . '.tpl')){
    $smarty->assign('page', $page);
 }else{
    $smarty->assign('page', '404');
 }
 
  $smarty->display('frame.tpl');
  
  $smarty->assign('HomeDir', $HOME_DIR);
 

//** un-comment the following line to show the debug console
//$smarty->debugging = true;

?>