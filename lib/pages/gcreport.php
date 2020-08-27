<?php

header("Refresh: 600");




if(!isset($_SESSION['idate'])){
    $_SESSION['idate'] =date('m');
}
$month = str_replace("0","",substr($_SESSION['idate'], 0, 2));
$year = substr($_SESSION['idate'], -4, 4);

$report = $data->getlistByMonth("soittolinja_projects", $month);


$agentResults = $data->getAgenttReportCount($month);
$projectResults = $data->getProjectReportCount($month);



$array_product = array(); 


foreach ($report as $row_product)
{
    $a = array("date" => $row_product["Call_time"]); 
    array_push($array_product, $a);     
}

$dayResults = array_count_values(array_column($array_product, 'date'));

$dailychartlables = "";
$dailychartdata = "";

$i =0;  
 


foreach ($dayResults as $key => $day){     
    $dailychartdata = $dailychartdata . "['" . strval($key) . "', " . strval($day) . "], ";   
}

print_r($dailychartdata);



$total=0;




foreach ($projectResults as $value) {
    $total += ($value["Count"] * $value["price"]); 
    
}

if(isset($_POST["showReports"])){
  
    $_SESSION['idate'] = $_POST["idate"];
    header('Location: '. "./?page=gcReportPage");
    
}

$smarty->assign('total', $total);
$smarty->assign('dailychartlables', $dailychartlables);
$smarty->assign('dailychartdata', $dailychartdata);
$smarty->assign('agentResults', $agentResults);
$smarty->assign('allprojectresults', $projectResults);
