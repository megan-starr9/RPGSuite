<?php
// Disallow direct access to this file for security reasons
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

require_once MYBB_ROOT."/inc/plugins/rpg_suite/models/class_RPGSuite.php";

$plugins->run_hooks("admin_rpgsuite_defaultranks_begin");

$page->add_breadcrumb_item('Manage Ranks');
$page->output_header('Setup Default Ranks');

// Generate list of IC Groups for editing
$rpgsuite = new RPGSuite($mybb,$db, $cache);
$ranktable = $rpgsuite->get_default_ranktable();

// Edit Vs Create
$sub_tabs['edit'] = array(
	'title' => "Edit Hierarchy",
	'link' => "index.php?module=rpgsuite-ranks&amp;action=edit",
	'description' => 'Edit Current Ranks'
);
$sub_tabs['create_tier'] = array(
	'title' => "Create New Tier",
	'link' => "index.php?module=rpgsuite-ranks&amp;action=create_tier",
	'description' => 'Create New Tier'
);
$sub_tabs['create_rank'] = array(
	'title' => "Create New Rank",
	'link' => "index.php?module=rpgsuite-ranks&amp;action=create_rank",
	'description' => 'Create New Rank'
);

if($mybb->request_method == "post") {
	if($mybb->input['action'] == 'create_tier') {
		if(!empty($mybb->input['tierlabel'])) {
			$createtier = array(
				'label' => $db->escape_string($mybb->input['tierlabel']),
				'seq' => $db->escape_string($mybb->input['tierseq'])
			);
			$ranktable->update_tier($createtier);
		}
		flash_message("Tier successfully created!", "success");
		admin_redirect("");

	} else if($mybb->input['action'] == 'create_rank') {
		if(!empty($mybb->input['ranklabel'])) {
			$createrank = array(
				'label' => $db->escape_string($mybb->input['ranklabel']),
				'tid' => $db->escape_string($mybb->input['ranktier']),
				'seq' => $db->escape_string($mybb->input['rankseq'])
			);
			$ranktable->update_rank($createrank);
		}
		flash_message("Rank successfully created!", "success");
		admin_redirect("");

	} else {
		$deleteids = '';
		foreach($ranktable->get_tiers() as $tier) {
			if(is_array($mybb->input['deletetiers']) && in_array($tier['id'], $mybb->input['deletetiers'])) {
				$deleteids .= $tier['id'].',';
			} else {
				$modifytier = array(
					'id' => $tier['id'],
					'label' => $db->escape_string($mybb->input['tier'.$tier['id'].'label']),
					'seq' => $db->escape_string($mybb->input['tier'.$tier['id'].'seq'])
				);
				$ranktable->update_tier($modifytier);
			}
		}
		if(!empty($deleteids)) {
			$ranktable->delete_tiers(rtrim($deleteids, ','));
		}
		$deleteids = '';
		foreach($ranktable->get_ranks() as $rank) {
			if(is_array($mybb->input['deleteranks']) && in_array($rank['id'], $mybb->input['deleteranks'])) {
				$deleteids .= $rank['id'].',';
			} else {
				$modifyrank = array(
					'id' => $rank['id'],
					'label' => $db->escape_string($mybb->input['rank'.$rank['id'].'label']),
					'tid' => $db->escape_string($mybb->input['rank'.$rank['id'].'tier']),
					'seq' => $db->escape_string($mybb->input['rank'.$rank['id'].'seq']),
					'visible' => $db->escape_string($mybb->input['rank'.$rank['id'].'visible']),
					'split_dups' => $db->escape_string($mybb->input['rank'.$rank['id'].'split_dups']),
					'dups' => $db->escape_string($mybb->input['rank'.$rank['id'].'dups']),
					'ignoreactivitycheck' => $db->escape_string($mybb->input['rank'.$rank['id'].'ignoreactivitycheck'])
				);
				$ranktable->update_rank($modifyrank);
			}
		}
		if(!empty($deleteids)) {
			$ranktable->delete_ranks(rtrim($deleteids, ','));
		}
		flash_message("Ranks successfully updated!", "success");
		admin_redirect("");
	}
}

if($mybb->input['action'] == 'create_tier') {
	$page->output_nav_tabs($sub_tabs, 'create_tier');

	$tierform = new Form("","post");
	$form_container = new FormContainer("Create New Tier");
	$form_container->output_row('Tier Label', '<input type="text" class="text_input" name="tierlabel">');
	$form_container->output_row('Display Order', '<input type="text" class="text_input" name="tierseq">');
	$form_container->end();
	$tierbuttons[] = $tierform->generate_submit_button("Add Tier");
	$tierform->output_submit_wrapper($tierbuttons);

} else if($mybb->input['action'] == 'create_rank') {
	$page->output_nav_tabs($sub_tabs, 'create_rank');

	$rankform = new Form("", "post");
	$form_container = new FormContainer("Create New Rank");
	$form_container->output_row('Rank Label', '<input type="text" class="text_input" name="ranklabel">');
	$form_container->output_row('Rank Tier', '<select name="ranktier">'.$ranktable->generate_tieroptions().'</select>');
	$form_container->output_row('Display Order', '<input type="text" class="text_input" name="rankseq">');
	$form_container->end();
	$rankbuttons[] = $rankform->generate_submit_button("Add Rank");
	$rankform->output_submit_wrapper($rankbuttons);

} else {
	$page->output_nav_tabs($sub_tabs, 'edit');

	$editform = new Form("","post");
	$table = new Table;

	$table->construct_header('Tier');
	$table->construct_header('Order');
	$table->construct_header('Delete?');
	foreach($ranktable->get_tiers() as $tier) {
		$table->construct_cell('<input type="text" class="text_input" name="tier'.$tier['id'].'label" value="'.$tier['label'].'">');
		$table->construct_cell('<input type="text" class="text_input" name="tier'.$tier['id'].'seq" value="'.$tier['seq'].'">');
		$table->construct_cell('<input type="checkbox" name="deletetiers[]" value="'.$tier['id'].'">');
		$table->construct_row();
	}

	$table->output();

	$table = new Table;

	$table->construct_header('Rank');
	$table->construct_header('Tier');
	$table->construct_header('Order');
	$table->construct_header('Always Visible?');
	$table->construct_header('Split Duplicates?');
	$table->construct_header('Duplicate Amount (if split)');
	$table->construct_header('Activity Check Exempt?');
	$table->construct_header('Delete?');
	foreach($ranktable->get_ranks() as $rank) {
		$table->construct_cell('<input type="text" class="text_input" name="rank'.$rank['id'].'label" value="'.$rank['label'].'">');
		$table->construct_cell('<select name="rank'.$rank['id'].'tier">'.$ranktable->generate_tieroptions($rank['tid']).'</select>');
		$table->construct_cell('<input type="text" class="text_input" name="rank'.$rank['id'].'seq" value="'.$rank['seq'].'">');
		$checked = ($rank['visible']) ? 'checked' : '';
		$table->construct_cell('<input type="checkbox" name="rank'.$rank['id'].'visible" value="1" '.$checked.'>');
		$checked = ($rank['split_dups']) ? 'checked' : '';
		$table->construct_cell('<input type="checkbox" name="rank'.$rank['id'].'split_dups" value="1" '.$checked.'>');
		$table->construct_cell('<input type="text" class="text_input" name="rank'.$rank['id'].'dups" value="'.$rank['dups'].'">');
		$checked = ($rank['ignoreactivitycheck']) ? 'checked' : '';
		$table->construct_cell('<input type="checkbox" name="rank'.$rank['id'].'ignoreactivitycheck" value="1" '.$checked.'>');
		$table->construct_cell('<input type="checkbox" name="deleteranks[]" value="'.$rank['id'].'">');
		$table->construct_row();
	}

	$table->output();

	$editbuttons[] = $editform->generate_submit_button("Submit Modifications");
	$editform->output_submit_wrapper($editbuttons);
}

$page->output_footer();
