<?php
if(!defined("IN_MYBB"))
{
    die("You Cannot Access This File Directly. Please Make Sure IN_MYBB Is Defined.");
}

require_once MYBB_ROOT."/inc/plugins/rpg_suite/models/class_UserGroup.php";

/**
For the Group Manager CP functionality
**/

/**
Load the page
**/
$plugins->add_hook('modcp_start', 'load_groupcp');

function load_groupcp() {
  global $mybb, $db, $cache, $templates, $title, $header, $headerinclude, $footer, $theme, $group;

  if($mybb->settings['rpgsuite_groupmanagecp'] && $mybb->input['action'] == "managegroup") {
    // Get group id
    if($mybb->input['gid'] && $mybb->usergroup['issupermod']) {
      $gid = intval($mybb->input['gid']);
      $groupnav = '&gid='.$gid;
    } else {
      $gid = $mybb->user['displaygroup'];
      $groupnav = '';
    }

    $cpcontent = "";
    if($mybb->settings['rpgsuite_groupranks_custom']) {
      $customranklink = '<a href="modcp.php?action=managegroup&section=customranks'.$groupnav.'">Manage Custom Ranks</a>';
    } else {
      $customranklink = '';
    }
    $usergroup = new UserGroup($mybb,$db,$cache);
    if($usergroup->initialize($gid)) {
      $group = $usergroup->get_info();
      if(handle_form($usergroup)) {
        $url = "modcp.php?action=managegroup&gid=".$gid;
        if($mybb->input['section']) {
          $url .= "&section=".$mybb->input['section'];
        }
        redirect($url, "Your pack settings were successfully updated.");
      }
      if($mybb->input['section'] == 'groupoptions') {
        $title = 'Manage Options';
        add_breadcrumb('Manage Options');
        $cpcontent = load_groupmod_options($usergroup);
      } else if($mybb->input['section'] == 'groupmembers') {
        $title = 'Manage Members';
        add_breadcrumb('Manage Members');
        $cpcontent = load_groupmod_members($usergroup);
      } else if($mybb->input['section'] == 'customranks' && $mybb->settings['rpgsuite_groupranks_custom']) {
        $ttile = 'Manage Custom Ranks';
        add_breadcrumb('Manage Custom Ranks');
        $cpcontent = load_groupmod_customranks($usergroup);
      } else {
        $title = 'Manage Ranks';
        add_breadcrumb('Manage Ranks');
        $cpcontent = load_groupmod_ranks($usergroup);
      }
      // Add group styling
      eval("\$headerinclude .= \"".$templates->get('rpgmisc_groupstyle')."\";");
    } else {
      $cpcontent = "Invalid Group";
    }
    eval("\$groupmanagecp = \"".$templates->get('rpggroupmanagecp_full')."\";");
    output_page($groupmanagecp);
    exit;
  }
}

/**
Easy Ranking Section!
*/
function load_groupmod_ranks($usergroup) {
  global $mybb, $templates, $group;

  eval("\$ranklist = \"\";");
  $ranks = $usergroup->get_ranks();
  foreach($ranks->get_tiers() as $tier) {
    foreach($ranks->get_ranks($tier['id']) as $rank) {
      $rankids[] = $rank['id'];
    }
  }
  $rankids[] = 0;
  $rowiterator = 0;
  foreach($rankids as $rankid) {
    foreach($ranks->get_members($rankid) as $member) {
      $user = $member->get_info();

      $lastpost = $member->get_last_icpost();
      $user['lasticpost'] = ($lastpost) ? my_date('relative', $lastpost['dateline']) : 'Never';
      $user['groupjoindate'] = ($user['group_dateline']) ? date($mybb->settings['dateformat'], $user['group_dateline']) : 'Unknown';

      $activitystats = $member->get_stats_for($group['activityperiod']);
      $rankselect = $member->generate_rank_select();
      $user_row = ($rowiterator % 2) ? "trow2" : "trow1";

      eval("\$ranklist .= \"".$templates->get('rpggroupmanagecp_user_rank_row')."\";");
      $rowiterator++;
    }
  }
  $datefrom = date($mybb->settings['dateformat'], time() - ($group['activityperiod'] * 86400));
  eval("\$rankcpfull = \"".$templates->get('rpggroupmanagecp_user_rank_cp')."\";");
  return $rankcpfull;
}

/**
Group Option Section!
*/
function load_groupmod_options($usergroup) {
  global $mybb, $templates, $group;

  eval("\$settinglist = \"\";");
  $rowiterator = 0;
  foreach($usergroup->get_settings_mod() as $setting) {
    $setting_row = ($rowiterator % 2) ? "trow2" : "trow1";
    eval("\$settinglist .= \"".$templates->get('rpggroupmanagecp_group_setting_row')."\";");
    $rowiterator++;
  }
  eval("\$optioncpfull = \"".$templates->get('rpggroupmanagecp_group_setting_cp')."\";");
  return $optioncpfull;
}

/**
Group Member Section!
*/
function load_groupmod_members($usergroup) {
  global $mybb, $templates, $group;

  $rowiterator = 0;
  eval("\$memberlist = \"\";");
  foreach($usergroup->get_members() as $member) {
    $user_row = ($rowiterator % 2) ? "trow2" : "trow1";
    $user = $member->get_info();
    eval("\$memberoptions = \"\";");
    foreach($member->get_settings_mod() as $setting) {
      eval("\$memberoptions .= \"".$templates->get('rpggroupmanagecp_user_manage_setting')."\";");
    }
    eval("\$memberlist .= \"".$templates->get('rpggroupmanagecp_user_manage_row')."\";");
    $rowiterator++;
  }
  eval("\$membercpfull = \"".$templates->get('rpggroupmanagecp_user_manage_cp')."\";");
  return $membercpfull;
}

/**
Custom Rank section!
*/
function load_groupmod_customranks($usergroup) {
  global $mybb, $templates, $group;

}

/**
Helper - handle any form submits!
*/
function handle_form($usergroup) {
  global $mybb, $db;
  $update = false;

  // Check for rank updates!
  foreach($usergroup->get_members() as $member) {
    $user = $member->get_info();
    if(isset($mybb->input['rank_uid'.$user['uid']])) {
      $rankid = (int)$mybb->input['rank_uid'.$user['uid']];
      if($rankid != $user['grouprank']) {
        $member->update_rank($rankid);
        $update = true;
      }
    }
  }

  // Check for option updates!
  if(isset($mybb->input['activityperiod'])) {
    foreach($usergroup->get_settings_mod() as $setting) {
      if(isset($mybb->input[$setting['name']])) {
        if($setting['name'] == 'founded') {
          // Handle datetime
          $usergroupchanges[$setting['name']] = strtotime($mybb->input[$setting['name']]);
        } else {
            $usergroupchanges[$setting['name']] = $db->escape_string($mybb->input[$setting['name']]);
        }
      }
    }
    $usergroup->update_group($usergroupchanges);
    $update = true;
  }

  // Check for user updates!
  if(isset($mybb->input['member_update'])) {
    foreach($usergroup->get_members() as $member) {
      $user = $member->get_info();
      if(is_array($mybb->input['delete_member']) && in_array($user['uid'], $mybb->input['delete_member'])) {
        $usergroup->remove_member($user['uid']);
      } else {
        foreach($member->get_settings_mod() as $setting) {
          $inputid = $setting['name'].'u'.$user['uid'];
          if(isset($mybb->input[$inputid])) {
            if($setting['name'] == 'group_dateline') {
              // Handle datetime
              $userchanges[$setting['name']] = strtotime($mybb->input[$inputid]);
            } else {
            $userchanges[$setting['name']] = $db->escape_string($mybb->input[$inputid]);
          }
          }
        }
        $member->update_member($userchanges);
      }
      $update = true;
    }
  } else if(isset($mybb->input['member_add'])) {
    $membername = $db->escape_string($mybb->input['username']);
    $user = get_user_by_username($membername);
    $userinfo = get_user($user['uid']);
    if($userinfo['usergroup'] != Groups::UNAPPROVED && $userinfo['usergroup'] != Groups::WAITING) {
      $usergroup->add_member_byname($membername);
      $update = true;
    }
  }


  return $update;
}
