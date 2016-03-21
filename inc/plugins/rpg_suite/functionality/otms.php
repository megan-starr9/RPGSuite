<?php
if(!defined("IN_MYBB"))
{
    die("You Cannot Access This File Directly. Please Make Sure IN_MYBB Is Defined.");
}

require_once MYBB_ROOT."/inc/plugins/rpg_suite/models/class_OtmAwards.php";

/**
Functionality behind the OTM awards
  This system allows for awards such as MOTM, COTM, and Crazy Writers (based on email account grouping)
*/

$plugins->add_hook('global_start', 'display_otms');
/**
Retrieve the otms and display on the sidebar
*/
function display_otms() {
  global $mybb, $db, $cache, $templates, $otms, $crazyweekwriters, $crazymonthwriters;
  $awards = new OtmAwards($mybb, $db, $cache);

  $weeklimit = strtotime('Last Sunday', time());
  $monthlimit = strtotime(date('Y-m-01 00:00:00'));

  $otmarray = $awards->get_display_otms();

  foreach($awards->crazy_writers($weeklimit,3) as $user) {
    eval("\$crazyweekwriters .= \"".$templates->get('rpgotm_crazywriter')."\";");
  }
  foreach($awards->crazy_writers($monthlimit,3) as $user) {
    eval("\$crazymonthwriters .= \"".$templates->get('rpgotm_crazywriter')."\";");
  }

  eval("\$otms = \"".$templates->get('rpgotm_index')."\";");
}
