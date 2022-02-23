<?php


$doc_root = $_SERVER['DOCUMENT_ROOT'].'/api';
require_once($doc_root.('/config.php'));
$full_req_url = req_url();
$url_param_array = url_param($full_req_url);
$req_method = $_SERVER['REQUEST_METHOD'];
header("Access-Control-Allow-Origin: *");
switch ($req_method){
	case "GET": 
		//response(405,'only POST method is alowed',NULL);
		//return false; 
	break;
	case "POST": 
		response(400,'only GET method is alowed',NULL);
		return false;
	break;
	case "PUT": 
		response(405,'only GET method is alowed',NULL);
		return false;
 	break;
	case "DELETE": 
		response(405,'only GET method is alowed',NULL);
		return false;
 	break;

}

if (!empty($_GET['id'])){
	$id = $_GET['id'];
	$data_array = _sqlselectall('projects_apiinvestment', "id = '$id'");
	if (empty($data_array)){
		//no register found
		echo 'investment id not found';
		return false;
	}else{
		$status = ($data_array[0]['ti_withdrawn'] == '1')? 'paid' :'active';
		$date = date_create($data_array[0]['dt_creation_date']);
		$investdate = $date->format('d-m-Y');
		if ($status == 'paid'){
			echo 'investment already paid';
			return false;
		}else{
			//optional date of withdrawal
			if (!empty($_GET['date'])){
				$checkdatastatus = check_datew($_GET['date']);

				if ($checkdatastatus =='ok'){					
					$datew = DateTime::createFromFormat('d/m/Y', $_GET['date']);
					$formdatew = $datew->format('d-m-Y');
					$datefinal = $formdatew;
				}else{
					response(400,'data invalid',$checkdatastatus);
					return false;
				}
			}else{	
				$datew	= new DateTime(gmdate("Y-m-d"));//today	
				$datefinal	= $datew->format('d-m-Y');
			}
		} 
	}	
	$arr_taxation = taxation($data_array[0]['db_amount'],$investdate,$datefinal);
	$sqldate = $datew->format('Y-m-d');	
	$data_array2 = array(	
	'dt_withdraw' => $sqldate,
	'db_amount_withdrawn' => $arr_taxation['amount total'],
	'ti_withdrawn' => '1'	
	);
	$result = _sqlupdate('projects_apiinvestment',$data_array2,"id = '$id'");
	if ($result){
		$result = array(
			'name of owner' => $data_array[0]['v_name'],
			'investment id' => $id,
			'status' => 'paid',
			'investment date' => $investdate,
			'withdrawn date' => $datefinal,
			'initial amount' => $data_array[0]['db_amount'],
			'compound gain' => $arr_taxation['compound gain'],
			'months invested' => $arr_taxation['months invested'],
			'profit total' => $arr_taxation['profit total'],
			'withdrawal tax' => $arr_taxation['withdrawal tax'],
			'tax total' => $arr_taxation['total_tax'],
			'free profit' => $arr_taxation['free_profit'],
			'amount total' => $arr_taxation['amount total']
			);
			//return data
			$json_response = json_encode($result,JSON_UNESCAPED_UNICODE |JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);		
			echo $json_response;
	}else{
		response(500,'error processing withdraw',NULL);	
	}	
}else{
	response(400,'missing parameters',NULL);
}


//___________________________________________________________________________________________________




//header( 'Retry-After: 5' );
//header("Location: https://google.com");
//header('Refresh: 10; url=https://google.com/');


?>