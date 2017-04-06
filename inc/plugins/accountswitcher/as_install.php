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


/**
 * Installs the plugin.
 *
 */
function accountswitcher_install()
{
	global $db, $mybb, $cache, $lang;

	// Drop columns to avoid database errors
	if ($db->field_exists("as_uid", "users"))
	{
		$db->drop_column("users", "as_uid");
	}
	if ($db->field_exists("as_share", "users"))
	{
		$db->drop_column("users", "as_share");
	}
	if ($db->field_exists("as_shareuid", "users"))
	{
		$db->drop_column("users", "as_shareuid");
	}
	if ($db->field_exists("as_sec", "users"))
	{
		$db->drop_column("users", "as_sec");
	}
	if ($db->field_exists("as_secreason", "users"))
	{
		$db->drop_column("users", "as_secreason");
	}
	if ($db->field_exists("as_privacy", "users"))
	{
		$db->drop_column("users", "as_privacy");
	}
	if ($db->field_exists("as_buddyhare", "users"))
	{
		$db->drop_column("users", "as_buddyshare");
	}
	if ($db->field_exists("as_canswitch", "usergroups"))
	{
		$db->drop_column("usergroups", "as_canswitch");
	}
	if ($db->field_exists("as_limit", "usergroups"))
	{
		$db->drop_column("usergroups", "as_limit");
	}

	// Add database columns
	if (!$db->field_exists("as_uid", "users"))
	{
		$db->add_column('users', 'as_uid', 'INT(11) NOT NULL DEFAULT "0"');
	}
	if (!$db->field_exists("as_share", "users"))
	{
		$db->add_column('users', 'as_share', 'INT(1) NOT NULL DEFAULT "0"');
	}
	if (!$db->field_exists("as_shareuid", "users"))
	{
		$db->add_column('users', 'as_shareuid', 'INT(11) NOT NULL DEFAULT "0"');
	}
	if (!$db->field_exists("as_sec", "users"))
	{
		$db->add_column('users', 'as_sec', 'INT(1) NOT NULL DEFAULT "0"');
	}
	if (!$db->field_exists("as_secreason", "users"))
	{
		$db->add_column('users', 'as_secreason', 'TEXT NOT NULL');
	}
	if (!$db->field_exists("as_privacy", "users"))
	{
		$db->add_column('users', 'as_privacy', 'INT(1) NOT NULL DEFAULT "0"');
	}
	if (!$db->field_exists("as_buddyshare", "users"))
	{
		$db->add_column('users', 'as_buddyshare', 'INT(1) NOT NULL DEFAULT "0"');
	}
	if (!$db->field_exists("as_canswitch", "usergroups"))
	{
		$db->add_column('usergroups', 'as_canswitch', 'INT(1) NOT NULL DEFAULT "0"');
	}
	if (!$db->field_exists("as_limit", "usergroups"))
	{
		$db->add_column('usergroups', 'as_limit', 'SMALLINT(5) NOT NULL DEFAULT "0"');
	}
	$cache->update_usergroups();

	$lang->load("accountswitcher");

	// Add the new templates
	accountswitcher_templates_add();

	/**
	 *
	 * Settings
	 *
	 **/

	// Avoid duplicated settings
	$query_setgr = $db->simple_select('settinggroups','gid','name="Enhanced Account Switcher"');
	$ams = $db->fetch_array($query_setgr);
	$db->delete_query('settinggroups',"gid='".$ams['gid']."'");
	$db->delete_query('settings',"gid='".$ams['gid']."'");

	// Add the settings
	$query = $db->simple_select("settinggroups", "COUNT(*) as rows");
	$rows = $db->fetch_field($query, "rows");

	// Add settinggroup for global settings
	$account_jumper_group = array(
		"name" => "Enhanced Account Switcher",
		"title" => $db->escape_string($lang->as_name),
		"description" => $db->escape_string($lang->aj_group_descr),
		"disporder" => $rows+1,
		"isdefault" => 0
	);
	$db->insert_query("settinggroups", $account_jumper_group);
	$gid = $db->insert_id();

	// Add settings for the settinggroup
	$account_jumper_1 = array(
		"name" => "aj_postjump",
		"title" => $db->escape_string($lang->aj_postjump_title),
		"description" => $db->escape_string($lang->aj_postjump_descr),
		"optionscode" => "yesno",
		"value" => 1,
		"disporder" => 1,
		"gid" => (int)$gid
		);
	$db->insert_query("settings", $account_jumper_1);

	$account_jumper_2 = array(
		"name" => "aj_changeauthor",
		"title" => $db->escape_string($lang->aj_changeauthor_title),
		"description" => $db->escape_string($lang->aj_changeauthor_descr),
		"optionscode" => "yesno",
		"value" => 1,
		"disporder" => 2,
		"gid" => (int)$gid
		);
	$db->insert_query("settings", $account_jumper_2);

	$account_jumper_3 = array(
		"name" => "aj_pmnotice",
		"title" => $db->escape_string($lang->aj_pmnotice_title),
		"description" => $db->escape_string($lang->aj_pmnotice_descr),
		"optionscode" => "yesno",
		"value" => 1,
		"disporder" => 3,
		"gid" => (int)$gid
		);
	$db->insert_query("settings", $account_jumper_3);

	$account_jumper_4 = array(
		"name" => "aj_profile",
		"title" => $db->escape_string($lang->aj_profile_title),
		"description" => $db->escape_string($lang->aj_profile_descr),
		"optionscode" => "yesno",
		"value" => 1,
		"disporder" => 4,
		"gid" => (int)$gid
		);
	$db->insert_query("settings", $account_jumper_4);

	$account_jumper_5 = array(
		"name" => "aj_away",
		"title" => $db->escape_string($lang->aj_away_title),
		"description" => $db->escape_string($lang->aj_away_descr),
		"optionscode" => "yesno",
		"value" => 1,
		"disporder" => 5,
		"gid" => (int)$gid
		);
	$db->insert_query("settings", $account_jumper_5);

	$account_jumper_6 = array(
		"name" => "aj_reload",
		"title" => $db->escape_string($lang->aj_reload_title),
		"description" => $db->escape_string($lang->aj_reload_descr),
		"optionscode" => "yesno",
		"value" => 1,
		"disporder" => 6,
		"gid" => (int)$gid
		);
	$db->insert_query("settings", $account_jumper_6);

	$account_jumper_7 = array(
		"name" => "aj_list",
		"title" => $db->escape_string($lang->aj_list_title),
		"description" => $db->escape_string($lang->aj_list_descr),
		"optionscode" => "yesno",
		"value" => 1,
		"disporder" => 7,
		"gid" => (int)$gid
		);
	$db->insert_query("settings", $account_jumper_7);

	$account_jumper_8 = array(
		"name" => "aj_postuser",
		"title" => $db->escape_string($lang->aj_postuser_title),
		"description" => $db->escape_string($lang->aj_postuser_descr),
		"optionscode" => "yesno",
		"value" => 1,
		"disporder" => 8,
		"gid" => (int)$gid
		);
	$db->insert_query("settings", $account_jumper_8);

	$account_jumper_9 = array(
		"name" => "aj_shareuser",
		"title" => $db->escape_string($lang->aj_shareuser_title),
		"description" => $db->escape_string($lang->aj_shareuser_descr),
		"optionscode" => "yesno",
		"value" => 1,
		"disporder" => 9,
		"gid" => (int)$gid
		);
	$db->insert_query("settings", $account_jumper_9);

	$account_jumper_10 = array(
		"name" => "aj_sharestyle",
		"title" => $db->escape_string($lang->aj_sharestyle_title),
		"description" => $db->escape_string($lang->aj_sharestyle_descr),
		"optionscode" => "yesno",
		"value" => 1,
		"disporder" => 10,
		"gid" => (int)$gid
		);
	$db->insert_query("settings", $account_jumper_10);

	$account_jumper_11 = array(
		"name" => "aj_sortuser",
		"title" => $db->escape_string($lang->aj_sortuser_title),
		"description" => $db->escape_string($lang->aj_sortuser_descr),
		"optionscode" => "select\nuid=User-ID\nuname=Username",
		"value" => "uid",
		"disporder" => 11,
		"gid" => (int)$gid
		);
	$db->insert_query("settings", $account_jumper_11);

	$account_jumper_12 = array(
		"name" => "aj_headerdropdown",
		"title" => $db->escape_string($lang->aj_headerdropdown_title),
		"description" => $db->escape_string($lang->aj_headerdropdown_descr),
		"optionscode" => "yesno",
		"value" => 0,
		"disporder" => 12,
		"gid" => (int)$gid
		);
	$db->insert_query("settings", $account_jumper_12);

	$account_jumper_13 = array(
		"name" => "aj_admin_changeauthor",
		"title" => $db->escape_string($lang->aj_admin_changeauthor_title),
		"description" => $db->escape_string($lang->aj_admin_changeauthor_descr),
		"optionscode" => "yesno",
		"value" => 1,
		"disporder" => 13,
		"gid" => (int)$gid
		);
	$db->insert_query("settings", $account_jumper_13);

	$account_jumper_14 = array(
		"name" => "aj_admin_changegroup",
		"title" => $db->escape_string($lang->aj_admin_changegroup_title),
		"description" => $db->escape_string($lang->aj_admin_changegroup_descr),
		"optionscode" => "radio
admin=".$db->escape_string($lang->aj_admin_changegroup_admins)."
supermods=".$db->escape_string($lang->aj_admin_changegroup_supermods)."
mods=".$db->escape_string($lang->aj_admin_changegroup_mods)."",
		"value" => "admin",
		"disporder" => 14,
		"gid" => (int)$gid
		);
	$db->insert_query("settings", $account_jumper_14);

	$account_jumper_15 = array(
		"name" => "aj_authorpm",
		"title" => $db->escape_string($lang->aj_authorpm_title),
		"description" => $db->escape_string($lang->aj_authorpm_descr),
		"optionscode" => "yesno",
		"value" => 0,
		"disporder" => 15,
		"gid" => (int)$gid
		);
	$db->insert_query("settings", $account_jumper_15);

	$account_jumper_16 = array(
		"name" => "aj_memberlist",
		"title" => $db->escape_string($lang->aj_memberlist_title),
		"description" => $db->escape_string($lang->aj_memberlist_descr),
		"optionscode" => "yesno",
		"value" => 1,
		"disporder" => 16,
		"gid" => (int)$gid
		);
	$db->insert_query("settings", $account_jumper_16);

	$account_jumper_17 = array(
		"name" => "aj_sidebar",
		"title" => $db->escape_string($lang->aj_sidebar_title),
		"description" => $db->escape_string($lang->aj_sidebar_descr),
		"optionscode" => "yesno",
		"value" => 1,
		"disporder" => 17,
		"gid" => (int)$gid
		);
	$db->insert_query("settings", $account_jumper_17);

	$account_jumper_18 = array(
		"name" => "aj_secstyle",
		"title" => $db->escape_string($lang->aj_secstyle_title),
		"description" => $db->escape_string($lang->aj_secstyle_descr),
		"optionscode" => "yesno",
		"value" => 1,
		"disporder" => 18,
		"gid" => (int)$gid
		);
	$db->insert_query("settings", $account_jumper_18);

	$account_jumper_19 = array(
		"name" => "aj_profilefield",
		"title" => $db->escape_string($lang->aj_profilefield_title),
		"description" => $db->escape_string($lang->aj_profilefield_descr),
		"optionscode" => "yesno",
		"value" => 0,
		"disporder" => 19,
		"gid" => (int)$gid
		);
	$db->insert_query("settings", $account_jumper_19);

	$account_jumper_20 = array(
		"name" => "aj_profilefield_id",
		"title" => $db->escape_string($lang->aj_profilefield_id_title),
		"description" => $db->escape_string($lang->aj_profilefield_id_descr),
		"optionscode" => "numeric",
		"value" => "0",
		"disporder" => 20,
		"gid" => (int)$gid
		);
	$db->insert_query("settings", $account_jumper_20);

	$account_jumper_21 = array(
		"name" => "aj_postcount",
		"title" => $db->escape_string($lang->aj_postcount_title),
		"description" => $db->escape_string($lang->aj_postcount_descr),
		"optionscode" => "yesno",
		"value" => 1,
		"disporder" => 21,
		"gid" => (int)$gid
		);
	$db->insert_query("settings", $account_jumper_21);

	$account_jumper_22 = array(
		"name" => "aj_myalerts",
		"title" => $db->escape_string($lang->aj_myalerts_title),
		"description" => $db->escape_string($lang->aj_myalerts_descr),
		"optionscode" => "yesno",
		"value" => $alertsetting,
		"disporder" => 22,
		"gid" => (int)$gid
		);
	$db->insert_query("settings", $account_jumper_22);

	$account_jumper_23 = array(
		"name" => "aj_privacy",
		"title" => $db->escape_string($lang->aj_privacy_title),
		"description" => $db->escape_string($lang->aj_privacy_descr),
		"optionscode" => "yesno",
		"value" => 1,
		"disporder" => 23,
		"gid" => (int)$gid
		);
	$db->insert_query("settings", $account_jumper_23);

	// Refresh settings.php
	rebuild_settings();
}


/**
 * Checks whether the plugin is installed.
 *
 * @return boolean True if the database fields exist, otherwise false.
 */
function accountswitcher_is_installed()
{
	global $db;

	if ($db->field_exists("as_uid", "users") && $db->field_exists("as_canswitch", "usergroups") && $db->field_exists("as_limit", "usergroups"))
	{
		return true;
	}
	else
	{
		return false;
	}
}

/**
 * Activates the plugin.
 *
 */
function accountswitcher_activate()
{
	global $mybb, $db, $cache, $templates, $lang, $eas;

	$lang->load("accountswitcher");

	// Integrate MyAlerts
	$alertsetting = 0;
	if ($db->table_exists('alert_types'))
	{
		if (!accountswitcher_alerts_status())
		{
			accountswitcher_alerts_integrate();
			$alertsetting = 1;
		}
	}

	// Template edits
	accountswitcher_revert_template_edits();
	accountswitcher_apply_template_edits();

	// Integrate MyAlerts
	$alertsetting = 0;
	if ($db->table_exists('alert_types'))
	{
		$alertsetting = 1;
		if (!accountswitcher_alerts_status())
		{
			accountswitcher_alerts_integrate();
		}
	}

	// If we are upgrading...add the new settings
	$query = $db->simple_select("settings", "*", "name='aj_postjump'");
	$result = $db->num_rows($query);

	if (!$result)
	{
		$query2 = $db->simple_select("settinggroups", "COUNT(*) as rows");
		$rows = $db->fetch_field($query2, "rows");

		// Add settinggroup for the settings
		$account_jumper_group = array(
			"name" => "Enhanced Account Switcher",
			"title" => $db->escape_string($lang->as_name),
			"description" => $db->escape_string($lang->aj_group_descr),
			"disporder" => $rows+1,
			"isdefault" => 0
		);
		$db->insert_query("settinggroups", $account_jumper_group);
		$gid = $db->insert_id();

		// Add settings for the settinggroup
		$account_jumper_1 = array(
			"name" => "aj_postjump",
			"title" => $db->escape_string($lang->aj_postjump_title),
			"description" => $db->escape_string($lang->aj_postjump_descr),
			"optionscode" => "yesno",
			"value" => 1,
			"disporder" => 1,
			"gid" => (int)$gid
			);
		$db->insert_query("settings", $account_jumper_1);

		$account_jumper_2 = array(
			"name" => "aj_changeauthor",
			"title" => $db->escape_string($lang->aj_changeauthor_title),
			"description" => $db->escape_string($lang->aj_changeauthor_descr),
			"optionscode" => "yesno",
			"value" => 1,
			"disporder" => 2,
			"gid" => (int)$gid
			);
		$db->insert_query("settings", $account_jumper_2);

		$account_jumper_3 = array(
			"name" => "aj_pmnotice",
			"title" => $db->escape_string($lang->aj_pmnotice_title),
			"description" => $db->escape_string($lang->aj_pmnotice_descr),
			"optionscode" => "yesno",
			"value" => 1,
			"disporder" => 3,
			"gid" => (int)$gid
			);
		$db->insert_query("settings", $account_jumper_3);

		$account_jumper_4 = array(
			"name" => "aj_profile",
			"title" => $db->escape_string($lang->aj_profile_title),
			"description" => $db->escape_string($lang->aj_profile_descr),
			"optionscode" => "yesno",
			"value" => 1,
			"disporder" => 4,
			"gid" => (int)$gid
			);
		$db->insert_query("settings", $account_jumper_4);

		$account_jumper_5 = array(
			"name" => "aj_away",
			"title" => $db->escape_string($lang->aj_away_title),
			"description" => $db->escape_string($lang->aj_away_descr),
			"optionscode" => "yesno",
			"value" => 1,
			"disporder" => 5,
			"gid" => (int)$gid
			);
		$db->insert_query("settings", $account_jumper_5);
	}

	// Upgrade to v1.5
	$query_gr = $db->simple_select("settinggroups", "gid", "name='Enhanced Account Switcher'");
	$eacgid = $db->fetch_array($query_gr);
	if ($eacgid)
	{
		$gid = $eacgid['gid'];
	}
	$query_reload = $db->simple_select("settings", "*", "name='aj_reload'");
	$result_reload = $db->num_rows($query_reload);

	if (!$result_reload)
	{
		$account_jumper_6 = array(
			"name" => "aj_reload",
			"title" => $db->escape_string($lang->aj_reload_title),
			"description" => $db->escape_string($lang->aj_reload_descr),
			"optionscode" => "yesno",
			"value" => 1,
			"disporder" => 6,
			"gid" => (int)$gid
			);
		$db->insert_query("settings", $account_jumper_6);
	}

	$query_list = $db->simple_select("settings", "*", "name='aj_list'");
	$result_list = $db->num_rows($query_list);

	if (!$result_list)
	{
		$account_jumper_7 = array(
			"name" => "aj_list",
			"title" => $db->escape_string($lang->aj_list_title),
			"description" => $db->escape_string($lang->aj_list_descr),
			"optionscode" => "yesno",
			"value" => 1,
			"disporder" => 7,
			"gid" => (int)$gid
			);
		$db->insert_query("settings", $account_jumper_7);

		$account_jumper_8 = array(
			"name" => "aj_postuser",
			"title" => $db->escape_string($lang->aj_postuser_title),
			"description" => $db->escape_string($lang->aj_postuser_descr),
			"optionscode" => "yesno",
			"value" => 1,
			"disporder" => 8,
			"gid" => (int)$gid
			);
		$db->insert_query("settings", $account_jumper_8);
	}

	$query_share = $db->simple_select("settings", "*", "name='aj_shareuser'");
	$result_share = $db->num_rows($query_share);

	if (!$result_share)
	{
		$account_jumper_9 = array(
			"name" => "aj_shareuser",
			"title" => $db->escape_string($lang->aj_shareuser_title),
			"description" => $db->escape_string($lang->aj_shareuser_descr),
			"optionscode" => "yesno",
			"value" => 1,
			"disporder" => 9,
			"gid" => (int)$gid
			);
		$db->insert_query("settings", $account_jumper_9);
	}

	$query_sort = $db->simple_select("settings", "*", "name='aj_sortuser'");
	$result_sort = $db->num_rows($query_sort);

	if (!$result_sort)
	{
		$account_jumper_11 = array(
			"name" => "aj_sortuser",
			"title" => $db->escape_string($lang->aj_sortuser_title),
			"description" => $db->escape_string($lang->aj_sortuser_descr),
			"optionscode" => "select\nuid=User-ID\nuname=Username",
			"value" => "uid",
			"disporder" => 11,
			"gid" => (int)$gid
			);
		$db->insert_query("settings", $account_jumper_11);
	}

	$query_dropdown = $db->simple_select("settings", "*", "name='aj_headerdropdown'");
	$result_dropdown = $db->num_rows($query_dropdown);

	// Upgrade to v1.6
	if (!$result_dropdown)
	{
		$account_jumper_12 = array(
			"name" => "aj_headerdropdown",
			"title" => $db->escape_string($lang->aj_headerdropdown_title),
			"description" => $db->escape_string($lang->aj_headerdropdown_descr),
			"optionscode" => "yesno",
			"value" => 0,
			"disporder" => 12,
			"gid" => (int)$gid
			);
		$db->insert_query("settings", $account_jumper_12);
	}

	$query_admin_changeauthor = $db->simple_select("settings", "*", "name='aj_admin_changeauthor'");
	$result_admin_changeauthor = $db->num_rows($query_admin_changeauthor);

	// Upgrade to v1.7
	if (!$result_admin_changeauthor)
	{
		$account_jumper_13 = array(
			"name" => "aj_admin_changeauthor",
			"title" => $db->escape_string($lang->aj_admin_changeauthor_title),
			"description" => $db->escape_string($lang->aj_admin_changeauthor_descr),
			"optionscode" => "yesno",
			"value" => 1,
			"disporder" => 13,
			"gid" => (int)$gid
			);
		$db->insert_query("settings", $account_jumper_13);

		$account_jumper_14 = array(
			"name" => "aj_admin_changegroup",
			"title" => $db->escape_string($lang->aj_admin_changegroup_title),
			"description" => $db->escape_string($lang->aj_admin_changegroup_descr),
			"optionscode" => "radio
admin=".$db->escape_string($lang->aj_admin_changegroup_admins)."
supermods=".$db->escape_string($lang->aj_admin_changegroup_supermods)."
mods=".$db->escape_string($lang->aj_admin_changegroup_mods)."",
			"value" => "admin",
			"disporder" => 14,
			"gid" => (int)$gid
			);
		$db->insert_query("settings", $account_jumper_14);
		}

	// Upgrade to v2.0
	$query_authorpm = $db->simple_select("settings", "*", "name='aj_authorpm'");
	$result_authorpm = $db->num_rows($query_authorpm);

	if (!$result_authorpm)
	{
		$account_jumper_15 = array(
			"name" => "aj_authorpm",
			"title" => $db->escape_string($lang->aj_authorpm_title),
			"description" => $db->escape_string($lang->aj_authorpm_descr),
			"optionscode" => "yesno",
			"value" => 0,
			"disporder" => 15,
			"gid" => (int)$gid
			);
		$db->insert_query("settings", $account_jumper_15);
	}

	$query_memberlist = $db->simple_select("settings", "*", "name='aj_memberlist'");
	$result_memberlist = $db->num_rows($query_memberlist);

	if (!$result_memberlist)
	{
		$account_jumper_16 = array(
			"name" => "aj_memberlist",
			"title" => $db->escape_string($lang->aj_memberlist_title),
			"description" => $db->escape_string($lang->aj_memberlist_descr),
			"optionscode" => "yesno",
			"value" => 1,
			"disporder" => 16,
			"gid" => (int)$gid
			);
		$db->insert_query("settings", $account_jumper_16);
	}

	$query_sidebar = $db->simple_select("settings", "*", "name='aj_sidebar'");
	$result_sidebar = $db->num_rows($query_sidebar);

	if (!$result_sidebar)
	{
		$account_jumper_17 = array(
			"name" => "aj_sidebar",
			"title" => $db->escape_string($lang->aj_sidebar_title),
			"description" => $db->escape_string($lang->aj_sidebar_descr),
			"optionscode" => "yesno",
			"value" => 1,
			"disporder" => 17,
			"gid" => (int)$gid
			);
		$db->insert_query("settings", $account_jumper_17);
	}

	$query_sharestyle = $db->simple_select("settings", "*", "name='aj_sharestyle'");
	$result_sharestyle = $db->num_rows($query_sharestyle);

	if (!$result_sharestyle)
	{
		$account_jumper_10 = array(
			"name" => "aj_sharestyle",
			"title" => $db->escape_string($lang->aj_sharestyle_title),
			"description" => $db->escape_string($lang->aj_sharestyle_descr),
			"optionscode" => "yesno",
			"value" => 1,
			"disporder" => 10,
			"gid" => (int)$gid
			);
		$db->insert_query("settings", $account_jumper_10);

		$account_jumper_18 = array(
			"name" => "aj_secstyle",
			"title" => $db->escape_string($lang->aj_secstyle_title),
			"description" => $db->escape_string($lang->aj_secstyle_descr),
			"optionscode" => "yesno",
			"value" => 1,
			"disporder" => 18,
			"gid" => (int)$gid
			);
		$db->insert_query("settings", $account_jumper_18);
	}

	$query_profilefield = $db->simple_select("settings", "*", "name='aj_profilefield'");
	$result_profilefield = $db->num_rows($query_profilefield);

	if (!$result_profilefield)
	{
		$account_jumper_19 = array(
			"name" => "aj_profilefield",
			"title" => $db->escape_string($lang->aj_profilefield_title),
			"description" => $db->escape_string($lang->aj_profilefield_descr),
			"optionscode" => "yesno",
			"value" => 0,
			"disporder" => 19,
			"gid" => (int)$gid
			);
		$db->insert_query("settings", $account_jumper_19);

		$account_jumper_20 = array(
			"name" => "aj_profilefield_id",
			"title" => $db->escape_string($lang->aj_profilefield_id_title),
			"description" => $db->escape_string($lang->aj_profilefield_id_descr),
			"optionscode" => "numeric",
			"value" => "0",
			"disporder" => 20,
			"gid" => (int)$gid
			);
		$db->insert_query("settings", $account_jumper_20);
	}

	$query_postcount = $db->simple_select("settings", "*", "name='aj_postcount'");
	$result_postcount = $db->num_rows($query_postcount);

	if (!$result_postcount)
	{
		$account_jumper_21 = array(
			"name" => "aj_postcount",
			"title" => $db->escape_string($lang->aj_postcount_title),
			"description" => $db->escape_string($lang->aj_postcount_descr),
			"optionscode" => "yesno",
			"value" => 1,
			"disporder" => 21,
			"gid" => (int)$gid
			);
		$db->insert_query("settings", $account_jumper_21);
	}

	$query_myalerts = $db->simple_select("settings", "*", "name='aj_myalerts'");
	$result_myalerts = $db->num_rows($query_myalerts);

	if (!$result_myalerts)
	{
		$account_jumper_22 = array(
			"name" => "aj_myalerts",
			"title" => $db->escape_string($lang->aj_myalerts_title),
			"description" => $db->escape_string($lang->aj_myalerts_descr),
			"optionscode" => "yesno",
			"value" => $alertsetting,
			"disporder" => 22,
			"gid" => (int)$gid
			);
		$db->insert_query("settings", $account_jumper_22);
	}

	$query_privacy = $db->simple_select("settings", "*", "name='aj_privacy'");
	$result_privacy = $db->num_rows($query_privacy);

	if (!$result_privacy)
	{
		$account_jumper_23 = array(
			"name" => "aj_privacy",
			"title" => $db->escape_string($lang->aj_privacy_title),
			"description" => $db->escape_string($lang->aj_privacy_descr),
			"optionscode" => "yesno",
			"value" => 1,
			"disporder" => 23,
			"gid" => (int)$gid
			);
		$db->insert_query("settings", $account_jumper_23);
	}

	// Refresh settings.php
	rebuild_settings();

	// If we are upgrading...add the new templates
	$query_tpl = $db->simple_select('templategroups','*','prefix="accountswitcher"');
	$result_template = $db->num_rows($query_tpl);

	if (!$result_template)
	{
		accountswitcher_templates_add();
	}

	// If we are upgrading... add the new table columns
	if (!$db->field_exists("as_share", "users"))
	{
		$db->add_column('users', 'as_share', 'INT(1) NOT NULL DEFAULT "0"');
	}
	if (!$db->field_exists("as_shareuid", "users"))
	{
		$db->add_column('users', 'as_shareuid', 'INT(11) NOT NULL DEFAULT "0"');
	}
	// Add new columns for 2.0
	if (!$db->field_exists("as_sec", "users"))
	{
		$db->add_column('users', 'as_sec', 'INT(1) NOT NULL DEFAULT "0"');
	}
	if (!$db->field_exists("as_secreason", "users"))
	{
		$db->add_column('users', 'as_secreason', 'TEXT NOT NULL');
	}
	if (!$db->field_exists("as_privacy", "users"))
	{
		$db->add_column('users', 'as_privacy', 'INT(1) NOT NULL DEFAULT "0"');
	}
	if (!$db->field_exists("as_buddyshare", "users"))
	{
		$db->add_column('users', 'as_buddyshare', 'INT(1) NOT NULL DEFAULT "0"');
	}

	// Update settings language phrases
	accountswitcher_settings_lang();

	// Build accounts and userfield cache
	require_once MYBB_ROOT."/inc/plugins/accountswitcher/class_accountswitcher.php";
	$eas = new AccountSwitcher($mybb, $db, $cache, $templates);
	$eas->update_accountswitcher_cache();
	$eas->update_userfields_cache();
}

/**
 * Deactivates the plugin.
 *
 */
function accountswitcher_deactivate()
{
	global $db, $cache;

	// Undo template edits
	accountswitcher_revert_template_edits();

	// Delete master templates for upgrade
	$db->delete_query("templategroups", "prefix = 'accountswitcher'");
	$db->delete_query("templates", "title LIKE 'accountswitcher_%' AND sid='-2'");

	// Delete deprecated templates
	$db->delete_query("templates", "`title` = 'as_usercp_nav'");
	$db->delete_query("templates", "`title` = 'as_usercp'");
	$db->delete_query("templates", "`title` = 'as_usercp_users'");
	$db->delete_query("templates", "`title` = 'as_usercp_userbit'");
	$db->delete_query("templates", "`title` = 'as_usercp_options'");
	$db->delete_query("templates", "`title` = 'as_header'");
	$db->delete_query("templates", "`title` = 'as_header_dropdown'");
	$db->delete_query("templates", "`title` = 'global_pm_switch_alert'");
	$db->delete_query("templates", "`title` = 'as_acclist_link'");

	// Clear cache
	$cache->update('accountswitcher',false);
	$cache->update('accountswitcher_fields',false);
}

/**
 * Uninstalls the plugin.
 *
 */
function accountswitcher_uninstall()
{
	global $db, $cache;

	// Delete the templates and templategroup
	$db->delete_query("templategroups", "prefix = 'accountswitcher'");
	$db->delete_query("templates", "title LIKE 'accountswitcher_%'");

	// Delete table columns
	if ($db->field_exists("as_uid", "users"))
	{
		$db->drop_column("users", "as_uid");
	}
	if ($db->field_exists("as_share", "users"))
	{
		$db->drop_column("users", "as_share");
	}
	if ($db->field_exists("as_shareuid", "users"))
	{
		$db->drop_column("users", "as_shareuid");
	}
	if ($db->field_exists("as_sec", "users"))
	{
		$db->drop_column("users", "as_sec");
	}
	if ($db->field_exists("as_secreason", "users"))
	{
		$db->drop_column("users", "as_secreason");
	}
	if ($db->field_exists("as_privacy", "users"))
	{
		$db->drop_column("users", "as_privacy");
	}
	if ($db->field_exists("as_buddyshare", "users"))
	{
		$db->drop_column("users", "as_buddyshare");
	}
	if ($db->field_exists("as_canswitch", "usergroups"))
	{
		$db->drop_column("usergroups", "as_canswitch");
	}
	if ($db->field_exists("as_limit", "usergroups"))
	{
		$db->drop_column("usergroups", "as_limit");
	}

	// Delete settings
	$query_setgr = $db->simple_select('settinggroups','gid','name="Enhanced Account Switcher"');
	$ams = $db->fetch_array($query_setgr);
	$db->delete_query('settinggroups',"gid='".$ams['gid']."'");
	$db->delete_query('settings',"gid='".$ams['gid']."'");

	$cache->update_usergroups();

	// Delete cache
	if (is_object($cache->handler))
	{
		$cache->handler->delete('accountswitcher');
		$cache->handler->delete('accountswitcher_fields');
	}
	// Delete database cache
	$db->delete_query("datacache", "title='accountswitcher'");
	$db->delete_query("datacache", "title='accountswitcher_fields'");

	// Unregister Alert types
	if (class_exists('MybbStuff_MyAlerts_AlertTypeManager'))
	{
		$alertTypeManager = MybbStuff_MyAlerts_AlertTypeManager::getInstance();

		if (!$alertTypeManager)
		{
			$alertTypeManager = MybbStuff_MyAlerts_AlertTypeManager::createInstance($db, $cache);
		}

		$alertTypeManager->deleteByCode('accountswitcher_author');
		$alertTypeManager->deleteByCode('accountswitcher_pm');
	}
}


/**
 * Add the templates.
 *
 */
function accountswitcher_templates_add()
{
	global $mybb, $db, $cache, $templates, $lang;

	$lang->load("accountswitcher");

	require_once MYBB_ROOT."/inc/plugins/accountswitcher/class_accountswitcher.php";
	$eas = new AccountSwitcher($mybb, $db, $cache, $templates);

	// Add templategroup
		$templategrouparray = array(
		'prefix' => 'accountswitcher',
		'title'  => $db->escape_string($lang->group_accountswitcher),
		'isdefault' => 1
	);
	$db->insert_query("templategroups", $templategrouparray);

	// Add the new templates

	// Accountlist templates
	$as_template[0] = array(
		"title" 	=> "accountswitcher_accountlist",
		"template"	=> $db->escape_string('<html>
				<head>
					<title>{$settings[\'bbname\']} - {$lang->aj_accountlist}</title>
					{$headerinclude}
				</head>
				<body>
					{$header}
					<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
						<tr>
							<td class="thead" {$colspan_head}><strong>{$lang->aj_accountlist}</strong></td>
						</tr>
						<tr>
							<td class="trow1" style="padding-left:30px;">
								<strong>{$lang->aj_masteraccount}</strong>
							</td>
							{$profile_head}
							<td class="trow1" style="padding-left:30px;">
								<strong>{$lang->aj_profile}</strong>
							</td>
							{$profile_head}
						</tr>
						{$accountlist_masterbit}
					</table>
					{$multipage}
					{$footer}
				</body>
			</html>'),
		"sid"		=> -2,
		"version"	=> $eas->version,
		"dateline"	=> TIME_NOW
	);

	$as_template[1] = array(
		"title" 	=> "accountswitcher_accountlist_master",
		"template"	=> $db->escape_string('<tr><td class="trow1" {$master_width} style="padding-left: 30px;">{$masterlink}</td>{$profile_field}<td class="trow1" {$colspan}><table width="100%">'),
		"sid"		=> -2,
		"version"	=> $eas->version,
		"dateline"	=> TIME_NOW
	);

	$as_template[2] = array(
		"title" 	=> "accountswitcher_accountlist_attached",
		"template"	=> $db->escape_string('<tr><td class="trow1" style="padding-left: 20px;">{$attachedlink}</td>{$profilefield_attached}{$tb_row}'),
		"sid"		=> -2,
		"version"	=> $eas->version,
		"dateline"	=> TIME_NOW
	);

	$as_template[3] = array(
		"title" 	=> "accountswitcher_accountlist_endbit",
		"template"	=> $db->escape_string('{$as_accountlist_hidden}
						</table>
					</td>
				</tr>
				'),
		"sid" 		=> -2,
		"version"	=> $eas->version,
		"dateline"	=> TIME_NOW
	);

	$as_template[4] = array(
		"title" 	=> "accountswitcher_accountlist_shared",
		"template"	=> $db->escape_string('<tr><td class="trow1" style="padding-left: 30px;">{$lang->as_isshared}</td>{$profile_field}<td class="trow1" {$colspan}><table width="100%">'),
		"sid"		=> -2,
		"version"	=> $eas->version,
		"dateline"	=> TIME_NOW
	);

	// UserCP templates
	$as_template[5] = array(
		"title" 	=> "accountswitcher_usercp_nav",
		"template"	=> $db->escape_string('<tbody><tr><td class="trow1 smalltext"><a href="usercp.php?action=as_edit" class="usercp_nav_item usercp_nav_usergroups">{$lang->as_name}</a></td></tr></tbody>'),
		"sid"		=> -2,
		"version"	=> $eas->version,
		"dateline"	=> TIME_NOW
	);

	$as_template[6] = array(
		"title" 	=> "accountswitcher_usercp_options",
		"template"	=> $db->escape_string('<form method="post" action="usercp.php" id="accountswitcher">
						<input type="hidden" name="my_post_key" value="{$mybb->post_code}" />
						<input type="hidden" name="manage" value="accountswitcher" />
							{$as_usercp_input}
						</table>
						</form>
						<script type="text/javascript">
						<!--
							if(use_xmlhttprequest == "1")
							{
								MyBB.select2();
								$("#accountswitcher_username").select2({
									placeholder: "{$lang->search_user}",
									minimumInputLength: 3,
									maximumSelectionSize: 3,
									multiple: false,
									ajax: { // instead of writing the function to execute the request we use Select2\'s convenient helper
										url: "xmlhttp.php?action=get_users",
										dataType: "json",
										data: function (term, page) {
											return {
												query: term, // search term
											};
										},
										results: function (data, page) { // parse the results into the format expected by Select2.
											// since we are using custom formatting functions we do not need to alter remote JSON data
											return {results: data};
										}
									},
									initSelection: function(element, callback) {
										var query = $(element).val();
										if (query !== "") {
											$.ajax("xmlhttp.php?action=get_users", {
												data: {
													query: query
												},
												dataType: "json"
											}).done(function(data) { callback(data); });
										}
									},
							       // Allow the user entered text to be selected as well
							       createSearchChoice:function(term, data) {
										if ( $(data).filter( function() {
											return this.text.localeCompare(term)===0;
										}).length===0) {
											return {id:term, text:term};
										}
									},
								});
							}
							// -->
						</script>
						{$as_sec_account}
						{$as_usercp_buddyshare}'),
		"sid"		=> -2,
		"version"	=> $eas->version,
		"dateline"	=> TIME_NOW
	);

	$as_template[7] = array(
		"title" 	=> "accountswitcher_usercp_attached_userbit",
		"template"	=> $db->escape_string('<tr><td>
							<form method="post" action="usercp.php">
							<input type="hidden" name="my_post_key" value="{$mybb->post_code}" />
							<input type="hidden" name="action" value="as_detachuser" />
							<input type="hidden" name="uid" value="{$attachedOneUID}" />
							<table width="100%" border="0" style="border:1px solid #000;margin:2px 0;padding:3px;" class="trow1">
								<tr>
									<td>{$attachedOneName}</td>
									<td align="right">
										<input type="submit" value="{$lang->as_detachuser}" name="{$lang->as_detachuser}" class="button" />
									</td>
								</tr>
							</table>
							</form>
						</td></tr>'),
		"sid" 		=> -2,
		"version"	=> $eas->version,
		"dateline"	=> TIME_NOW
	);

	$as_template[8] = array(
		"title" 	=> "accountswitcher_usercp_attached_users",
		"template"	=> $db->escape_string('<td class="trow1" valign="top">
							<fieldset class="trow2">
								<legend><strong>{$lang->as_usercp_users}</strong></legend>
								<table cellspacing="0" cellpadding="{$theme[\'tablespace\']}">
									<tr><td>{$lang->as_usercp_attached}</td></tr>
									{$as_usercp_userbit}
								</table>
							</fieldset>
						</td>'),
		"sid" 		=> -2,
		"version"	=> $eas->version,
		"dateline"	=> TIME_NOW
	);

	$as_template[9] = array(
		"title" 	=> "accountswitcher_usercp",
		"template"	=> $db->escape_string('<html>
						<head>
							<title>{$mybb->settings[\'bbname\']} - {$lang->as_name}</title>
							{$headerinclude}
							<link rel="stylesheet" href="{$mybb->asset_url}/jscripts/select2/select2.css" />
							<script type="text/javascript" src="{$mybb->asset_url}/jscripts/select2/select2.min.js?ver='.$mybb->version_code.'"></script>
						</head>
						<body>
						{$header}
						<table width="100%" border="0" align="center">
							<tr>
							{$usercpnav}
								<td valign="top">
									<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
										<tr><td class="thead" colspan="2"><strong>{$lang->as_name}</strong></td></tr>
										<tr>
											<td class="trow1" valign="top" {$colspan}>
												<fieldset class="trow2">
													<legend><strong>{$lang->as_usercp_options}</strong></legend>
													{$as_usercp_options}
												</fieldset>
											</td>
											{$as_usercp_users}
										</tr>
										<tr>
											<td class="trow2" valign="top" {$colspan}>
												{$as_usercp_privacy}
                                            </td>
                                      </tr>
									</table>
								</td>
							</tr>
						</table>
						{$footer}
						</body>
						</html>'),
		"sid" 		=> -2,
		"version"	=> $eas->version,
		"dateline"	=> TIME_NOW
	);

	$as_template[10] = array(
		"title" 	=> "accountswitcher_usercp_unshare",
		"template"	=> $db->escape_string('<input type="hidden" name="action" value="as_unshare" />
						<table width="100%" border="0">
							<tr><td>{$lang->as_isshared}</td></tr>
							<tr><td>
								<input type="submit" value="{$lang->as_unshare}" name="{$lang->as_unshare}" class="button" />
							</td></tr>'),
		"sid" 		=> -2,
		"version"	=> $eas->version,
		"dateline"	=> TIME_NOW
	);

	$as_template[11] = array(
		"title" 	=> "accountswitcher_usercp_attached_detach",
		"template"	=> $db->escape_string('<input type="hidden" name="action" value="as_detach" />
						<table width="100%" border="0">
							<tr><td>{$lang->as_isattached}</td></tr>
							<tr><td>
								<input type="submit" value="{$lang->as_detach}" name="{$lang->as_detach}" class="button" />
							</td></tr>'),
		"sid" 		=> -2,
		"version"	=> $eas->version,
		"dateline"	=> TIME_NOW
	);

	$as_template[12] = array(
		"title" 	=> "accountswitcher_usercp_free_attach",
		"template"	=> $db->escape_string('<input type="hidden" name="action" value="as_attach" />
									<table width="100%" border="0">
										{$shareuser}
										<tr><td><input type="radio" name="select" value="attachuser" checked="checked" />&nbsp;{$lang->as_attachuser}<br /></td></tr>
										<tr><td><input type="radio" name="select" value="attachme" />&nbsp;{$lang->as_attachme}</td></tr>
										<tr><td><span id="as_username" class="smalltext">{$lang->as_username}</span></td></tr>
										<tr><td><input type="textbox" name="username" id="accountswitcher_username" style="width: 260px;" class="textbox" /></td></tr>
										<tr><td><span id="as_password" class="smalltext">{$lang->as_password}</span></td></tr>
										<tr><td><input type="password" name="password" size="30" /></td></tr>
										<tr><td>
											<input type="submit" value="{$lang->as_attach}" name="{$lang->as_attach}" class="button" />
										</td></tr>'),
		"sid" 		=> -2,
		"version"	=> $eas->version,
		"dateline"	=> TIME_NOW
	);

	$as_template[13] = array(
		"title" 	=> "accountswitcher_usercp_shareuser",
		"template"	=> $db->escape_string('<tr><td><input type="radio" id="shareuser" name="select" value="shareuser" />&nbsp;{$lang->as_shareuser}<br /></td></tr>'),
		"sid" 		=> -2,
		"version"	=> $eas->version,
		"dateline"	=> TIME_NOW
	);

	$as_template[14] = array(
		"title" 	=> "accountswitcher_usercp_master_attach",
		"template"	=> $db->escape_string('<input type="hidden" name="action" value="as_attach" />
									<input type="hidden" name="select" value="attachuser" />
									<table width="100%" border="0">
										<tr><td>{$lang->as_attachuser}</td></tr>
										<tr><td><span class="smalltext">{$lang->as_username}</span></td></tr>
										<tr><td><input type="hidden" name="username" id="accountswitcher_username" style="width: 260px;" class="textbox" /></td></tr>
										<tr><td><span class="smalltext">{$lang->as_password}</span></td></tr>
										<tr><td><input type="password" name="password" size="30" /></td></tr>
										<tr><td>
											<input type="submit" value="{$lang->as_attach}" name="{$lang->as_attach}" class="button" />
										</td></tr>'),
		"sid" 		=> -2,
		"version"	=> $eas->version,
		"dateline"	=> TIME_NOW
	);

	$as_template[15] = array(
		"title" 	=> "accountswitcher_usercp_away",
		"template"	=> $db->escape_string('</td>
				</tr>
				<tr>
					<td valign="top" width="1"><input type="checkbox" class="checkbox" style="{$checkbox}" name="awayall" id="awayall" value="1" {$away_all_check} /></td>
					<td><span class="smalltext"><label for="awayall">{$lang->aj_usercp_profile_away}</label></span>
					'),
		"sid" 		=> -2,
		"version"	=> $eas->version,
		"dateline"	=> TIME_NOW
	);

	// Header templates
	$as_template[16] = array(
		"title" 	=> "accountswitcher_header_userbit",
		"template"	=> $db->escape_string('{$userAvatar}&nbsp;<a id="switch_{$userUid}" href="#switch" class="switchlink" style="margin-right: 5px; background: none; padding-left: 0;"><span class="noscript" title="{$lang->aj_no_script}" onmouseover="style.textDecoration=\'underline\';" onmouseout="style.textDecoration=\'none\';">{$attachedUser}</span></a>'),
		"sid" 		=> -2,
		"version"	=> $eas->version,
		"dateline"	=> TIME_NOW
	);

	$as_template[17] = array(
		"title" 	=> "accountswitcher_header_dropdown_userbit",
		"template"	=> $db->escape_string('<li style="list-style-type: none; white-space: nowrap;">{$userAvatar}&nbsp;<a id="menuswitch_{$userUid}" href="#switch" class="switchlink"><span class="noscript" title="{$lang->aj_no_script}" onmouseover="style.fontSize=\'1.2em\';" onmouseout="style.fontSize=\'1em\';">{$attachedUser}</span></a></li>'),
		"sid" 		=> -2,
		"version"	=> $eas->version,
		"dateline"	=> TIME_NOW
	);

	$as_template[18] = array(
		"title" 	=> "accountswitcher_header",
		"template"	=> $db->escape_string('</li></ul><ul class="menu panel_links" style="border-top: 1px solid; margin-top: 7px; padding-top: 7px; clear: both; min-width: 400px;"><li>{$as_header_userbit}'),
		"sid" 		=> -2,
		"version"	=> $eas->version,
		"dateline"	=> TIME_NOW
	);

	$as_template[19] = array(
		"title" 	=> "accountswitcher_header_dropdown",
		"template"	=> $db->escape_string('&nbsp;<a href="#" id="accountswitcher_header">{$lang->aj_profile}</a>
							<div id="accountswitcher_header_popup" style="display: none; padding:0 30px 0 15px;">
								<ul class="trow1" style="position: absolute; left: 0; padding-left: 10px; padding-right: 20px; min-width: 80px; margin-top:7px; line-height: 200%; border-bottom-right-radius: 10px; border-bottom-left-radius: 15px; white-space: pre-line;">
									{$as_header_dropdown}
								</ul>
							</div>
							<script type="text/javascript">
							//<![CDATA[
								$("#accountswitcher_header").popupMenu();
							//]]>
							</script>'),
		"sid" 		=> -2,
		"version"	=> $eas->version,
		"dateline"	=> TIME_NOW
	);

	$as_template[20] = array(
		"title" 	=> "accountswitcher_header_accountlist",
		"template"	=> $db->escape_string('<li><a href="{$mybb->settings[\'bburl\']}/accountlist.php">{$lang->aj_accountlist}</a></li>'),
		"sid" 		=> -2,
		"version"	=> $eas->version,
		"dateline"	=> TIME_NOW
	);

	$as_template[21] = array(
		"title" 	=> "accountswitcher_newpm_messagebit",
		"template"	=> $db->escape_string('<a id="switch_{$userUid}" href="#switch" class="switchlink">{$attachedUser}</a>'),
		"sid" 		=> -2,
		"version"	=> $eas->version,
		"dateline"	=> TIME_NOW
	);

	$as_template[22] = array(
		"title" 	=> "accountswitcher_newpm_message",
		"template"	=> $db->escape_string('<div class="pm_alert" id="pm_switch_notice">
								<div>{$privatemessage_switch}</div>
								</div>
								<br />'),
		"sid" 		=> -2,
		"version"	=> $eas->version,
		"dateline"	=> TIME_NOW
	);

	// Post switch templates
	$as_template[23] = array(
		"title" 	=> "accountswitcher_post_userbit",
		"template"	=> $db->escape_string('<option value="{$userUid}">{$attachedPostUser}</option>
			'),
		"sid" 		=> -2,
		"version"	=> $eas->version,
		"dateline"	=> TIME_NOW
	);

	$as_template[24] = array(
		"title" 	=> "accountswitcher_post",
		"template"	=> $db->escape_string('<span id="as_reload" title="Reload Page" style="display:none; cursor:pointer;">&#8635;</span>&nbsp;
					<select id="postswitch" title="{$lang->aj_no_script}" style="min-width: 90px;">
					<option value="{$mybbUid}" selected="selected">{$mybbUsername}</option>
					{$as_post_userbit}
					</select>'),
		"sid" 		=> -2,
		"version"	=> $eas->version,
		"dateline"	=> TIME_NOW
	);

	// Footer template
	$as_template[25] = array(
		"title" 	=> "accountswitcher_footer",
		"template"	=> $db->escape_string('<script type="text/javascript">
		//<![CDATA[
			reply_button = "{$as_postreply}";
			as_desc_button = "{$lang->aj_submit_button}";
			account_id = parseInt({$mybbUid});
			account_name = "{$mybbUsername}"
			can_switch = parseInt({$as_canswitch});
			dropdown_reload = {$as_reload};
			user_post_key = "{$mybb->post_code}";
			switch_success_text = "{$lang->aj_switch_success}";
			AS_SCRIPT = "{$as_script}";
		//]]>
		</script>
		<script src="{$mybb->asset_url}/jscripts/accountswitcher/as_script.min.js?v='.$eas->version.'" type="text/javascript"></script>
		'),
		"sid" 		=> -2,
		"version"	=> $eas->version,
		"dateline"	=> TIME_NOW
	);

	// Profile templates
	$as_template[26] = array(
		"title" 	=> "accountswitcher_profile_switch",
		"template"	=> $db->escape_string('<li style="list-style-type: none;"><a id="profile_switch_{$userUid}" href="#switch" class="switchlink">{$userAvatar}&nbsp;{$attachedUser}</a></li>'),
		"sid" 		=> -2,
		"version"	=> $eas->version,
		"dateline"	=> TIME_NOW
	);

	$as_template[27] = array(
		"title" 	=> "accountswitcher_profile_link",
		"template"	=> $db->escape_string('<li style="list-style-type: none;">{$userAvatar}&nbsp;{$attachedUser}</li>'),
		"sid" 		=> -2,
		"version"	=> $eas->version,
		"dateline"	=> TIME_NOW
	);

	$as_template[28] = array(
		"title" 	=> "accountswitcher_profile",
		"template"	=> $db->escape_string('<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
						<tr>
							<td class="thead"><strong>{$lang->aj_profile}</strong></td>
						</tr>
						<tr>
							<td class="trow1">
							<ul>
							{$as_profile_userbit}
							{$as_profile_hidden}
							</ul>
							</td>
						</tr>
					</table>
					<br />'),
		"sid" 		=> -2,
		"version"	=> $eas->version,
		"dateline"	=> TIME_NOW
	);

	// Postbit templates
	$as_template[29] = array(
		"title" 	=> "accountswitcher_postbit_switch",
		"template"	=> $db->escape_string('<li style="list-style-type: none; white-space: nowrap;"><img src="{$mybb_asset_url}/images/attuser.png" alt="" />&nbsp;<a id="pb_{$postId}_switch_{$userUid}" href="#pid{$postId}" class="switchpb" title="Switch">{$attachedUser}</a></li>'),
		"sid" 		=> -2,
		"version"	=> $eas->version,
		"dateline"	=> TIME_NOW
	);

	$as_template[30] = array(
		"title" 	=> "accountswitcher_postbit_link",
		"template"	=> $db->escape_string('<li style="list-style-type: none; white-space: nowrap;"><img src="{$mybb_asset_url}/images/attuser.png" alt="" />&nbsp;{$attachedUser}</li>'),
		"sid" 		=> -2,
		"version"	=> $eas->version,
		"dateline"	=> TIME_NOW
	);

	$as_template[31] = array(
		"title" 	=> "accountswitcher_postbit",
		"template"	=> $db->escape_string('<br />
				<a id="aj_postuser_{$post[\'pid\']}" href="javascript:void(0);">{$numaccounts}{$lang->aj_memberlist}</a>
				<br />
				<div id="aj_postbit_{$post[\'pid\']}" style="display:none; position:absolute; cursor:pointer; z-index:100;">
					<div class="thead" style="font-size:1em;"><strong>{$lang->aj_memberlist}</strong></div>
					<div class="trow1">
						<ul style="padding-left:20px;">
							{$as_postuser_userbit}
							{$as_postuser_hidden}
						</ul>
					</div>
				</div>'),
		"sid" 		=> -2,
		"version"	=> $eas->version,
		"dateline"	=> TIME_NOW
	);

	// Author change templates
	$as_template[32] = array(
		"title" 	=> "accountswitcher_author_button_admin",
		"template"	=> $db->escape_string('<div class="popup_item_container"><a class="popup_item" style="padding-left: 10px;" href="javascript:MyBB.popupWindow(\'/editpost.php?pid={$post[\'pid\']}&amp;adminauthor=1&amp;modal=1\')">{$lang->aj_admin_changeauthor_postbit}</a></div>'),
		"sid" 		=> -2,
		"version"	=> $eas->version,
		"dateline"	=> TIME_NOW
	);

	$as_template[33] = array(
		"title" 	=> "accountswitcher_author_button_attached",
		"template"	=> $db->escape_string('<div class="popup_item_container"><a class="popup_item" style="padding-left: 10px;" href="javascript:MyBB.popupWindow(\'/editpost.php?pid={$post[\'pid\']}&amp;changeauthor=1&amp;modal=1\')">{$lang->aj_changeauthor_postbit}</a></div>'),
		"sid" 		=> -2,
		"version"	=> $eas->version,
		"dateline"	=> TIME_NOW
	);

	$as_template[34] = array(
		"title" 	=> "accountswitcher_author_button",
		"template"	=> $db->escape_string('<span id="changeauthor_{$post[\'pid\']}">
		<img border="0" style="margin-top: 2px;margin-left: 10px;" src="{$theme[\'imgdir\']}/arrow_down.png" alt="" /></span>
		<div id="changeauthor_{$post[\'pid\']}_popup" class="popup_menu" style="display: none; color: #000000; width: 180px;">
			{$author_change}
			{$admin_change}
		</div>
		<script type="text/javascript">
			// <!--
			if(use_xmlhttprequest == 1)
			{
				$("#changeauthor_{$post[\'pid\']}").popupMenu();
			}
			// -->
		</script>
		'),
		"sid" 		=> -2,
		"version"	=> $eas->version,
		"dateline"	=> TIME_NOW
	);

	$as_template[35] = array(
		"title" 	=> "accountswitcher_author_selfbit",
		"template"	=> $db->escape_string('<option value="{$userUid}" selected="selected">{$attachedUser}</option>
					'),
		"sid" 		=> -2,
		"version"	=> $eas->version,
		"dateline"	=> TIME_NOW
	);

	$as_template[36] = array(
		"title" 	=> "accountswitcher_author_userbit",
		"template"	=> $db->escape_string('<option value="{$userUid}">{$attachedUser}</option>
					'),
		"sid" 		=> -2,
		"version"	=> $eas->version,
		"dateline"	=> TIME_NOW
	);

	$as_template[37] = array(
		"title" 	=> "accountswitcher_author_admin",
		"template"	=> $db->escape_string('<div style="overflow-y: auto; max-height: 400px;">
				<link rel="stylesheet" href="{$mybb->asset_url}/jscripts/select2/select2.css" />
				<script type="text/javascript" src="{$mybb->asset_url}/jscripts/select2/select2.min.js?ver='.$mybb->version_code.'"></script>
				<form action="editpost.php?pid={$pid}" method="post">
					<input type="hidden" name="my_post_key" value="{$mybb->post_code}" />
					<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
						<tr><td align="center">
							<table width="200px" border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
								<tr>
									<td class="thead" align="center">
										<strong>{$lang->aj_changeauthor_headline2}</strong>
									</td>
								</tr>
							</table>
							<table width="200px" height="300px" border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
								<tr>
									<td class="trow1" style="vertical-align:top;" align="center">
										<div class="float_left" style="width: 120px; text-align: right;"><strong>{$lang->as_username}</strong></div>
										<div style="margin-left: 30px;"><input type="hidden" name="authorswitch" id="authorswitch" style="width: 80%;" class="textbox" /></div>
									</td>
								</tr>
							</table>
							<table width="200px" style="margin-bottom: 30px;" border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
								<tr>
									<td class="trow2" align="center">
										<input type="hidden" name="action" value="do_cancel" />
										<input type="submit" class="button" name="submit" value="{$lang->aj_changeauthor_cancel}" onclick="{$cancel}" />
									</td>
									<td class="trow2" align="center">
										<input type="hidden" name="action" value="do_author" />
										<input type="hidden" name="p_link" value="{$postlink}" />
										<input type="submit" class="button" name="submit" value="{$lang->aj_changeauthor_submit}" />
									</td>
								</tr>
							</table>
						</td></tr>
					</table>
				</form>
				<script type="text/javascript">
				<!--
					lang.select2_match= "{$lang->select2_match}";
					lang.select2_matches= "{$lang->select2_matches}";
					lang.select2_nomatches= "{$lang->select2_nomatches}";
					lang.select2_inputtooshort_single = "{$lang->select2_inputtooshort_single}";
					lang.select2_inputtooshort_plural = "{$lang->select2_inputtooshort_plural}";
					lang.select2_inputtoolong_single = "{$lang->select2_inputtoolong_single}";
					lang.select2_inputtoolong_plural = "{$lang->select2_inputtoolong_plural}";
					lang.select2_selectiontoobig_single = "{$lang->select2_selectiontoobig_single}";
					lang.select2_selectiontoobig_plural = "{$lang->select2_selectiontoobig_plural}";
					lang.select2_loadmore = "{$lang->select2_loadmore}";
					lang.select2_searching = "{$lang->select2_searching}";

					MyBB.select2();
					$("#authorswitch").select2({
						placeholder: "{$lang->aj_changeauthor_headline2}",
						minimumInputLength: 3,
						maximumSelectionSize: 3,
						multiple: false,
						ajax: { // instead of writing the function to execute the request we use Select2\'s convenient helper
							url: "xmlhttp.php?action=get_users",
							dataType: "json",
							data: function (term, page) {
								return {
									query: term, // search term
								};
							},
							results: function (data, page) { // parse the results into the format expected by Select2.
								// since we are using custom formatting functions we do not need to alter remote JSON data
								return {results: data};
							}
						},
						initSelection: function(element, callback) {
							var query = $(element).val();
							if (query !== "") {
								$.ajax("xmlhttp.php?action=get_users", {
									data: {
										query: query
									},
									dataType: "json"
								}).done(function(data) { callback(data); });
							}
						},
				       // Allow the user entered text to be selected as well
				       createSearchChoice:function(term, data) {
							if ( $(data).filter( function() {
								return this.text.localeCompare(term)===0;
							}).length===0) {
								return {id:term, text:term};
							}
						},
					});
				// -->
				</script>
				</div>
				'),
		"sid" 		=> -2,
		"version"	=> $eas->version,
		"dateline"	=> TIME_NOW
	);

	$as_template[38] = array(
		"title" 	=> "accountswitcher_author_change",
		"template"	=> $db->escape_string('<div style="overflow-y: auto; max-height: 400px;">
				<form action="editpost.php?pid={$pid}" method="post">
					<input type="hidden" name="my_post_key" value="{$mybb->post_code}" />
					<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
						<tr><td align="center">
							<table width="200px" border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
								<tr>
									<td class="thead" align="center">
										<strong>{$lang->aj_changeauthor_headline2}</strong>
									</td>
								</tr>
							</table>
							<table width="200px" height="300px" border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
								<tr>
									<td class="trow1" style="vertical-align:top;" align="center">
									<select name="authorswitch" style="min-width: 150px;">
										{$as_author_userbit}
									</select>
									</td>
								</tr>
							</table>
							<table width="200px" style="margin-bottom: 30px;" border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
								<tr>
									<td class="trow2" align="center">
										<input type="hidden" name="action" value="do_cancel" />
										<input type="submit" class="button" name="submit" value="{$lang->aj_changeauthor_cancel}" onclick="{$cancel}" />
									</td>
									<td class="trow2" align="center">
										<input type="hidden" name="action" value="do_author" />
										<input type="hidden" name="p_link" value="{$postlink}" />
										<input type="submit" class="button" name="submit" value="{$lang->aj_changeauthor_submit}" />
									</td>
								</tr>
							</table>
						</td></tr>
					</table>
				</form>
			</div>
			'),
		"sid" 		=> -2,
		"version"	=> $eas->version,
		"dateline"	=> TIME_NOW
	);

	// Postbit templates
	$as_template[39] = array(
		"title" 	=> "accountswitcher_memberlist_switch",
		"template"	=> $db->escape_string('<li style="list-style-type: none; white-space: nowrap;"><img src="{$mybb_asset_url}/images/attuser.png" alt="" />&nbsp;<a id="ml_switch_{$userUid}" href="#" class="switchml" title="Switch">{$attachedUser}</a></li>'),
		"sid" 		=> -2,
		"version"	=> $eas->version,
		"dateline"	=> TIME_NOW
	);

	$as_template[40] = array(
		"title" 	=> "accountswitcher_memberlist_link",
		"template"	=> $db->escape_string('<li style="list-style-type: none; white-space: nowrap;"><img src="{$mybb_asset_url}/images/attuser.png" alt="" />&nbsp;{$attachedUser}</li>'),
		"sid" 		=> -2,
		"version"	=> $eas->version,
		"dateline"	=> TIME_NOW
	);


	$as_template[41] = array(
		"title" 	=> "accountswitcher_memberlist_shared",
		"template"	=> $db->escape_string('<div style="float: right; margin-right: 100px; min-width: 200px;">({$lang->aj_memberlist_shared})</div>'),
		"sid" 		=> -2,
		"version"	=> $eas->version,
		"dateline"	=> TIME_NOW
	);

	$as_template[42] = array(
		"title" 	=> "accountswitcher_memberlist",
		"template"	=> $db->escape_string('<div style="float: right; margin-right: 100px; width: 200px;">
				<a id="aj_user_{$user[\'uid\']}" href="javascript:void(0);">{$numaccounts}{$lang->aj_memberlist}</a>
				<br />
				<div id="aj_memberbit_{$user[\'uid\']}" style="display:none; position:relative; cursor:pointer; z-index:100;">
					<div class="thead" style="font-size:1em;"><strong>{$lang->aj_memberlist}</strong></div>
					<div class="trow1">
						<ul style="padding-left:20px;">
							{$as_user_userbit}
							{$as_user_hidden}
						</ul>
					</div>
				</div>
				</div>'),
		"sid" 		=> -2,
		"version"	=> $eas->version,
		"dateline"	=> TIME_NOW
	);

	// Sidebar templates
	$as_template[43] = array(
		"title" 	=> "accountswitcher_sidebar_userbit",
		"template"	=> $db->escape_string('<li style="list-style-type: none; white-space: nowrap; height: 24px;">{$userAvatar}&nbsp;<a id="sideswitch_{$userUid}" href="#switch" class="switchlink"><span class="noscript" title="{$lang->aj_no_script}" onmouseover="style.fontSize=\'1.3em\';" onmouseout="style.fontSize=\'1em\';">{$attachedUser}</span></a></li>'),
		"sid" 		=> -2,
		"version"	=> $eas->version,
		"dateline"	=> TIME_NOW
	);

	$as_template[44] = array(
		"title" 	=> "accountswitcher_sidebar",
		"template"	=> $db->escape_string('<div class="as_menu trow1">
				<div class="tcat" style="width: 280px;">{$lang->aj_profile}</div>
				<ul>
					{$as_sidebar_userbit}
				</ul>
				</div>
				<div class="as_menu-arrow"></div>
				'),
		"sid" 		=> -2,
		"version"	=> $eas->version,
		"dateline"	=> TIME_NOW
	);

	// Shared accounts style and secondary accounts templates
	$as_template[45] = array(
		"title" 	=> "accountswitcher_usercp_sec_account",
		"template"	=> $db->escape_string('<form method="post" action="usercp.php">
				<input type="hidden" name="my_post_key" value="{$mybb->post_code}" />
				<table border="0" style="margin-top: 50px; padding: 5px;" width="430px;">
					<tr>
						<td valign="top" width="1"><input type="checkbox" class="checkbox" style="{$checkbox}" name="secacc" id="secacc" value="1" {$sec_check} /></td>
						<td class="trow1"><div><span class="smalltext"><label for="secacc">{$lang->as_usercp_secaccount_desc}</label></span></div>
                          <div style="padding-top: 10px;"><span class="smalltext"><label for="secacc_reason">{$lang->as_usercp_secaccount_reason}</label></span>
                            <input type="text" name="secacc_reason" id="secacc_reason" value="{$user_sec_reason}" size="30" />
                            <input type="hidden" name="action" value="do_secaccount" /></div></td>
							<td class="trow1"><input type="submit" value="{$lang->as_usercp_secaccount}" name="{$lang->as_usercp_secaccount}" class="button float_right" /></td>
					</tr>
				</table>
				</form>
				'),
		"sid" 		=> -2,
		"version"	=> $eas->version,
		"dateline"	=> TIME_NOW
	);

	$as_template[46] = array(
		"title" 	=> "accountswitcher_sec_accountsbit",
		"template"	=> $db->escape_string('<span style="font-style: italic; color: #0000ff;" title="{$user_sec_reason}">{$attachedPostUser}</span>'),
		"sid" 		=> -2,
		"version"	=> $eas->version,
		"dateline"	=> TIME_NOW
	);

	$as_template[47] = array(
		"title" 	=> "accountswitcher_shared_accountsbit",
		"template"	=> $db->escape_string('<span style="font-style: italic; color: #ff0000;">{$attachedPostUser}</span>'),
		"sid" 		=> -2,
		"version"	=> $eas->version,
		"dateline"	=> TIME_NOW
	);

	$as_template[48] = array(
		"title" 	=> "accountswitcher_profilefield",
		"template"	=> $db->escape_string('<td class="trow2" width="22%" style="padding-left: 20px;">{$profilefield}</td>'),
		"sid" 		=> -2,
		"version"	=> $eas->version,
		"dateline"	=> TIME_NOW
	);

	$as_template[49] = array(
		"title" 	=> "accountswitcher_profilefield_attached",
		"template"	=> $db->escape_string('<td class="trow2" width="44%" style="padding-left: 20px;">{$profilefield}</td></tr>'),
		"sid" 		=> -2,
		"version"	=> $eas->version,
		"dateline"	=> TIME_NOW
	);

	$as_template[50] = array(
		"title" 	=> "accountswitcher_profilefield_head",
		"template"	=> $db->escape_string('<td class="trow2" width="22%" style="padding-left: 20px;"><strong>{$profile_name}</strong></td>'),
		"sid" 		=> -2,
		"version"	=> $eas->version,
		"dateline"	=> TIME_NOW
	);

	$as_template[51] = array(
		"title" 	=> "accountswitcher_avatar",
		"template"	=> $db->escape_string('<img src="{$userAvatar}" alt="Avatar" {$avadims} title="{$attachedPostUser}" />'),
		"sid" 		=> -2,
		"version"	=> $eas->version,
		"dateline"	=> TIME_NOW
	);

	$as_template[52] = array(
		"title" 	=> "accountswitcher_usercp_privacy",
		"template"	=> $db->escape_string('<fieldset class="trow2">
			<legend><strong>{$lang->as_usercp_privacy}</strong></legend>
			<form method="post" action="usercp.php">
							<input type="hidden" name="my_post_key" value="{$mybb->post_code}" />
							<table border="0" style="margin-top: 10px; padding: 5px;" width="430px;">
								<tr>
									<td valign="top" width="1">
										<input type="checkbox" class="checkbox" style="{$checkbox}" name="as_privacy" id="as_privacy" value="1" {$privacy_check} />
									</td>
									<td class="trow1">
										<span class="smalltext" style="margin-right: 20px; vertical-align:top;"><label for="as_privacy">{$lang->as_usercp_privacy_desc}</label></span>
										<input type="hidden" name="action" value="do_as_privacy" />
										<input type="submit" value="{$lang->as_usercp_secaccount}" name="{$lang->as_usercp_secaccount}" class="button float_right" />
									</td>
								</tr>
							</table>
						</form>
			</fieldset>
			{$as_usercp_privacy_master}
		'),
		"sid" 		=> -2,
		"version"	=> $eas->version,
		"dateline"	=> TIME_NOW
	);

	$as_template[53] = array(
		"title" 	=> "accountswitcher_usercp_privacy_master",
		"template"	=> $db->escape_string('</td>
			<td class="trow1">
			<fieldset class="trow2">
			<legend><strong>{$lang->as_usercp_privacy} - {$lang->aj_memberlist_more}</strong></legend>
			<form method="post" action="usercp.php">
							<input type="hidden" name="my_post_key" value="{$mybb->post_code}" />
							<table border="0" style="margin-top: 20px; padding: 5px;">
								<tr>
									<td class="trow1" width="400px;">
										<span class="smalltext" style="margin-right: 20px;"><label for="do_as_privacy_master">{$lang->as_usercp_priv_hideall}</label></span>
										<input type="hidden" name="action" value="do_as_privacy_master" />
										<input type="submit" value="{$lang->as_usercp_secaccount}" id="do_as_privacy_master" name="{$lang->as_usercp_secaccount}" class="button float_right" />
									</td>
								</tr>
							</table>
						</form>
						<form method="post" action="usercp.php">
							<input type="hidden" name="my_post_key" value="{$mybb->post_code}" />
							<table border="0" style="margin-top: 20px; padding: 5px;">
								<tr>
									<td class="trow1" width="400px;">
										<span class="smalltext" style="margin-right: 20px;"><label for="undo_as_privacy_master">{$lang->as_usercp_priv_showall}</label></span>
                                        <input type="hidden" name="action" value="undo_as_privacy_master" />
										<input type="submit" value="{$lang->as_usercp_secaccount}" id="undo_as_privacy_master" name="{$lang->as_usercp_secaccount}" class="button float_right" />
									</td>
								</tr>
							</table>
						</form>
			</fieldset>
		'),
		"sid" 		=> -2,
		"version"	=> $eas->version,
		"dateline"	=> TIME_NOW
	);

	$as_template[54] = array(
		"title" 	=> "accountswitcher_usercp_buddyshare",
		"template"	=> $db->escape_string('<form method="post" action="usercp.php">
				<input type="hidden" name="my_post_key" value="{$mybb->post_code}" />
				<table border="0" style="margin-top: 50px; padding: 5px;" width="430px;">
					<tr>
						<td valign="top" width="1"><input type="checkbox" class="checkbox" style="{$checkbox}" name="buddyshare" id="buddyshare" value="1" {$buddy_check} /></td>
						<td class="trow1"><span class="smalltext"><label for="buddyshare">{$lang->as_usercp_buddyshare_desc}</label></span>
							<input type="hidden" name="action" value="do_buddyshare" />
							<input type="submit" value="{$lang->as_usercp_secaccount}" name="{$lang->as_usercp_secaccount}" class="button float_right" /></td>
					</tr>
				</table>
				</form>
		'),
		"sid" 		=> -2,
		"version"	=> $eas->version,
		"dateline"	=> TIME_NOW
	);

	foreach ($as_template as $row)
	{
		$db->insert_query("templates", $row);
	}
}

/**
 * MyAlerts 2.0 integration.
 *
 */
function accountswitcher_alerts_integrate()
{
	global $db, $cache, $lang;

	if (!isset($lang->aj_name))
	{
		$lang->load('accountswitcher');
	}
	if (class_exists('MybbStuff_MyAlerts_AlertTypeManager'))
	{
		$alertTypeManager = MybbStuff_MyAlerts_AlertTypeManager::getInstance();

		if (!$alertTypeManager)
		{
			$alertTypeManager = MybbStuff_MyAlerts_AlertTypeManager::createInstance($db, $cache);
		}

		$alertType = new MybbStuff_MyAlerts_Entity_AlertType();
		$alertType->setCode("accountswitcher_author");
		$alertType->setEnabled(true);
		$alertType->setCanBeUserDisabled(true);
		$alertTypeManager->add($alertType);

		$alertType->setCode("accountswitcher_pm");
		$alertType->setEnabled(true);
		$alertType->setCanBeUserDisabled(true);
		$alertTypeManager->add($alertType);
	}
}

/**
 * Checks for MyAlerts plugin.
 *
 */
function accountswitcher_alerts_status()
{
	global $db;

	if ($db->table_exists('alert_types'))
	{
		$query = $db->simple_select('alert_types', "*", "code='accountswitcher_author'");
		if ($db->num_rows($query) == 1)
		{
			return true;
		}
	}
	return false;
}
