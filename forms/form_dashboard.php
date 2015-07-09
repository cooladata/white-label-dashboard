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

// Request method: GET

require_once("../models/config.php");

// Request method: GET
$ajax = checkRequestMode("get");

if (!securePage(__FILE__)){
  apiReturnError($ajax);
}

// TODO: allow setting default groups

// Sanitize input data
$get = filter_input_array(INPUT_GET, FILTER_SANITIZE_SPECIAL_CHARS);

// Parameters: box_id, render_mode, [user_id, show_dates, disabled]
// box_id: the desired name of the div that will contain the form.
// render_mode: modal or panel
// user_id (optional): if specified, will load the relevant data for the user into the form.  Form will then be in "update" mode.


// Set up Valitron validator
$v = new Valitron\DefaultValidator($get);

$v->rule('required', 'box_id');
$v->rule('required', 'render_mode');
$v->rule('in', 'render_mode', array('modal', 'panel'));
$v->rule('integer', 'dashboard_id');

$v->setDefault('dashboard_id', null);
$v->setDefault('fields', array());
$v->setDefault('buttons', array());

// Validate!
$v->validate();

// Process errors
if (count($v->errors()) > 0) {
  foreach ($v->errors() as $idx => $error){
    addAlert("danger", $error);
  }
  apiReturnError($ajax, ACCOUNT_ROOT);
} else {
    $get = $v->data();
}

// Create appropriate labels
if ($get['dashboard_id']){
    $populate_fields = true;
    $button_submit_text = "Update Dashboard";
    $target = "update_dashboard.php";
    $box_title = "Update Dashboard";
} else {
    $populate_fields = false;
    $button_submit_text = "Create dashboard";
    $target = "create_dashboard.php";
    $box_title = "New Dashboard";
}

// If we're in update mode, load Dashboard data
if ($populate_fields){
    $dashboard = loadDashboards($get['dashboard_id']);
    $deleteLabel = $dashboard[$get['dashboard_id']]['dashboard_name'];
    if ($get['render_mode'] == "panel"){
        $box_title = $dashboard[$get['dashboard_id']]['dashboard_name'];
    }
} else {
    $dashboard = array();
    $deleteLabel = "";
}

$fields_default = [
    'dashboard_name' => [
        'type' => 'text',
        'label' => 'Dashboard name',
        'display' => 'disabled',
        'validator' => [
            'minLength' => 1,
            'maxLength' => 25,
            'label' => 'Dashboard name'
        ],
        'placeholder' => 'Please enter the Dashboard name'
    ],
    'dashboard_order' => [
        'type' => 'text',
        'label' => 'Dashboard Order',
        'display' => 'disabled',
        'validator' => [
            'minLength' => 1,
            'maxLength' => 50,
            'label' => 'Dashboard Order'
        ],
        'placeholder' => 'Please enter the Dashboard Order #Num'
    ],
    'widget_config' => [
        'type' => 'text',
        'label' => 'Widget Config',
        'display' => 'disabled',
        'validator' => [
            'minLength' => 1,
            'maxLength' => 10000,
            'label' => 'Widget Config'
        ],
        'placeholder' => 'Please enter the Widget Config'
    ]
];

$fields = array_merge_recursive_distinct($fields_default, $get['fields']);

// Buttons (optional)
// submit: display the submission button for this form.
// edit: display the edit button for panel mode.
// disable: display the enable/disable button.
// delete: display the deletion button.
// activate: display the activate button for inactive users.

$buttons_default = [
  "btn_submit" => [
    "type" => "submit",
    "label" => $button_submit_text,
    "display" => "hidden",
    "style" => "success",
    "size" => "lg"
  ],
  "btn_edit" => [
    "type" => "launch",
    "label" => "Edit",
    "icon" => "fa fa-edit",
    "display" => "show"
  ],
  "btn_activate" => [
    "type" => "button",
    "label" => "Activate",
    "icon" => "fa fa-bolt",
    "display" => (isset($dashboard[$get['dashboard_id']]['active']) && $dashboard[$get['dashboard_id']]['active'] == '0') ? "show" : "hidden",
    "style" => "success"
  ],
  "btn_disable" => [
    "type" => "button",
    "label" => "Disable",
    "icon" => "fa fa-minus-circle",
    "display" => (isset($dashboard[$get['dashboard_id']]['enabled']) && $dashboard[$get['dashboard_id']]['enabled'] == '1') ? "show" : "hidden",
    "style" => "warning"
  ],
  "btn_enable" => [
    "type" => "button",
    "label" => "Enable",
    "icon" => "fa fa-plus-circle",
    "display" => (isset($dashboard[$get['dashboard_id']]['enabled']) && $dashboard[$get['dashboard_id']]['enabled'] == '1') ? "hidden" : "show",
    "style" => "warning"
  ],
  "btn_delete" => [
    "type" => "launch",
    "label" => "Delete",
    "icon" => "fa fa-trash-o",
    "display" => "show",
    "data" => array(
        "label" => $deleteLabel
    ),
    "style" => "danger"
  ],
  "btn_cancel" => [
    "type" => "cancel",
    "label" => "Cancel",
    "display" => ($get['render_mode'] == 'modal') ? "show" : "hidden",
    "style" => "link",
    "size" => "lg"
  ]
];

$buttons = array_merge_recursive_distinct($buttons_default, $get['buttons']);

$template = "";

if ($get['render_mode'] == "modal"){
    $template .=
    "<div id='{$get['box_id']}' class='modal fade'>
        <div class='modal-dialog'>
            <div class='modal-content'>
                <div class='modal-header'>
                    <button type='button' class='close' data-dismiss='modal' aria-hidden='true'>&times;</button>
                    <h4 class='modal-title'>$box_title</h4>
                </div>
                <div class='modal-body'>
                    <form method='post' action='$target'>";
} else if ($get['render_mode'] == "panel"){
    $template .=
    "<div class='panel panel-primary'>
        <div class='panel-heading'>
            <h2 class='panel-title pull-left'>$box_title</h2>
            <div class='clearfix'></div>
            </div>
            <div class='panel-body'>
                <form method='post' action='$target'>";
} else {
    echo "Invalid render mode.";
    exit();
}

// Load CSRF token
$csrf_token = $loggedInUser->csrf_token;
$template .= "<input type='hidden' name='csrf_token' value='$csrf_token'/>";

$template .= "
<div class='dialog-alert'>
</div>
<div class='row'>
    <div class='col-sm-6'>
        {{dashboard_name}}
    </div>
    <div class='col-sm-6'>
        {{dashboard_order}}
    </div>
</div>
<div class='row'>
    <div class='col-sm-12'>
        {{widget_config}}
    </div>
</div>";


//$template .= "</div>";

// Buttons
$template .= "<br>
<div class='row'>
    <div class='col-xs-8 col-sm-4 hideable'>
        {{btn_submit}}
    </div>
    <div class='col-xs-6 col-sm-3 hideable'>
        {{btn_edit}}
    </div>
    <div class='col-xs-6 col-sm-3 hideable'>
        {{btn_activate}}
    </div>
    <div class='col-xs-6 col-sm-3 hideable'>
      {{btn_enable}}
    </div>
    <div class='col-xs-6 col-sm-3 hideable'>
      {{btn_delete}}
    </div>
    <div class='col-xs-4 col-sm-3 pull-right'>
      {{btn_cancel}}
    </div>
</div>";

// Add closing tags as appropriate
if ($get['render_mode'] == "modal")
    $template .= "</form></div></div></div></div>";
else
    $template .= "</form></div></div>";

// Render form
$fb = new FormBuilder($template, $fields, $buttons, $dashboard[$get['dashboard_id']]);
$response = $fb->render();

if ($ajax)
    echo json_encode(array("data" => $response), JSON_FORCE_OBJECT);
else
    echo $response;

?>