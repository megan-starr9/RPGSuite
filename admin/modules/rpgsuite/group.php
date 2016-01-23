<?php
// Disallow direct access to this file for security reasons
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

require_once MYBB_ROOT."/inc/plugins/rpg_suite/models/class_UserGroup.php";
require_once MYBB_ROOT."/inc/plugins/rpg_suite/models/class_RPGSuite.php";

$plugins->run_hooks("admin_rpgsuite_group_begin");

$page->add_breadcrumb_item('Manage Packs','index.php?module=rpgsuite-groups');

  // Ensure we have a valid group id
  $usergroup = new UserGroup($mybb,$db,$cache);
  if($usergroup->initialize((int)$mybb->input['gid'])) {
    $group = $usergroup->get_info();
    $page->add_breadcrumb_item('Pack: '.$group['title']);

    // Play with group settings! :)
    $sub_tabs['settings'] = array(
    	'title' => "Settings",
    	'link' => "index.php?module=rpgsuite-group&amp;action=settings&amp;gid=".$group['gid'],
    	'description' => 'Group settings'
    );
    $sub_tabs['members'] = array(
    	'title' => "Manage Pack Members",
    	'link' => "index.php?module=rpgsuite-group&amp;action=members&amp;gid=".$group['gid'],
    	'description' => "The group's current members"
    );
    $sub_tabs['ranks'] = array(
    	'title' => "Custom Ranks",
    	'link' => "index.php?module=rpgsuite-group&amp;action=ranks&amp;gid=".$group['gid'],
    	'description' => "The group's custom ranks (can be set even if disallowed, only by admin)"
    );
    $sub_tabs['disband'] = array(
    	'title' => "Disband Pack",
    	'link' => "index.php?module=rpgsuite-group&amp;action=disband&amp;gid=".$group['gid'],
    	'description' => "Disband this group"
    );
		$sub_tabs['relocate'] = array(
			'title' => "Relocate Pack",
			'link' => "index.php?module=rpgsuite-group&amp;action=relocate&amp;gid=".$group['gid'],
			'description' => "Relocate to another territory."
		);

		// FIRST let's handle any submits....
		if($mybb->request_method == "post") {
			if($mybb->input['action'] == 'relocate') {
				$settings = array(
					'title' => $db->escape_string($mybb->input['title']),
					'region' => $db->escape_string($mybb->input['region']),
					'prefix' => $db->escape_string($mybb->input['prefix'])
				);
				$usergroup->relocate($settings);

				flash_message("Group Relocated", "success");
				admin_redirect("index.php?module=rpgsuite-group&action=settings&gid=".$group['gid']);
			} else if($mybb->input['action'] == 'disband') {
				$usergroup->disband();

				flash_message("Group Disbanded", "success");
				admin_redirect("index.php?module=rpgsuite");
			} else if($mybb->input['action'] == 'ranks') {
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
				if(!empty($mybb->input['tierlabel'])) {
					$createtier = array(
						'label' => $db->escape_string($mybb->input['tierlabel']),
						'seq' => $db->escape_string($mybb->input['tierseq']),
						'gid' => $group['gid']
					);
					$ranktable->update_tier($createtier);
				}
				if(!empty($mybb->input['ranklabel'])) {
					$createrank = array(
						'label' => $db->escape_string($mybb->input['ranklabel']),
						'tid' => $db->escape_string($mybb->input['ranktier']),
						'seq' => $db->escape_string($mybb->input['rankseq'])
					);
					$ranktable->update_rank($createrank);
				}
				flash_message("Ranks successfully updated!", "success");
				admin_redirect("");
			}  else if($mybb->input['action'] == 'members') {
				if($mybb->input['perform'] == 'remove') {
					$usergroup->remove_member((int)$mybb->input['uid']);
					flash_message("User removed from group", "success");
					admin_redirect('index.php?module=rpgsuite-group&action=members&gid='.(int)$mybb->input['gid']);

				} else if($mybb->input['perform'] == 'promote') {
					$usergroup->promote_member((int)$mybb->input['uid']);
					flash_message("User Promoted to Group Leader", "success");
					admin_redirect('index.php?module=rpgsuite-group&action=members&gid='.(int)$mybb->input['gid']);

				} else if($mybb->input['perform'] == 'demote') {
					$usergroup->demote_member((int)$mybb->input['uid']);
					flash_message("User demoted to regular member", "success");
					admin_redirect('index.php?module=rpgsuite-group&action=members&gid='.(int)$mybb->input['gid']);

				}
			} else {
				$hasranks = 0;
				$activitycheck = 0;
				foreach($usergroup->get_settings_admin() as $setting) {
					if(isset($mybb->input[$setting['name']])) {
						if($setting['name'] == 'founded') {
							// Handle datetime
							$usergroupchanges[$setting['name']] = strtotime($mybb->input[$setting['name']]);
						} else if($setting['name'] == 'hasranks') {
							$hasranks = 1;
						} else if($setting['name'] == 'activitycheck') {
							$activitycheck = 1;
						} else {
								$usergroupchanges[$setting['name']] = $db->escape_string($mybb->input[$setting['name']]);
						}
					}
				}
				$usergroupchanges['hasranks'] = $hasranks;
				$usergroupchanges['activitycheck'] = $activitycheck;
				$usergroup->update_group($usergroupchanges);

				flash_message("Settings successfully updated!", "success");
				admin_redirect("");
			}
		}

    $page->output_header($group['title']);

    if($mybb->input['action'] == 'disband') {
      $page->output_nav_tabs($sub_tabs, 'disband');

			$form = new Form("", "post");
			$form_container = new FormContainer();
			$form_container->output_row('', 'Are you sure you wish to disband this group?  The following will occur when you take this action:
						<ul>
							<li>All members will be moved to the default IC group.</li>
							<li>All posts in the IC forum will be moved to its parent forum before it is deleted.</li>
							<li>All PMs will be demoted.</li>
							<li><b>All posts in the MO forum will be deleted along with the forum</b> (so be sure to move what you want to keep beforehand).</li>
							<li>The usergroup will be deleted.</li>
						</ul>');
			$form_container->end();
			$buttons[] = $form->generate_submit_button("Disband Pack");
			$form->output_submit_wrapper($buttons);

		} else if($mybb->input['action'] == 'relocate') {

			$page->output_nav_tabs($sub_tabs, 'relocate');

			$rpgsuite = new RPGSuite($mybb,$db, $cache);
			$form = new Form("", "post");
			$form_container = new FormContainer();
			$form_container->output_row('', 'Just relocating? A relocation will do the following:
						<ul>
							<li>All posts in the IC forum will be moved to its parent forum before it is deleted.</li>
							<li><b>All posts in the MO forum will be deleted along with the forum</b> (so be sure to move what you want to keep beforehand).</li>
							<li>New forums will be created for the group in the chosen location (and containing the chosen prefix).</li>
							<li>Any threads in the prefixed location will be moved into the pack forum.</li>
						</ul>');
			$form_container->output_row('Pack Name', 'Pack\'s new name.  Even if this isn\'t changing, still provide.', '<input type="text" name="title" value="'.$group['title'].'">');
			$form_container->output_row('New Pack Location (Region)', 'Region where the pack\'s new claim lies.', '<select id="region" name="region">'.$rpgsuite->generate_regionoptions().'</select>');
			$form_container->output_row('New Pack Location (Territory)', 'Prefix representing the Pack\'s new claim.', '<span id="prefix">'.$rpgsuite->generate_prefixselect().'</span>');
			$form_container->end();
			$buttons2[] = $form->generate_submit_button("Relocate Pack");
			$form->output_submit_wrapper($buttons2);

    } else if($mybb->input['action'] == 'members') {
      $page->output_nav_tabs($sub_tabs, 'members');

			$table = new Table;
			foreach($usergroup->get_members() as $member) {
				$user = $member->get_info();
				$table->construct_cell('<strong>'.$user['username'].'</strong>');
				$promote = ($member->is_leader()) ? '<form method="post" action="">
																							<input type="hidden" name="perform" value="demote">
																							<input type="hidden" name="gid" value="'.$group['gid'].'">
																							<input type="hidden" name="uid" value="'.$user['uid'].'">
																							<input type="hidden" name="my_post_key" value="'.$mybb->post_code.'">
																							<input type="submit" class="submit_button" value="Demote">
																						</form>'
												: '<form method="post" action="">
																<input type="hidden" name="perform" value="promote">
																<input type="hidden" name="gid" value="'.$group['gid'].'">
																<input type="hidden" name="uid" value="'.$user['uid'].'">
																<input type="hidden" name="my_post_key" value="'.$mybb->post_code.'">
																<input type="submit" class="submit_button" value="Promote">
														</form>';
				$table->construct_cell($promote);
				$table->construct_cell('<form method="post" action="">
																	<input type="hidden" name="perform" value="remove">
																	<input type="hidden" name="gid" value="'.$group['gid'].'">
																	<input type="hidden" name="uid" value="'.$user['uid'].'">
																	<input type="hidden" name="my_post_key" value="'.$mybb->post_code.'">
																	<input type="submit" class="submit_button" value="Remove">
																</form>');
				$table->construct_row();
			}

			$table->output();

    } else if($mybb->input['action'] == 'ranks') {
      $page->output_nav_tabs($sub_tabs, 'ranks');

			$ranktable = $usergroup->get_ranks(1);

			$editform = new Form("","post");
			$table = new Table;

			$table->construct_header('Tier');
			$table->construct_header('Order');
			$table->construct_header('Delete?');
			foreach($ranktable->get_tiers() as $tier) {
				$table->construct_cell('<input type="text" name="tier'.$tier['id'].'label" value="'.$tier['label'].'">');
				$table->construct_cell('<input type="text" name="tier'.$tier['id'].'seq" value="'.$tier['seq'].'">');
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
				$table->construct_cell('<input type="text" name="rank'.$rank['id'].'label" value="'.$rank['label'].'">');
				$table->construct_cell('<select name="rank'.$rank['id'].'tier">'.$ranktable->generate_tieroptions($rank['tid']).'</select>');
				$table->construct_cell('<input type="text" name="rank'.$rank['id'].'seq" value="'.$rank['seq'].'">');
				$checked = ($rank['visible']) ? 'checked' : '';
				$table->construct_cell('<input type="checkbox" name="rank'.$rank['id'].'visible" value="1" '.$checked.'>');
				$checked = ($rank['split_dups']) ? 'checked' : '';
				$table->construct_cell('<input type="checkbox" name="rank'.$rank['id'].'split_dups" value="1" '.$checked.'>');
				$table->construct_cell('<input type="text" name="rank'.$rank['id'].'dups" value="'.$rank['dups'].'">');
				$checked = ($rank['ignoreactivitycheck']) ? 'checked' : '';
				$table->construct_cell('<input type="checkbox" name="rank'.$rank['id'].'ignoreactivitycheck" value="1" '.$checked.'>');
				$table->construct_cell('<input type="checkbox" name="deleteranks[]" value="'.$rank['id'].'">');
				$table->construct_row();
			}

			$table->output();

			$editbuttons[] = $editform->generate_submit_button("Submit Modifications");
			$editform->output_submit_wrapper($editbuttons);
			echo "<br>";

			$tierform = new Form("","post");
			$form_container = new FormContainer("Create New Tier");
			$form_container->output_row('Tier Label', '<input type="text" class="text_input" name="tierlabel">');
			$form_container->output_row('Display Order', '<input type="text" class="text_input" name="tierseq">');
			$form_container->end();
			$tierbuttons[] = $tierform->generate_submit_button("Add Tier");
			$tierform->output_submit_wrapper($tierbuttons);
			echo "<br>";
			$rankform = new Form("", "post");
			$form_container = new FormContainer("Create New Rank");
			$form_container->output_row('Rank Label', '<input type="text" class="text_input" name="ranklabel">');
			$form_container->output_row('Rank Tier', '<select name="ranktier">'.$ranktable->generate_tieroptions().'</select>');
			$form_container->output_row('Display Order', '<input type="text" class="text_input" name="rankseq">');
			$form_container->end();
			$rankbuttons[] = $rankform->generate_submit_button("Add Rank");
			$rankform->output_submit_wrapper($rankbuttons);

    } else {
      $page->output_nav_tabs($sub_tabs, 'settings');
			$form = new Form("", "post");
			$form_container = new FormContainer();
			foreach($usergroup->get_settings_admin() as $setting) {
				$form_container->output_row($setting['label'], $setting['description'], $setting['form']);
			}
			$form_container->end();
			$buttons[] = $form->generate_submit_button("Update Settings");
			$form->output_submit_wrapper($buttons);
    }
}

$page->output_footer();
