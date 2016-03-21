<?php
// Disallow direct access to this file for security reasons
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

require_once MYBB_ROOT."/inc/plugins/rpg_suite/models/class_OtmAwards.php";

// Generate list of IC Groups for editing
$awards = new OtmAwards($mybb,$db, $cache);

$plugins->run_hooks("admin_rpgsuite_otms_begin");

if($mybb->request_method == "post") {
		$deleteids = '';
		if($mybb->input['action'] == 'create') {
			if(!empty($mybb->input['awardname'])) {
				$createaward = array(
					'name' => $db->escape_string($mybb->input['awardname']),
					'type' => $db->escape_string($mybb->input['awardtype']),
          'value' => $db->escape_string($mybb->input['awardvalue'])
				);
				$awards->update_otm($createaward);
				flash_message("Award successfully created!", "success");
				admin_redirect("");
			}
		} else {
			foreach($awards->get_otms() as $award) {
				if(is_array($mybb->input['deleteawards']) && in_array($award['id'], $mybb->input['deleteawards'])) {
					$deleteids .= $award['id'].',';
				} else {
					$modifyaward = array(
						'id' => $award['id'],
						'name' => $db->escape_string($mybb->input['award'.$award['id'].'name']),
						'type' => $db->escape_string($mybb->input['award'.$award['id'].'type']),
            'value' => $db->escape_string($mybb->input['award'.$award['id'].'value'])
					);
					$awards->update_otm($modifyaward);
				}
			}
			if(!empty($deleteids)) {
				$awards->delete_otms(rtrim($deleteids, ','));
			}
			flash_message("Awards successfully updated!", "success");
			admin_redirect("");
		}
}

$page->add_breadcrumb_item('Manage Otm Awards');

// Edit Vs Create
$sub_tabs['edit'] = array(
	'title' => "Edit Awards",
	'link' => "index.php?module=rpgsuite-otms&amp;action=edit",
	'description' => 'Edit Awards.  For Users/Members/Groups, provide names for value.  For threads and posts, provide IDs.'
);
$sub_tabs['create'] = array(
	'title' => "Create Award",
	'link' => "index.php?module=rpgsuite-otms&amp;action=create",
	'description' => 'Create Award'
);

$page->output_header('Manage Awards');

if($mybb->input['action'] == 'create') {
	$page->output_nav_tabs($sub_tabs, 'create');

	$fieldform = new Form("","post");
	$form_container = new FormContainer("Create New Group Field");
	$form_container->output_row('Name', '<input type="text" class="text_input" name="awardname">');
	$form_container->output_row('Type', '<select name="awardtype">'.$awards->otm_type_options().'</select>');
	$form_container->output_row('Value', '<input type="text" class="text_input" name="awardvalue">');
	$form_container->end();
	$fieldbuttons[] = $fieldform->generate_submit_button("Add Award");
	$fieldform->output_submit_wrapper($fieldbuttons);

} else {
	$page->output_nav_tabs($sub_tabs, 'edit');

	$editform = new Form("","post");
	$table = new Table;
	$table->construct_header("Name");
	$table->construct_header("Type");
	$table->construct_header("Value");
	$table->construct_header("Delete?");

	 $otms = $awards->get_otms();
   foreach($otms as $award) {
		$table->construct_cell("<input type='text' class='text_input' name='award".$award['id']."name' value='".$award['name']."'>");
		$table->construct_cell("<select name='award".$award['id']."type'>".$awards->otm_type_options($award['type'])."</select>");
		$table->construct_cell("<input type='text' class='text_input' name='award".$award['id']."value' value='".$award['value']."'>");
		$table->construct_cell("<input type='checkbox' name='deleteawards[]' value='".$award['id']."'>");
		$table->construct_row();
	}

	$table->output("Otm Awards");

	$editbuttons[] = $editform->generate_submit_button("Submit Modifications");
	$editform->output_submit_wrapper($editbuttons);
}


$page->output_footer();
