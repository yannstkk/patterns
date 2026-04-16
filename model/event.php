<?php 

//model.event.php
include(__DIR__."/../config/config.php");

  $cnx = connectBaseExterne_PDO(200);


function getListEvent(){
global $cnx;

$testquery = "select * from config_event";


$stmt = $cnx->prepare($testquery);

 $stmt->execute();

$result = $stmt->fetchAll(PDO::FETCH_ASSOC);

return $result;


}


?>