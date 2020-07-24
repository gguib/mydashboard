<?php
/*
 -------------------------------------------------------------------------
 MyDashboard plugin for GLPI
 Copyright (C) 2015 by the MyDashboard Development Team.
 -------------------------------------------------------------------------

 LICENSE

 This file is part of MyDashboard.

 MyDashboard is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 MyDashboard is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with MyDashboard. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

/**
 * Class PluginMydashboardKpiviasante
 */
class PluginMydashboardTrescal extends CommonGLPI {

   public $widgets = [];
   private $options;
   private $form;
   const GROUP_HELPDESK = 1;
   const ENTITY_FR = 1;

   /**
    * PluginMydashboardInfotel constructor.
    *
    * @param array $_options
    */
   public function __construct($_options = []) {
      $this->options = $_options;

      $preference = new PluginMydashboardPreference();
      if (Session::getLoginUserID() !== false && !$preference->getFromDB(Session::getLoginUserID())) {
         $preference->initPreferences(Session::getLoginUserID());
      }
      $preference->getFromDB(Session::getLoginUserID());
      $this->preferences = $preference->fields;
   }

   function init() {
      global $CFG_GLPI;
   }

   function generateTitle($object, $status, $filters, $dataType, $pluriel = 2, $widgetType) {


      $objects = ["NUMBER" => __('Number of', 'mydashboard'),
                  "AVERAGE" => __('Average of', 'mydashboard'),
                  "TIME" => __('Time passed', 'mydashobard'),
                  "PROBLEM" => __('Problems', 'mydashboard'),
                  "TAKEN INTO ACCOUNT" => __('Taken into account', 'mydashboard'),
                  "DISTRIBUTION" => __('Distribution of', 'mydashboard'),
                  "RESOLUTION TIME RESPECTED" => __('Resolution time respected', 'mydashboard'),
                  null => ""];


      $statusNames = ["OPENED TICKETS IN MONTH" => __('opened tickets in month', 'mydashboard'),
                      "OPENED AND WAITING TICKET" => __('opened and waiting ticket', 'mydashboard'),
                      "CLOSED AND OPENED TICKETS FOR HELPDESK GROUP" => __("closed and opened tickets for helpdesk group", "mydashboard"),
                      "TICKETS LIFETIME AND TAKE INTO ACCOUNT" => __('lifetime and support for tickets', 'mydashboard'),
                      "TICKETS CLOSED" => __('closed tickets', 'mydashboard'),
                      "IDENTIFIED" => __('identified', 'mydashboard'),
                      "TICKETS CLOSED BY CATEGORY" => __('tickets closed by category', 'mydashboard'),
                      "TICKET STOCK" => __('Ticket stock by month', 'mydashboard'),

         null => ""];


      $dataFilters = ["BY POLE" => __('by pole', "mydashboard"), "IN MONTH" => __('in month', "mydashboard"), "BY MONTH" => __('by month', "mydashboard"), "BY TECHNICIAN" => __('by technician', "mydashboard"), "WITHIN TWO HOURS" => __('within two hours', 'mydashboard'), null => ""];

      $widgetTypes = ["BAR" => "&nbsp;<i class='fas fa-chart-bar'></i>&nbsp;", "LINE" => "&nbsp;<i class='fas fa-chart-line'></i>&nbsp;", "PIE" => "&nbsp;<i class='fa fa-chart-pie'></i>&nbsp;", null => ""];

      $title = "";


      if ($object) {
         $title .= $objects[$object] . " ";
      }


      if ($status) {
         $title .= $statusNames[$status] . " ";
      }


      if ($filters) {
         $title .= $dataFilters[$filters];
      }


      if ($widgetType) {
         $title .= $widgetTypes[$widgetType];
      }

      return $title;
   }

   /**
    * @return array
    */
   public function getWidgetsForItem() {

      $isDebug = $_SESSION['glpi_use_mode'] == Session::DEBUG_MODE;

      $titles = [];
      $titles[$this->getType() . "1"] = "1 " . $this->generateTitle("NUMBER", "OPENED TICKETS IN MONTH", "BY POLE", "", (($isDebug) ? "1 " : ""), "BAR");
      $titles[$this->getType() . "2"] = "2 " . $this->generateTitle("NUMBER", "OPENED AND WAITING TICKET", "BY POLE", "", (($isDebug) ? "2 " : ""), "BAR");
      $titles[$this->getType() . "3"] = "3 " . $this->generateTitle("NUMBER", "CLOSED AND OPENED TICKETS FOR HELPDESK GROUP", "IN MONTH", "", (($isDebug) ? "3 " : ""), "BAR");
      $titles[$this->getType() . "4"]  = "4 " . $this->generateTitle("NUMBER", "TICKET STOCK", "BY MONTH", "", (($isDebug) ? "4 " : ""), "LINE");
      $titles[$this->getType() . "5"] = "5 " . $this->generateTitle("AVERAGE", "TICKETS LIFETIME AND TAKE INTO ACCOUNT", "BY MONTH", "", (($isDebug) ? "5 " : ""), "LINE");
      $titles[$this->getType() . "6"] = "6 " . $this->generateTitle("TAKEN INTO ACCOUNT", "", "WITHIN TWO HOURS", "", (($isDebug) ? "6 " : ""), "BAR");
      $titles[$this->getType() . "7"] = "7 " . $this->generateTitle("RESOLUTION TIME RESPECTED", "", "WITHIN TWO HOURS", "", (($isDebug) ? "7 " : ""), "BAR");
      $titles[$this->getType() . "8"] = "8 " . $this->generateTitle("DISTRIBUTION", "TICKETS CLOSED BY CATEGORY", "BY TECHNICIAN", "", (($isDebug) ? "8 " : "BAR"), "LINE");
      $titles[$this->getType() . "9"] = "9 " . $this->generateTitle("TIME", "", "BY TECHNICIAN", "", (($isDebug) ? "9 " : ""), "BAR");
      $titles[$this->getType() . "10"] = "10 " . $this->generateTitle("NUMBER", "TICKETS CLOSED", "BY MONTH", "", (($isDebug) ? "10 " : ""), "BAR");
      $titles[$this->getType() . "11"] = "11 " . $this->generateTitle("PROBLEM", "IDENTIFIED", "IN MONTH", "", (($isDebug) ? "11 " : ""), "BAR");

      return [__('Trescal') => $titles];
   }


   /**
    * @param $widgetId
    *
    * @return PluginMydashboardDatatable|PluginMydashboardHBarChart|PluginMydashboardHtml|PluginMydashboardLineChart|PluginMydashboardPieChart|PluginMydashboardVBarChart
    */
   public function getWidgetContentForItem($widgetId, $opt = []) {
      global $DB;

      $isDebug = $_SESSION['glpi_use_mode'] == Session::DEBUG_MODE;

      switch ($widgetId) {

         case $this->getType() . "1":
            $criterias = ['year', 'type', 'multiple_locations_id', 'entities_id', 'is_recursive'];
            $params = ["preferences" => $this->preferences, "criterias" => $criterias, "opt" => $opt];
            $options = PluginMydashboardHelper::manageCriterias($params);

            $opt = $options['opt'];
            $crit = $options['crit'];

            $tickets_per_group = self::getTicketsPerGroup($crit);

            $months_t = Toolbox::getMonthsOfYearArray();
            $months = [];
            foreach ($months_t as $key => $month) {
               $months[] = $month;
            }

            $nb_bar = 0;
            foreach ($tickets_per_group as $group_id => $tickets) {
               $nb_bar++;
            }
            $palette = PluginMydashboardColor::getColors($nb_bar);
            $i = 0;
            $dataset = [];
            foreach ($tickets_per_group as $group_id => $tickets) {
               unset($tickets_per_group[$group_id]);
               $groupName = self::getGroupName($group_id);
               $i++;

               $dataset[] = ["label" => $groupName, "data" => array_values($tickets), "backgroundColor" => $palette[$i]];
            }


            $widget = new PluginMydashboardHtml();
            $widget->setWidgetTitle((($isDebug) ? "1 " : "") . $this->generateTitle("NUMBER", "OPENED TICKETS IN MONTH", "BY POLE", "", (($isDebug) ? "1 " : ""), ""));
            $widget->setWidgetComment(__("Sum of ticket affected by technicians", "mydashboard"));

            $dataLineset = json_encode($dataset);
            $labelsLine = json_encode($months);

            $graph = "<script type='text/javascript'>
                     var TicketsByGroupChart = {
                           datasets: $dataLineset,
                           labels: $labelsLine
                           };
                     
                            var isChartRendered = false;
                            var canvas = document . getElementById('TicketsByGroupChart');
                            var ctx = canvas . getContext('2d');
                            ctx.canvas.width = 700;
                            ctx.canvas.height = 400;
                            var TicketsByGroupChart = new Chart(ctx, {
                                  type: 'bar',
                                  data: TicketsByGroupChart,
                                  options: {
                                      responsive:true,
                                      maintainAspectRatio: true,
                                      title:{
                                          display:false,
                                          text:'TimeByGroupChart'
                                      },
                                      tooltips: {
                                          mode: 'index',
                                          intersect: false
                                      },
                                      scales: {
                                          xAxes: [{
                                              stacked: true,
                                              gridLines: {
                                              display: false
                                             },
                                          }],
                                          yAxes: [{
                                              stacked: true
                                          }]
                                      },
                                      animation: {
                                          onComplete: function() {
                                            isChartRendered = true
                                          }
                                        },
                                      legend: {
                                         display: true
                                       },
                                       plugins: {
                                          datalabels: {
                                             display: true,
                                             align: 'center',
                                             anchor: 'center'
                                          }
                                    }
                                  }
                              });
                      </script>";

            $params = ["widgetId" => $widgetId, "name" => "TicketsByGroupChart", "onsubmit" => true, "opt" => $opt, "criterias" => $criterias, "export" => true, "canvas" => true, "nb" => count($dataset)];
            $widget->setWidgetHeader(PluginMydashboardHelper::getGraphHeader($params));
            $widget->setWidgetHtmlContent($graph);

            return $widget;

            break;

         case $this->getType() . "2":
            $name = 'reportHorizontalStackedBarNbOpenedAndWatingTicketByPole';

            $criterias = ['type','entities_id', 'is_recursive', 'multiple_locations_id'];
            $params = ["preferences" => $this->preferences, "criterias" => $criterias, "opt" => $opt];
            $options = PluginMydashboardHelper::manageCriterias($params);

            $opt = $options['opt'];
            $crit = $options['crit'];

            $isDeleted = " `glpi_tickets`.`is_deleted` = 0 ";
            $waitingTicket = " AND `glpi_tickets`.`status` = " . CommonITILObject::WAITING;
            $type_criteria = $crit['type'];
            $entities_criteria = $crit['entities_id'];
            $locations_criteria = $crit['multiple_locations_id'];

            $assign = Group_Ticket::ASSIGN;

            $datasets = [];

            $queryWaitingTicket = "SELECT `glpi_groups`.`name` as groupName, COUNT(`glpi_tickets`.`id`)  AS nb
                           FROM `glpi_tickets`  INNER JOIN `glpi_groups_tickets` 
                           ON (`glpi_tickets`.`id` = `glpi_groups_tickets`.`tickets_id`
                                AND `glpi_groups_tickets`.`type` = {$assign} AND {$isDeleted} )
                            LEFT JOIN `glpi_groups` ON `glpi_groups`.`id` = `glpi_groups_tickets`.`groups_id`
                           WHERE {$isDeleted} {$type_criteria} {$waitingTicket} {$entities_criteria} {$locations_criteria}
                             GROUP BY  `glpi_groups`.`name`";


            $results = $DB->query($queryWaitingTicket);
            $nbResults = $DB->numrows($results);
            $tabWaitingTickets = [];
            $tabnames = [];

            $opened = "AND `status` NOT IN (" . CommonITILObject::SOLVED . "," . CommonITILObject::CLOSED . ")";

            if ($nbResults) {
               while ($data = $DB->fetch_array($results)) {
                  $tabWaitingTickets[] = $data['nb'];
                  $tabnames[] = $data['groupName'];
               }
            }

            $queryOpenedTicket = "SELECT `glpi_groups`.`name` as grName, COUNT(`glpi_tickets`.`id`)  AS nb
                           FROM `glpi_tickets`  INNER JOIN `glpi_groups_tickets` 
                           ON (`glpi_tickets`.`id` = `glpi_groups_tickets`.`tickets_id`
                                AND `glpi_groups_tickets`.`type` = {$assign} AND {$isDeleted})
                            LEFT JOIN `glpi_groups` ON `glpi_groups`.`id` = `glpi_groups_tickets`.`groups_id`
                           WHERE {$isDeleted} {$type_criteria} {$opened} {$entities_criteria} 
                             GROUP BY  `glpi_groups`.`name`";


            $results = $DB->query($queryOpenedTicket);
            $nbResults = $DB->numrows($results);
            $tabTickets = [];
            $tabOpenedNames = [];
            $tabOpenedTickets = [];

            if ($nbResults) {
               while ($data = $DB->fetch_array($results)) {
                  $tabOpenedTickets[] = $data['nb'];
                  $tabOpenedNames[] = $data['grName'];
               }
            }

            $widget = new PluginMydashboardHtml();
            $title = $this->generateTitle("NUMBER", "OPENED AND WAITING TICKET", "BY POLE", "", (($isDebug) ? "2 " : ""), "");
            $widget->setWidgetTitle((($isDebug) ? "2 " : "") . $title);
            $widget->setWidgetComment(__("Sum of ticket affected by group", "mydashboard"));
            $widget->toggleWidgetRefresh();


            $titleOpenedTicket = __("Opened", "mydashboard");
            $titleWaitingTicket = __("Waiting", "mydashboard");
            $labels = json_encode($tabOpenedNames);


            $datasets[] = ["type" => "horizontalBar", "data" => $tabWaitingTickets, "label" => $titleWaitingTicket, "backgroundColor" => '#6B8E23'];

            $datasets[] = ["type" => "horizontalBar", "data" => $tabOpenedTickets, "label" => $titleOpenedTicket, "backgroundColor" => '#000080'];

            $max = 100;
            if (!empty($tabTickets)) {
               $max = max($tabTickets) * 10;
            }

            $graph_datas = ['name' => $name, 'ids' => json_encode([]), 'data' => json_encode($datasets), 'labels' => $labels, 'label' => $title, 'max' => $max];


            $graph = PluginMydashboardBarChart::launcHorizontalBar($graph_datas, []);

            $params = ["widgetId" => $widgetId, "name" => $name, "onsubmit" => true, "opt" => $opt, "criterias" => $criterias, "export" => true, "canvas" => true, "nb" => 1];
            $widget->setWidgetHeader(PluginMydashboardHelper::getGraphHeader($params));

            $widget->setWidgetHtmlContent($graph);

            return $widget;
            break;


         case $this->getType() . "3":

            $criterias = ['year','groups_id', 'type', 'entities_id', 'is_recursive', 'multiple_locations_id'];
            $params = ["preferences" => $this->preferences, "criterias" => $criterias, "opt" => $opt];

            $options = PluginMydashboardHelper::manageCriterias($params);

            $opt = $options['opt'];
            $crit = $options['crit'];

            $closed_tickets_helpdesk = self::getTicketsForHelpdeskGroups($crit, true);
            $opened_tickets_helpdesk = self::getTicketsForHelpdeskGroups($crit, false);

            $months_t = Toolbox::getMonthsOfYearArray();
            $months = [];
            foreach ($months_t as $key => $month) {
               $months[] = $month;
            }

            $dataset = [];
            $closed_ticket_data = array_values($closed_tickets_helpdesk[$crit['groups_id']]);
            $opened_ticket_data = array_values($opened_tickets_helpdesk[$crit['groups_id']]);

            $dataset[] = ["type" => 'bar', "label" => __('Closed'), "data" => $closed_ticket_data, "backgroundColor" => '#CD5C5C'];

            $dataset[] = ["type" => 'bar', "label" => __('Opened'), "data" => $opened_ticket_data, "backgroundColor" => '#6B8E23'];

            //$dataset[] = ["type" => 'line', "label" => __('Objective'), "data" => [600, 600, 600, 600, 600, 600, 600, 600, 600, 600, 600, 600], "backgroundColor" => '#ffdb58', "fill" => false, "lineTension" => '0.1'];


            $widget = new PluginMydashboardHtml();
            $widget->setWidgetTitle((($isDebug) ? "3 " : "") . $this->generateTitle("NUMBER", "CLOSED AND OPENED TICKETS FOR HELPDESK GROUP", "IN MONTH", "", (($isDebug) ? "3 " : ""), ""));
            $widget->setWidgetComment(__("Sum of ticket affected for the Helpdesk group", "mydashboard"));

            $dataLineset = json_encode($dataset);
            $labelsLine = json_encode($months);

            $graph = "<script type='text/javascript'>
                     var TicketsClosedAndOpenedHelpdeskChart = {
                           datasets: $dataLineset,
                           labels: $labelsLine
                           };
                   
                            var isChartRendered = false;
                            var canvas = document . getElementById('TicketsClosedAndOpenedHelpdeskChart');
                            var ctx = canvas . getContext('2d');
                            ctx.canvas.width = 700;
                            ctx.canvas.height = 400;
                            var TicketsClosedAndOpenedHelpdeskChart = new Chart(ctx, {
                                  type: 'bar',
                                  data: TicketsClosedAndOpenedHelpdeskChart,
                                  options: {
                                      responsive:true,
                                      maintainAspectRatio: true,
                                      title:{
                                          display:false,
                                          text:'TimeByGroupChart'
                                      },
                                      tooltips: {
                                          enabled: false,
                                      },
                                      scales: {
                                        yAxes: [{
                                          ticks: {
                                              beginAtZero: true
                                          }
                                      }]
                                      },
                                      animation: {
                                          onComplete: function() {
                                 var ctx = this.chart.ctx;
                                     ctx.font = Chart.helpers.fontString(Chart.defaults.global.defaultFontSize, 'normal', Chart.defaults.global.defaultFontFamily);
                                     ctx.fillStyle = '#595959';
                                     ctx.textAlign = 'center';
                                     ctx.textBaseline = 'bottom';
                                     this.data.datasets.forEach(function (dataset) {
                                         for (var i = 0; i < dataset.data.length; i++) {
                                             var model = dataset._meta[Object.keys(dataset._meta)[0]].data[i]._model;
                                             ctx.fillText(dataset.data[i], model.x, model.y - 5);
                                         }
                                     });
                                isChartRendered = true;
                                          }
                                        }
                                  }
                              });
                      </script>";

            $params = ["widgetId" => $widgetId, "name" => "TicketsClosedAndOpenedHelpdeskChart", "onsubmit" => true, "opt" => $opt, "criterias" => $criterias, "export" => true, "canvas" => true, "nb" => count($dataset)];
            $widget->setWidgetHeader(PluginMydashboardHelper::getGraphHeader($params));
            $widget->setWidgetHtmlContent($graph);

            return $widget;

            break;

         case $this->getType() . "4":

            $criterias = ['entities_id', 'is_recursive', 'groups_id'];
            $params    = ["preferences" => $this->preferences,
               "criterias"   => $criterias,
               "opt"         => $opt];
            $options   = PluginMydashboardHelper::manageCriterias($params);

            $opt  = $options['opt'];
            $crit = $options['crit'];
            $groups_sql_criteria = "";

            // GROUP
            if (isset($crit['groups_id']) && $crit['groups_id'] != 0 && !empty($crit['groups_id'])){
                  $groups_sql_criteria .= "AND `glpi_plugin_mydashboard_stocktickets`.`groups_id`  = ". $crit['groups_id'];
               }


            $entities_criteria = $crit['entities_id'];
            $mdentities        = self::getSpecificEntityRestrict("glpi_plugin_mydashboard_stocktickets", $opt);

            $currentmonth = date("m");
            $currentyear  = date("Y");
            $previousyear = $currentyear - 1;
            $query_2      = "SELECT DATE_FORMAT(`glpi_plugin_mydashboard_stocktickets`.`date`, '%Y-%m') as month,
                                    DATE_FORMAT(`glpi_plugin_mydashboard_stocktickets`.`date`, '%b %Y') as monthname,
                                    SUM(nbStockTickets) as nbStockTickets
                                    FROM `glpi_plugin_mydashboard_stocktickets`
                                    WHERE  (`glpi_plugin_mydashboard_stocktickets`.`date` >= '$previousyear-$currentmonth-01 00:00:00')
                                    AND (`glpi_plugin_mydashboard_stocktickets`.`date` <= '$currentyear-$currentmonth-01 00:00:00')
                                    " . $mdentities .  $groups_sql_criteria . "
                                    GROUP BY DATE_FORMAT(`glpi_plugin_mydashboard_stocktickets`.`date`, '%Y-%m')";

            $tabdata    = [];
            $tabnames   = [];
            $results2   = $DB->query($query_2);
            $maxcount   = 0;
            $i          = 0;
            $is_deleted = "`glpi_tickets`.`is_deleted` = 0";
            while ($data = $DB->fetch_array($results2)) {
               $tabdata[$i] = $data["nbStockTickets"];
               $tabnames[]  = $data['monthname'];
               if ($data["nbStockTickets"] > $maxcount) {
                  $maxcount = $data["nbStockTickets"];
               }
               $i++;
            }

            $query = "SELECT DATE_FORMAT(`glpi_tickets`.`date`, '%Y-%m') AS month, 
                        DATE_FORMAT(`glpi_tickets`.`date`, '%b %Y') AS monthname, 
                        DATE_FORMAT(`glpi_tickets`.`date`, '%Y%m') AS monthnum, count(MONTH(`glpi_tickets`.`date`))
                        FROM `glpi_tickets`
                        WHERE $is_deleted ";
            $query .= $entities_criteria . " 
                     AND MONTH(`glpi_tickets`.`date`)='" . date("m") . "' 
                     AND(YEAR(`glpi_tickets`.`date`) = '" . date("Y") . "') 
                     GROUP BY DATE_FORMAT(`glpi_tickets`.`date`, '%Y-%m')";

            $results = $DB->query($query);
            while ($data = $DB->fetch_array($results)) {

               list($year, $month) = explode('-', $data['month']);

               $nbdays  = date("t", mktime(0, 0, 0, $month, 1, $year));
               $query_1 = "SELECT COUNT(*) as count FROM `glpi_tickets`
                     WHERE $is_deleted " . $entities_criteria . "
                     AND (((`glpi_tickets`.`date` <= '$year-$month-$nbdays 23:59:59') 
                     AND `status` NOT IN (" . CommonITILObject::SOLVED . "," . CommonITILObject::CLOSED . ")) 
                     OR ((`glpi_tickets`.`date` <= '$year-$month-$nbdays 23:59:59') 
                     AND (`glpi_tickets`.`solvedate` > ADDDATE('$year-$month-$nbdays 00:00:00' , INTERVAL 1 DAY))))";

               $results_1 = $DB->query($query_1);
               $data_1    = $DB->fetch_array($results_1);

               $tabdata[$i] = $data_1['count'];

               $tabnames[] = $data['monthname'];
               $i++;
            }

            $widget = new PluginMydashboardHtml();
            $title  = $this->generateTitle("NUMBER", "TICKET STOCK", "BY MONTH", "", (($isDebug) ? "4 " : ""), "");
            $widget->setWidgetComment(__("Sum of not solved tickets by month", "mydashboard"));
            $widget->setWidgetTitle((($isDebug)?"4 ":"").$title);
            $widget->toggleWidgetRefresh();

            $dataLineset = json_encode($tabdata);
            $labelsLine  = json_encode($tabnames);

            $month     = _n('month', 'months', 2);
            $nbtickets = __('Tickets number', 'mydashboard');

            $graph = "<script type='text/javascript'>
      

            var dataStockLine = {
                    datasets: [{
                      data: $dataLineset,
                      label: '$title',
                      borderColor: '#1f77b4',
                            fill: false,
                            lineTension: '0.1',
                    }],
                  labels:
                  $labelsLine
                  };
            
                 var isChartRendered = false;
                  var canvas = document . getElementById('TicketStockLineChart');
                   var ctx = canvas . getContext('2d');
                   ctx.canvas.width = 700;
                   ctx.canvas.height = 400;
                   var TicketStockLineChart = new Chart(ctx, {
                  type:
                  'line',
                     data: dataStockLine,
                     options: {
                     responsive: true,
                     maintainAspectRatio: true,
                      title:{
                          display: false,
                          text:'Line Chart'
                      },
                      tooltips: {
                     mode:
                     'index',
                          intersect: false,
                      },
                      hover: {
                     mode:
                     'nearest',
                          intersect: true
                      },
                      scales: {
                     xAxes:
                     [{
                        display:
                        true,
                              scaleLabel: {
                           display:
                           true,
                                  labelString: '$month'
                              }
                          }],
                          yAxes: [{
                        display:
                        true,
                              scaleLabel: {
                           display:
                           true,
                                  labelString: '$nbtickets'
                              }
                          }]
                      },
                       animation: {
                        onComplete: function() {
                          isChartRendered = true
                        }
                      }
                   }
                   });

             </script>";

            $params = ["widgetId"  => $widgetId,
               "name"      => 'TicketStockLineChart',
               "onsubmit"  => false,
               "opt"       => $opt,
               "criterias" => $criterias,
               "export"    => true,
               "canvas"    => true,
               "nb"        => 1];
            $widget->setWidgetHeader(PluginMydashboardHelper::getGraphHeader($params));

            $widget->setWidgetHtmlContent(
               $graph
            );

            return $widget;

            break;


         case $this->getType() . "5" :
            $name = 'reportLineLifeTimeAndTakenAccountAverageByMonthHelpdesk';

            $criterias = ['entities_id', 'is_recursive', 'type', 'year' , 'groups_id'];
            $params = ["preferences" => $this->preferences, "criterias" => $criterias, "opt" => $opt];
            $options = PluginMydashboardHelper::manageCriterias($params);

            $opt = $options['opt'];
            $crit = $options['crit'];

            $lifetime_avg_ticket = self::getLifetimeOrTakeIntoAccountTicketAverage($crit);

            $months_t = Toolbox::getMonthsOfYearArray();
            $months = [];
            foreach ($months_t as $key => $month) {
               $months[] = $month;
            }

            $dataset = [];
            $avg_tickets_data = array_values($lifetime_avg_ticket[self::GROUP_HELPDESK]);
            foreach ($avg_tickets_data as $avg_tickets_d) {
               $avg_lifetime_ticket_data[] = round($avg_tickets_d['lifetime'] / $avg_tickets_d['nb'], 2);
               $avg_takeintoaccount_ticket_data[] = round($avg_tickets_d['takeintoaccount'] / $avg_tickets_d['nb'], 2);
            }

            $dataset[] = ["type" => 'line', "label" => __('Life time average', 'mydashboard'), "data" => $avg_lifetime_ticket_data, "backgroundColor" => '#CD5C5C', 'fill' => false];

            $dataset[] = ["type" => 'line', "label" => __('Take into account average', 'mydashboard'), "data" => $avg_takeintoaccount_ticket_data, "backgroundColor" => '#6B8E23', 'fill' => false];

            $widget = new PluginMydashboardHtml();
            $title = $this->generateTitle("AVERAGE", "TICKETS LIFETIME AND TAKE INTO ACCOUNT", "BY MONTH", "", (($isDebug) ? "5 " : ""), "");
            $widget->setWidgetTitle((($isDebug) ? "5 " : "") . $title);
            $widget->setWidgetComment(__("Helpdesk pole", "mydashboard"));
            $widget->toggleWidgetRefresh();

            $dataLineset = json_encode($dataset);
            $labelsLine = json_encode($months);

            $graph = "<script type='text/javascript'>
                     var reportLineLifeTimeAndTakenAccountAverageByMonthHelpdesk = {
                           datasets: $dataLineset,
                           labels: $labelsLine
                           };
                   
                            var isChartRendered = false;
                            var canvas = document . getElementById('reportLineLifeTimeAndTakenAccountAverageByMonthHelpdesk');
                            var ctx = canvas . getContext('2d');
                            ctx.canvas.width = 700;
                            ctx.canvas.height = 400;
                            var reportLineLifeTimeAndTakenAccountAverageByMonthHelpdesk = new Chart(ctx, {
                                  type: 'bar',
                                  data: reportLineLifeTimeAndTakenAccountAverageByMonthHelpdesk,
                                  options: {
                                      responsive:true,
                                      maintainAspectRatio: true,
                                      title:{
                                          display:false,
                                          text:'reportLineLifeTimeAndTakenAccountAverageByMonthHelpdesk'
                                      },
                                      tooltips: {
                                          enabled: false,
                                      },
                                      scales: {
                                          ticks:{beginAtZero:0}
                                      },
                                      animation: {
                                          onComplete: function() {
                                 var ctx = this.chart.ctx;
                                     ctx.font = Chart.helpers.fontString(Chart.defaults.global.defaultFontSize, 'normal', Chart.defaults.global.defaultFontFamily);
                                     ctx.fillStyle = '#595959';
                                     ctx.textAlign = 'center';
                                     ctx.textBaseline = 'bottom';
                                     this.data.datasets.forEach(function (dataset) {
                                         for (var i = 0; i < dataset.data.length; i++) {
                                             var model = dataset._meta[Object.keys(dataset._meta)[0]].data[i]._model;
                                             ctx.fillText(dataset.data[i], model.x, model.y - 5);
                                         }
                                     });
                                isChartRendered = true;
                                          }
                                        }
                                  }
                              });
                      </script>";


            $params = ["widgetId" => $widgetId, "name" => "reportLineLifeTimeAndTakenAccountAverageByMonthHelpdesk", "onsubmit" => true, "opt" => $opt, "criterias" => $criterias, "export" => true, "canvas" => true, "nb" => count($dataset)];
            $widget->setWidgetHeader(PluginMydashboardHelper::getGraphHeader($params));
            $widget->setWidgetHtmlContent($graph);

            return $widget;

            break;

         case $this->getType() . "6" :

            $name = 'reportBarPercentTimeToOwnAndLateTicketSLA';
            $criterias = ['entities_id', 'is_recursive', 'year', 'groups_id'];
            $params = ["preferences" => $this->preferences, "criterias" => $criterias, "opt" => $opt];
            $options = PluginMydashboardHelper::manageCriterias($params);

            $opt = $options['opt'];
            $crit = $options['crit'];

            $taken_into_account = self::getSlaRespectWithinTwoHours($crit, true);

            $dataset                 = [];
            $taken_into_account_ok   = [];
            $taken_into_account_late = [];

            foreach($taken_into_account as $taken) {
               $total = $taken['ok'] + $taken['late'];
               $taken_into_account_ok[] = round(($taken['ok'] * 100) / $total, 2) ;
               $taken_into_account_late[] = round(($taken['late'] * 100) / $total, 2);
            }

            $months_t = Toolbox::getMonthsOfYearArray();
            $months = [];
            foreach ($months_t as $key => $month) {
               $months[] = $month;
            }

            $labelsName = [];

            $dataset[] = ["type" => 'bar', "label" => __('Ok', 'mydashboard'), "data" => $taken_into_account_ok, "backgroundColor" => '#6B8E23'];
            $dataset[] = ["type" => 'bar', "label" => __('Late', 'mydashboard'), "data" => $taken_into_account_late, "backgroundColor" => '#000080'];


            $widget = new PluginMydashboardHtml();
            $title = $this->generateTitle("TAKEN INTO ACCOUNT", "", "WITHIN TWO HOURS", "", (($isDebug) ? "6 " : ""), "");
            $widget->setWidgetTitle((($isDebug) ? "6 " : "") . $title);
            $widget->toggleWidgetRefresh();

            $dataLineset = json_encode($dataset);
            $labelsLine = json_encode($months);

            $graph = "<script type='text/javascript'>
                     var data$name = {
                           datasets: $dataLineset,
                           labels: $labelsLine
                           };
                     
                            var isChartRendered = false;
                            var canvas = document . getElementById('$name');
                            var ctx = canvas . getContext('2d');
                            ctx.canvas.width = 700;
                            ctx.canvas.height = 400;
                            var TicketsByGroupChart = new Chart(ctx, {
                                  type: 'bar',
                                  data: data$name,
                                  options: {
                                      responsive:true,
                                      maintainAspectRatio: true,
                                      title:{
                                          display:false,
                                          text:'$name'
                                      },
                                      tooltips: {
                                          mode: 'index',
                                          intersect: false
                                      },
                                      scales: {
                                          xAxes: [{
                                              stacked: true,
                                          }],
                                          yAxes: [{
                                              stacked: true
                                          }]
                                      },
                                      animation: {
                                          onComplete: function() {
                                            isChartRendered = true
                                          }
                                        }
                                  }
                              });
                      </script>";

            $params = ["widgetId" => $widgetId, "name" => $name, "onsubmit" => true, "opt" => $opt, "criterias" => $criterias, "export" => true, "canvas" => true, "nb" => count($dataset)];
            $widget->setWidgetHeader(PluginMydashboardHelper::getGraphHeader($params));
            $widget->setWidgetHtmlContent($graph);

            return $widget;

            break;

         case $this->getType() . "7" :

            $name = 'reporentities_id_criteriatBarPercentResolutionSLA';
            $criterias = ['entities_id', 'is_recursive', 'year', 'groups_id'];
            $params = ["preferences" => $this->preferences, "criterias" => $criterias, "opt" => $opt];
            $options = PluginMydashboardHelper::manageCriterias($params);

            $opt = $options['opt'];
            $crit = $options['crit'];

            $solve_delay = self::getSlaRespectWithinTwoHours($crit, false);

            $dataset                 = [];
            $solve_ok   = [];
            $solve_late = [];

            foreach($solve_delay as $solve) {
               $total = $solve['ok'] + $solve['late'];
               $solve_ok[] = round(($solve['ok'] * 100) / $total, 2) ;
               $solve_late[] = round(($solve['late'] * 100) / $total, 2);
            }

            $months_t = Toolbox::getMonthsOfYearArray();
            $months = [];
            foreach ($months_t as $key => $month) {
               $months[] = $month;
            }

            $labelsName = [];

            $dataset[] = ["type" => 'bar', "label" => __('Ok', 'mydashboard'), "data" => $solve_ok, "backgroundColor" => '#6B8E23'];
            $dataset[] = ["type" => 'bar', "label" => __('Late', 'mydashboard'), "data" => $solve_late, "backgroundColor" => '#000080'];


            $widget = new PluginMydashboardHtml();
            $title = $this->generateTitle("RESOLUTION TIME RESPECTED", "", "WITHIN TWO HOURS", "", (($isDebug) ? "7 " : ""), "");
            $widget->setWidgetTitle((($isDebug) ? "6 " : "") . $title);
            $widget->toggleWidgetRefresh();

            $dataLineset = json_encode($dataset);
            $labelsLine = json_encode($months);

            $graph = "<script type='text/javascript'>
                     var data$name = {
                           datasets: $dataLineset,
                           labels: $labelsLine
                           };
                     
                            var isChartRendered = false;
                            var canvas = document . getElementById('$name');
                            var ctx = canvas . getContext('2d');
                            ctx.canvas.width = 700;
                            ctx.canvas.height = 400;
                            var TicketsByGroupChart = new Chart(ctx, {
                                  type: 'bar',
                                  data: data$name,
                                  options: {
                                      responsive:true,
                                      maintainAspectRatio: true,
                                      title:{
                                          display:false,
                                          text:'$name'
                                      },
                                      tooltips: {
                                          mode: 'index',
                                          intersect: false
                                      },
                                      scales: {
                                          xAxes: [{
                                              stacked: true,
                                          }],
                                          yAxes: [{
                                              stacked: true
                                          }]
                                      },
                                      animation: {
                                          onComplete: function() {
                                            isChartRendered = true
                                          }
                                        }
                                  }
                              });
                      </script>";

            $params = ["widgetId" => $widgetId, "name" => $name, "onsubmit" => false, "opt" => $opt, "criterias" => $criterias, "export" => true, "canvas" => true, "nb" => count($dataset)];
            $widget->setWidgetHeader(PluginMydashboardHelper::getGraphHeader($params));
            $widget->setWidgetHtmlContent($graph);

            return $widget;

            break;

         case $this->getType() . "8" :

            $criterias = ['entities_id', 'is_recursive', 'year', 'month', 'technicians_id'];
            $params = ["preferences" => $this->preferences, "criterias" => $criterias, "opt" => $opt];

            $options = PluginMydashboardHelper::manageCriterias($params);
            $crit = $options['crit'];
            $opt = $options['opt'];

            $name = 'reportPieRepartitionClosedTicketsByCatByTechnician';

            $closed_tickets_by_tech_by_cat = self::getCLosedTicketsByCategoryByTechnician($crit);
            $i = 0;

            $widget = new PluginMydashboardHtml();
            $title = $this->generateTitle("DISTRIBUTION", "TICKETS CLOSED BY CATEGORY", "BY TECHNICIAN", "", (($isDebug) ? "7 " : "BAR"), "");
            $widget->setWidgetTitle((($isDebug) ? "8 " : "") . $title);
            $widget->toggleWidgetRefresh();

            $username      = '';
            $graph         = '';
            $labelsName    = [];
            $catsName      = [];
            $totalNbCats   = 0;
            $percentNbsCat = [];
            $palette       = "";
            $nbcat         = 0;

            foreach ($closed_tickets_by_tech_by_cat as $cat => $value) {
               $totalNbCats += $value;
            }

            foreach ($closed_tickets_by_tech_by_cat as $cat => $value) {

               if(isset($crit['technicians_id']) && $crit['technicians_id'] != 0) {
                  $username    = getUserName($crit['technicians_id']);
               }

               $nbcat++;
               $dataset     = [];
               $catsName[] = $cat;
               $percentNbsCat  [] = round(($value * 100) / $totalNbCats, 2) ;

               $palette = PluginMydashboardColor::getColors($nbCat);
            }
            $dataset[] = ["type" => 'doughnut', "label" => $username, "data" => $percentNbsCat, "backgroundColor" => $palette,];

            $dataLineset = json_encode($dataset);
            $labelsLine = json_encode($catsName);

            $graph=  "<script type='text/javascript'>";
            $graph.= "
                           var data$name = {
                                 datasets: $dataLineset,
                                 labels: $labelsLine
                                 };
                           
                                  var isChartRendered = false;
                                  var canvas$name = document . getElementById('$name');
                                  var ctx = canvas$name . getContext('2d');
                                  ctx.canvas.width = 800;
                                  ctx.canvas.height = 600;
                                  var $name = new Chart(ctx, {
                                        type: 'doughnut',
                                        data: data$name,
                                   options: {
                                         responsive: true,
                                         maintainAspectRatio: true,
                                         tooltips: {
                                           enabled: false
                                         },
                                    animation: {
                                        onComplete: function () {

                                          var ctx = this.chart.ctx;
                                          ctx.font = Chart.helpers.fontString(Chart.defaults.global.defaultFontFamily, 'normal', Chart.defaults.global.defaultFontFamily);
                                          ctx.textAlign = 'center';
                                          ctx.textBaseline = 'bottom';
                                    
                                          this.data.datasets.forEach(function (dataset) {
                                    
                                            for (var i = 0; i < dataset.data.length; i++) {
                                              var model = dataset._meta[Object.keys(dataset._meta)[0]].data[i]._model,
                                                  total = dataset._meta[Object.keys(dataset._meta)[0]].total,
                                                  mid_radius = model.innerRadius + (model.outerRadius - model.innerRadius)/2,
                                                  start_angle = model.startAngle,
                                                  end_angle = model.endAngle,
                                                  mid_angle = start_angle + (end_angle - start_angle) / 2;
                                                  

                                    
                                              var x = mid_radius * Math.cos(mid_angle);
                                              var y = mid_radius * Math.sin(mid_angle);
                                    
                                              ctx.fillStyle = '#fff';
                                              if (i == 3) { // Darker text color for lighter background
                                                ctx.fillStyle = '#444';
                                              }
                                              var percent = dataset.data[i] + \"%\";      
                                              //Don't Display If Legend is hide or value is 0
                                              if (dataset.data[i] != 0) {
                                                //ctx.fillText(dataset.data[i], model.x + x, model.y + y);
                                                // Display percent in another line, line break doesn't work for fillText
                                                ctx.fillText(percent, model.x + x, model.y + y + 15);
                                              }
                                            }
                                           }); 
                                          }
                                        }
                                      }
                                     });
                            ";

            $graph .= "</script>";
            $params = ["widgetId" => $widgetId, "name" => $name, "onsubmit" => true, "opt" => $opt, "criterias" => $criterias, "export" => true, "canvas" => true, "nb" => count($dataset)];
            $widget->setWidgetHeader(PluginMydashboardHelper::getGraphHeader($params));
            $widget->setWidgetHtmlContent($graph);

            return $widget;

            break;

         case $this->getType() . "9" :
            $name = 'reportBarTimePassedByTechnician';

            $criterias = ['entities_id', 'is_recursive', 'groups_id', 'type', 'begin', 'end'];
            $params = ["preferences" => $this->preferences, "criterias" => $criterias, "opt" => $opt];
            $options = PluginMydashboardHelper::manageCriterias($params);
            $opt = $options['opt'];

            $time_passed_techs = self::getTimePerTech($options);

            foreach ($time_passed_techs as $tech_id => $tickets) {
               $labelsName[] = getUserName($tech_id);
               $datasTech[] = $tickets;
            }

            $dataset = [];

            $dataset[] = ["type" => 'bar', "label" => "", "data" => $datasTech, "backgroundColor" => '#CD5C5C', 'fill' => false];


            $widget = new PluginMydashboardHtml();
            $title = $this->generateTitle("TIME", "", "BY TECHNICIAN", "", (($isDebug) ? "8 " : ""), "");
            $widget->setWidgetTitle((($isDebug) ? "9 " : "") . $title);
            $widget->setWidgetComment(__("Sum of ticket tasks duration by technicians", "mydashboard"));
            $widget->toggleWidgetRefresh();

            $dataLineset = json_encode($dataset);
            $labelsLine = json_encode($labelsName);


            $graph = "<script type='text/javascript'>
                     var reportBarTimePassedByTechnician = {
                             datasets: $dataLineset,
                             labels: $labelsLine
                           };
         
                     
                      var isChartRendered = false;
                      var canvas = document . getElementById('reportBarTimePassedByTechnician');
                      var ctx = canvas . getContext('2d');
                      ctx.canvas.width = 700;
                      ctx.canvas.height = 400;
                      var reportBarTimePassedByTechnician = new Chart(ctx, {
                            type: 'bar',
                            data: reportBarTimePassedByTechnician,
                            options: {
                                responsive:true,
                                maintainAspectRatio: true,
                                title:{
                                    display:false,
                                    text:'reportBarTimePassedByTechnician'
                                },
                                tooltips: {
                                    mode: 'index',
                                    intersect: false
                                },
                                scales: {
                                    ticks:{
                                        beginAtZero:0,
                                         autoSkip: false,
                                         stepSize: 1,
                                         min: 0,
                                    }
                                },
                                animation: {
                                    onComplete: function() {
                                      isChartRendered = true
                                    }
                                  }
                            }
                        });

                      </script>";

            $params = ["widgetId" => $widgetId, "name" => 'reportBarTimePassedByTechnician', "onsubmit" => true, "opt" => $opt, "criterias" => $criterias, "export" => true, "canvas" => true, "nb" => count($dataset)];
            $widget->setWidgetHeader(PluginMydashboardHelper::getGraphHeader($params));
            $widget->setWidgetHtmlContent($graph);

            return $widget;

            break;

         case $this->getType() . "10" :
            $name = 'reportHorizontalBarNbTicketClosedByTechnicianHelpdesk';

            $criterias = ['month', 'year', 'type', 'groups_id', 'entities_id'];
            $params = ["preferences" => $this->preferences, "criterias" => $criterias, "opt" => $opt];
            $options = PluginMydashboardHelper::manageCriterias($params);

            $nb_tickets_helpdesk = self::getNbTicketsPerTechHelpdesk($options);
            $labelsName = [];
            $datasTech = [];
            asort($nb_tickets_helpdesk);
            $nb_tickets_helpdesk = array_reverse($nb_tickets_helpdesk, true);


            foreach ($nb_tickets_helpdesk as $tech_id => $tickets) {
               $labelsName[] = getUserName($tech_id);
               $datasTech[] = $tickets;
            }

            $dataset = [];

            $dataset[] = ["type" => 'horizontalBar', "label" => "", "data" => $datasTech, "backgroundColor" => '#CD5C5C', 'fill' => false];


            $widget = new PluginMydashboardHtml();
            $title = $this->generateTitle("NUMBER", "TICKETS CLOSED", "BY MONTH", "", (($isDebug) ? "9 " : ""), "");
            $widget->setWidgetTitle((($isDebug) ? "10 " : "") . $title);
            $widget->setWidgetComment(__("Helpdesk pole", "mydashboard"));
            $widget->toggleWidgetRefresh();

            $dataLineset = json_encode($dataset);
            $labelsLine = json_encode($labelsName);


            $graph = "<script type='text/javascript'>
                     var reportHorizontalBarNbTicketClosedByTechnicianHelpdesk = {
                           datasets: $dataLineset,
                           labels: $labelsLine
                           };
                   
                            var isChartRendered = false;
                            var canvas = document . getElementById('reportHorizontalBarNbTicketClosedByTechnicianHelpdesk');
                            var ctx = canvas . getContext('2d');
                            ctx.canvas.width = 700;
                            ctx.canvas.height = 400;
                            var reportHorizontalBarNbTicketClosedByTechnicianHelpdesk = new Chart(ctx, {
                                  type: 'horizontalBar',
                                  data: reportHorizontalBarNbTicketClosedByTechnicianHelpdesk,
                                  options: {
                                      responsive:true,
                                      maintainAspectRatio: true,
                                      title:{
                                          display:false,
                                          text:'TimeByGroupChart'
                                      },
                                      tooltips: {
                                          enabled: false,
                                      },
                                      scales: {
                                        yAxes: [{
                                          ticks: {
                                              beginAtZero: true
                                          }
                                      }]
                                      },
                                      animation: {
                                          onComplete: function() {
                                 var ctx = this.chart.ctx;
                                     ctx.font = Chart.helpers.fontString(Chart.defaults.global.defaultFontSize, 'normal', Chart.defaults.global.defaultFontFamily);
                                     ctx.fillStyle = '#595959';
                                     ctx.textAlign = 'center';
                                     ctx.textBaseline = 'bottom';
                                     this.data.datasets.forEach(function (dataset) {
                                         for (var i = 0; i < dataset.data.length; i++) {
                                             var model = dataset._meta[Object.keys(dataset._meta)[0]].data[i]._model;
                                             ctx.fillText(dataset.data[i], model.x, model.y - 5);
                                         }
                                     });
                                        isChartRendered = true;
                                          }
                                        }
                                  }
                              });
                      </script>";

            $params = ["widgetId" => $widgetId, "name" => "reportHorizontalBarNbTicketClosedByTechnicianHelpdesk", "onsubmit" => true, "opt" => $opt, "criterias" => $criterias, "export" => true, "canvas" => true, "nb" => count($dataset)];
            $widget->setWidgetHeader(PluginMydashboardHelper::getGraphHeader($params));
            $widget->setWidgetHtmlContent($graph);

            return $widget;

            break;

         case $this->getType() . "11" :
            $name = 'reportBarNbProblemInMonth';

            $criterias = ['entities_id', 'is_recursive', 'month', 'year'];
            $params = ["preferences" => $this->preferences, "criterias" => $criterias, "opt" => $opt];
            $options = PluginMydashboardHelper::manageCriterias($params);

            $opt = $options['opt'];
            $crit = $options['crit'];

            $problems_ticket = self::getProblemsRelatedToTickets($crit);
            $labelsName = [];
            $datasProblems = [];

            foreach ($problems_ticket as $probname => $nb) {
               $formatProbName  = wordwrap($probname,15,"<br>");
               $labelsName[]    = $formatProbName;
               $datasProblems[] = $nb;
            }

            $dataset = [];

            $dataset[] = ["type" => 'bar', "label" => __('Number of problems', 'mydashboard'), "data" => $datasProblems, "backgroundColor" => '#CD5C5C'];

            $dataLineset = json_encode($dataset);
            $labelsLine = json_encode($labelsName);

            $widget = new PluginMydashboardHtml();
            $title = $this->generateTitle("PROBLEM", "IDENTIFIED", "IN MONTH", "", (($isDebug) ? "10 " : ""), "");
            $widget->setWidgetTitle((($isDebug) ? "11 " : "") . $title);
            $widget->toggleWidgetRefresh();

            $graph = "<script type='text/javascript'>
                     var reportBarNbProblemInMonth = {
                           datasets: $dataLineset,
                           labels: $labelsLine
                           };
                    
                            var isChartRendered = false;
                            var canvas = document . getElementById('reportBarNbProblemInMonth');
                            var ctx = canvas . getContext('2d');
                            ctx.canvas.width = 800;
                            ctx.canvas.height = 600;
                            var reportBarNbProblemInMonth = new Chart(ctx, {
                                  type: 'bar',
                                  data: reportBarNbProblemInMonth,
                             options: {
                                responsive:true,
                                   maintainAspectRatio: true,
                                   title:{
                                       display:false,
                                       text:'reportBarNbProblemInMonth'
                                   },
                                   tooltips: {
                                       mode: 'index',
                                       intersect: false
                                   },
                               scales: {
                                   yAxes: [{
                                       ticks: {
                                           beginAtZero: true
                                       }
                                   }],
                                   xAxes: [{
                                       stacked:false,
                                       beginAtZero: true,
                                       ticks: {
                                        autoSkip:false
                                       }
                                   }]
                               },
                                animation: {
                                    onComplete: function() {
                                          this.chart.data.labels.forEach(function(e, i, a) {
                                             a[i] = e.split('<br>');   
                                          });
                                          isChartRendered = true
                                   }
                                }
                             }
                            });
                      </script>";

            $params = ["widgetId" => $widgetId, "name" => "reportBarNbProblemInMonth", "onsubmit" => true, "opt" => $opt, "criterias" => $criterias, "export" => true, "canvas" => true, "nb" => count($dataset)];
            $widget->setWidgetHeader(PluginMydashboardHelper::getGraphHeader($params));
            $widget->setWidgetHtmlContent($graph);

            return $widget;

            break;
      }
   }


   function getAllMonthAndYear($currentYear, $currentMonth, $previousYear) {

      $begin = new DateTime($previousYear . '-' . $currentMonth . '-' . '01');
      $end = new DateTime($currentYear . '-' . $currentMonth . '-' . '01');
      $period = new DatePeriod($begin, new DateInterval('P1M'), $end);
      $datesTab = [];


      foreach ($period as $date) {
         array_push($datesTab, $date->format("M Y"));
      }
      return $datesTab;
   }


   function getSpecificCategoryRestrict($results) {
      global $DB;
      $categories = [];
      while ($data = $DB->fetch_array($results)) {
         $categories = $categories + getSonsOf("glpi_itilcategories", $data['Id']);
      }
      if (count($categories) > 1) {
         $categories = " AND `glpi_tickets`.`itilcategories_id` IN  (" . implode(",", $categories) . ") ";
      } else {
         $categories = " AND `glpi_tickets`.`itilcategories_id` = " . implode(",", $categories);
      }
      return $categories;
   }

   private static function getSlaRespectWithinTwoHours($params, $takeIntoAccount) {

      global $DB;

      $isDeleted           = " AND `glpi_tickets`.`is_deleted` = 0 ";
      $date_criteria       = $params['date'];
      $entities_criteria   = $params['entities_id'];
      $groups_sql_criteria = "";

      $rate_result       = [];
      $tickets_helpdesk  = [];

      $months = Toolbox::getMonthsOfYearArray();

      $mois = intval(strftime("%m") - 1);
      $year = intval(strftime("%Y") - 1);

      if ($mois > 0) {
         $year = date("Y");
      }

      if (isset($params["year"]) && $params["year"] > 0) {
         $year = $params["year"];
      }

      $type_criteria = " AND 1 = 1";
      if (isset($params["type"]) && $params["type"] > 0) {
         $type_criteria = " AND `glpi_tickets`.`type` = '" . $params["type"] . "' AND";
      }

      // GROUP
      if (isset($params['groups_id']) && $params['groups_id'] != 0 && !empty($params['groups_id'])){
         $groups_sql_criteria = " AND `glpi_groups_tickets`.`groups_id`";
         if (is_array($params['groups_id'])) {
            $groups_sql_criteria .= " IN (". implode(",", $params['groups_id']) . ")";
         } else {
            $groups_sql_criteria .= " = ".$params['groups_id'];
         }
      }

      $current_month = date("m");
      foreach ($months as $key => $month) {

         if ($key > $current_month && $year == date("Y")) {
            break;
         }

         $next = $key + 1;

         $month_tmp = $key;
         $nb_jours = date("t", mktime(0, 0, 0, $key, 1, $year));

         if (strlen($key) == 1) {
            $month_tmp = "0" . $month_tmp;
         }
         if (strlen($next) == 1) {
            $next = "0" . $next;
         }

         if ($key == 0) {
            $year = $year - 1;
            $month_tmp = "12";
            $nb_jours = date("t", mktime(0, 0, 0, 12, 1, $year));
         }

         $month_deb_date     = "$year-$month_tmp-01";
         $month_deb_datetime = $month_deb_date . " 00:00:00";
         $month_end_date     = "$year-$month_tmp-$nb_jours";
         $month_end_datetime = $month_end_date . " 23:59:59";
         $is_deleted         = " AND `glpi_tickets`.`is_deleted` = 0 ";
         $assign             = Group_Ticket::ASSIGN;
         $date               = "`glpi_tickets`.`date`";

         $typeDelay = $takeIntoAccount ? "takeintoaccount_delay_stat" : "solve_delay_stat";

         $querym_ai = "SELECT
                            SUM(case when `glpi_tickets`.`$typeDelay` >= 7200 then 1 else 0 end) as late,
                            SUM(case when `glpi_tickets`.`$typeDelay` <= 7200 then 1 else 0 end) as ok
                        FROM `glpi_tickets` 
                        INNER JOIN `glpi_groups_tickets` 
                        ON (`glpi_tickets`.`id` = `glpi_groups_tickets`.`tickets_id` 
                        AND  `glpi_groups_tickets`.`type` = {$assign} {$is_deleted})
                         WHERE $date >= '$month_deb_datetime' AND $date <= '$month_end_datetime'" . $groups_sql_criteria .
                           $type_criteria  .
                              $is_deleted;
         $querym_ai .= " GROUP BY DATE(`glpi_tickets`.`date`) ";
         $result_ai_q = $DB->query($querym_ai);

         while ($data = $DB->fetch_assoc($result_ai_q)) {
            $tickets_helpdesk[$key]['late'] += $data['late'];
            $tickets_helpdesk[$key]['ok'] += $data['ok'];
         }
      }

      if ($key == 0) {
         $year++;
      }

      return $tickets_helpdesk;
   }

   /**
    * @param $params
    *
    * @return array
    */
   private static function getTicketsPerGroup($params) {
      global $DB;

      $tickets_per_group = [];
      $months = Toolbox::getMonthsOfYearArray();

      $mois = intval(strftime("%m") - 1);
      $year = intval(strftime("%Y") - 1);

      if ($mois > 0) {
         $year = date("Y");
      }

      if (isset($params["year"]) && $params["year"] > 0) {
         $year = $params["year"];
      }

      $type_criteria = "AND 1 = 1";
      if (isset($params["type"]) && $params["type"] > 0) {
         $type_criteria = " AND `glpi_tickets`.`type` = '" . $params["type"] . "' ";
      }

      $entities_criteria  = $params['entities_id'];
      $locations_criteria = $params['multiple_locations_id'];
      $groups             = new Group();
      $groupsEntities     = $groups->find(['entities_id' => $_SESSION['glpiactive_entity']], ['name']);

      if (count($groupsEntities) > 0) {
         foreach ($groupsEntities as $groupsEntity) {
            $groupList[] = $groupsEntity['id'];
         }
      }

      if (count($groupList) > 0) {

      $current_month = date("m");
      foreach ($months as $key => $month) {

         if ($key > $current_month && $year == date("Y")) {
            break;
         }

         $next = $key + 1;

         $month_tmp = $key;
         $nb_jours = date("t", mktime(0, 0, 0, $key, 1, $year));

         if (strlen($key) == 1) {
            $month_tmp = "0" . $month_tmp;
         }
         if (strlen($next) == 1) {
            $next = "0" . $next;
         }

         if ($key == 0) {
            $year = $year - 1;
            $month_tmp = "12";
            $nb_jours = date("t", mktime(0, 0, 0, 12, 1, $year));
         }

         $month_deb_date = "$year-$month_tmp-01";
         $month_deb_datetime = $month_deb_date . " 00:00:00";
         $month_end_date = "$year-$month_tmp-$nb_jours";
         $month_end_datetime = $month_end_date . " 23:59:59";
         $is_deleted = "`glpi_tickets`.`is_deleted` = 0";
         $requester = Group_Ticket::ASSIGN;

         foreach ($groupList as $groupId) {
            //$tickets_per_group[$groupId][$key] = 0;

            $querym_ai = "SELECT COUNT(`glpi_tickets`.`id`) AS nbtickets
                        FROM `glpi_tickets` 
                        INNER JOIN `glpi_groups_tickets` 
                        ON (`glpi_tickets`.`id` = `glpi_groups_tickets`.`tickets_id`
                          AND `glpi_groups_tickets`.`type` = {$requester} AND $is_deleted) ";
            $querym_ai .= "WHERE ";
            $querym_ai .= "(
                           `glpi_tickets`.`date` >= '{$month_deb_datetime}' 
                           AND `glpi_tickets`.`date` <= '{$month_end_datetime}'
                           AND `glpi_groups_tickets`.`groups_id` =  {$groupId} {$type_criteria} ) 
                           {$entities_criteria} {$locations_criteria}";
            $querym_ai .= "GROUP BY DATE(`glpi_tickets`.`date`);
                        ";
            $result_ai_q = $DB->query($querym_ai);
            while ($data = $DB->fetch_assoc($result_ai_q)) {
               $tickets_per_group[$groupId][$key] += $data['nbtickets'];
            }
         }

         if ($key == 0) {
            $year++;
         }
      }
      return $tickets_per_group;
      }
      return [];
   }

   private static function getProblemsRelatedToTickets($crit) {


      global $DB;

      $isDeleted = " AND `glpi_tickets`.`is_deleted` = 0 ";
      $isDeletedP = " AND `glpi_problems`.`is_deleted` = 0 ";
      $date_criteria = $crit['date'];
      $type_criteria = $crit['type'];
      $entities_criteria = $crit['entities_id'];

      $problems_entities = str_replace("tickets", "problems", $entities_criteria);


      $query_problems = "SELECT COUNT(`glpi_problems_tickets`.`problems_id`) as totalProblem, `glpi_problems`.`name` as problem  FROM `glpi_problems_tickets`
                           LEFT JOIN `glpi_problems` ON `glpi_problems`.`id` = `glpi_problems_tickets`.`problems_id` 
                           LEFT JOIN `glpi_tickets` ON `glpi_problems_tickets`.`tickets_id` = `glpi_tickets`.`id`
                           WHERE {$date_criteria} {$type_criteria} {$isDeleted} {$isDeletedP} {$problems_entities}
                           GROUP BY `glpi_problems_tickets`.`problems_id` ;";

      $result = $DB->query($query_problems);
      $problems_tickets = [];

      while ($data = $DB->fetch_assoc($result)) {
         $problems_tickets[$data['problem']] = $data['totalProblem'];
      }

      return $problems_tickets;
   }

   private static function getLifetimeOrTakeIntoAccountTicketAverage($params) {
      global $DB;

      $entities_criteria   = $params['entities_id'];
      $locations_criteria  = $params['multiple_locations_id'];
      $type_criteria       = "AND 1 = 1";
      $groups_sql_criteria = "";


      if (isset($params["type"]) && $params["type"] > 0) {
         $type_criteria = " AND `glpi_tickets`.`type` = '" . $params["type"] . "' ";
      }

      // GROUP
      if (isset($params['groups_id']) && $params['groups_id'] != 0 && !empty($params['groups_id'])){
         $groups_sql_criteria = " AND `glpi_groups_tickets`.`groups_id`";
         if (is_array($params['groups_id'])) {
            $groups_sql_criteria .= " IN (". implode(",", $params['groups_id']) . ")";
         } else {
            $groups_sql_criteria .= " = ".$params['groups_id'];
         }
      }

      $tickets_helpdesk = [];
      $months = Toolbox::getMonthsOfYearArray();

      $mois = intval(strftime("%m") - 1);
      $year = intval(strftime("%Y") - 1);

      if ($mois > 0) {
         $year = date("Y");
      }

      if (isset($params["year"]) && $params["year"] > 0) {
         $year = $params["year"];
      }


      if (isset($params["type"]) && $params["type"] > 0) {
         $type_criteria = " AND `glpi_tickets`.`type` = '" . $params["type"] . "' ";
      }

      $current_month = date("m");
      foreach ($months as $key => $month) {

         if ($key > $current_month && $year == date("Y")) {
            break;
         }

         $next = $key + 1;

         $month_tmp = $key;
         $nb_jours = date("t", mktime(0, 0, 0, $key, 1, $year));

         if (strlen($key) == 1) {
            $month_tmp = "0" . $month_tmp;
         }
         if (strlen($next) == 1) {
            $next = "0" . $next;
         }

         if ($key == 0) {
            $year = $year - 1;
            $month_tmp = "12";
            $nb_jours = date("t", mktime(0, 0, 0, 12, 1, $year));
         }

         $month_deb_date = "$year-$month_tmp-01";
         $month_deb_datetime = $month_deb_date . " 00:00:00";
         $month_end_date = "$year-$month_tmp-$nb_jours";
         $month_end_datetime = $month_end_date . " 23:59:59";
         $is_deleted = " `glpi_tickets`.`is_deleted` = 0 ";
         $assign = Group_Ticket::ASSIGN;
         $date = "`glpi_tickets`.`date`";

         $helpdesk_group = self::GROUP_HELPDESK;

         $queryavg = "SELECT COUNT(`glpi_tickets`.`id`) AS nbtickets, FLOOR(`glpi_tickets`.`close_delay_stat`  % 86400 / 3600) as lifetime,
                                FLOOR(`glpi_tickets`.`takeintoaccount_delay_stat` % 86400 / 3600) as takeintoaccount
                        FROM `glpi_tickets` 
                        INNER JOIN `glpi_groups_tickets` 
                        ON (`glpi_tickets`.`id` = `glpi_groups_tickets`.`tickets_id`
                          AND `glpi_groups_tickets`.`type` = {$assign} AND {$is_deleted}) ";
         $queryavg .= "WHERE ";
         $queryavg .= "
                           {$date} >= '{$month_deb_datetime}' 
                           AND {$date} <= '{$month_end_datetime}'
                           {$groups_sql_criteria} {$type_criteria} 
                            {$entities_criteria} ";
         $queryavg .= "GROUP BY DATE(`glpi_tickets`.`date`);
                        ";
         $result_avg = $DB->query($queryavg);

         while ($data = $DB->fetch_assoc($result_avg)) {
            $tickets_helpdesk[$helpdesk_group][$key]['nb'] += $data['nbtickets'];
            $tickets_helpdesk[$helpdesk_group][$key]['lifetime'] += $data['lifetime'];
            $tickets_helpdesk[$helpdesk_group][$key]['takeintoaccount'] += $data['takeintoaccount'];
         }
      }

      if ($key == 0) {
         $year++;
      }

      return $tickets_helpdesk;


   }

   /**
    * @param $params
    *
    * @return array
    */
   private static function getTicketsForHelpdeskGroups($params, $isClosed) {
      global $DB;

      $tickets_helpdesk = [];
      $months = Toolbox::getMonthsOfYearArray();

      $mois = intval(strftime("%m") - 1);
      $year = intval(strftime("%Y") - 1);

      $entities_criteria   = $params['entities_id'];
      $locations_criteria  = $params['multiple_locations_id'];
      $groups_sql_criteria = "";
      $type_criteria       = "AND 1 = 1";

      if ($mois > 0) {
         $year = date("Y");
      }

      if (isset($params["year"]) && $params["year"] > 0) {
         $year = $params["year"];
      }

      if (isset($params["type"]) && $params["type"] > 0) {
         $type_criteria = " AND `glpi_tickets`.`type` = '" . $params["type"] . "' ";
      }

      // GROUP
      if (isset($params['groups_id']) && $params['groups_id'] != 0 && !empty($params['groups_id'])){
         $groups_sql_criteria = " AND `glpi_groups_tickets`.`groups_id`";
         if (is_array($params['groups_id'])) {
            $groups_sql_criteria .= " IN (". implode(",", $params['groups_id']) . ")";
         } else {
            $groups_sql_criteria .= " = ".$params['groups_id'];
         }
      }

      $current_month = date("m");
      foreach ($months as $key => $month) {

         if ($key > $current_month && $year == date("Y")) {
            break;
         }

         $next = $key + 1;

         $month_tmp = $key;
         $nb_jours = date("t", mktime(0, 0, 0, $key, 1, $year));

         if (strlen($key) == 1) {
            $month_tmp = "0" . $month_tmp;
         }
         if (strlen($next) == 1) {
            $next = "0" . $next;
         }

         if ($key == 0) {
            $year = $year - 1;
            $month_tmp = "12";
            $nb_jours = date("t", mktime(0, 0, 0, 12, 1, $year));
         }

         $month_deb_date = "$year-$month_tmp-01";
         $month_deb_datetime = $month_deb_date . " 00:00:00";
         $month_end_date = "$year-$month_tmp-$nb_jours";
         $month_end_datetime = $month_end_date . " 23:59:59";
         $is_deleted = " `glpi_tickets`.`is_deleted` = 0 ";
         $assign = Group_Ticket::ASSIGN;
         $date = "`glpi_tickets`.`date`";
         $status = '';

         if ($isClosed) {
            $status = " AND `status` IN (" . CommonITILObject::SOLVED . "," . CommonITILObject::CLOSED . ")";
         }

         $querym_ai = "SELECT COUNT(`glpi_tickets`.`id`) AS nbtickets
                        FROM `glpi_tickets` 
                        INNER JOIN `glpi_groups_tickets` 
                        ON (`glpi_tickets`.`id` = `glpi_groups_tickets`.`tickets_id`
                          AND `glpi_groups_tickets`.`type` = {$assign} AND {$is_deleted})";
         $querym_ai .= "WHERE ";
         $querym_ai .= "(
                           $date >= '$month_deb_datetime' 
                           AND $date <= '$month_end_datetime'
                           {$groups_sql_criteria} {$type_criteria} ) 
                            {$entities_criteria} {$locations_criteria} {$status} ";
         $querym_ai .= "GROUP BY DATE(`glpi_tickets`.`date`);
                        ";
         $result_ai_q = $DB->query($querym_ai);

         while ($data = $DB->fetch_assoc($result_ai_q)) {
            $tickets_helpdesk[$params['groups_id']][$key] += $data['nbtickets'];
         }
      }

      if ($key == 0) {
         $year++;
      }

      return $tickets_helpdesk;
   }


   private static function getClosedTicketsByCategoryByTechnician($crit) {
      global $DB;

      $type_criteria      = $crit['type'];
      $entities_criteria  = $crit['entities_id'];
      $closedate_criteria = $crit['closedate'];
      $close_status       = CommonITILObject::CLOSED;

      $ticket_users_join   = "";
      $technician_criteria = "";
      $total               = "";

      if(isset($crit['technicians_id']) && $crit['technicians_id'] != 0) {
         $ticket_users_join = " INNER JOIN glpi_tickets_users ON glpi_tickets_users.tickets_id = glpi_tickets.id ";
         $technician_criteria = "AND glpi_tickets_users.type = ".CommonITILObject::ASSIGNED;
         $technician_criteria .= " AND glpi_tickets_users.users_id = " . $crit['technicians_id'];


         $is_deleted            = " AND `glpi_tickets`.`is_deleted` = 0 ";
         $ticket_by_cat_by_tech = [];


         $querym_ai = "SELECT COUNT(DISTINCT `glpi_tickets`.`id`) AS nbtickets, `glpi_itilcategories`.`name` AS cat 
                          FROM `glpi_tickets`
                          $ticket_users_join
                          LEFT JOIN `glpi_entities` ON (`glpi_tickets`.`entities_id` = `glpi_entities`.`id`)
                          LEFT JOIN `glpi_itilcategories` ON `glpi_itilcategories`.`id` = `glpi_tickets`.`itilcategories_id`
                          WHERE `glpi_tickets`.`status` = {$close_status}
                          $technician_criteria
                          AND {$closedate_criteria} {$entities_criteria} {$type_criteria} {$is_deleted} GROUP BY `glpi_itilcategories`.`name` LIMIT 10";
         $result_ai_q = $DB->query($querym_ai);

         while ($data = $DB->fetch_assoc($result_ai_q)) {
            $ticket_by_cat_by_tech[$data['cat']] = $data['nbtickets'];
         }

         return $ticket_by_cat_by_tech;
      }

      return [];
   }

   /**
    * @param $params
    *
    * @return array
    */
   private static function getTimePerTech($params) {
      global $DB;

      $time_per_tech = [];

      $opt               = $params['opt'];
      $crit              = $params['crit'];
      $type_criteria     = $crit['type'];
      $entities_criteria = $crit['entities_id'];
      $begin_criteria    = $crit['begin'];
      $end_criteria      = $crit['end'];
      $is_deleted        = "`glpi_tickets`.`is_deleted` = 0";

      $selected_group = [];
      if (isset($opt["groups_id"]) && $opt["groups_id"] > 0) {
         $groups_id = $opt['groups_id'];
      }

      if (isset($groups_id) && $groups_id > 0) {
         $selected_group[] = $groups_id;
      } else if (count($_SESSION['glpigroups']) > 0) {
         $selected_group = $_SESSION['glpigroups'];
      }

      $techlist = [];
      if (count($selected_group) > 0) {
         $groups = implode(",", $selected_group);
         $query_group_member = "SELECT `glpi_groups_users`.`users_id`" . "FROM `glpi_groups_users` " . "LEFT JOIN `glpi_groups` ON (`glpi_groups_users`.`groups_id` = `glpi_groups`.`id`) " . "WHERE `glpi_groups_users`.`groups_id` IN (" . $groups . ") AND `glpi_groups`.`is_assign` = 1 " . " GROUP BY `glpi_groups_users`.`users_id`";

         $result_gu = $DB->query($query_group_member);

         while ($data = $DB->fetch_assoc($result_gu)) {
            $techlist[] = $data['users_id'];
         }
      }



      foreach ($techlist as $techid) {
         $time_per_tech[$techid] = 0;

         $querym_ai = "SELECT SUM(`glpi_tickettasks`.`actiontime`) AS actiontime_date
                        FROM `glpi_tickettasks` 
                        INNER JOIN `glpi_tickets` ON (`glpi_tickets`.`id` = `glpi_tickettasks`.`tickets_id` AND $is_deleted) 
                        LEFT JOIN `glpi_entities` ON (`glpi_tickets`.`entities_id` = `glpi_entities`.`id`) ";
         $querym_ai .= "WHERE ";
         $querym_ai .= "(
                           `glpi_tickettasks`.`begin` >= '$begin_criteria' 
                           AND `glpi_tickettasks`.`end` <= '$end_criteria'
                           AND `glpi_tickettasks`.`users_id_tech` = (" . $techid . ") " . $entities_criteria . ") 
                        OR (
                           `glpi_tickettasks`.`date` >= '$begin_criteria' 
                           AND `glpi_tickettasks`.`date` <= '$end_criteria' 
                           AND `glpi_tickettasks`.`users_id`  = (" . $techid . ") 
                           AND `glpi_tickettasks`.`begin` IS NULL " . $entities_criteria . ")
                           AND `glpi_tickettasks`.`actiontime` != 0 $type_criteria ";
         $result_ai_q = $DB->query($querym_ai);
         while ($data = $DB->fetch_assoc($result_ai_q)) {
            $time_per_tech[$techid] = round(($data['actiontime_date'] / 3600 / 8));
         }
      }

      return $time_per_tech;
   }

   private static function getNbTicketsPerTechHelpdesk($params) {

      global $DB;

      $opt = $params['opt'];
      $crit = $params['crit'];

      $tickets_helpdesk   = [];
      $helpdesk_group     = self::GROUP_HELPDESK;
      $assign             = Ticket_User::ASSIGN;
      $isDeleted          = " `glpi_tickets`.`is_deleted` = 0 ";
      $closedate_criteria = $crit['closedate'];
      $type_criteria      = $crit['type'];
      $entities_criteria  = $crit['entities_id'];

      $selected_group = [];
      if (isset($opt["groups_id"]) && $opt["groups_id"] > 0) {
         $groups_id = $opt['groups_id'];
      }

      if (isset($groups_id) && $groups_id > 0) {
         $selected_group[] = $groups_id;
      } else if (count($_SESSION['glpigroups']) > 0) {
         $selected_group = $_SESSION['glpigroups'];
      }


      $techlist = [];
      if (count($selected_group) > 0) {
         $groups = implode(",", $selected_group);
         $query_group_member = "SELECT `glpi_groups_users`.`users_id`" . "FROM `glpi_groups_users` " . "LEFT JOIN `glpi_groups` ON (`glpi_groups_users`.`groups_id` = `glpi_groups`.`id`) " . "WHERE `glpi_groups_users`.`groups_id` IN (" . $groups . ") AND `glpi_groups`.`is_assign` = 1 " . " GROUP BY `glpi_groups_users`.`users_id`";

         $result_gu = $DB->query($query_group_member);

         while ($data = $DB->fetch_assoc($result_gu)) {
            $techlist[] = $data['users_id'];
         }
      }

      foreach ($techlist as $techid) {

         $query_users_helpdesk = "SELECT COUNT(`glpi_tickets`.`id`) AS nbtickets
                                    FROM `glpi_tickets` 
                                    INNER JOIN `glpi_tickets_users` 
                                    ON (`glpi_tickets`.`id` = `glpi_tickets_users`.`tickets_id`
                                    AND `glpi_tickets_users`.`type` = {$assign} AND {$isDeleted} AND `glpi_tickets_users`.`users_id` = {$techid})
                                    WHERE {$closedate_criteria}  {$type_criteria} {$entities_criteria}
                                    ";

         $result = $DB->query($query_users_helpdesk);

         while ($data = $DB->fetch_assoc($result)) {
            $tickets_helpdesk[$techid] = $data['nbtickets'];
         }
      }


      return $tickets_helpdesk;
   }

   static function getGroupName($id) {
      global $DB;
      $group = "";

      $iterator = $DB->request(['SELECT' => 'name', 'FROM' => 'glpi_groups', 'WHERE' => ['id' => $id]]);
      while ($data = $iterator->next()) {
         $group = $data['name'];
      }

      return $group;
   }

   /**
    * @param $table
    * @param $params
    *
    * @return string
    */
   private static function getSpecificEntityRestrict($table, $params) {

      if (isset($params['entities_id']) && $params['entities_id'] == "") {
         $params['entities_id'] = $_SESSION['glpiactive_entity'];
      }
      if (isset($params['entities_id']) && ($params['entities_id'] != -1)) {
         if (isset($params['sons']) && ($params['sons'] != "") && ($params['sons'] != 0)) {
            $entities = " AND `$table`.`entities_id` IN  (" . implode(",", getSonsOf("glpi_entities", $params['entities_id'])) . ") ";
         } else {
            $entities = " AND `$table`.`entities_id` = " . $params['entities_id'] . " ";
         }
      } else {
         if (isset($params['sons']) && ($params['sons'] != "") && ($params['sons'] != 0)) {
            $entities = " AND `$table`.`entities_id` IN  (" . implode(",", getSonsOf("glpi_entities", $_SESSION['glpiactive_entity'])) . ") ";
         } else {
            $entities = " AND `$table`.`entities_id` = " . $_SESSION['glpiactive_entity'] . " ";
         }
      }
      return $entities;
   }

   private static function getSpecificLocationRestrict($params) {

      if (isset($params) && $params != -1) {

               $locations  = getSonsOf("glpi_locations", $params);

               if (count($locations) > 1) {
                  $locations = " AND `glpi_tickets`.`locations_id` IN  (" . implode(",", $locations) . ") ";
               } else {
                  $locations = " AND `glpi_tickets`.`locations_id` = " . implode(",", $locations);
               }
               return $locations;
         }
      }
}
