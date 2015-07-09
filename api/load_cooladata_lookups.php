<?php 
require_once("../models/config.php");

Class Lookup {
	public $Name;
	public $Id;
	public $Values;
}

$token = $loggedInUser->token; 
$project_id = $loggedInUser->project_id;

if (isset($_COOKIE[$project_id."_lookups"])) {
	print_r($_COOKIE[$project_id."_lookups"]);
	return;
}

// EXEC Query
$result = curl_php_getmetadata($token,$project_id);

$lookup_array = array();

foreach ($result["columns"] as $column) {
	if ($column["lookup_size"] > 1 && ($column["name"] == "session_ip_country")) {
		$lookup = new Lookup();
		$lookup->Name = str_replace("session_ip_","",$column["name"]);
		$lookup->Id = $column["id"];
		$lookup->Values = array();
		array_push($lookup_array, $lookup);
	}
}

foreach	($lookup_array as $current_lookup) {
	$values = curl_php_getlookupvalues($token, $project_id, $current_lookup);
	foreach($values["lookupValuesMap"] as $value) {
		array_push($current_lookup->Values, $value);
	}
	sort($current_lookup->Values);
}

// Save results to session
setcookie($project_id."_lookups", json_encode($lookup_array), time()+3600*24);

// Return Results
print_r(json_encode($lookup_array));


function curl_php_getmetadata($token,$project_id)
{
    $url = 'https://app.cooladata.com/api/v1/projects/'.$project_id.'/doc_metadata/';
    $curl = curl_init();
    curl_setopt($curl,CURLOPT_URL, $url); 
    //curl_setopt($curl,CURLOPT_POST, 1); 
    curl_setopt($curl,CURLOPT_RETURNTRANSFER, 1); 
    curl_setopt($curl, CURLINFO_HEADER_OUT, 1);
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
	//curl_setopt($curl, CURLOPT_PROXY, '127.0.0.1:8888'); // for DEBUG only! Queries will not run if local proxy does not exist !
    curl_setopt($curl,CURLOPT_HTTPHEADER, array('Authorization: Token '.$token, 'Accept: application/json'));
    $result = curl_exec($curl);
	if (!$result) {
		return curl_error($curl);
	}
    else {	
		curl_close($curl);
		return json_decode($result, true);
	}
}

function curl_php_getlookupvalues($token,$project_id,$lookup)
{
    $url = 'https://app.cooladata.com//api/projects/'.$project_id.'/lookup/'.$lookup->Id.'/?key='.$lookup->Name; 
    $curl = curl_init();
    curl_setopt($curl,CURLOPT_URL, $url); 
    //curl_setopt($curl,CURLOPT_POST, 1); 
    curl_setopt($curl,CURLOPT_RETURNTRANSFER, 1); 
    curl_setopt($curl, CURLINFO_HEADER_OUT, 1);
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
	//curl_setopt($curl, CURLOPT_PROXY, '127.0.0.1:8888'); // for DEBUG only! Queries will not run if local proxy does not exist !
    curl_setopt($curl,CURLOPT_HTTPHEADER, array('Authorization: Token '.$token, 'Accept: application/json'));
    $result = curl_exec($curl);
	if (!$result) {
		return curl_error($curl);
	}
    else {	
		curl_close($curl);
		return json_decode($result, true);
	}
}
?>
