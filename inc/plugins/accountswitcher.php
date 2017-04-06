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

if (!defined("IN_MYBB"))
	die("We all know this is your fault.");

/**
 * The Plugin info.
 *
 * @return array The plugin information in plugin list.
 */
function accountswitcher_info()
{
	global $mybb, $lang, $db, $plugins_cache;

	$lang->load("accountswitcher");

	$accountswitcher_info = array(
		"name"			=> $lang->as_name,
		"description"	=> $lang->as_desc,
		"website"		=> "http://mybbplugins.de.vu",
		"author"		=> "doylecc",
		"authorsite"	=> "http://mybbplugins.de.vu",
		"version"		=> "2.0.8",
		"compatibility"	=> "18*",
		"codename"		=> "eas_accountswitcher"
	);

	// Add settings link to plugin info
	if (accountswitcher_is_installed() && is_array($plugins_cache) && is_array($plugins_cache['active']) && $plugins_cache['active']['accountswitcher'])
	{
		$result = $db->simple_select('settinggroups', 'gid', "name = 'Enhanced Account Switcher'");
		$set = $db->fetch_array($result);
		if (!empty($set))
		{
			$accountswitcher_info['description'] .= "<div style=\"float: right; padding-right: 20px;\"><img src=\"styles/default/images/icons/custom.png\" alt=\"\" style=\"margin-left: 10px;\" /><a href=\"index.php?module=config-settings&amp;action=change&amp;gid=".(int)$set['gid']."\" style=\"margin: 10px;\">".$lang->as_name_settings."</a>|";
		}
		// Add cleanup and debug links
		$accountswitcher_info['description'] .= "<img src=\"styles/default/images/icons/run_task.png\" alt=\"\" style=\"margin-left: 10px;\" /><a href=\"index.php?module=tools-accountswitcher&amp;action=cleanup&amp;my_post_key=".$mybb->post_code."\" style=\"margin: 10px;\">".$lang->as_name_cleanup."</a>|";
		$accountswitcher_info['description'] .= "<img src=\"styles/default/images/icons/find.png\" alt=\"\" style=\"margin-left: 10px;\" /><a href=\"../index.php?as_debug=1\" style=\"margin: 10px;\" target=\"_blank\">Plugin Debug Information</a><hr style=\"margin-bottom: 5px;\" /></div>";
	}
	return $accountswitcher_info;
}


// Load the install/admin functions in ACP.
if (defined("IN_ADMINCP"))
{
	require_once MYBB_ROOT."inc/plugins/accountswitcher/as_install.php";
	require_once MYBB_ROOT."inc/plugins/accountswitcher/as_admincp.php";
}
else  // Load all the frontend functions
{
	global $mybb, $cache;
	require_once MYBB_ROOT."inc/plugins/accountswitcher/as_usercp.php";
	require_once MYBB_ROOT."inc/plugins/accountswitcher/as_functions.php";
	if ($mybb->settings['aj_myalerts'] == 1 && isset($mybb->settings['myalerts_avatar_size']))
	{
		$plugins_cache = $cache->read('plugins');
		if (is_array($plugins_cache) && is_array($plugins_cache['active']) && $plugins_cache['active']['myalerts'])
		{
			require_once MYBB_ROOT.'inc/plugins/accountswitcher/as_alerts.php';
		}
	}
}



//########## Cache and online functions ################
$plugins->add_hook("global_start", "accountswitcher_init");
$plugins->add_hook("admin_load", "accountswitcher_init");
/**
 * Load the account switcher class.
 *
 */
function accountswitcher_init()
{
	global $mybb, $db, $cache, $templates, $eas;

	require_once MYBB_ROOT."/inc/plugins/accountswitcher/class_accountswitcher.php";
	$eas = new AccountSwitcher($mybb, $db, $cache, $templates);
}


// Add the hooks for updating the cache
$plugins->add_hook("private_do_send_end", "accountswitcher_pm");
$plugins->add_hook("private_read_end", "accountswitcher_pm");
$plugins->add_hook("newreply_do_newreply_end", "accountswitcher_pm");
$plugins->add_hook("newthread_do_newthread_end", "accountswitcher_pm");
$plugins->add_hook("usercp_do_avatar_end", "accountswitcher_pm");
$plugins->add_hook('admin_user_users_edit_commit', 'accountswitcher_pm');
$plugins->add_hook('modcp_do_editprofile_end', 'accountswitcher_pm');
/**
 * Updates the cache.
 *
 */
function accountswitcher_pm()
{
	global $eas;
	$eas->update_accountswitcher_cache();
}

// Add the hooks for updating profile field cache
$plugins->add_hook("admin_config_settings_change_commit", "accountswitcher_ufields");
$plugins->add_hook("admin_user_users_edit_commit", "accountswitcher_ufields");
$plugins->add_hook("usercp_do_profile_end", "accountswitcher_ufields");
$plugins->add_hook("modcp_do_editprofile_end", "accountswitcher_ufields");
/**
 * Updates the cache when changing plugin settings or user profile settings.
 *
 */
function accountswitcher_ufields()
{
	global $eas;
	$eas->update_userfields_cache();
}


// Add hook for online location
$plugins->add_hook('build_friendly_wol_location_end', 'accountswitcher_online');
/**
 * Show the location for accountlist in online.php.
 *
 */
function accountswitcher_online(&$plugin_array)
{
	global $lang;

	if (!isset($lang->aj_online))
	{
		$lang->load("accountswitcher");
	}

	if (my_strpos($plugin_array['user_activity']['location'], 'accountlist.php'))
	{
		$plugin_array['location_name'] = $lang->aj_online;
	}
}


// Add hook for debug page
$plugins->add_hook('index_end', 'accountswitcher_debug');
/**
 * Show account switcher debug information
 *
 */
function accountswitcher_debug()
{
	global $mybb, $db, $cache, $lang, $eas;

	// Admin access only
	if ($mybb->usergroup['cancp'] == 1 && isset($mybb->input['as_debug']) && $mybb->input['as_debug'] == 1)
	{
		// Declare variables
		$grouprows = $groupname = $groupid = $groupswitch = $grouplimit = $nogroups = '';
		$groupcache = $cache->read('usergroups');
		$count = 0;
		$myalertscheck = 'no';
		if ($db->table_exists('alert_types'))
		{
			$myalertscheck = 'yes';
		}
		$lang->load("accountswitcher");
		// User group settings
		if (is_array($groupcache))
		{
			foreach ($groupcache as $usergroup)
			{
				$groupname = htmlspecialchars_uni($usergroup['title']);
				$groupid = (int)$usergroup['gid'];
				$groupswitch = (int)$usergroup['as_canswitch'];
				$grouplimit = (int)$usergroup['as_limit'];
				$grouprows .= '
					<tr>
					<td style="background: #fff;">'.$groupname.'</td><td style="background: #fff; text-align: center;">'.$groupid.'</td><td style="background: #fff; text-align: center;">'.$groupswitch.'</td><td style="background: #fff; text-align: center;">'.$grouplimit.'</td>
					</tr>
					';
				if ($usergroup['as_canswitch'] == 1)
				{
					++$count;
				}
			}
		}

		if ($count == 0)
		{
			$nogroups = '<tr>
							<td style="background: #fff;" colspan="4">
								<h3 style="color: red;">'.$lang->as_installed_nogroup.'</h3>
							</td>
						</tr>';
		}

		// Output page
		echo "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">\n";
		echo "<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"en\" lang=\"en\">";
		echo "<head>";
		echo "<meta name=\"robots\" content=\"noindex\" />";
		echo "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\" />";
		echo "<title>Enhanced Account Switcher Debug Information</title>";
		echo "</head>";
		echo "<body>";
		echo "<table style=\"background-color: #666; font-size: 0.9em;\" width=\"91%\" cellpadding=\"4\" cellspacing=\"1\" align=\"center\">\n";
		echo "<tr>\n";
		echo "<td style=\"background: #fff; text-align: center;\"><h2>Enhanced Account Switcher Debug Information</h2></td>\n";
		echo "</td>\n";
		echo "</tr>\n";
		echo "<tr>\n";
		echo "<td>\n";
		echo "<table style=\"background-color: #666; font-size: 0.9em; float: left;\" width=\"49%\" cellpadding=\"4\" cellspacing=\"1\">\n";
		echo "<tr>\n";
		echo "<td style=\"background: #fff;\" colspan=\"2\">";
		echo "<h2 style=\"text-align: center;\">Plugin Settings </h2>\n";
		echo "</td>\n";
		echo "</tr>\n";
		echo "<tr>\n";
		echo "<td style=\"background: #fff;\">Account switch on posting: </td>\n";
		echo "<td style=\"background: #fff; text-align: center;\">".(int)$mybb->settings['aj_postjump']."</td>\n";
		echo "</tr>\n";
		echo "<tr>\n";
		echo "<td style=\"background: #fff;\">Change author of an existing post: </td>\n";
		echo "<td style=\"background: #fff; text-align: center;\">".(int)$mybb->settings['aj_changeauthor']."</td>\n";
		echo "</tr>\n";
		echo "<tr>\n";
		echo "<td style=\"background: #fff;\">PM Notice for all attached accounts: </td>\n";
		echo "<td style=\"background: #fff; text-align: center;\">".(int)$mybb->settings['aj_pmnotice']."</td>\n";
		echo "</tr>\n";
		echo "<tr>\n";
		echo "<td style=\"background: #fff;\">Attached accounts in profile: </td>\n";
		echo "<td style=\"background: #fff; text-align: center;\">".(int)$mybb->settings['aj_profile']."</td>\n";
		echo "</tr>\n";
		echo "<tr>\n";
		echo "<td style=\"background: #fff;\">Away Status of the master account for all attached accounts: </td>\n";
		echo "<td style=\"background: #fff; text-align: center;\">".(int)$mybb->settings['aj_away']."</td>\n";
		echo "</tr>\n";
		echo "<tr>\n";
		echo "<td style=\"background: #fff;\">Reload after switch from dropdown list: </td>\n";
		echo "<td style=\"background: #fff; text-align: center;\">".(int)$mybb->settings['aj_reload']."</td>\n";
		echo "</tr>\n";
		echo "<tr>\n";
		echo "<td style=\"background: #fff;\">Accountlist: </td>\n";
		echo "<td style=\"background: #fff; text-align: center;\">".(int)$mybb->settings['aj_list']."</td>\n";
		echo "</tr>\n";
		echo "<tr>\n";
		echo "<td style=\"background: #fff;\">Accounts in Postbit: </td>\n";
		echo "<td style=\"background: #fff; text-align: center;\">".(int)$mybb->settings['aj_postuser']."</td>\n";
		echo "</tr>\n";
		echo "<tr>\n";
		echo "<td style=\"background: #fff;\">Share Accounts: </td>\n";
		echo "<td style=\"background: #fff; text-align: center;\">".(int)$mybb->settings['aj_shareuser']."</td>\n";
		echo "</tr>\n";
		echo "<tr>\n";
		echo "<td style=\"background: #fff;\">Username Style of Shared Accounts: </td>\n";
		echo "<td style=\"background: #fff; text-align: center;\">".(int)$mybb->settings['aj_sharestyle']."</td>\n";
		echo "</tr>\n";
		echo "<tr>\n";
		echo "<td style=\"background: #fff;\">Sort Accounts: </td>\n";
		echo "<td style=\"background: #fff; text-align: center;\">".htmlspecialchars_uni($mybb->settings['aj_sortuser'])."</td>\n";
		echo "</tr>\n";
		echo "<tr>\n";
		echo "<td style=\"background: #fff;\">Accounts in header as dropdown: </td>\n";
		echo "<td style=\"background: #fff; text-align: center;\">".(int)$mybb->settings['aj_headerdropdown']."</td>\n";
		echo "</tr>\n";
		echo "<tr>\n";
		echo "<td style=\"background: #fff;\">Team change post author: </td>\n";
		echo "<td style=\"background: #fff; text-align: center;\">".(int)$mybb->settings['aj_admin_changeauthor']."</td>\n";
		echo "</tr>\n";
		echo "<tr>\n";
		echo "<td style=\"background: #fff;\">Team author change permission: </td>\n";
		echo "<td style=\"background: #fff; text-align: center;\">".htmlspecialchars_uni($mybb->settings['aj_admin_changegroup'])."</td>\n";
		echo "</tr>\n";
		echo "<tr>\n";
		echo "<td style=\"background: #fff;\">PM after Author Moderation: </td>\n";
		echo "<td style=\"background: #fff; text-align: center;\">".(int)$mybb->settings['aj_authorpm']."</td>\n";
		echo "</tr>\n";
		echo "<tr>\n";
		echo "<td style=\"background: #fff;\">Attached Accounts in Memberlist: </td>\n";
		echo "<td style=\"background: #fff; text-align: center;\">".(int)$mybb->settings['aj_memberlist']."</td>\n";
		echo "</tr>\n";
		echo "<tr>\n";
		echo "<td style=\"background: #fff;\">Attached Accounts in Sidebar: </td>\n";
		echo "<td style=\"background: #fff; text-align: center;\">".(int)$mybb->settings['aj_sidebar']."</td>\n";
		echo "</tr>\n";
		echo "<tr>\n";
		echo "<td style=\"background: #fff;\">Mark Secondary Accounts: </td>\n";
		echo "<td style=\"background: #fff; text-align: center;\">".(int)$mybb->settings['aj_secstyle']."</td>\n";
		echo "</tr>\n";
		echo "<tr>\n";
		echo "<td style=\"background: #fff;\">Profile Field in Accountlist: </td>\n";
		echo "<td style=\"background: #fff; text-align: center;\">".(int)$mybb->settings['aj_profilefield']."</td>\n";
		echo "</tr>\n";
		echo "<tr>\n";
		echo "<td style=\"background: #fff;\">ID of the Profile Field: </td>\n";
		echo "<td style=\"background: #fff; text-align: center;\">".(int)$mybb->settings['aj_profilefield_id']."</td>\n";
		echo "</tr>\n";
		echo "<tr>\n";
		echo "<td style=\"background: #fff;\">Attached Accounts Post Count: </td>\n";
		echo "<td style=\"background: #fff; text-align: center;\">".(int)$mybb->settings['aj_postcount']."</td>\n";
		echo "</tr>\n";
		echo "<tr>\n";
		echo "<td style=\"background: #fff;\">MyAlerts Integration: </td>\n";
		echo "<td style=\"background: #fff; text-align: center;\">".(int)$mybb->settings['aj_myalerts']."</td>\n";
		echo "</tr>\n";
		echo "<tr>\n";
		echo "<td style=\"background: #fff;\">Hide Attached Accounts: </td>\n";
		echo "<td style=\"background: #fff; text-align: center;\">".(int)$mybb->settings['aj_privacy']."</td>\n";
		echo "</tr>\n";
		echo "</table>\n";
		echo "<table style=\"background-color: #666; float: left; font-size: 0.9em; margin-left: 27px;\" width=\"49%\" cellpadding=\"4\" cellspacing=\"1\">\n";
		echo "<tr>\n";
		echo "<td style=\"background: #fff;\" colspan=\"4\">";
		echo "<h2 style=\"text-align: center;\">Plugin Version </h2>\n";
		echo "</td>\n";
		echo "</tr>\n";
		echo "<tr>\n";
		echo "<td style=\"background: #fff;\" colspan=\"3\">Version:</td>\n";
		echo "<td style=\"background: #fff; text-align: center;\">".$eas->release."</td>\n";
		echo "</tr>\n";
		echo "<tr>\n";
		echo "<td style=\"background: #fff;\" colspan=\"3\">Version Number:</td>\n";
		echo "<td style=\"background: #fff; text-align: center;\">".$eas->version."</td>\n";
		echo "</tr>\n";
		echo "<tr>\n";
		echo "<td style=\"background: #fff;\" colspan=\"4\">";
		echo "<h2 style=\"text-align: center;\">Ajax Setting </h2>\n";
		echo "</td>\n";
		echo "</tr>\n";
		echo "<tr>\n";
		echo "<td style=\"background: #fff;\" colspan=\"3\">Enable XMLHttp request features: </td>\n";
		echo "<td style=\"background: #fff; text-align: center;\">".(int)$mybb->settings['use_xmlhttprequest']."</td>\n";
		echo "</tr>\n";
		echo "<tr>\n";
		echo "<td style=\"background: #fff;\" colspan=\"4\">";
		echo "<h2 style=\"text-align: center;\">My Alerts 2.0 Plugin </h2>\n";
		echo "</td>\n";
		echo "</tr>\n";
		echo "<tr>\n";
		echo "<td style=\"background: #fff;\" colspan=\"3\">Installed: </td>\n";
		echo "<td style=\"background: #fff; text-align: center;\">".$myalertscheck."</td>\n";
		echo "</tr>\n";
		echo "<tr>\n";
		echo "<td style=\"background: #fff;\" colspan=\"4\">";
		echo "<h2 style=\"text-align: center;\">User Group Settings </h2>\n";
		echo "</td>\n";
		echo "</tr>\n";
		echo "<tr>\n";
		echo "<td style=\"background: #fff; font-weight: bold;\">Group Title</td>\n";
		echo "<td style=\"background: #fff; font-weight: bold; text-align: center;\">Group ID</td>\n";
		echo "<td style=\"background: #fff; font-weight: bold; text-align: center;\">Can Switch</td>\n";
		echo "<td style=\"background: #fff; font-weight: bold; text-align: center;\">Switch Limit</td>\n";
		echo "</tr>\n";
		echo $grouprows;
		echo $nogroups;
		echo "</table>\n";
		echo "<br />\n";
		echo "</td>\n";
		echo "</tr>\n";
		echo "</table>\n";
		echo "</body>";
		echo "</html>";
		exit;
	}

}


// Caching templates
global $templatelist;

if (isset($templatelist))
{
	$templatelist .= ',';
}
$templatelist .= 'accountswitcher_header_userbit,accountswitcher_header_dropdown_userbit,accountswitcher_header,accountswitcher_header_dropdown,accountswitcher_header_accountlist,accountswitcher_newpm_messagebit,accountswitcher_newpm_message,accountswitcher_footer,accountswitcher_post_userbit,accountswitcher_post,accountswitcher_sec_accountsbit,accountswitcher_shared_accountsbit,accountswitcher_sidebar_userbit,accountswitcher_sidebar,accountswitcher_avatar';

if (my_strpos($_SERVER['PHP_SELF'], 'usercp.php'))
{
	if (isset($templatelist))
	{
		$templatelist .= ',';
	}
	$templatelist .= 'accountswitcher_usercp_nav,accountswitcher_usercp_options,accountswitcher_usercp_attached_userbit,accountswitcher_usercp_attached_users,accountswitcher_usercp,accountswitcher_usercp_unshare,accountswitcher_usercp_attached_detach,accountswitcher_usercp_free_attach,accountswitcher_usercp_shareuser,accountswitcher_usercp_master_attach,accountswitcher_usercp_away,accountswitcher_usercp_sec_account,accountswitcher_usercp_privacy_master,accountswitcher_usercp_privacy,accountswitcher_usercp_buddyshare';
}
if (my_strpos($_SERVER['PHP_SELF'], 'private.php'))
{
	if (isset($templatelist))
	{
		$templatelist .= ',';
	}
	$templatelist .= 'accountswitcher_usercp_nav';
}
if (my_strpos($_SERVER['PHP_SELF'], 'member.php'))
{
	if (isset($templatelist))
	{
		$templatelist .= ',';
	}
	$templatelist .= 'accountswitcher_profile_switch,accountswitcher_profile_link,accountswitcher_profile';
}
if (my_strpos($_SERVER['PHP_SELF'], 'showthread.php'))
{
	if (isset($templatelist))
	{
		$templatelist .= ',';
	}
	$templatelist .= 'accountswitcher_postbit_switch,accountswitcher_postbit_link,accountswitcher_postbit,accountswitcher_author_button,accountswitcher_author_button_admin,accountswitcher_author_button_attached,accountswitcher_author_userbit,accountswitcher_author_selfbit,accountswitcher_author_admin,accountswitcher_author_change';
}
if (my_strpos($_SERVER['PHP_SELF'], 'editpost.php'))
{
	if (isset($templatelist))
	{
		$templatelist .= ',';
	}
	$templatelist .= 'accountswitcher_author_userbit,accountswitcher_author_selfbit,accountswitcher_author_admin,accountswitcher_author_change';
}
if (my_strpos($_SERVER['PHP_SELF'], 'memberlist.php'))
{
	if (isset($templatelist))
	{
		$templatelist .= ',';
	}
	$templatelist .= 'accountswitcher_memberlist_switch,accountswitcher_memberlist_link,accountswitcher_memberlist,accountswitcher_memberlist_shared';
}
