<?php
// Disallow direct access to this file for security reasons
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

require_once MYBB_ROOT."/inc/plugins/rpg_suite/models/class_RPGSuite.php";

// Generate list of IC Groups for editing
$rpgsuite = new RPGSuite($mybb,$db, $cache);
$options = array('text','textarea');

$plugins->run_hooks("admin_rpgsuite_groupfields_begin");

if($mybb->request_method == "post") {
		$deleteids = '';
		if($mybb->input['action'] == 'create') {
			if(!empty($mybb->input['fieldname'])) {
				$createfield = array(
					'name' => $db->escape_string($mybb->input['fieldname']),
					'type' => $db->escape_string($mybb->input['fieldtype']),
					'description' => $db->escape_string($mybb->input['fielddescription']),
					'disporder' => $db->escape_string($mybb->input['fielddisporder']),
					'onlyadmin' => $db->escape_string($mybb->input['fieldonlyadmin'])
				);
				$rpgsuite->update_groupfield($createfield);
				flash_message("Group Field successfully created!", "success");
				admin_redirect("");
			}
		} else {
			foreach($rpgsuite->get_groupfields() as $field) {
				if(is_array($mybb->input['deletefields']) && in_array($field['fid'], $mybb->input['deletefields'])) {
					$deleteids .= $field['fid'].',';
				} else {
					$modifyfield = array(
						'fid' => $field['fid'],
						'name' => $db->escape_string($mybb->input['field'.$field['fid'].'name']),
						'type' => $db->escape_string($mybb->input['field'.$field['fid'].'type']),
						'description' => $db->escape_string($mybb->input['field'.$field['fid'].'description']),
						'disporder' => $db->escape_string($mybb->input['field'.$field['fid'].'disporder']),
						'onlyadmin' => $db->escape_string($mybb->input['field'.$field['fid'].'onlyadmin'])
					);
					$rpgsuite->update_groupfield($modifyfield);
				}
			}
			if(!empty($deleteids)) {
				$rpgsuite->delete_groupfields(rtrim($deleteids, ','));
			}
			flash_message("Group Fields successfully updated!", "success");
			admin_redirect("");
		}
}

$page->add_breadcrumb_item('Manage Group Fields');

// Edit Vs Create
$sub_tabs['edit'] = array(
	'title' => "Edit Group Fields",
	'link' => "index.php?module=rpgsuite-groupfields&amp;action=edit",
	'description' => 'Edit Group Fields'
);
$sub_tabs['create'] = array(
	'title' => "Create Group Field",
	'link' => "index.php?module=rpgsuite-groupfields&amp;action=create",
	'description' => 'Create Group Field'
);

$page->output_header('Manage Group Fields');

if($mybb->input['action'] == 'create') {
	$page->output_nav_tabs($sub_tabs, 'create');

	$optionlist = "";
	foreach($options as $option) {
		$optionlist .= "<option value='".$option."'>".$option."</option>";
	}
	$fieldform = new Form("","post");
	$form_container = new FormContainer("Create New Group Field");
	$form_container->output_row('Name', '<input type="text" class="text_input" name="fieldname">');
	$form_container->output_row('Type', '<select name="fieldtype">'.$optionlist.'</select>');
	$form_container->output_row('Description', '<textarea name="fielddescription" rows="5" cols="45"></textarea>');
	$form_container->output_row('Display Order', '<input type="text" class="text_input" name="fielddisporder">');
	$form_container->output_row('Admin Only?', '<input type="checkbox" name="fieldonlyadmin" value="1">');
	$form_container->end();
	$fieldbuttons[] = $fieldform->generate_submit_button("Add Field");
	$fieldform->output_submit_wrapper($fieldbuttons);

} else {
	$page->output_nav_tabs($sub_tabs, 'edit');

	$editform = new Form("","post");
	$table = new Table;
	$table->construct_header("Selector");
	$table->construct_header("Name");
	$table->construct_header("Type");
	$table->construct_header("Description");
	$table->construct_header("Display Order");
	$table->construct_header("Only Admin?");
	$table->construct_header("Delete?");

	$fields = $rpgsuite->get_groupfields();
	foreach($fields as $field) {
		$optionlist = "";
		foreach($options as $option) {
			$optionlist .= "<option value='".$option."' ";
			$optionlist .= ($field['type'] == $option) ? "selected" : "";
			$optionlist .= ">".$option."</option>";
		}
		$table->construct_cell("<strong>fid".$field['fid']."</strong>");
		$table->construct_cell("<input type='text' class='text_input' name='field".$field['fid']."name' value='".$field['name']."'>");
		$table->construct_cell("<select name='field".$field['fid']."type'>".$optionlist."</select>");
		$table->construct_cell("<textarea name='field".$field['fid']."description' rows='5' cols='45'>".$field['description']."</textarea>");
		$table->construct_cell("<input type='text' class='text_input' name='field".$field['fid']."disporder' value='".$field['disporder']."'>");
		$checked = ($field['onlyadmin']) ? 'checked' : '';
		$table->construct_cell("<input type='checkbox' name='field".$field['fid']."onlyadmin' value='1' ".$checked.">");
		$table->construct_cell("<input type='checkbox' name='deletefields[]' value='".$field['fid']."'>");
		$table->construct_row();
	}

	$table->output("Group Fields");

	$editbuttons[] = $editform->generate_submit_button("Submit Modifications");
	$editform->output_submit_wrapper($editbuttons);
}


$page->output_footer();
