<?php
/**
 * MyBB 1.8
 * Copyright 2014 MyBB Group, All Rights Reserved
 *
 * Website: http://www.mybb.com
 * License: http://www.mybb.com/about/license
 *
 */

require_once MYBB_ROOT."/inc/plugins/rpg_suite/models/class_UserGroup.php";

 /**
 For setting joined group to Display Group automatically (and setting join date)
 **/


// Make sure we can't access this file directly from the browser.
if(!defined('IN_MYBB')) {
	die('This file cannot be accessed directly.');
}

//Accepting single user
$plugins->add_hook('managegroup_do_add_end', 'approve_user_join');
function approve_user_join() {
  global $user, $gid;
	update_display_group($user['uid'], $gid);
}

//Accepting multiple requests
$plugins->add_hook('managegroup_do_joinrequests_end', 'approve_multiple_joins');
function approve_multiple_joins() {
  global $mybb, $gid;
  foreach($mybb->get_input('request', MyBB::INPUT_ARRAY) as $uid => $what) {
    if($what == "accept") {
      update_display_group($uid, $gid);
    }
  }
}

//Admin accepting
$plugins->add_hook('admin_user_groups_approve_join_request_commit', 'admin_approve_user_join');
function admin_approve_user_join() {
  global $request;
  update_display_group($request['uid'], $request['gid']);
}

//Admin accepting many requests
$plugins->add_hook('admin_user_groups_join_requests_commit', 'admin_approve_multiple_joins');
function admin_approve_multiple_joins() {
  global $mybb, $group;
  if(isset($mybb->input['approve']) && is_array($mybb->input['users'])) {
			foreach($mybb->input['users'] as $uid) {
        update_display_group($uid, $group['gid']);
      }
    }
}

function update_display_group($uid, $gid) {
  global $mybb, $db, $cache;
	$usergroup = new UserGroup($mybb,$db,$cache);
	if($usergroup->initialize($gid)) {
		$usergroup->add_member($uid);
	}
}
