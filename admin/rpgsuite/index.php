<?php
// Disallow direct access to this file for security reasons
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

require_once MYBB_ROOT."/inc/plugins/rpg_suite/models/class_RPGSuite.php";
require_once MYBB_ROOT."/inc/plugins/rpg_suite/models/class_Ticker.php";

$plugins->run_hooks("admin_rpgsuite_index_begin");

$page->add_breadcrumb_item('Manage Packs');
$page->output_header('Pack Management');

$table = new Table;
$table->construct_header('Job');
$table->construct_header('Next Run');
$table->construct_cell('<strong>Activity Check</strong>');
if($mybb->settings['rpgsuite_activitycheck']) {
	$activitycheck = new Ticker($db, 1, $mybb->settings['rpgsuite_activitycheck_freq']);
	$table->construct_cell(date("D, jS M Y @ g:ia", $activitycheck->next_run()));
} else {
	$table->construct_cell('<i>Disabled</i>');
}
$table->construct_row();

$table->construct_cell('<strong>Group Rewards/Penalties</strong>');
if($mybb->settings['rpgsuite_grouppoints']) {
	$grouppoints = new Ticker($db, 2, $mybb->settings['rpgsuite_grouppoints_freq']);
	$table->construct_cell(date("D, jS M Y @ g:ia", $grouppoints->next_run()));
} else {
	$table->construct_cell('<i>Disabled</i>');
}
$table->construct_row();

$table->output("Management Jobs");

// Generate list of IC Groups for editing
$rpgsuite = new RPGSuite($mybb,$db, $cache);

$table = new Table;
foreach($rpgsuite->get_icgroups() as $usergroup) {
  $group = $usergroup->get_info();
  $table->construct_cell("<strong>".$group['title']."</strong>");
	$table->construct_cell("<a href='index.php?module=rpgsuite-group&action=settings&gid=".$group['gid']."'>Manage Pack</a>");
	$table->construct_cell("<a target='_blank' href='".$mybb->settings['bburl']."/modcp.php?action=managegroup&gid=".$group['gid']."'>Mod CP</a>");
	$table->construct_row();
}

$table->output("Current Packs");

	echo("<div style='width:100%;text-align:center;'><a href='index.php?module=rpgsuite-create'><h2>Create a New Pack</h2></a></div>");
$page->output_footer();
