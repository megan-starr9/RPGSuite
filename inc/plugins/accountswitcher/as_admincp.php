<?php
/**
 * Enhanced Account Switcher for MyBB 1.8
 * Copyright (c) 2012-2015 doylecc
 * http://mybbplugins.de.vu
 *
 * based on the Plugin:
 * Account Switcher 1.0 by Harest
 * Copyright (c) 2011 Harest
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 */


// Disallow direct access to this file for security reasons
if (!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />
		Please make sure IN_MYBB is defined.");
}


// Hooks for template edits when adding/importing a new theme
$plugins->add_hook("admin_style_themes_add_commit", "accountswitcher_new_themes");
$plugins->add_hook("admin_style_themes_import_commit", "accountswitcher_new_themes");
/**
 * Applies the template edits to added/imported themes.
 *
 */
function accountswitcher_new_themes()
{
	accountswitcher_revert_template_edits();
	accountswitcher_apply_template_edits();
}


/**
 * Applies the template edits.
 *
 */
function accountswitcher_apply_template_edits()
{
	require_once MYBB_ROOT."/inc/adminfunctions_templates.php";
	// Apply the template edits
	find_replace_templatesets('header_welcomeblock_member', '#{\$lang->welcome_pms_usage}#', '{\$lang->welcome_pms_usage}<!-- AccountSwitcher -->{$as_header}<!-- /AccountSwitcher -->');
	find_replace_templatesets('newreply', "#".preg_quote('<input type="submit" class="button" name="submit"')."#s", '{$as_post}&nbsp;<input type="submit" class="button" name="submit"');
	find_replace_templatesets('newthread', "#".preg_quote('<input type="submit" class="button" name="submit"')."#s", '{$as_post}&nbsp;<input type="submit" class="button" name="submit"');
	find_replace_templatesets('showthread_quickreply', "#".preg_quote('<input type="submit" class="button" value="{$lang->post_reply}')."#s", '{$as_post}&nbsp;<input type="submit" class="button" value="{$lang->post_reply}');
	find_replace_templatesets('newreply', "#".preg_quote('{$lang->reply_to}</strong>')."#s", '{$lang->reply_to}</strong><a name="switch" id="switch"></a>');
	find_replace_templatesets('newthread', "#".preg_quote('{$lang->post_new_thread}</strong>')."#s", '{$lang->post_new_thread}</strong><a name="switch" id="switch"></a>');
	find_replace_templatesets('showthread', "#".preg_quote('{$quickreply}')."#s", '<a name="switch" id="switch"></a>{$quickreply}');
	find_replace_templatesets("header", "#".preg_quote('{$pm_notice}')."#i", '{$pm_notice}{$pm_switch_notice}');
	find_replace_templatesets("header", "#".preg_quote('{$menu_memberlist}')."#i", '{$menu_memberlist}{$menu_accountlist}');
	find_replace_templatesets("header", "#".preg_quote('<div id="container">')."#i", '{$as_sidebar}<div id="container">');
	find_replace_templatesets("postbit", "#".preg_quote('{$post[\'onlinestatus\']}')."#i", '{$post[\'onlinestatus\']}{$post[\'authorchange\']}');
	find_replace_templatesets("postbit_classic", "#".preg_quote('{$post[\'onlinestatus\']}')."#i", '{$post[\'onlinestatus\']}{$post[\'authorchange\']}');
	find_replace_templatesets("postbit", "#".preg_quote('{$post[\'user_details\']}')."#i", '{$post[\'user_details\']}{$post[\'attached_accounts\']}');
	find_replace_templatesets("postbit_classic", "#".preg_quote('{$post[\'user_details\']}')."#i", '{$post[\'user_details\']}{$post[\'attached_accounts\']}');
	find_replace_templatesets("member_profile", "#".preg_quote('{$profilefields}')."#i", '{$profilefields}{$profile_attached}');
	find_replace_templatesets("memberlist_user", "#".preg_quote('{$user[\'profilelink\']}')."#i", '{$user[\'profilelink\']}{$user[\'attached_accounts\']}');
	find_replace_templatesets("headerinclude", "#".preg_quote('var modal_zindex = 9999')."#i", 'var modal_zindex = 9995');
}

/**
 * Revert the template edits.
 *
 */
function accountswitcher_revert_template_edits()
{
	require_once MYBB_ROOT."/inc/adminfunctions_templates.php";
	// Undo template edits
	find_replace_templatesets('header_welcomeblock_member', '#\<!--\sAccountSwitcher\s--\>(.+)\<!--\s/AccountSwitcher\s--\>#is', '', '', false);
	find_replace_templatesets('newreply', "#".preg_quote('{$as_post}&nbsp;')."#s", '', '', false);
	find_replace_templatesets('newthread', "#".preg_quote('{$as_post}&nbsp;')."#s", '', '', false);
	find_replace_templatesets('showthread_quickreply', "#".preg_quote('{$as_post}&nbsp;')."#s", '', '', false);
	find_replace_templatesets('newreply', "#".preg_quote('<a name="switch" id="switch"></a>')."#s", '', '', false);
	find_replace_templatesets('newthread', "#".preg_quote('<a name="switch" id="switch"></a>')."#s", '', '', false);
	find_replace_templatesets('showthread', "#".preg_quote('<a name="switch" id="switch"></a>')."#s", '', '', false);
	find_replace_templatesets("header", "#".preg_quote('{$pm_switch_notice}')."#s", '', '', false);
	find_replace_templatesets("header", "#".preg_quote('{$menu_accountlist}')."#s", '', '', false);
	find_replace_templatesets("header", "#".preg_quote('{$as_sidebar}')."#s", '', '', false);
	find_replace_templatesets("postbit", "#".preg_quote('{$post[\'authorchange\']}')."#s", '', '', false);
	find_replace_templatesets("postbit_classic", "#".preg_quote('{$post[\'authorchange\']}')."#s", '', '', false);
	find_replace_templatesets("postbit", "#".preg_quote('{$post[\'attached_accounts\']}')."#s", '', '', false);
	find_replace_templatesets("postbit_classic", "#".preg_quote('{$post[\'attached_accounts\']}')."#s", '', '', false);
	find_replace_templatesets("member_profile", "#".preg_quote('{$profile_attached}')."#s", '', '', false);
	find_replace_templatesets("memberlist_user", "#".preg_quote('{$user[\'attached_accounts\']}')."#s", '', '', false);
	find_replace_templatesets("headerinclude", "#".preg_quote('var modal_zindex = 9995')."#i", 'var modal_zindex = 9999');
}

// Add the hooks for editing usergroups and users
$plugins->add_hook("admin_user_groups_edit", "accountswitcher_admingroups_edit");
$plugins->add_hook("admin_user_groups_edit_commit", "accountswitcher_admingroups_commit");
$plugins->add_hook("admin_user_users_add_commit", "accountswitcher_pm");
$plugins->add_hook("admin_user_users_edit_commit", "accountswitcher_pm");

// ##### Admin CP functions #####
/**
 * Adds a hook for the form in ACP.
 *
 */
function accountswitcher_admingroups_edit()
{
	global $plugins;

	// Add new hook
	$plugins->add_hook("admin_formcontainer_end", "accountswitcher_admingroups_editform");
}

/**
 * Adds a setting in group options in ACP.
 *
 */
function accountswitcher_admingroups_editform()
{
	global $mybb, $lang, $form, $form_container;

	$lang->load("accountswitcher");

	// Create the input fields
	if ($form_container->_title == $lang->misc)
	{
		$as_group_can = array(
			$form->generate_check_box("as_canswitch", 1, $lang->as_admin_canswitch, array("checked" => $mybb->input['as_canswitch']))
		);
		$as_group_limit = "<div class=\"group_settings_bit\">".$lang->as_admin_limit."<br />".$form->generate_text_box("as_limit", $mybb->input['as_limit'], array('class' => 'field50'))."</div>";
		$form_container->output_row($lang->as_name, "", "<div class=\"group_settings_bit\">".implode("</div><div class=\"group_settings_bit\">", $as_group_can)."</div>".$as_group_limit);
	}
}

/**
 * Sets the group options values in ACP.
 *
 */
function accountswitcher_admingroups_commit()
{
	global $mybb, $updated_group;

	$updated_group['as_canswitch'] = $mybb->get_input('as_canswitch', MyBB::INPUT_INT);
	$updated_group['as_limit'] = $mybb->get_input('as_limit', MyBB::INPUT_INT);
}

// Hook for the remove attached users function
$plugins->add_hook("admin_user_users_delete_commit", "accountswitcher_del_user");
/**
 * Removes the attached user entries when the master user is deleted.
 *
 */
function accountswitcher_del_user()
{
	global $db, $user, $eas;

	$updated_as_uid = array(
		"as_uid" => 0,
	);
	$db->update_query('users', $updated_as_uid, "as_uid='".(int)$user['uid']."'");

	$updated_as_shareuid = array(
		"as_shareuid" => 0,
	);
	$db->update_query('users', $updated_as_shareuid, "as_shareuid='".(int)$user['uid']."'");

	$eas->update_accountswitcher_cache();
	$eas->update_userfields_cache();
}

// Hook for the action handler
$plugins->add_hook("admin_tools_action_handler", "accountswitcher_admin_tools_action_handler");

function accountswitcher_admin_tools_action_handler(&$actions)
{
	$actions['accountswitcher'] = array('active' => 'accountswitcher', 'file' => 'accountswitcher');
}

// Hook for the remove attached users function (set priority to 50 to get access to the eas object)
$plugins->add_hook("admin_load", "accountswitcher_cleanup", 50);
/**
 * Removes the attached user entries if the master user doesn't exist.
 *
 */
function accountswitcher_cleanup()
{
	global $mybb, $db, $lang, $page, $run_module, $action_file, $eas;

	if ($page->active_action != 'accountswitcher')
	{
		return false;
	}

	if ($run_module == 'tools' && $action_file == 'accountswitcher')
	{
		if ($mybb->input['action'] == "cleanup")
		{
			if (!verify_post_check($mybb->get_input('my_post_key')))
			{
				flash_message($lang->invalid_post_verify_key2, 'error');
				admin_redirect("index.php?module=config-plugins");
			}
			else
			{
				$accounts = $eas->accountswitcher_cache;

				if (is_array($accounts))
				{
					foreach ($accounts as $key => $account)
					{
						$masters[] = $account['as_uid'];
					}

					$masters = array_unique($masters);
					$masters = array_values($masters);
					$query_auser = $db->simple_select('users', 'uid');
					while ($actual_users = $db->fetch_array($query_auser))
					{
						$useraccounts[] = $actual_users['uid'];
					}

					foreach ($masters as $master_check)
					{

						if (!in_array($master_check, $useraccounts))
						{
							$updated_record = array(
								"as_uid" => 0
							);
							$db->update_query('users', $updated_record, "as_uid='".(int)$master_check."'");

							$eas->update_accountswitcher_cache();
						}
					}
				}
				admin_redirect("index.php?module=config-plugins");
			}
			exit;
		}
	}
}

// Hook to change settings for peeker
$plugins->add_hook("admin_config_settings_change","accountswitcher_settings_change");
/**
 * Set peeker in ACP
 *
 */
function accountswitcher_settings_change()
{
	global $db, $mybb, $accountswitcher_settings_peeker;

	$result = $db->simple_select("settinggroups", "gid", "name='Enhanced Account Switcher'", array("limit" => 1));
	$group = $db->fetch_array($result);
	$accountswitcher_settings_peeker = ($mybb->input["gid"] == $group["gid"]) && ($mybb->request_method != "post");
}

// Hook for the peeker
$plugins->add_hook("admin_settings_print_peekers", "accountswitcher_settings_peek");
/**
 * Add peeker in ACP
 *
 */
function accountswitcher_settings_peek(&$peekers)
{
	global $mybb, $accountswitcher_settings_peeker;

	if ($accountswitcher_settings_peeker)
	{
		// Peeker for author moderation settings
		$peekers[] = 'new Peeker($(".setting_aj_admin_changeauthor"), $("#row_setting_aj_admin_changegroup"),/1/,true)';
		$peekers[] = 'new Peeker($(".setting_aj_admin_changeauthor"), $("#row_setting_aj_authorpm"),/1/,true)';
		// Peeker for shared accounts style settings
		$peekers[] = 'new Peeker($(".setting_aj_shareuser"), $("#row_setting_aj_sharestyle"),/1/,true)';
		// Peeker for profile field on accountlist page settings
		$peekers[] = 'new Peeker($(".setting_aj_profilefield"), $("#row_setting_aj_profilefield_id"),/1/,true)';
	}
}

// Hook for updated settings
$plugins->add_hook("admin_config_settings_start", "accountswitcher_language_change");
/**
 * Change settings language strings after switching ACP language
 *
 */
function accountswitcher_language_change()
{
	global $mybb, $db, $lang;
	// Load language strings in plugin function
	if (!isset($lang->aj_group_descr))
	{
		$lang->load("accountswitcher");
	}

	// Get settings language string
	$query = $db->simple_select("settinggroups", "*", "name='Enhanced Account Switcher'");
	$easgroup = $db->fetch_array($query);

	if ($easgroup['description'] != $lang->aj_group_descr)
	{
		accountswitcher_settings_lang();
	}
}

/**
 * Update settings language in ACP
 *
 */
function accountswitcher_settings_lang()
{
	global $mybb, $db, $lang;

	// Load language strings in plugin function
	if (!isset($lang->aj_group_descr))
	{
		$lang->load("accountswitcher");
	}

	// Update setting group
	$updated_record_gr = array(
		"title" => $db->escape_string($lang->as_name),
		"description" => $db->escape_string($lang->aj_group_descr)
			);
	$db->update_query('settinggroups', $updated_record_gr, "name='Enhanced Account Switcher'");

	// Update settings
	$updated_record1 = array(
		"title" => $db->escape_string($lang->aj_postjump_title),
		"description" => $db->escape_string($lang->aj_postjump_descr)
			);
	$db->update_query('settings', $updated_record1, "name='aj_postjump'");

	$updated_record2 = array(
		"title" => $db->escape_string($lang->aj_changeauthor_title),
		"description" => $db->escape_string($lang->aj_changeauthor_descr)
			);
	$db->update_query('settings', $updated_record2, "name='aj_changeauthor'");

	$updated_record3 = array(
		"title" => $db->escape_string($lang->aj_pmnotice_title),
		"description" => $db->escape_string($lang->aj_pmnotice_descr)
			);
	$db->update_query('settings', $updated_record3, "name='aj_pmnotice'");

	$updated_record4 = array(
		"title" => $db->escape_string($lang->aj_profile_title),
		"description" => $db->escape_string($lang->aj_profile_descr)
			);
	$db->update_query('settings', $updated_record4, "name='aj_profile'");

	$updated_record5 = array(
		"title" => $db->escape_string($lang->aj_away_title),
		"description" => $db->escape_string($lang->aj_away_descr)
			);
	$db->update_query('settings', $updated_record5, "name='aj_away'");

	$updated_record6 = array(
		"title" => $db->escape_string($lang->aj_reload_title),
		"description" => $db->escape_string($lang->aj_reload_descr)
			);
	$db->update_query('settings', $updated_record6, "name='aj_reload'");

	$updated_record7 = array(
		"title" => $db->escape_string($lang->aj_list_title),
		"description" => $db->escape_string($lang->aj_list_descr)
			);
	$db->update_query('settings', $updated_record7, "name='aj_list'");

	$updated_record8 = array(
		"title" => $db->escape_string($lang->aj_postuser_title),
		"description" => $db->escape_string($lang->aj_postuser_descr)
			);
	$db->update_query('settings', $updated_record8, "name='aj_postuser'");

	$updated_record9 = array(
		"title" => $db->escape_string($lang->aj_shareuser_title),
		"description" => $db->escape_string($lang->aj_shareuser_descr)
			);
	$db->update_query('settings', $updated_record9, "name='aj_shareuser'");

	$updated_record10 = array(
		"title" => $db->escape_string($lang->aj_sharestyle_title),
		"description" => $db->escape_string($lang->aj_sharestyle_descr)
			);
	$db->update_query('settings', $updated_record10, "name='aj_sharestyle'");

	$updated_record11 = array(
		"title" => $db->escape_string($lang->aj_sortuser_title),
		"description" => $db->escape_string($lang->aj_sortuser_descr),
		"disporder" => 11
			);
	$db->update_query('settings', $updated_record11, "name='aj_sortuser'");

	$updated_record12 = array(
		"title" => $db->escape_string($lang->aj_headerdropdown_title),
		"description" => $db->escape_string($lang->aj_headerdropdown_descr),
		"disporder" => 12
			);
	$db->update_query('settings', $updated_record12, "name='aj_headerdropdown'");

	$updated_record13 = array(
		"title" => $db->escape_string($lang->aj_admin_changeauthor_title),
		"description" => $db->escape_string($lang->aj_admin_changeauthor_descr),
		"disporder" => 13
			);
	$db->update_query('settings', $updated_record13, "name='aj_admin_changeauthor'");

	$updated_record14 = array(
		"title" => $db->escape_string($lang->aj_admin_changegroup_title),
		"description" => $db->escape_string($lang->aj_admin_changegroup_descr),
		"disporder" => 14
			);
	$db->update_query('settings', $updated_record14, "name='aj_admin_changegroup'");

	$updated_record15 = array(
		"title" => $db->escape_string($lang->aj_authorpm_title),
		"description" => $db->escape_string($lang->aj_authorpm_descr),
		"disporder" => 15
			);
	$db->update_query('settings', $updated_record15, "name='aj_authorpm'");

	$updated_record16 = array(
		"title" => $db->escape_string($lang->aj_memberlist_title),
		"description" => $db->escape_string($lang->aj_memberlist_descr),
		"disporder" => 16
			);
	$db->update_query('settings', $updated_record16, "name='aj_memberlist'");

	$updated_record17 = array(
		"title" => $db->escape_string($lang->aj_sidebar_title),
		"description" => $db->escape_string($lang->aj_sidebar_descr),
		"disporder" => 17
			);
	$db->update_query('settings', $updated_record17, "name='aj_sidebar'");

	$updated_record18 = array(
		"title" => $db->escape_string($lang->aj_secstyle_title),
		"description" => $db->escape_string($lang->aj_secstyle_descr)
			);
	$db->update_query('settings', $updated_record18, "name='aj_secstyle'");

	$updated_record19 = array(
		"title" => $db->escape_string($lang->aj_profilefield_title),
		"description" => $db->escape_string($lang->aj_profilefield_descr)
			);
	$db->update_query('settings', $updated_record19, "name='aj_profilefield'");

	$updated_record20 = array(
		"title" => $db->escape_string($lang->aj_profilefield_id_title),
		"description" => $db->escape_string($lang->aj_profilefield_id_descr)
			);
	$db->update_query('settings', $updated_record20, "name='aj_profilefield_id'");

	$updated_record21 = array(
		"title" => $db->escape_string($lang->aj_postcount_title),
		"description" => $db->escape_string($lang->aj_postcount_descr)
			);
	$db->update_query('settings', $updated_record21, "name='aj_postcount'");

	$updated_record22 = array(
		"title" => $db->escape_string($lang->aj_myalerts_title),
		"description" => $db->escape_string($lang->aj_myalerts_descr),
		"disporder" => 22
			);
	$db->update_query('settings', $updated_record22, "name='aj_myalerts'");

	$updated_record23 = array(
		"title" => $db->escape_string($lang->aj_privacy_title),
		"description" => $db->escape_string($lang->aj_privacy_descr),
		"disporder" => 23
			);
	$db->update_query('settings', $updated_record23, "name='aj_privacy'");

	rebuild_settings();
}
