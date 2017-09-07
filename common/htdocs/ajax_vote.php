<?php
require_once '../data/lib/lib_abstract.php';
require_once params::$params['common_data_server']['value']."lib/op/rating.php";

if($_POST['data_te_obj'] && $_POST['data_id'] && $_POST['rating']){
	$data_te_obj = $_POST['data_te_obj'];
	$data_id = $_POST['data_id'];
	$rating = $_POST['rating'];
	rating::setRating($data_te_obj, $data_id, $rating);
	$rating = rating::getRating( $data_te_obj, $data_id );
	echo json_encode($rating);
}

exit();