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


// Add the hooks
$plugins->add_hook("usercp_menu", "accountswitcher_usercpmenu", 40);
$plugins->add_hook("usercp_start", "accountswitcher_usercp");

/**
 * Adds a button to the usercp navigation.
 *
 */
function accountswitcher_usercpmenu()
{
	global $db, $mybb, $lang, $templates, $theme, $usercpmenu;

	// Show the button if the user can use the Enhanced Account Switcher or the user is attached to an account
	if ($mybb->usergroup['as_canswitch'] == 1 || $mybb->user['as_uid'] != 0)
	{
		$lang->load("accountswitcher");

		$usercpmenu .= eval($templates->render('accountswitcher_usercp_nav'));
	}
}

/**
 * Gets the usercp Enhanced Account Switcher page and handles all actions.
 *
 */
function accountswitcher_usercp()
{
	global $db, $mybb, $lang, $templates, $theme, $eas, $headerinclude, $header, $usercpnav, $usercpmenu, $as_usercp, $as_usercp_options, $as_usercp_privacy, $as_usercp_users, $as_usercp_userbit, $as_usercp_input, $footer, $shareuser, $attachedOneName, $attachedOneUID, $as_sec_account, $sec_check, $checkbox, $privacy_check, $as_usercp_privacy_master, $buddy_check, $as_usercp_buddyshare, $colspan, $user_sec_reason;

	if (!isset($lang->as_isshared))
	{
		$lang->load("accountswitcher");
	}

	// Get the master account of the current user
	$master = get_user((int)$mybb->user['as_uid']);

	// Get the number of attached ones
	$count = $eas->get_attached($mybb->user['uid']);

	// Get limit for users group, declare variables
	$limit = (int)$mybb->usergroup['as_limit'];
	$user_sec_reason = htmlspecialchars_uni($mybb->user['as_secreason']);
	$as_usercp_input = $colspan = $shareuser = $as_sec_account = $sec_check = $privacy_check = $as_usercp_privacy = $as_usercp_privacy_master = $buddy_check = $as_usercp_buddyshare = '';

	// Check if user can use the Enhanced Account Switcher or is attached to an account. If yes grant access to the page
	if ($mybb->input['action'] == "as_edit" && ($mybb->usergroup['as_canswitch'] == 1 || $mybb->user['as_uid'] != 0 || $mybb->user['as_share'] != 0))
	{
		add_breadcrumb($lang->nav_usercp, "usercp.php");
		add_breadcrumb($lang->as_name);

		// Mark secondary accounts, exclude master account
		if (isset($mybb->settings['aj_secstyle']) && $mybb->settings['aj_secstyle'] == 1 && $count == 0 && $mybb->user['as_share'] == 0)
		{
			if ($mybb->user['as_sec'] == 1)
			{
				$sec_check = 'checked="checked"';
			}
			$as_sec_account .= eval($templates->render('accountswitcher_usercp_sec_account'));
		}

		// Hide account from list
		if (isset($mybb->settings['aj_privacy']) && $mybb->settings['aj_privacy'] == 1)
		{
			// Master can hide all attached accounts
			if ($mybb->user['as_uid'] == 0 && $count > 0)
			{
				$as_usercp_privacy_master .= eval($templates->render('accountswitcher_usercp_privacy_master'));
			}
			if ($mybb->user['as_privacy'] == 1)
			{
				$privacy_check = 'checked="checked"';
			}
			$as_usercp_privacy .= eval($templates->render('accountswitcher_usercp_privacy'));
		}

		// If the user account is shared
		if ($mybb->user['as_share'] != 0)
		{
			if ($mybb->user['as_buddyshare'] == 1)
			{
				$buddy_check = 'checked="checked"';
			}
			if ($mybb->user['buddylist'] != '')
			{
				$buddylist = explode(",", $mybb->user['buddylist']);
			}
			if (!empty($buddylist))
			{
				$as_usercp_buddyshare .= eval($templates->render('accountswitcher_usercp_buddyshare'));
			}

			// Build the detach button
			if ($mybb->user['as_buddyshare'] != 0)
			{
				$lang->as_isshared = $lang->as_isshared_buddy;
			}
			$as_usercp_input .= eval($templates->render('accountswitcher_usercp_unshare'));
			$as_usercp_options = eval($templates->render('accountswitcher_usercp_options'));
		}
		// If the user is attached to an account he only can detach himself
		elseif ($mybb->user['as_uid'] != 0)
		{
			$colspan = 'colspan="2"';
			$lang->as_isattached = $lang->sprintf($lang->as_isattached, htmlspecialchars_uni($master['username']));

			// Build the detach button
			$as_usercp_input .= eval($templates->render('accountswitcher_usercp_attached_detach'));
			$as_usercp_options = eval($templates->render('accountswitcher_usercp_options'));
		}
		// If user is free
		else
		{
			// If limit is set to 0 = unlimited
			if ($limit != 0)
			{
				$lang->as_usercp_attached = $lang->sprintf($lang->as_usercp_attached, (int)$count, $limit);
			}
			else
			{
				$lang->as_usercp_attached = $lang->sprintf($lang->as_usercp_attached, (int)$count, $lang->as_unlimited);
			}

			// If there are no users attached grant full acccess
			if ($count == 0)
			{
				$colspan = 'colspan="2"';
				if (isset($mybb->settings['aj_shareuser']) && $mybb->settings['aj_shareuser'] == 1)
				{
					$shareuser = eval($templates->render('accountswitcher_usercp_shareuser'));
				}
				$as_usercp_input .= eval($templates->render('accountswitcher_usercp_free_attach'));
				$as_usercp_options = eval($templates->render('accountswitcher_usercp_options'));
			}

			// If there are users attached allow only user attachment
			if ($count != 0)
			{
				$as_usercp_input .= eval($templates->render('accountswitcher_usercp_master_attach'));
				$as_usercp_options = eval($templates->render('accountswitcher_usercp_options'));

				// Get attached ones from the cache
				$accounts = $eas->accountswitcher_cache;
				if (is_array($accounts))
				{
					foreach ($accounts as $key => $account)
					{
						$attachedOneUID = (int)$account['uid'];
						$attachedOneName = htmlspecialchars_uni($account['username']);
						if ($account['as_uid'] == $mybb->user['uid'])
						{
							$as_usercp_userbit .= eval($templates->render('accountswitcher_usercp_attached_userbit'));
						}
					}
					$as_usercp_users = eval($templates->render('accountswitcher_usercp_attached_users'));
				}
			}
		}
		$as_usercp = eval($templates->render('accountswitcher_usercp'));
		output_page($as_usercp);
		exit;
	}

//########## ACTIONS ##########
	// Attach current user to another account
	if ($mybb->input['action'] == "as_attach" && $mybb->input['select'] == "attachme" && $mybb->request_method == "post")
	{
		verify_post_check($mybb->get_input('my_post_key'));

		// Check if current user is already attached
		if ($mybb->user['as_uid'] != 0)
		{
			error($lang->as_alreadyattached);
		}

		// Validate input
		$select = $db->escape_string($mybb->get_input('select'));
		$username = $db->escape_string($mybb->get_input('username'));
		$password = $db->escape_string($mybb->get_input('password'));

		// Get the target
		$targetUser = get_user_by_username($username);
		$target = get_user($targetUser['uid']);

		// User exist? Password correct?
		if (!$target) error($lang->as_invaliduser);
		if (validate_password_from_uid($target['uid'], $password) == false) error($lang->as_invaliduser);

		// Check targets permission and limit
		$permission = user_permissions((int)$target['uid']);
		// Count number of attached accounts
		$count = $eas->get_attached($target['uid']);

		// If other user is shared or already attached return
		if ($target['as_uid'] != 0 || $target['as_share'] != 0)
		{
			error($lang->as_alreadyattached);
		}

		// If target has permission
		if ($permission['as_canswitch'] == 0)
		{
			error($lang->as_usercp_nopermission);
		}
		if ($permission['as_limit'] != 0 && $count == $permission['as_limit'])
		{
			error($lang->as_limitreached);
		}

		// Set uid of the new master
		$as_uid = array("as_uid" => (int)$target['uid']);

		// Update database
		$db->update_query("users", $as_uid, "uid='".(int)$mybb->user['uid']."'");
		$eas->update_accountswitcher_cache();
		redirect("usercp.php?action=as_edit", $lang->aj_attach_success);
	}

	// Detach current user from master
	if ($mybb->input['action'] == "as_detach" && $mybb->request_method == "post")
	{
		verify_post_check($mybb->get_input('my_post_key'));

		// Reset master uid
		$as_uid = array("as_uid" => 0);

		// Update database
		if ($db->update_query("users", $as_uid, "uid='".(int)$mybb->user['uid']."'"))
		{
			$eas->update_accountswitcher_cache();
			// If user can use Enhanced Account Switcher stay here
			if ($mybb->usergroup['as_canswitch'] == 1)
			{
				redirect("usercp.php?action=as_edit", $lang->aj_update_success);
			}

			// Else redirect to usercp
			redirect("usercp.php", $lang->aj_detach_success);
		}
	}

	// Attach an user to the current account
	if ($mybb->input['action'] == "as_attach" && $mybb->input['select'] == "attachuser" && $mybb->request_method == "post" && $mybb->user['as_uid'] == 0)
	{
		verify_post_check($mybb->get_input('my_post_key'));
		// Validate input
		$select = $db->escape_string($mybb->get_input('select'));
		$username = $db->escape_string($mybb->get_input('username'));
		$password = $db->escape_string($mybb->get_input('password'));

		// Get the target
		$targetUser = get_user_by_username($username);
		$target = get_user($targetUser['uid']);

		// User exist? Password correct?
		if (!$target) error($lang->as_invaliduser);
		if (validate_password_from_uid($target['uid'], $password) == false) error($lang->as_invaliduser);

		// Check targets permission and limit
		$permission = user_permissions((int)$target['uid']);
		// Count number of attached accounts
		$count = $eas->get_attached($mybb->user['uid']);
		$counttarget = $eas->get_attached($target['uid']);

		// If other user is shared or already attached return
		if ($target['as_uid'] != 0 || $target['as_share'] != 0 || $counttarget > 0)
		{
			error($lang->as_alreadyattached);
		}

		// If we have permission
		if ($mybb->usergroup['as_canswitch'] == 0)
		{
			error($lang->as_usercp_nopermission);
		}
		if ($mybb->usergroup['as_limit'] != 0 && $count == $mybb->usergroup['as_limit'])
		{
			error($lang->as_limitreached);
		}

		// Set his new masters uid
		$as_uid = array("as_uid" => (int)$mybb->user['uid']);

		// Update database
		$db->update_query("users", $as_uid, "uid='".(int)$target['uid']."'");
		$eas->update_accountswitcher_cache();
		redirect("usercp.php?action=as_edit", $lang->aj_user_attach_success);
	}

	// Detach user from current account
	if ($mybb->input['action'] == "as_detachuser" && $mybb->request_method == "post")
	{
		verify_post_check($mybb->get_input('my_post_key'));
		// Validate input
		if (!is_numeric($mybb->input['uid']))
		{
			die("UID must be numeric!");
		}

		// Reset master uid
		$as_uid = array("as_uid" => 0);

		$db->update_query("users", $as_uid, "uid='".$mybb->get_input('uid', MyBB::INPUT_INT)."'");
		$eas->update_accountswitcher_cache();;
		redirect("usercp.php?action=as_edit", $lang->aj_user_detach_success);
	}

	// Share the current account
	if ($mybb->input['action'] == "as_attach" && $mybb->input['select'] == "shareuser" && $mybb->request_method == "post" && $mybb->user['as_uid'] == 0 && $mybb->settings['aj_shareuser'] == 1)
	{
		verify_post_check($mybb->get_input('my_post_key'));
		// Validate input
		$select = $db->escape_string($mybb->get_input('select'));

		// Update database
		$as_share = array("as_share" => 1);
		$db->update_query("users", $as_share, "uid='".(int)$mybb->user['uid']."'");
		$eas->update_accountswitcher_cache();
		redirect("usercp.php?action=as_edit", $lang->aj_user_share_success);
	}

	// Unshare the current account
	if ($mybb->input['action'] == "as_unshare" && $mybb->request_method == "post")
	{
		verify_post_check($mybb->get_input('my_post_key'));
		$as_unshare = array("as_share" => 0);
		$as_unshareuid = array("as_shareuid" => 0);
		$as_unsharebuddy = array("as_buddyshare" => 0);
		$db->update_query("users", $as_unshare, "uid='".(int)$mybb->user['uid']."'");
		$db->update_query("users", $as_unshareuid, "uid='".(int)$mybb->user['uid']."'");
		$db->update_query("users", $as_unsharebuddy, "uid='".(int)$mybb->user['uid']."'");
		$eas->update_accountswitcher_cache();
		redirect("usercp.php?action=as_edit", $lang->aj_user_unshare_success);
	}

	// Mark/unmark the current account as secondary
	if ($mybb->input['action'] == "do_secaccount" && $mybb->request_method == "post")
	{
		verify_post_check($mybb->get_input('my_post_key'));
		$secacc_reason = $mybb->get_input('secacc_reason');
		// When account is unmarked delete the reason too
		if ($mybb->get_input('secacc', MyBB::INPUT_INT) != 1)
		{
			$secacc_reason = '';
		}
		$as_secacc = array("as_sec" => $mybb->get_input('secacc', MyBB::INPUT_INT),
					"as_secreason"  => $db->escape_string($secacc_reason)
					);
		$db->update_query("users", $as_secacc, "uid='".(int)$mybb->user['uid']."'");
		$eas->update_accountswitcher_cache();
		redirect("usercp.php?action=as_edit", $lang->aj_user_seacc_success);
	}

	// Hide/show the current account on account list
	if ($mybb->input['action'] == "do_as_privacy" && $mybb->request_method == "post")
	{
		verify_post_check($mybb->get_input('my_post_key'));
		$as_privacc = array("as_privacy" => $mybb->get_input('as_privacy', MyBB::INPUT_INT));
		$db->update_query("users", $as_privacc, "uid='".(int)$mybb->user['uid']."'");
		$eas->update_accountswitcher_cache();
		redirect("usercp.php?action=as_edit", $lang->aj_user_seacc_success);
	}

	// Hide the all attached accounts on account list
	if ($mybb->input['action'] == "do_as_privacy_master" && $mybb->request_method == "post")
	{
		verify_post_check($mybb->get_input('my_post_key'));
		$as_privacc_master = array("as_privacy" => 1);
		$db->update_query("users", $as_privacc_master, "uid='".(int)$mybb->user['uid']."'");
		$db->update_query("users", $as_privacc_master, "as_uid='".(int)$mybb->user['uid']."'");
		$eas->update_accountswitcher_cache();
		redirect("usercp.php?action=as_edit", $lang->aj_user_seacc_success);
	}

	// Unhide the all attached accounts on account list
	if ($mybb->input['action'] == "undo_as_privacy_master" && $mybb->request_method == "post")
	{
		verify_post_check($mybb->get_input('my_post_key'));
		$as_privacc_master = array("as_privacy" => 0);
		$db->update_query("users", $as_privacc_master, "uid='".(int)$mybb->user['uid']."'");
		$db->update_query("users", $as_privacc_master, "as_uid='".(int)$mybb->user['uid']."'");
		$eas->update_accountswitcher_cache();
		redirect("usercp.php?action=as_edit", $lang->aj_user_seacc_success);
	}

	// Share with buddies only
	if ($mybb->input['action'] == "do_buddyshare" && $mybb->request_method == "post")
	{
		verify_post_check($mybb->get_input('my_post_key'));

		if ($mybb->user['buddylist'] != '')
		{
			$buddylist = explode(",", $mybb->user['buddylist']);
		}
		if (!empty($buddylist))
		{
			$as_buddy_share = array("as_buddyshare" => $mybb->get_input('buddyshare', MyBB::INPUT_INT));
			$db->update_query("users", $as_buddy_share, "uid='".(int)$mybb->user['uid']."'");
			$eas->update_accountswitcher_cache();
			redirect("usercp.php?action=as_edit", $lang->aj_user_seacc_success);
		}
		else
		{
			error($lang->aj_user_buddy_none);
		}
	}
}

// Hook for the all attached accounts away function
$plugins->add_hook('usercp_do_profile_end', 'accountswitcher_set_away');
/**
 * Sets all attached accounts to away when the master account status is set to away.
 *
 */
function accountswitcher_set_away()
{
	global $db, $mybb, $eas, $returndate, $awaydate;

	if (!isset($mybb->input['awayall'])) return;

	if ($mybb->user['uid'] != 0 && $mybb->settings['aj_away'] == 1)
	{

		// Get the number of users attached to this account
		$count = 0;

		// If there are users attached and the current user can use the Enhanced Account Switcher...
		if ($mybb->usergroup['as_canswitch'] == 1)
		{
			$accounts = $eas->accountswitcher_cache;
			if (is_array($accounts))
			{
				foreach ($accounts as $key => $account)
				{
					$userUid = (int)$account['uid'];
					if ($account['as_uid'] == $mybb->user['uid'])
					{
						++$count;
						if ($count > 0)
						{
							$updated_record = array(
								"away" => $mybb->get_input('away', MyBB::INPUT_INT),
								"awaydate" => $db->escape_string($awaydate),
								"returndate" => $db->escape_string($returndate),
								"awayreason" => $db->escape_string($mybb->get_input('awayreason'))
							);
							$db->update_query("users", $updated_record, "uid='".$userUid."'");
						}
					}
				}
			}
		}
	}
}

// Hook for user cp away status setting function
$plugins->add_hook('pre_output_page', 'accountswitcher_check_away');
/**
 * Shows an option for away status setting in user cp - profile settings of master accounts.
 *
 * @param string The contents of the page.
 * @return string The new contents of the page
 */
function accountswitcher_check_away($page)
{
	global $mybb, $eas, $returndate, $lang, $templates, $checkbox, $away_all_check;

	$away_all_check = '';
	if ($mybb->usergroup['as_canswitch'] == 1 && THIS_SCRIPT == "usercp.php" && $mybb->input['action'] == "profile" && $mybb->user['uid'] != 0 && $mybb->settings['aj_away'] == 1)
	{
		$count = $eas->get_attached($mybb->user['uid']);
		if ($count > 0)
		{
			if (!isset($lang->aj_usercp_profile_away))
			{
				$lang->load('accountswitcher');
			}
			$away_all_check = 'checked="checked"';

			$usercp_option = eval($templates->render('accountswitcher_usercp_away'));
			$find = '<input type="text" class="textbox" size="4" maxlength="4" name="awayyear" value="'.$returndate['2'].'" />';
			$page = str_replace($find, $find.$usercp_option, $page);
		}
	}
	return $page;
}
