<?php
if(!defined("IN_MYBB"))
{
	die("You Cannot Access This File Directly. Please Make Sure IN_MYBB Is Defined.");
}

require_once MYBB_ROOT."/inc/plugins/rpg_suite/models/class_GroupMember.php";
require_once MYBB_ROOT."/inc/plugins/rpg_suite/models/class_UserGroup.php";

/**
* Add any special locations to online list
*
*/
$plugins->add_hook('build_friendly_wol_location_end', 'update_location');

function update_location(&$plugin_array) {
	global $db, $mybb;
	parse_str(parse_url($plugin_array['user_activity']['location'],  PHP_URL_QUERY), $urlarr);

	if($urlarr['action'] == 'showranks') {
		$plugin_array['location_name'] = rankview_bit($urlarr['amp;gid']);
	}
  else if($urlarr['action'] == 'threadlog') {
    $plugin_array['location_name'] = threadlog_bit($urlarr['amp;uid']);
  }
	else if($urlarr['action'] == 'lonelythreads') {
    $plugin_array['location_name'] = "<a href='".$plugin_array['user_activity']['location']."'>Viewing Lonely Threads</a>";
  }
}

function rankview_bit($gid) {
  global $db, $mybb, $cache;
  $usergroup = new UserGroup($mybb, $db,$cache);

  if($usergroup->initialize($gid)) {
    $group = $usergroup->get_info();
    if($group['hasranks']) {
      return "Viewing <a href='index.php?action=showranks&gid=".$gid."'>".$group['title']." Ranks</a>"	;
    } else {
      return "Viewing <a href='index.php?action=showranks&gid=".$gid."'>".$group['title']." Members</a>"	;
    }
  } else {
    return "Viewing <a href='index.php?action=showranks'>Group Members</a>";
  }
}

function threadlog_bit($uid) {
  global $db, $mybb, $cache;
  $member = new GroupMember($mybb, $db, $cache);

  if($member->initialize($uid)) {
    $user = $member->get_info();
    return "Viewing <a href='misc.php?action=threadlog&uid=".$uid."'>".$user['username']."'s Threadlog</a>";
  } else {
    return "Viewing <a href='misc.php?action=threadlog'>Threadlog</a>";
  }
}
