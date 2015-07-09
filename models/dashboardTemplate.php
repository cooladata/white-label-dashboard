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

// Request method: GET
$ajax = checkRequestMode("get");

if (!securePage(__FILE__)){
    apiReturnError($ajax);
}

setReferralPage(getAbsoluteDocumentPath(__FILE__));

?>

<!DOCTYPE html>
<html lang="en">
  <?php
  	echo renderAccountPageHeader(array("#SITE_ROOT#" => SITE_ROOT, "#SITE_TITLE#" => SITE_TITLE, "#PAGE_TITLE#" => "Dashboard"));
  ?>
  <body>
    <script type="text/javascript">

        var widgets = null;
        function getWidgetConfig() {
            var filename = location.pathname.substr(location.pathname.lastIndexOf("/") + 1);
            filename = filename.split('.');
            filename = filename[0].split('_');
            $.post(APIPATH + "get_widget_config.php",
                    {
                        fileNameID: filename[1]
                    },
                    function(data, status){
                        widgets = data;
                        widgets = JSON.parse(widgets);
                        renderWidgets();
                    });
        }
        getWidgetConfig();
    </script>

    <div id="wrapper">

      <!-- Sidebar -->
        <?php
          echo renderMenu(preg_replace('/\.php$/', '', __FILE__));
        ?>
	  <!-- /Sidebar -->


		<!-- DatePicker & Title-->
	    <div class="row datepicker-dashboard">
			<div id="reportrange" class="pull-right">
                <i class="fa fa-calendar fa-lg"></i>
                <span><?php echo date("F j, Y", strtotime('-7 day')); ?> - <?php echo date("F j, Y", strtotime('-1 day')); ?></span> <b class="caret""></b>
            </div>

            <script type="text/javascript">
            var datepicker_start;
            var datepicker_end;
            $('#reportrange').daterangepicker(
                {
                  ranges: {
                     'Yesterday': [moment().subtract(1,'days'), moment().subtract(1,'days')],
                     'Last 7 Days': [moment().subtract(7, 'days'), moment().subtract(1,'days')],
                     'Last 30 Days': [moment().subtract(30, 'days'), moment().subtract(1,'days')],
                     'This Month': [moment().startOf('month'), moment().subtract(1,'days')],
                     'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1,'days').subtract(1,'month').endOf('month')]
                  },
                  startDate: moment().subtract(7,'days'),
                  endDate: moment().subtract(1,'days'),
            	  opens: 'center'
                },
                function(start, end) {
                    $('#reportrange span').html(start.format('MMMM D, YYYY') + ' - ' + end.format('MMMM D, YYYY'));
            		datepicker_start = start.format('YYYY-MM-DD');
            		datepicker_end = end.format('YYYY-MM-DD');
                }
            );
            datepicker_start = moment().subtract('days', 7).format('YYYY-MM-DD');
            datepicker_end = moment().subtract('days', 1).format('YYYY-MM-DD');

            $('#reportrange').on('apply.daterangepicker', function(ev, picker) {
            	responses = []
            	showLoader();
            	getData();
            });
            </script>
		</div>
		<!-- /DatePicker -->

        <img class="loader" src="../images/ajax-loader.gif" />
        <div class="gridster" style="clear:both; margin-left: 10px;">
            <ul style="position: absolute; display: none;">
            </ul>
        </div>


	<script type="text/javascript">
	 var responses = [];
     function renderWidgets(){
         var availWidth = ($(document).width() - 200) / 4.35;
         $(function(){
             $(".block-widget-outer").css('width',availWidth);
             var gridster = $(".gridster ul").gridster({
                    widget_margins: [10, 10],
                    widget_base_dimensions: [availWidth, 200],
                    max_cols: 4,
                    //resize: {
                    //    enabled: true,
                    //    stop : function() { getData(); }
                    //}
             }).data('gridster');

              for (var i = 0; i < widgets.length; i++) {
                    var j = i+1;
                    var code;
                    switch (widgets[i].type) {
                        case "block":
                            code = '<div class="row block-widget-outer" id="widget' + j + '" style="margin:auto"><div class="block-widget-inner-top"><span class="block-widget-title">' + widgets[i].title + '</span></div><div class="block-widget-inner-bottom"><span class="block-widget-value"></span></div></div>';
                            gridster.add_widget(code, widgets[i].size_x, widgets[i].size_y, widgets[i].col, widgets[i].row)
                            break;
                        case "line":
                            code = '<div class="row widget"><div class="panel panel-default widget-size"><div class="panel-heading"><table><tr><td><i class="fa fa-line-chart fa-lg"></i></td><td><span class="widget-header">' + widgets[i].title + '</span></td></tr></table></div><div class="panel-body"><div id="widget' + j + '"></div></div></div></div>';
                            gridster.add_widget(code, widgets[i].size_x, widgets[i].size_y, widgets[i].col, widgets[i].row)
                            break;
                        case "bar":
                            code = '<div class="row widget"><div class="panel panel-default widget-size"><div class="panel-heading"><table><tr><td><i class="fa fa-bar-chart fa-lg"></i></td><td><span class="widget-header">' + widgets[i].title + '</span></td></tr></table></div><div class="panel-body"><div id="widget' + j + '"></div></div></div></div>';
                            gridster.add_widget(code, widgets[i].size_x, widgets[i].size_y, widgets[i].col, widgets[i].row)
                            break;
                        case "column":
                            code = '<div class="row widget"><div class="panel panel-default widget-size"><div class="panel-heading"><table><tr><td><i class="fa fa-bar-chart fa-lg"></i></td><td><span class="widget-header">' + widgets[i].title + '</span></td></tr></table></div><div class="panel-body"><div id="widget' + j + '"></div></div></div></div>';
                            gridster.add_widget(code, widgets[i].size_x, widgets[i].size_y, widgets[i].col, widgets[i].row)
                            break;
                        case "area":
                            code = '<div class="row widget"><div class="panel panel-default widget-size"><div class="panel-heading"><table><tr><td><i class="fa fa-area-chart fa-lg"></i></td><td><span class="widget-header">' + widgets[i].title + '</span></td></tr></table></div><div class="panel-body"><div id="widget' + j + '"></div></div></div></div>';
                            gridster.add_widget(code, widgets[i].size_x, widgets[i].size_y, widgets[i].col, widgets[i].row)
                            break;
                        case "map":
                            code = '<div class="row widget"><div class="panel panel-default widget-size"><div class="panel-heading"><table><tr><td><i class="fa fa-map-marker fa-lg"></i></td><td><span class="widget-header">' + widgets[i].title + '</span></td></tr></table></div><div class="panel-body"><div id="widget' + j + '"></div></div></div></div>';
                           gridster.add_widget(code, widgets[i].size_x, widgets[i].size_y, widgets[i].col, widgets[i].row)
                            break;
                        case "pie":
                            code = '<div class="row widget"><div class="panel panel-default widget-size"><div class="panel-heading"><table><tr><td><i class="fa fa-pie-chart fa-lg"></i></td><td><span class="widget-header">' + widgets[i].title + '</span></td></tr></table></div><div class="panel-body"><div id="widget' + j + '"></div></div></div></div>';
                            gridster.add_widget(code, widgets[i].size_x, widgets[i].size_y, widgets[i].col, widgets[i].row)
                            break;
                        default:
                            code = '<div class="row widget"><div class="panel panel-default widget-size"><div class="panel-heading"><table><tr><td><i class="fa fa-tachometer fa-lg"></i></td><td><span class="widget-header">' + widgets[i].title + '</span></td></tr></table></div><div class="panel-body"><div id="widget' + j + '"></div></div></div></div>';
                            gridster.add_widget(code, widgets[i].size_x, widgets[i].size_y, widgets[i].col, widgets[i].row)
                            break;
                    }
                 }
            getData();

         });
     }
    </script>


	<script type="text/javascript" src="https://www.google.com/jsapi"></script>
	<script type="text/javascript">
     var loadedLookups = false;
     google.load('visualization', '1.0', { 'packages': ['corechart','table','geochart','calendar'] });
     //google.setOnLoadCallback(populate_gridster);
     function getData() {
          showLoader();
          var queries = [];
          widgets.forEach(function(obj) {
             queries.push(setDates(obj.query));
          });
          if (responses.length == 0) {
              $.ajax({
                  url: APIPATH + "load_cooladata_query.php",
                  dataType: "json",
                  type: "POST",
                  async: true,
                  data: {
                      'tq': queries
                  },
                  success: function (json) {
                      for (var x = 0; x < json.length; x++) {
                        drawWidget(x, JSON.parse(json[x]).table);
                        responses.push(JSON.parse(json[x]).table);
                        showCharts();
                      }
                  }
              });
          }
          else {
              for (var x = 0; x < responses.length; x++) {
                drawWidget(x, responses[x]);
              }
              showCharts();
          }
     }

     function showLoader() {
         $(".gridster > ul").css("display","none");
         $(".loader").css("display","block");
     }

     function showCharts() {
        $(".loader").css("display","none");
        $(".gridster > ul").css("display","block");
     }

     function drawWidget(id, data) {
        var widget = widgets.filter(function(x) { return x.id === id+1 })[0];
        var type = widget.type;
        var chart;
        var options;
        switch (type) {
            case "line":
                data = new google.visualization.DataTable(data);
                chart = new google.visualization.LineChart(document.getElementById('widget' + (id+1)));
                options = {
                    height: "340",
                    width: "100%",
                    colors: ['#6DB400','#008EC3','#A70195','#C3022A','#6DB400','#009ED9','#BA01A6','#D9022F'],
                    animation: {
                        startup: true,
                        duration: 1000,
                        easing: 'out',

                    },
                    legend: {
                        position: 'top',
                        alignment: 'center',
                    },
                    hAxis: {
                        gridlines: {
                           color: 'transparent'
                        },
                         textStyle: {
                             fontName: "Arial",
                             fontSize: 13
                         }
                    },
                      vAxis: {
                           gridlines: {
                             color: 'transparent'
                          },
                           textStyle: {
                               fontName: "Arial",
                               fontSize: 13
                           }
                       }
                };
                options.hAxis.format = 'MMM dd';
                chart.draw(data, options);
                break;
            case "bar":
                data = new google.visualization.DataTable(data);
                chart = new google.visualization.BarChart(document.getElementById('widget' + (id+1)));
                options = {
                    height: "340",
                    width: "100%",
                    colors: ['#6DB400','#008EC3','#A70195','#C3022A','#6DB400','#009ED9','#BA01A6','#D9022F'],
                    animation: {
                        duration: 1000,
                        easing: 'out',
                        startup: true
                    },
                    legend: {
                        position: 'top',
                        alignment: 'center',
                    },
                     hAxis: {
                         gridlines: {
                           color: 'transparent'
                        },
                         textStyle: {
                             fontName: "Arial",
                             fontSize: 13
                         }
                     },
                     vAxis: {
                          gridlines: {
                            color: 'transparent'
                         },
                          textStyle: {
                              fontName: "Arial",
                              fontSize: 13
                          }
                      }
                };
                options.vAxis.format = 'MMM dd';
                chart.draw(data, options);
                break;
            case "column":
                data = new google.visualization.DataTable(data);
                chart = new google.visualization.ColumnChart(document.getElementById('widget' + (id+1)));
                options = {
                    height: "340",
                    width: "100%",
                    colors: ['#A70195','#008EC3','#6DB400','#C3022A','#BA01A6','#009ED9','#6DB400','#D9022F'],
                    animation: {
                        duration: 1000,
                        easing: 'out',
                        startup: true
                    },
                    legend: {
                        position: 'top',
                        alignment: 'center',
                    },
                     hAxis: {
                         gridlines: {
                           color: 'transparent'
                        },
                         textStyle: {
                             fontName: "Arial",
                             fontSize: 13
                         }
                     },
                    vAxis: {
                    gridlines: {
                      color: 'transparent'
                    },
                    textStyle: {
                        fontName: "Arial",
                        fontSize: 13
                    }
                    }
                };
                options.hAxis.format = 'MMM dd';
                chart.draw(data, options);
                break;
            case "area":
                data = new google.visualization.DataTable(data);
                chart = new google.visualization.AreaChart(document.getElementById('widget' + (id+1)));
                options = {
                    height: "340",
                    width: "100%",
                    colors: ['#008EC3','#6DB400','#C3022A','#A70195','#009ED9','#6DB400','#D9022F','#BA01A6'],
                    animation: {
                        duration: 1000,
                        easing: 'out',
                        startup: true
                    },
                    legend: {
                        position: 'top',
                        alignment: 'center',
                    },
                     hAxis: {
                         gridlines: {
                           color: 'transparent'
                        },
                        textStyle: {
                            fontName: "Arial",
                            fontSize: 13
                        },
                     },
                       vAxis: {
                            gridlines: {
                              color: 'transparent'
                           },
                            textStyle: {
                                fontName: "Arial",
                                fontSize: 13
                            }
                        }
                };
                options.hAxis.format = 'MMM dd';
                chart.draw(data, options);
                break;
            case "pie":
                data = new google.visualization.DataTable(data);
                chart = new google.visualization.PieChart(document.getElementById('widget' + (id+1)));
                options = {
                    height: "340",
                    width: "100%",
                    colors: ['#6DB400','#008EC3','#A70195','#C3022A','#6DB400','#009ED9','#BA01A6','#D9022F'],
                    animation: {
                        duration: 1000,
                        easing: 'out',
                        startup: true
                    },
                    legend: {
                        position: 'top',
                        alignment: 'center',
                    },
                  hAxis: {
                     textStyle: {
                         fontName: "Arial",
                         fontSize: 13
                     }
                  },
                    vAxis: {
                         textStyle: {
                             fontName: "Arial",
                             fontSize: 13
                         }
                     },
                     pieHole : 0.4
                };
                chart.draw(data, options);
                break;
            case "table":
                data = new google.visualization.DataTable(data);
                chart = new google.visualization.Table(document.getElementById('widget' + (id+1)));
                options = {
                    height: "340",
                    width: "100%"
                };
                chart.draw(data, options);
                break;
            case 'map':
                data = new google.visualization.DataTable(data);
                chart = new google.visualization.GeoChart(document.getElementById('widget' + (id+1)));
                options = {
                    height: "340",
                    width: "100%",
                    region: widget.region,
                    resolution: "provinces",
                    colorAxis: {colors: ['#FFE6E6', '#C3022A']},
                };
                chart.draw(data, options);
                break;
            case "calendar":
                data = new google.visualization.DataTable(data);
                chart = new google.visualization.Calendar(document.getElementById('widget' + (id+1)));
                options = {
                    height: "340",
                    width: "100%",
                };
                chart.draw(data, options);
                break;
            case 'block':
                var color = widget.color;
                $("#widget" + (id+1) + " > .block-widget-inner-bottom > .block-widget-value")[0].innerHTML = numberWithCommas(data.rows[0].c[0].v);
                $("#widget" + (id+1) + " > .block-widget-inner-top").addClass(color);
                $("#widget" + (id+1) + " > .block-widget-inner-bottom").addClass(color);
                break;
        }
     }

      function setDates(query) {
        return query.replace("datepicker_start", datepicker_start).replace("datepicker_end",datepicker_end);
      }

      function numberWithCommas(x) {
          return x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
      }

	</script>
  </body>
</html>