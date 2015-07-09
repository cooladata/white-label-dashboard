/*

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

/* Display a table of dashboards */
function dashboardTable(box_id, options) {
    console.log('dashboardTable');
    options = typeof options !== 'undefined' ? options : {};

    var data = options;
    data['ajaxMode'] = true;

    // Generate the form
    $.ajax({
        type: "GET",
        url: FORMSPATH + "table_dashboards.php",
        data: data,
        dataType: 'json',
        cache: false
    })
        .fail(function(result) {
            addAlert("danger", "Oops, looks like our server might have goofed.  If you're an admin, please check the PHP error logs.");
            alertWidget('display-alerts');
        })
        .done(function(result) {
            $('#' + box_id).html(result['data']);

            // define pager options
            var pagerOptions = {
                // target the pager markup - see the HTML block below
                container: $('#' + box_id + ' .pager'),
                // output string - default is '{page}/{totalPages}'; possible variables: {page}, {totalPages}, {startRow}, {endRow} and {totalRows}
                output: '{startRow} - {endRow} / {filteredRows} ({totalRows})',
                // if true, the table will remain the same height no matter how many records are displayed. The space is made up by an empty
                // table row set to a height to compensate; default is false
                fixedHeight: true,
                // remove rows from the table to speed up the sort of large tables.
                // setting this to false, only hides the non-visible rows; needed if you plan to add/remove rows with the pager enabled.
                removeRows: false,
                size: 10,
                // go to page selector - select dropdown that sets the current page
                cssGoto: '.gotoPage'
            };

            // Initialize the tablesorter
            $('#' + box_id + ' .table').tablesorter({
                debug: false,
                theme: 'bootstrap',
                widthFixed: true,
                widgets: ['filter']
            }).tablesorterPager(pagerOptions);

            // Link buttons
            $('#' + box_id + ' .btn-add-user').click(function() {
                dashboardForm('dashboard-create-dialog');
            });

            $('#' + box_id + ' .btn-edit-user').click(function() {
                var btn = $(this);
                var dashboard_id = btn.data('id');
                dashboardForm('dashboard-update-dialog', dashboard_id);
            });

            $('#' + box_id + ' .btn-delete-user').click(function() {
                var btn = $(this);
                var dashboard_id = btn.data('id');
                var dashboard_name = btn.data('dashboard_name');
                deleteDashboardDialog('dashboard-delete-dialog', dashboard_id, dashboard_name);
                $('#dashboard-delete-dialog').modal('show');
            });
        });
}

/* Display a modal form for updating/creating a dashboard */
function dashboardForm(box_id, dashboard_id) {
    console.log('dashboardForm');
    dashboard_id = typeof dashboard_id !== 'undefined' ? dashboard_id : "";

    // Delete any existing instance of the form with the same name
    if($('#' + box_id).length ) {
        $('#' + box_id).remove();
    }

    var data = {
        box_id: box_id,
        render_mode: 'modal',
        ajaxMode: "true",
        fields: {
            'dashboard_name' : {
                'display' : 'show'
            },
            'dashboard_order' : {
                'display' : 'show'
            },
            'widget_config' : {
                'display' : 'show'
            }
        },
        buttons: {
            'btn_submit' : {
                'display' : 'show'
            },
            'btn_edit' : {
                'display' : 'hidden'
            },
            'btn_disable' : {
                'display' : 'hidden'
            },
            'btn_enable' : {
                'display' : 'hidden'
            },
            'btn_activate' : {
                'display' : 'hidden'
            },
            'btn_delete' : {
                'display' : 'hidden'
            }
        }
    };
    console.log('data',data);
    if (dashboard_id != "") {
        console.log("Update mode");
        data['dashboard_id'] = dashboard_id;
        data['fields']['dashboard_name']['display'] = "enabled";
        data['fields']['dashboard_order']['display'] = "enabled";
        data['fields']['widget_config']['display'] = "enabled";
    }

    // Generate the form
    $.ajax({
        type: "GET",
        url: FORMSPATH + "form_dashboard.php",
        data: data,
        dataType: 'json',
        cache: false
    })
        .fail(function(result) {
            addAlert("danger", "Oops, looks like our server might have goofed.  If you're an admin, please check the PHP error logs.");
            alertWidget('display-alerts');
        })
        .done(function(result) {
            // Append the form as a modal dialog to the body
            $( "body" ).append(result['data']);
            $('#' + box_id).modal('show');

            // Initialize bootstrap switches
            var switches = $('#' + box_id + ' input[name="select_groups"]');
            switches.data('on-label', '<i class="fa fa-check"></i>');
            switches.data('off-label', '<i class="fa fa-times"></i>');
            switches.bootstrapSwitch();
            switches.bootstrapSwitch('setSizeClass', 'switch-mini' );

            // Initialize primary group buttons
            $(".bootstrapradio").bootstrapradio();

            // Enable/disable primary group buttons when switch is toggled
            switches.on('switch-change', function(event, data){
                var el = data.el;
                var id = el.data('id');
                // Get corresponding primary button
                var primary_button = $('#' + box_id + ' button.bootstrapradio[name="primary_group_id"][value="' + id + '"]');
                // If switch is turned on, enable the corresponding button, otherwise turn off and disable it
                if (data.value) {
                    console.log("enabling");
                    primary_button.bootstrapradio('disabled', false);
                } else {
                    console.log("disabling");
                    primary_button.bootstrapradio('disabled', true);
                }
            });

            // Link submission buttons
            $('#' + box_id + ' form').submit(function(e){
                var errorMessages = validateFormFields(box_id);
                if (errorMessages.length > 0) {
                    $('#' + box_id + ' .dialog-alert').html("");
                    $.each(errorMessages, function (idx, msg) {
                        $('#' + box_id + ' .dialog-alert').append("<div class='alert alert-danger'>" + msg + "</div>");
                    });
                } else {
                    if (dashboard_id != "")
                        updateDashboard(box_id, dashboard_id);
                    else
                        createDashboard(box_id);
                }
                e.preventDefault();
            });
        });
}

// Display dashboard info in a panel
function dashboardDisplay(box_id, dashboard_id) {
    console.log('dashboardDisplay');
    // Generate the form
    $.ajax({
        type: "GET",
        url: FORMSPATH + "form_dashboard.php",
        data: {
            box_id: box_id,
            render_mode: 'panel',
            dashboard_id: dashboard_id,
            ajaxMode: "true",
            fields: {
                'dashboard_name' : {
                    'display' : 'disabled'
                },
                'dashboard_order' : {
                    'display' : 'disabled'
                },
                'widget_config' : {
                    'display' : 'disabled'
                }
            }
        },
        dataType: 'json',
        cache: false
    })
        .fail(function(result) {
            addAlert("danger", "Oops, looks like our server might have goofed.  If you're an admin, please check the PHP error logs.");
            alertWidget('display-alerts');
        })
        .done(function(result) {
            $('#' + box_id).html(result['data']);

            // Initialize bootstrap switches for dashboard groups
            var switches = $('#' + box_id + ' input[name="select_groups"]');
            switches.data('on-label', '<i class="fa fa-check"></i>');
            switches.data('off-label', '<i class="fa fa-times"></i>');
            switches.bootstrapSwitch();
            switches.bootstrapSwitch('setSizeClass', 'switch-mini' );

            // Initialize primary group buttons
            $(".bootstrapradio").bootstrapradio();

            // Link buttons
            $('#' + box_id + ' button[name="btn_edit"]').click(function() {
                dashboardForm('dashboard-update-dialog', dashboard_id);
            });

            $('#' + box_id + ' button[name="btn_delete"]').click(function() {
                var dashboard_name = $(this).data('label');
                deleteDashboardDialog('delete-dashboard-dialog', dashboard_id, dashboard_name);
                $('#delete-dashboard-dialog').modal('show');
            });

        });
}

function deleteDashboardDialog(box_id, dashboard_id, dashboard_name){
    console.log('deleteDashboardDialog');
    // Delete any existing instance of the form with the same dashboard_name
    if($('#' + box_id).length ) {
        $('#' + box_id).remove();
    }

    var data = {
        box_id: box_id,
        title: "Delete Dashboard",
        message: "Are you sure you want to delete the Dashboard " + dashboard_name + "?",
        confirm: "Yes, delete Dashboard"
    }

    // Generate the form
    $.ajax({
        type: "GET",
        url: FORMSPATH + "form_confirm_delete.php",
        data: data,
        dataType: 'json',
        cache: false
    })
        .fail(function(result) {
            addAlert("danger", "Oops, looks like our server might have goofed.  If you're an admin, please check the PHP error logs.");
            alertWidget('display-alerts');
        })
        .done(function(result) {
            if (result['errors']) {
                console.log("error");
                alertWidget('display-alerts');
                return;
            }

            // Append the form as a modal dialog to the body
            $( "body" ).append(result['data']);
            $('#' + box_id).modal('show');

            $('#' + box_id + ' .btn-group-action .btn-confirm-delete').click(function(){
                deleteDashboard(dashboard_id);
            });
        });
}

// Create dashboard with specified data from the dialog
function createDashboard(dialog_id) {
    console.log('createDashboard');
    var add_groups = [];
    var group_switches = $('#' + dialog_id + ' input[name="select_groups"]');
    group_switches.each(function(idx, element) {
        group_id = $(element).data('id');
        if ($(element).prop('checked')) {
            add_groups.push(group_id);
        }
    });
    // Process form
    var $form = $('#' + dialog_id + ' form');

    // Serialize and post to the backend script in ajax mode
    var serializedData = $form.serialize();

    serializedData += '&' + encodeURIComponent('add_groups') + '=' + encodeURIComponent(add_groups.join(','));
    serializedData += '&admin=true&skip_activation=true';
    serializedData += '&ajaxMode=true';
    //console.log(serializedData);

    var url = APIPATH + "create_dashboard.php";
    $.ajax({
        type: "POST",
        url: url,
        data: serializedData
    }).done(function(result) {
        processJSONResult(result);
        window.location.reload();
    });
    return;
}

// Update dashboard with specified data from the dialog
function updateDashboard(dialog_id, dashboard_id) {
    console.log('updateDashboard');
    var errorMessages = validateFormFields(dialog_id);
    if (errorMessages.length > 0) {
        $('#' + dialog_id + ' .dialog-alert').html("");
        $.each(errorMessages, function (idx, msg) {
            $('#' + dialog_id + ' .dialog-alert').append("<div class='alert alert-danger'>" + msg + "</div>");
        });
        return false;
    }

    var add_groups = [];
    var remove_groups = [];
    var group_switches = $('#' + dialog_id + ' input[name="select_groups"]');
    group_switches.each(function(idx, element) {
        group_id = $(element).data('id');
        if ($(element).prop('checked')) {
            add_groups.push(group_id);
        } else {
            remove_groups.push(group_id);
        }
    });

    // Process form
    var $form = $('#' + dialog_id + ' form');

    // Serialize and post to the backend script in ajax mode
    var serializedData = $form.serialize();

    serializedData += '&' + encodeURIComponent('add_groups') + '=' + encodeURIComponent(add_groups.join(','));
    serializedData += '&' + encodeURIComponent('remove_groups') + '=' + encodeURIComponent(remove_groups.join(','));
    serializedData += '&dashboard_id=' + dashboard_id;
    serializedData += '&ajaxMode=true';
    console.log(serializedData);

    var url = APIPATH + "update_dashboard.php";
    $.ajax({
        type: "POST",
        url: url,
        data: serializedData
    }).done(function(result) {
        processJSONResult(result);
        window.location.reload();
    });
    return;
}

function deleteDashboard(dashboard_id) {
    console.log('deleteDashboard');
    var url = APIPATH + "delete_dashboard.php";
    $.ajax({
        type: "POST",
        url: url,
        data: {
            dashboard_id:	dashboard_id,
            ajaxMode:	"true"
        }
    }).done(function(result) {
        processJSONResult(result);
        window.location.reload();
    });
}
