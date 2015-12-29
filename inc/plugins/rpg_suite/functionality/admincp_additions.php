<?php
if(!defined("IN_MYBB"))
{
    die("You Cannot Access This File Directly. Please Make Sure IN_MYBB Is Defined.");
}

require_once MYBB_ROOT."/inc/plugins/rpg_suite/models/class_GroupMember.php";
require_once MYBB_ROOT."/inc/plugins/rpg_suite/models/class_RPGSuite.php";

/**
Any additional functionality being added to the AdminCP (existing tabs)
**/

$plugins->add_hook("admin_page_output_footer", "rpgsuite_admin_scripts");

// Add scripts to admin (temporary?)
function rpgsuite_admin_scripts($args) {
  global $mybb;
	echo '<script type="text/javascript" src="'.$mybb->settings['bburl'].'/inc/plugins/rpg_suite/scripts/adminscripts.js"></script>';
}

/**
 * Adds any settings in User tab of Admin CP
 *
 */
// Add Hooks
$plugins->add_hook("admin_user_users_edit", "admin_user_edit");
$plugins->add_hook("admin_user_users_edit_commit_start", "admin_user_commit");

function admin_user_edit() {
	global $plugins;

	// Add new hook
	$plugins->add_hook("admin_formcontainer_end", "admin_user_editform");
}

/**
 * Add additional inputs to user form
 */
 function admin_user_editform() {
   global $mybb, $cache, $lang, $form, $form_container, $user, $db;

   // Create the input fields for User Rank
   if($mybb->settings['rpgsuite_groupranks']) {
     if (strpos($form_container->_title, $lang->required_profile_info) !== false)	{
       if($mybb->settings['rpgsuite_groupranks']) {
         $groupuser = new GroupMember($mybb,$db,$cache);
         $groupuser->initialize($user['uid']);
         $form_container->output_row("User Rank", "", "<div class=\"forum_settings_bit\">".$groupuser->generate_rank_select()."</div>");
       }
     }
   }
 }

/**
 * Sets the user options values in ACP on submit
 */
 function admin_user_commit() {
   global $mybb, $user, $db, $cache;

   // Save new group rank
   if($mybb->settings['rpgsuite_groupranks']) {
     $rankid = (int)$mybb->input['rank_uid'.$user['uid']];
     $groupuser = new GroupMember($mybb,$db,$cache);
     $groupuser->initialize($user['uid']);
     $groupuser->update_rank($rankid);
   }
}

/**
 * Adds a setting in group options in ACP.
 *
 */
// Add Hooks
$plugins->add_hook("admin_user_groups_edit", "admin_group_edit");
$plugins->add_hook("admin_user_groups_edit_commit", "admin_group_commit");

function admin_group_edit() {
	global $plugins;

	// Add new hook
	$plugins->add_hook("admin_formcontainer_end", "admin_group_editform");
}

/**
 * Add additional inputs to group form
 */
 function admin_group_editform() {
   global $mybb, $lang, $form, $form_container, $usergroup;

  // Create the input fields
	if ($form_container->_title == $lang->misc) {
		$group_isic = array(
			$form->generate_check_box("icgroup", 1, "Group is character group", array("checked" => $mybb->input['icgroup']))
		);
		$form_container->output_row('IC Group', "", "<div class=\"group_settings_bit\">".implode("</div><div class=\"group_settings_bit\">", $group_isic)."</div>");
	}
}

/**
 * Sets the forum options values in ACP on submit
 */
 function admin_group_commit() {
   global $mybb, $db, $cache, $updated_group, $usergroup;
   $rpgsuite = new RPGSuite($mybb,$db, $cache);
 	 $makeic = (int)$mybb->input['icgroup'];
   $updated_group['icgroup'] = $makeic;
  if($makeic) {
    $rpgsuite->convert_to_ic($usergroup['gid']);
  } else {
    $rpgsuite->revert_to_ooc($usergroup['gid']);
  }

}

/**
 * Adds a setting in forum options in ACP.
 *
 */
// Add Hooks
$plugins->add_hook("admin_forum_management_edit", "admin_forum_edit");
$plugins->add_hook("admin_forum_management_edit_commit", "admin_forum_commit");

function admin_forum_edit() {
	global $plugins;

	// Add new hook
	$plugins->add_hook("admin_formcontainer_end", "admin_forum_editform");
}

/**
 * Add additional input to form
 */
function admin_forum_editform() {
	global $mybb, $lang, $form, $form_container, $forum_data;

	// Create the input fields
	if ($form_container->_title == $lang->additional_forum_options)
	{
		$forum_isic = array(
				$form->generate_check_box("icforum", 1, "Forum is used for in-character posts", array("checked" => $forum_data['icforum']))
		);
		$form_container->output_row("IC Forum", "", "<div class=\"forum_settings_bit\">".implode("</div><div class=\"forum_settings_bit\">", $forum_isic)."</div>");
	}
}

/**
 * Sets the forum options values in ACP on submit
 *
 */
function admin_forum_commit()
{
	global $mybb, $db, $fid;

	$update_array = array(
			'icforum' => (int)$mybb->input['icforum']
		);
	$db->update_query("forums", $update_array, "fid='$fid'");
}

?>
