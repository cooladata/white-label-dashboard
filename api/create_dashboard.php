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


// Create a new user.

require_once("../models/config.php");

set_error_handler('logAllErrors');

// Request method: POST
$ajax = checkRequestMode("post");

$validator = new Validator();
// POST: user_name, display_name, email, title, password, passwordc, [admin, add_groups, skip_activation, csrf_token]

// Check if request is from public or backend
$admin = $validator->optionalPostVar('admin');

if ($admin == "true"){
    // Admin mode must be from a logged in user
    checkLoggedInUser($ajax);

    $csrf_token = $validator->requiredPostVar('csrf_token');

    // Validate csrf token
    checkCSRF($ajax, $csrf_token);

}

else {
  global $can_register;

  if (!userIdExists('1')){
	  addAlert("danger", lang("MASTER_ACCOUNT_NOT_EXISTS"));
	  apiReturnError($ajax, SITE_ROOT);
  }

  // If registration is disabled, send them back to the home page with an error message
  if (!$can_register){
	  addAlert("danger", lang("ACCOUNT_REGISTRATION_DISABLED"));
	  apiReturnError($ajax, SITE_ROOT);
  }

  //Prevent the user visiting the logged in page if he/she is already logged in
  if(isUserLoggedIn()) {
	  addAlert("danger", "I'm sorry, you cannot register for an account while logged in.  Please log out first.");
	  apiReturnError($ajax, ACCOUNT_ROOT);
  }

}

$dashboard_name = trim($validator->requiredPostVar('dashboard_name'));
$widget_config = trim($validator->requiredPostVarWidgetConfig('widget_config'));
$dashboard_order = trim($validator->requiredPostVar('dashboard_order'));

// If we're in admin mode, require title.  Otherwise, use the default title
if ($admin == "true"){
  //$dashboard_name = trim($validator->requiredPostVar('dashboard_name'));
} else {
  $title = $new_user_title;
}

// Add alerts for any failed input validation
foreach ($validator->errors as $error){
  addAlert("danger", $error);
}

$error_count = count($validator->errors);

if ($error_count == 0){
	// Try to create the new user
	if ($new_user_id = createDashboard($dashboard_name, $dashboard_order, $widget_config, $admin)){

	} else {
		apiReturnError($ajax, ($admin == "true") ? ACCOUNT_ROOT : SITE_ROOT);
	}
}
else {
	apiReturnError($ajax, ($admin == "true") ? ACCOUNT_ROOT : SITE_ROOT);
}

restore_error_handler();

apiReturnSuccess($ajax, ($admin == "true") ? ACCOUNT_ROOT : SITE_ROOT);

?>