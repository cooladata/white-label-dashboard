<?php

require_once("../models/config.php");

// Request method: GET
$ajax = checkRequestMode("get");

if (!securePage(__FILE__)){
  apiReturnError($ajax);
}

// Sanitize input data
$get = filter_input_array(INPUT_GET, FILTER_SANITIZE_SPECIAL_CHARS);

// Parameters: [title, limit, columns, actions, buttons]
// title (optional): title of this table. 
// limit (optional): if specified, loads only the first n rows.
// columns (optional): a list of columns to render.
// actions (optional): a list of actions to render in a dropdown in a special 'action' column.
// buttons (optional): a list of buttons to render at the bottom of the table.

// Set up Valitron validator
$v = new Valitron\DefaultValidator($get);

// Add default values
$v->setDefault('title', 'Dashboards');
$v->setDefault('limit', null);
$v->setDefault('columns',
    [
    'name' =>  [
        'label' => 'Name',
        'sort' => 'asc',
        'sorter' => 'metatext',
        'sort_field' => 'dashboard_name',
        'template' => "
            <div class='h4'>
                <a href='dashboard_details.php?id={{dashboard_id}}'>{{dashboard_name}}</a>
            </div>
            "
    ],
    'order' => [
        'label' => 'Order',
        'sorter' => 'metanum',
        'sort_field' => 'oreder',
        'template' => "
            {{dashboard_order}}"
    ]
    
]);

$v->setDefault('menu_items',
    [
    /*
    'dashboard_activate' => [
        'template' => "<a href='#' data-id='{{dashboard_id}}' class='btn-activate-user {{hide_activation}}'><i class='fa fa-bolt'></i> Activate Dashboard</a>"
    ]
    */
    
    'dashboard_edit' => [
        'template' => "<a href='#' data-id='{{dashboard_id}}' class='btn-edit-user' data-target='#dashboard-update-dialog' data-toggle='modal'><i class='fa fa-edit'></i> Edit Dashboard</a>"
    ]
    /*
    ,
    'dashboard_disable' => [
        'template' => "<a href='#' data-id='{{dashboard_id}}' class='{{toggle_disable_class}}'><i class='{{toggle_disable_icon}}'></i> {{toggle_disable_label}}</a>"
    ]
    */,
    'dashboard_delete' => [
        'template' => "<a href='#' data-id='{{dashboard_id}}' class='btn-delete-user' data-dashboard_name='{{dashboard_name}}' data-target='#dashboard-delete-dialog' data-toggle='modal'><i class='fa fa-trash-o'></i> Delete Dashboard</a>"
    ]
]);

$v->setDefault('buttons',
    [
    'add' => "",
    'view_all' => ""
]);

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


// Generate button display modes
$buttons_render = ['add', 'view_all'];
if (isset($get['buttons']['add'])){
    $buttons_render['add']['hidden'] = "";
} else {
    $buttons_render['add']['hidden'] = "hidden";
}
if (isset($get['buttons']['view_all'])){
    $buttons_render['view_all']['hidden'] = "";
} else {
    $buttons_render['view_all']['hidden'] = "hidden";
}
/*
// Load Dashborads loadDashboards
if (($dashboards = loadDashboards($get['limit'])) === false) {
  //apiReturnError($ajax, ACCOUNT_ROOT);  
}
print_r($dashboards);
*/
// Load dashboards
if (($dashboards = loadDashboards($get['limit'])) === false) {
  apiReturnError($ajax, ACCOUNT_ROOT);  
}

// Compute dashboard table properties
foreach($dashboards as $dashboard_id => $dashboard){
    $dashboards[$dashboard_id]['dashboard_status'] = "Active";
    $dashboards[$dashboard_id]['dashboard_status_style'] = "primary";
    
    /*
    if ($dashboards[$dashboard_id]['active'] == '1')
        $dashboards[$dashboard_id]['hide_activation'] = "hidden";
    else {
        $dashboards[$dashboard_id]['hide_activation'] = "";
        $dashboards[$dashboard_id]['dashboard_status'] = "Unactivated";
        $dashboards[$dashboard_id]['dashboard_status_style'] = "warning";        
    }
    if ($dashboards[$dashboard_id]['enabled'] == '1') {
        $dashboards[$dashboard_id]['toggle_disable_class'] = "btn-disable-user";
        $dashboards[$dashboard_id]['toggle_disable_icon'] = "fa fa-minus-circle";
        $dashboards[$dashboard_id]['toggle_disable_label'] = "Disable dashboard";        
    } 
    else {
        $dashboards[$dashboard_id]['toggle_disable_class'] = "btn-enable-user";
        $dashboards[$dashboard_id]['toggle_disable_icon'] = "fa fa-plus-circle";
        $dashboards[$dashboard_id]['toggle_disable_label'] = "Enable dashboard";
        $dashboards[$dashboard_id]['dashboard_status'] = "Disabled";
        $dashboards[$dashboard_id]['dashboard_status_style'] = "default";        
    }
    */
}


// Load CSRF token
$csrf_token = $loggedInUser->csrf_token;

$response = "
<div class='panel panel-primary'>
  <div class='panel-heading'>
    <h3 class='panel-title'><i class='fa fa-dashboard'></i> {$get['title']}</h3>
  </div>
  <div class='panel-body'>
    <input type='hidden' name='csrf_token' value='$csrf_token'/>";

// Don't bother unless there are some records found
if (count($dashboards) > 0) {
    // var_dump($dashboards);
    $tb = new TableBuilder($get['columns'], $dashboards, $get['menu_items'], "Status/Actions", "dashboard_status", "dashboard_status_style");
    $response .= $tb->render();
    $response .= "</div>";
} else {
    $response .= "<div class='alert alert-info'>No dashboards found.</div>";
}

$response .= "
        <div class='row'>
            <div class='col-md-6 {$buttons_render['add']['hidden']}'>
                <button type='button' class='btn btn-success btn-add-user' data-toggle='modal' data-target='#dashboard-create-dialog'>
                    <i class='fa fa-plus-square'></i>  Create New Dashboard
                </button>
            </div>
            <div class='col-md-6 text-right {$buttons_render['view_all']['hidden']}'>
                <a href='dashboards.php'>View All Dashboards <i class='fa fa-arrow-circle-right'></i></a>
            </div>
        </div>
    </div> <!-- end panel body -->
</div> <!-- end panel -->";


if ($ajax){
    echo json_encode(array("data" => $response), JSON_FORCE_OBJECT);

} else {
    echo $response;
}
?>
