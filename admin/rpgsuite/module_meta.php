<?php

// Disallow direct access to this file for security reasons
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

function rpgsuite_meta()
{
	global $page, $lang, $plugins;

	$sub_menu = array();
	$sub_menu['10'] = array("id" => "groups", "title" => "Manage Packs", "link" => "index.php?module=rpgsuite-groups");
	$sub_menu['20'] = array("id" => "ranks", "title" => "Manage Default Ranks", "link" => "index.php?module=rpgsuite-ranks");
	$sub_menu['40'] = array("id" => "groupfields", "title" => "Manage Custom Pack Fields", "link" => "index.php?module=rpgsuite-groupfields");
	$sub_menu['60'] = array("id" => "create", "title" => "Create Pack", "link" => "index.php?module=rpgsuite-create");
	$sub_menu = $plugins->run_hooks("admin_rpgsuite_menu", $sub_menu);

	$page->add_menu_item('Pack Management', "rpgsuite", "index.php?module=rpgsuite", 100, $sub_menu);

	return true;
}

function rpgsuite_action_handler($action)
{
	global $page, $db, $lang, $plugins;

	$page->active_module = "rpgsuite";

	$actions = array(
		'groups' => array('active' => 'groups', 'file' => 'index.php'),
		'group' => array('active' => 'groups', 'file' => 'group.php'),
		'groupfields' => array('active' => 'groupfields', 'file' => 'groupfields.php'),
		'ranks' => array('active' => 'ranks', 'file' => 'defaultranks.php'),
		'create' => array('active' => 'create', 'file' => 'creategroup.php')
	);

	if(!isset($actions[$action]))
	{
		$page->active_action = "groups";
	}
	else
	{
		$page->active_action = $actions[$action]['active'];
	}

	$actions = $plugins->run_hooks("admin_rpgsuite_action_handler", $actions);

	if($page->active_action == "groups")
	{
		// OOC Group List
		$sub_menu = array();
		$groupquery = $db->simple_select('usergroups u','u.gid, u.title','not exists (select 1 from '.TABLE_PREFIX.'icgroups i where i.gid = u.gid)');
		$subindex = 10;

		while($group = $groupquery->fetch_array()) {
			$sub_menu[$subindex] = array("id" => "group_".$group['gid'], "title" => $group['title'], "link" => "index.php?module=user-groups&action=edit&gid=".$group['gid']);
			$subindex += 10;
		}

		$sub_menu = $plugins->run_hooks("admin_home_menu_quick_access", $sub_menu);

		$sidebar = new SidebarItem('OOC Groups');
		$sidebar->add_menu_items($sub_menu, $page->active_action);

		$page->sidebar .= $sidebar->get_markup();
	}

	if(isset($actions[$action]))
	{
		$page->active_action = $actions[$action]['active'];
		return $actions[$action]['file'];
	}
	else
	{
		$page->active_action = "groups";
		return "index.php";
	}
}

function rpgsuite_admin_permissions()
{
	global $lang, $plugins;

	$admin_permissions = array(
		"ic_groups" => "Can Manage IC Groups"
	);

	$admin_permissions = $plugins->run_hooks("admin_user_permissions", $admin_permissions);

	return array("name" => "IC Groups", "permissions" => $admin_permissions, "disporder" => 60);
}
