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
      } else if(inactive_leader($member)) {
        $group->demote_member($user['uid']);
        $member->update_rank(0);
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
  // Check Join Grace Period
  if($user['group_dateline'] > time() - ($mybb->settings['rpgsuite_activitycheck_joingraceperiod'] * 86400)) {
    return false;
  }
  // Should player be removed based on rank?
  $rank = $member->get_rank();
  if($rank['ignoreactivitycheck']) {
    return false;
  }
  // If player is absent, add grace period (if set)
  $lastpost = $member->get_last_icpost();
  if($user['away']) {
    return ($lastpost['dateline'] < time() - ($mybb->settings['rpgsuite_activitycheck_absence'] + $mybb->settings['rpgsuite_activitycheck_period']) * 86400);
  }
  // If none of those, check posts
  return ($lastpost['dateline'] < time() - $mybb->settings['rpgsuite_activitycheck_period'] * 86400);
}

/**
Determines if a leader has met their requirements
*/
function inactive_leader($member) {
  global $mybb;
  $user = $member->get_info();

  if($member->is_leader()) {
    if($user['group_dateline'] > time() - ($mybb->settings['rpgsuite_activitycheck_joingraceperiod'] * 86400)) {
      return false;
    }
    $lastpost = $member->get_last_icpost();
    if($user['away']) {
      return ($lastpost['dateline'] < time() - ($mybb->settings['rpgsuite_activitycheck_absence'] + $mybb->settings['rpgsuite_activitycheck_leaderperiod']) * 86400);
    }
    return ($lastpost['dateline'] < time() - $mybb->settings['rpgsuite_activitycheck_leaderperiod'] * 86400);
  }
  return false;
}

/**
Display check variables on profile to inform user
**/
$plugins->add_hook("member_profile_end","display_check");
function display_check() {
  global $mybb,$db,$cache,$next_activity_run,$memprofile,$time_to_removal,$time_to_demotion;

  if($memprofile['uid'] == $mybb->user['uid']) {
    $activitycheck = new Ticker($db, 1, $mybb->settings['rpgsuite_activitycheck_freq']);
    $next_activity_run = $activitycheck->next_run();

    $member = new GroupMember($mybb,$db,$cache);
    $member->initialize($mybb->user['uid']);
    $lastpost = $member->get_last_icpost();

    $time_to_removal = floor((($lastpost['dateline'] + ($mybb->settings['rpgsuite_activitycheck_period'] * 86400)) - time()) / 86400);
    $time_to_demotion = floor((($lastpost['dateline'] + ($mybb->settings['rpgsuite_activitycheck_leaderperiod'] * 86400)) - time()) / 86400);
  }
}
