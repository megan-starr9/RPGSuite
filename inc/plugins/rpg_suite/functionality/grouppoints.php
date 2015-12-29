<?php
if(!defined("IN_MYBB"))
{
    die("You Cannot Access This File Directly. Please Make Sure IN_MYBB Is Defined.");
}

require_once MYBB_ROOT."/inc/plugins/rpg_suite/models/class_RPGSuite.php";
require_once MYBB_ROOT."/inc/plugins/rpg_suite/models/class_Ticker.php";
require_once MYBB_ROOT."/inc/plugins/rpg_suite/rpgsuite_defaults.php";

/**
Functionality behind the group points
  This system allows members of a group to be penalized or rewarded on a set time interval

  This file encompasses the actual running of the system.
  Changing of group specific settings contained within Group Admin CP
**/

$plugins->add_hook("index_start","grouppoints_init");

function grouppoints_init() {
  global $mybb, $db;
  $grouppoints = new Ticker($db, 2, $mybb->settings['rpgsuite_grouppoints_freq']);

  if($mybb->settings['rpgsuite_grouppoints'] && $grouppoints->needs_run()) {
    run_grouppoints();
    $grouppoints->update();
  }
}

/**
The actual Group Point functionality
*/
function run_grouppoints() {
  global $mybb,$db,$cache;
  $masterclass = new RPGSuite($mybb,$db, $cache);
  $grouplist = $masterclass->get_icgroups_members('grouppoints <> 0');
  foreach($grouplist as $group) {
    foreach($group['members'] as $member) {
      $currentpoints = $member[Fields::GROUPPOINTS];
      if($group['grouppoints'] > 0) {
        $member->set_grouppoints(max(array($mybb->settings['rpgsuite_grouppoints_max'], $currentpoints + point_variance($group['grouppoints']))));
      } else {
        $member->set_grouppoints(min(array(0, $currentpoints + point_variance($group['grouppoints']))));
      }
    }
  }
}

/**
Where we keep our point equation to add some random/variety to the generation!
*/
function point_variance($pointval) {
  $winter = array('December','January','February');
  $spring = array('March','April','May');
  $summer = array('June','July','August');
  $fall = array('September','October','November');
  $month = date('F');

  if($pointval > 0) {
    // If rewarding, we want to always award full points
    return $pointval;
  }

  // If subtracting, throw a little luck in!
  if(in_array($month, $winter)) {
		if(rand(1,100) >= Chance::WINTER) {
			return $pointval;
		}
    return $pointval;
  } else if(in_array($month, $fall)) {
    if(rand(1,100) >= Chance::FALL) {
      return $pointval;
    }
  } else if(in_array($month, $summer) || in_array($month, $spring)) {
    if(rand(1,100) >= Chance::SPRINGSUMMER) {
      return $pointval;
    }
  }
  return 0;
}
