<?php
require_once(dirname(__FILE__).'../../../config/config.inc.php');
require_once(dirname(__FILE__).'../../../init.php');
$myproduct = new Product((int)$_GET["productId"]);



$attribute = $myproduct->getAttributeCombinationsById($_GET["id_data"], $cookie->id_lang);
$attrs = array();

array_push($attrs, 'Name--'.$myproduct->name["1"]);
array_push($attrs, 'Reference--'.$myproduct->reference);



foreach($attribute as $attr) {
    
    $a = $attr["group_name"];
    $b = $attr["attribute_name"];
  array_push($attrs, $a.'--'.$b);
}



echo json_encode($attrs);
exit();
//$comb = new Combination((int)$_GET["id_data"]);
//print_r($comb);
