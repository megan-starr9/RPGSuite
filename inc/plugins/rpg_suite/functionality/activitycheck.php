<?php
if(!defined("IN_MYBB"))
{
    die("You Cannot Access This File Directly. Please Make Sure IN_MYBB Is Defined.");
}

require_once MYBB_ROOT."/inc/plugins/rpg_suite/models/class_RPGSuite.php";
require_once MYBB_ROOT."/inc/plugins/rpg_suite/models/class_Ticker.php";

/**
Functionality behind the activity checker
  This system allows members to be removed from a group to a default if enough time passes without an ic post

  This file encompasses the actual running of the system.
  Changing of group specific settings contained within Group Admin CP
**/

$plugins->add_hook("index_start","activitycheck_init");

function activitycheck_init() {
  global $mybb, $db;

  $activitycheck = new Ticker($db, 1, $mybb->settings['rpgsuite_activitycheck_freq']);

  if($mybb->settings['rpgsuite_activitycheck'] && $activitycheck->needs_run()) {
    run_activitycheck();
    $activitycheck->increment();
  }
}

/**
The actual Activity Check functionality
*/
function run_activitycheck() {
  global $mybb,$db, $cache;
  $masterclass = new RPGSuite($mybb,$db, $cache);
  $grouplist = $masterclass->get_icgroups_members('activitycheck = 1');
  foreach($grouplist as $id => $group) {
    foreach($group->get_members() as $member) {
      $user = $member->get_info();
      if(inactive($member)) {
        $group->hard_remove_member($user['uid']);
      }
    }
  }
}

/**
Determines if player is inactive or not!
*/
function inactive($member) {
  global $mybb;
  $user = $member->get_info();
  // Is player absent and has been less than specified days?
  if($user['away'] && ($user['awaydate'] > time() - $mybb->settings['rpgsuite_activitycheck_absence'] * 86400)) {
    return false;
  }
  // Should player be removed based on rank?
  $rank = $member->get_rank();
  if($rank['ignoreactivitycheck']) {
    return false;
  }
  // If neither of those, check posts
  $lastpost = $member->get_last_icpost();
  return ($lastpost['dateline'] < time() - $mybb->settings['rpgsuite_activitycheck_period'] * 86400);
}
