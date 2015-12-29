<?php
// Disallow direct access to this file for security reasons
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

require_once MYBB_ROOT."/inc/plugins/rpg_suite/models/class_RPGSuite.php";
$rpgsuite = new RPGSuite($mybb,$db, $cache);

if($mybb->request_method == "post") {
  $settings = array(
    'title' => $db->escape_string($mybb->input['title']),
    'description' => $db->escape_string($mybb->input['description']),
    'region' => (int)$mybb->input['region'],
    'prefix' => (int)$mybb->input['prefix'],
    'namestyle' => $db->escape_string($mybb->input['namestyle']),
    'image' => $db->escape_string($mybb->input['image']),
    'members' => $db->escape_string($mybb->input['members']),
    'pms' => $db->escape_string($mybb->input['managers'])
  );
  $rpgsuite->create_icgroup($settings);

  flash_message('Pack Created!', 'success');
  admin_redirect("index.php?module=rpgsuite");
}

$plugins->run_hooks("admin_rpgsuite_groupfields_begin");
$page->add_breadcrumb_item('Create Pack');
$page->output_header('Pack Creation');

// Generate list of IC Groups for editing
$form = new Form("", "post");
$form_container = new FormContainer();

$form_container->output_row('Pack Name', '', '<input type="text" class="text_input" name="title">');
$form_container->output_row('Pack Description', 'The pack\'s joining rules.', '<textarea name="description" rows="5" cols="45"></textarea>');
$form_container->output_row('Pack Location (Region)', 'Region where the pack\'s claim lies.', '<select id="region" name="region">'.$rpgsuite->generate_regionoptions().'</select>');
$form_container->output_row('Pack Location (Territory)', 'Prefix representing the Pack\'s claim.', '<span id="prefix">'.$rpgsuite->generate_prefixselect().'</span>');
$form_container->output_row('Pack Namestyle', '', '<input type="text" class="text_input" name="namestyle" value="{username}">');
$form_container->output_row('Pack Image', 'Group image', '<input type="text" class="text_input" name="image" value="">');
$form_container->output_row('Pack Members', '', '<textarea name="members" id="members" rows="2" cols="38" tabindex="1" style="width: 450px;"></textarea>');
$form_container->output_row('Pack Managers', '', '<textarea name="managers" id="managers" rows="2" cols="38" tabindex="1" style="width: 450px;"></textarea>');

$form_container->end();
$buttons[] = $form->generate_submit_button("Create Pack");
$form->output_submit_wrapper($buttons);

// Autocompletion for usernames
echo '
<link rel="stylesheet" href="../jscripts/select2/select2.css">
<script type="text/javascript" src="../jscripts/select2/select2.min.js?ver=1804"></script>
<script type="text/javascript">
<!--
$("#members").select2({
	placeholder: "'.$lang->search_for_a_user.'",
	minimumInputLength: 3,
	maximumSelectionSize: 12,
	multiple: true,
	ajax: { // instead of writing the function to execute the request we use Select2\'s convenient helper
		url: "../xmlhttp.php?action=get_users",
		dataType: \'json\',
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
			$.ajax("../xmlhttp.php?action=get_users&getone=1", {
				data: {
					query: query
				},
				dataType: "json"
			}).done(function(data) { callback(data); });
		}
	},
});

$(\'[for=members]\').click(function(){
	$("#members").select2(\'open\');
	return false;
});

$("#managers").select2({
	placeholder: "'.$lang->search_for_a_user.'",
	minimumInputLength: 3,
	maximumSelectionSize: 6,
	multiple: true,
	ajax: { // instead of writing the function to execute the request we use Select2\'s convenient helper
		url: "../xmlhttp.php?action=get_users",
		dataType: \'json\',
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
			$.ajax("../xmlhttp.php?action=get_users&getone=1", {
				data: {
					query: query
				},
				dataType: "json"
			}).done(function(data) { callback(data); });
		}
	},
});

$(\'[for=managers]\').click(function(){
	$("#managers").select2(\'open\');
	return false;
});
// -->
</script>';

$page->output_footer();
