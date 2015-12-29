<?php
if(!defined("IN_MYBB"))
{
  die("You Cannot Access This File Directly. Please Make Sure IN_MYBB Is Defined.");
}

require_once MYBB_ROOT."/inc/plugins/rpg_suite/models/class_GroupMember.php";

/**
Any additional functionality being added to the ModCP
**/

/**
* Add options to user edit through ModCP
*
*/
$plugins->add_hook("modcp_editprofile_end", "mod_user_edit");
$plugins->add_hook("modcp_do_editprofile_update", "mod_user_commit");

function mod_user_edit() {
  global $mybb, $db, $cache, $requiredfields, $user, $form;

  // Add moderator setting to update user rank
  if($mybb->settings['rpgsuite_groupranks']) {
    $groupuser = new GroupMember($mybb,$db,$cache);
    $groupuser->initialize($user['uid']);
      $requiredfields .= "<div class=\"forum_settings_bit\"><b>User Rank: </b>".$groupuser->generate_rank_select()."</div><br>";
    }
  }

  /**
  * Save extra ModCP user info on submit
  *
  */
  function mod_user_commit() {
    global $mybb, $user, $cache, $db;

    // Save new group rank
    if($mybb->settings['rpgsuite_groupranks']) {
      $rankid = (int)$mybb->input['rank_uid'.$user['uid']];
      $groupuser = new GroupMember($mybb,$db,$cache);
      $groupuser->initialize($user['uid']);
      $groupuser->update_rank($rankid);
    }
  }
