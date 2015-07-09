<?php
/*
White-Label Dashboard Version: 0.0.1
By Cooladata
Developed by Gil Adirim, Snir Shalev
Copyright (c) 2015

UserFrosting Version: 0.2.2
By Alex Weissman
Copyright (c) 2014

Based on the UserCake user management system, v2.0.2.
Copyright (c) 2009-2012

UserFrosting, like UserCake, is 100% free and open-source.

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the 'Software'), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:
The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED 'AS IS', WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.

*/

require_once("../models/config.php");
set_error_handler('logAllErrors');

// Request method: POST
$ajax = checkRequestMode("post");

// User must be logged in
checkLoggedInUser($ajax);

$validator = new Validator();
// Required: csrf_token, user_id
$csrf_token = $validator->requiredPostVar('csrf_token');
$dashboard_id = $validator->requiredNumericPostVar('dashboard_id');

$dashboard_name = trim($validator->optionalPostVar('dashboard_name'));
$dashboard_order = trim($validator->optionalPostVar('dashboard_order'));
$widget_config = trim($validator->optionalPostVar('widget_config'));
$partner_id = trim($validator->optionalPostVar('partner_id'));
$token = trim($validator->optionalPostVar('token'));
$title = trim($validator->optionalPostVar('title'));

$rm_groups = $validator->optionalPostVar('remove_groups');
$add_groups = $validator->optionalPostVar('add_groups');
$enabled = $validator->optionalPostVar('enabled');
$primary_group_id = $validator->optionalPostVar('primary_group_id');

// For updating passwords.  The user's current password must also be included (passwordcheck) if they are resetting their own password.

// Add alerts for any failed input validation
foreach ($validator->errors as $error){
  addAlert("danger", $error);
}

// Validate csrf token
checkCSRF($ajax, $csrf_token);

if (count($validator->errors) > 0){
    apiReturnError($ajax, getReferralPage());
}

// Special case to update the logged in user (self)
$self = false;
if ($dashboard_id == "0"){
	$self = true;
	$dashboard_id = $loggedInUser->dashboard_id;
}

//Check if selected user exists
if(!$dashboard_id or !dashboardIdExists($dashboard_id)){
	addAlert("danger", 'ACCOUNT_INVALID_DASHBOARD_ID');
	apiReturnError($ajax, getReferralPage());
}

$dashboarddetails = fetchDashboardAuthById($dashboard_id); //Fetch user details
//print_r($dashboarddetails);
$error_count = 0;
$success_count = 0;

//Update display name if specified and different from current value
if ($dashboard_name && $dashboarddetails['dashboard_name'] != $dashboard_name){
	if (!updateDashboardName($dashboard_id, $dashboard_name)){
		$error_count++;
		$dashboard_name = $dashboarddetails['dashboard_name'];
	} else {
		$success_count++;
	}
} else {
	$dashboard_name = $dashboarddetails['dashboard_name'];
}

//Update email if specified and different from current value
if ($dashboard_order && $dashboarddetails['dashboard_order'] != $dashboard_order){
	if (!updateDashboardOrder($dashboard_id, $dashboard_order, $dashboard_name)){
		$error_count++;
	} else {
		$success_count++;
	}
}

//Update title if specified and different from current value
if ($widget_config && $dashboarddetails['widget_config'] != $widget_config){
	if (!updateDashboardWidgetConfig($dashboard_id, $widget_config)){
		$error_count++;
	} else {
		$success_count++;
	}
}


restore_error_handler();

if ($error_count > 0){
    apiReturnError($ajax, getReferralPage());
} else {
    apiReturnSuccess($ajax, getReferralPage());
}

?>