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

// Add the hook for the account switcher in header
$plugins->add_hook("global_intermediate", "accountswitcher_header");
/**
 * Adds a list of users attached to the account in header, sidebar and below the quick reply, new reply and new thread editor.
 * Shows a new PM message for attached accounts in header.
 *
 *
 */
function accountswitcher_header()
{
	global $db, $mybb, $lang, $eas, $templates, $theme, $current_page, $as_header, $user_sec_reason, $as_header_userbit, $as_sidebar, $as_sidebar_userbit, $as_header_dropdown, $as_post, $as_post_userbit, $pm_switch_notice, $privatemessage_bit, $privatemessage_switch, $mybbUsername, $mybbUid, $attachedPostUser, $userUid, $attachedUser, $userAvatar, $avadims, $user_profilefield;

	if ($mybb->user['uid'] != 0)
	{
		if (!isset($lang->aj_no_script))
		{
			$lang->load("accountswitcher");
		}
		// Display a different no script message for admins
		if ($mybb->usergroup['cancp'] == 1)
		{
			$lang->aj_no_script = $lang->aj_no_script_admin;
		}

		// Declare variables
		$count = $eas->get_attached($mybb->user['uid']);
		$as_header = $as_header_userbit = $as_sidebar = $as_sidebar_userbit = $as_header_dropdown = $as_post_userbit = $privatemessage_bit = $privatemessage_switch = $pm_switch_notice = '';
		$avadims = 'width="22" height="22"';

		// If there are users attached and current user can use the Enhanced Account Switcher...
		if ($mybb->usergroup['as_canswitch'] == 1 && $count > 0)
		{
			$accounts = $eas->accountswitcher_cache;
			if (is_array($accounts))
			{
				// Sort accounts by first, secondary, shared accounts and by uid or username
				$accounts = $eas->sort_attached();

				// Get all attached accounts
				foreach ($accounts as $key => $account)
				{
					$attachedPostUser = htmlspecialchars_uni($account['username']);
					$userUid = (int)$account['uid'];
					$userAvatar = $eas->attached_avatar($account['avatar'], $account['avatardimensions']);

					// Get shared accounts buddylist
					if ($account['as_share'] == 1 && $account['as_buddyshare'] == 1)
					{
						if ($account['buddylist'] != '')
						{
							$buddylist = explode(",", $account['buddylist']);
						}
					}

					if ($account['as_uid'] == $mybb->user['uid'])
					{
						if ($count > 0)
						{
							// Set username styles
							if ($mybb->settings['aj_sharestyle'] == 1 && $account['as_share'] != 0)
							{
								$attachedUser = eval($templates->render('accountswitcher_shared_accountsbit'));
							}
							elseif ($mybb->settings['aj_secstyle'] == 1 && $account['as_sec'] != 0 && $account['as_share'] == 0)
							{
								$user_sec_reason = htmlspecialchars_uni($account['as_secreason']);
								$attachedUser = eval($templates->render('accountswitcher_sec_accountsbit'));
							}
							else
							{
								$attachedUser = $attachedPostUser;
							}
							// Load userbits
							if ($mybb->settings['aj_headerdropdown'] != 1)
							{
								$as_header_userbit .= eval($templates->render('accountswitcher_header_userbit'));
							}
							else
							{
								$as_header_dropdown .= eval($templates->render('accountswitcher_header_dropdown_userbit'));
							}
							if ($mybb->settings['aj_sidebar'] == 1)
							{
								$as_sidebar_userbit .= eval($templates->render('accountswitcher_sidebar_userbit'));
							}
							if ($mybb->settings['aj_postjump'] == 1)
							{
								$as_post_userbit .= eval($templates->render('accountswitcher_post_userbit'));
							}
							// Check if this user has a new private message.
							if ($account['unreadpms'] > 0 && $mybb->settings['enablepms'] != 0 && ($current_page != "private.php" || $mybb->input['action'] != "read"))
							{
								$privatemessage_bit = eval($templates->render('accountswitcher_newpm_messagebit'));
								$privatemessage_switch .= $lang->sprintf($lang->aj_newpm_switch_notice_one, $privatemessage_bit);
								$pm_switch_notice = eval($templates->render('accountswitcher_newpm_message'));
							}
						}
					}

					// Show shared accounts for users attached to other accounts
					if ($count > 0 && $mybb->settings['aj_shareuser'] == 1 && (($account['as_share'] != 0 && $account['as_shareuid'] == 0 && $account['as_buddyshare'] == 0) || ($account['as_shareuid'] != 0 && $account['as_shareuid'] == $mybb->user['uid']) || ($account['as_share'] != 0 && $account['as_shareuid'] == 0 && $account['as_buddyshare'] != 0 && !empty($buddylist) && in_array($mybb->user['uid'], $buddylist))))
					{
						// Set username styles
						if ($mybb->settings['aj_sharestyle'] == 1 && $account['as_share'] != 0)
						{
							$attachedUser = eval($templates->render('accountswitcher_shared_accountsbit'));
						}
						elseif ($mybb->settings['aj_secstyle'] == 1 && $account['as_sec'] != 0 && $account['as_share'] == 0)
						{
							$user_sec_reason = htmlspecialchars_uni($account['as_secreason']);
							$attachedUser = eval($templates->render('accountswitcher_sec_accountsbit'));
						}
						else
						{
							$attachedUser = format_name($attachedPostUser, (int)$account['usergroup'], (int)$account['displaygroup']);
						}
						// Load userbits
						if ($mybb->settings['aj_headerdropdown'] != 1)
						{
							$as_header_userbit .= eval($templates->render('accountswitcher_header_userbit'));
						}
						else
						{
							$as_header_dropdown .= eval($templates->render('accountswitcher_header_dropdown_userbit'));
						}
						if ($mybb->settings['aj_sidebar'] == 1)
						{
							$as_sidebar_userbit .= eval($templates->render('accountswitcher_sidebar_userbit'));
						}
						if ($mybb->settings['aj_postjump'] == 1)
						{
							$as_post_userbit .= eval($templates->render('accountswitcher_post_userbit'));
						}
					}
				}
				if (trim($as_header_userbit) != '')
				{
					$as_header = eval($templates->render('accountswitcher_header'));
				}
				// Select box below editor
				if ($mybb->settings['aj_postjump'] == 1 && $count > 0)
				{
					$mybbUid = (int)$mybb->user['uid'];
					$mybbUsername = htmlspecialchars_uni($mybb->user['username']);
					$as_post = eval($templates->render('accountswitcher_post'));
				}
			}
		}
		// If there are no users attached to the current account but the current account is attached to another user
		if ($count == 0 && $mybb->user['as_uid'] != 0)
		{
			// Get the master account
			$master = get_user($mybb->user['as_uid']);
			// Get the masters permission
			$permission = user_permissions($master['uid']);

			// If the master has permission to use the Enhanced Account Switcher, get the userlist
			if ($permission['as_canswitch'] == 1)
			{
				// Create link to the master
				$attachedPostUser = htmlspecialchars_uni($master['username']);
				$userUid = (int)$master['uid'];
				$userAvatar = $eas->attached_avatar($master['avatar'], $master['avatardimensions']);
				$attachedUser = '<span style="font-weight: bold;" title="Master Account">'.format_name($attachedPostUser, (int)$master['usergroup'], (int)$master['displaygroup']).'</span>';

				if ($mybb->settings['aj_headerdropdown'] != 1)
				{
					$as_header_userbit .= eval($templates->render('accountswitcher_header_userbit'));
				}
				else
				{
					$as_header_dropdown .= eval($templates->render('accountswitcher_header_dropdown_userbit'));
				}
				if ($mybb->settings['aj_sidebar'] == 1)
				{
					$as_sidebar_userbit .= eval($templates->render('accountswitcher_sidebar_userbit'));
				}
				if ($mybb->settings['aj_postjump'] == 1)
				{
					$as_post_userbit .= eval($templates->render('accountswitcher_post_userbit'));
				}
				if ($master['unreadpms'] > 0 && $mybb->settings['enablepms'] != 0 && ($current_page != "private.php" || $mybb->input['action'] != "read"))
				{
					$privatemessage_bit = eval($templates->render('accountswitcher_newpm_messagebit'));
					$privatemessage_switch .= $lang->sprintf($lang->aj_newpm_switch_notice_one, $privatemessage_bit);
					$pm_switch_notice = eval($templates->render('accountswitcher_newpm_message'));
				}
				// Get all users attached to master from the cache
				$accounts = $eas->accountswitcher_cache;
				if (is_array($accounts))
				{
					// Sort accounts by first, secondary, shared accounts and by uid or username
					$accounts = $eas->sort_attached();

					// Get all attached accounts
					foreach ($accounts as $key => $account)
					{
						$userUid = (int)$account['uid'];
						$attachedPostUser = htmlspecialchars_uni($account['username']);
						$userAvatar = $eas->attached_avatar($account['avatar'], $account['avatardimensions']);

						// Leave current user out
						if ($account['uid'] == $mybb->user['uid'])
						{
							continue;
						}
						// Get shared accounts buddylist
						if ($account['as_share'] == 1 && $account['as_buddyshare'] == 1)
						{
							if ($account['buddylist'] != '')
							{
								$buddylist = explode(",", $account['buddylist']);
							}
						}
						if ($account['as_uid'] == $master['uid'])
						{
							// Set username styles
							if ($mybb->settings['aj_sharestyle'] == 1 && $account['as_share'] != 0)
							{
								$attachedUser = eval($templates->render('accountswitcher_shared_accountsbit'));
							}
							elseif ($mybb->settings['aj_secstyle'] == 1 && $account['as_sec'] != 0 && $account['as_share'] == 0)
							{
								$user_sec_reason = htmlspecialchars_uni($account['as_secreason']);
								$attachedUser = eval($templates->render('accountswitcher_sec_accountsbit'));
							}
							else
							{
								$attachedUser = format_name($attachedPostUser, (int)$account['usergroup'], (int)$account['displaygroup']);
							}
							// Load userbits
							if ($mybb->settings['aj_headerdropdown'] != 1)
							{
								$as_header_userbit .= eval($templates->render('accountswitcher_header_userbit'));
							}
							else
							{
								$as_header_dropdown .= eval($templates->render('accountswitcher_header_dropdown_userbit'));
							}
							if ($mybb->settings['aj_sidebar'] == 1)
							{
								$as_sidebar_userbit .= eval($templates->render('accountswitcher_sidebar_userbit'));
							}
							if ($mybb->settings['aj_postjump'] == 1)
							{
								$as_post_userbit .= eval($templates->render('accountswitcher_post_userbit'));
							}
							// Check if this user has a new private message.
							if ($account['pmnotice'] == 2 && $account['unreadpms'] > 0 && $mybb->settings['enablepms'] != 0 && ($current_page != "private.php" || $mybb->input['action'] != "read"))
							{
								$privatemessage_bit = eval($templates->render('accountswitcher_newpm_messagebit'));
								$privatemessage_switch .= $lang->sprintf($lang->aj_newpm_switch_notice_one, $privatemessage_bit);
								$pm_switch_notice = eval($templates->render('accountswitcher_newpm_message'));
							}
						}

						// Show shared accounts for master accounts
						if ($mybb->settings['aj_shareuser'] == 1 && (($account['as_share'] != 0 && $account['as_shareuid'] == 0 && $account['as_buddyshare'] == 0) || $account['as_shareuid'] == $mybb->user['uid'] || ($account['as_share'] != 0 && $account['as_shareuid'] == 0 && $account['as_buddyshare'] != 0 && !empty($buddylist) && in_array($mybb->user['uid'], $buddylist))))
						{
							// Set username styles
							if ($mybb->settings['aj_sharestyle'] == 1 && $account['as_share'] != 0)
							{
								$attachedUser = eval($templates->render('accountswitcher_shared_accountsbit'));
							}
							elseif ($mybb->settings['aj_secstyle'] == 1 && $account['as_sec'] != 0 && $account['as_share'] == 0)
							{
								$user_sec_reason = htmlspecialchars_uni($account['as_secreason']);
								$attachedUser = eval($templates->render('accountswitcher_sec_accountsbit'));
							}
							else
							{
								$attachedUser = format_name($attachedPostUser, (int)$account['usergroup'], (int)$account['displaygroup']);
							}
							// Load userbits
							if ($mybb->settings['aj_headerdropdown'] != 1)
							{
								$as_header_userbit .= eval($templates->render('accountswitcher_header_userbit'));
							}
							else
							{
								$as_header_dropdown .= eval($templates->render('accountswitcher_header_dropdown_userbit'));
							}
							if ($mybb->settings['aj_sidebar'] == 1)
							{
								$as_sidebar_userbit .= eval($templates->render('accountswitcher_sidebar_userbit'));
							}
							if ($mybb->settings['aj_postjump'] == 1)
							{
								$as_post_userbit .= eval($templates->render('accountswitcher_post_userbit'));
							}
						}
					}
					// Select box below editor
					if ($mybb->settings['aj_postjump'] == 1)
					{
						$mybbUid = (int)$mybb->user['uid'];
						$mybbUsername = htmlspecialchars_uni($mybb->user['username']);
						$as_post = eval($templates->render('accountswitcher_post'));
					}
				}
			}
		}
		// If there are no users attached to the current account and the current account is not attached to another user
		if ($count == 0 && $mybb->user['as_uid'] == 0 && $mybb->user['as_share'] == 0 && $mybb->usergroup['as_canswitch'] == 1)
		{
			// Show shared accounts
			if ($mybb->settings['aj_headerdropdown'] != 1)
			{
				$as_header_userbit = $eas->shared_userlist(1);
			}
			else
			{
				$as_header_dropdown = $eas->shared_userlist(2);
			}
			if ($mybb->settings['aj_sidebar'] == 1)
			{
				$as_sidebar_userbit = $eas->shared_userlist(3);
			}
			if ($mybb->settings['aj_postjump'] == 1)
			{
				$as_post_userbit .= $eas->shared_userlist(4);
				if (trim($as_post_userbit) != '')
				{
					$mybbUid = (int)$mybb->user['uid'];
					$mybbUsername = htmlspecialchars_uni($mybb->user['username']);
					$as_post = eval($templates->render('accountswitcher_post'));
				}
			}
		}
		// If this account is a shared one, show only the account you came from
		if ($mybb->settings['aj_shareuser'] == 1 && $mybb->user['as_share'] != 0 && $mybb->user['as_shareuid'] != 0)
		{
			$attachedShare = get_user((int)$mybb->user['as_shareuid']);
			$attachedPostUser = htmlspecialchars_uni($attachedShare['username']);
			$userUid = (int)$mybb->user['as_shareuid'];
			$userAvatar = $eas->attached_avatar($attachedShare['avatar'], $attachedShare['avatardimensions']);
			// Set username styles
			if ($mybb->settings['aj_sharestyle'] == 1 && $attachedShare['as_share'] != 0)
			{
				$attachedUser = eval($templates->render('accountswitcher_shared_accountsbit'));
			}
			elseif ($mybb->settings['aj_secstyle'] == 1 && $attachedShare['as_sec'] != 0 && $attachedShare['as_share'] == 0)
			{
				$user_sec_reason = htmlspecialchars_uni($account['as_secreason']);
				$attachedUser = eval($templates->render('accountswitcher_sec_accountsbit'));
			}
			else
			{
				$attachedUser = format_name($attachedPostUser, (int)$attachedShare['usergroup'], (int)$attachedShare['displaygroup']);
			}
			// Load userbits
			if ($mybb->settings['aj_headerdropdown'] != 1)
			{
				$as_header_userbit .= eval($templates->render('accountswitcher_header_userbit'));
			}
			else
			{
				$as_header_dropdown .= eval($templates->render('accountswitcher_header_dropdown_userbit'));
			}
			if ($mybb->settings['aj_sidebar'] == 1)
			{
				$as_sidebar_userbit .= eval($templates->render('accountswitcher_sidebar_userbit'));
			}
			if ($mybb->settings['aj_postjump'] == 1)
			{
				$as_post_userbit .= eval($templates->render('accountswitcher_post_userbit'));
			}

			// Select box below editor
			if ($mybb->settings['aj_postjump'] == 1)
			{
				$mybbUid = (int)$mybb->user['uid'];
				$mybbUsername = htmlspecialchars_uni($mybb->user['username']);
				$as_post = eval($templates->render('accountswitcher_post'));
			}
		}
	}
	// Sidebar enabled
	if (isset($mybb->settings['aj_sidebar']) && $mybb->settings['aj_sidebar'] == 1 && trim($as_sidebar_userbit) != '')
	{
		$as_sidebar = eval($templates->render('accountswitcher_sidebar'));
	}
	// Header dropdown selected
	if (isset($mybb->settings['aj_headerdropdown']) && $mybb->settings['aj_headerdropdown'] == 1 && trim($as_header_dropdown) != '')
	{
		$as_header = eval($templates->render('accountswitcher_header_dropdown'));
	}
	// Default header
	elseif (trim($as_header_userbit) != '')
	{
		$as_header = eval($templates->render('accountswitcher_header'));
	}
}

// Hook for change post author window
$plugins->add_hook("editpost_action_start", "accountswitcher_author");
/**
 * Modal box for changing the post author.
 *
 *
 */
function accountswitcher_author()
{
	global $mybb, $pid, $tid, $post, $db, $theme, $eas, $headerinclude, $lang, $templates, $postlink, $userUid, $attachedUser, $as_author_userbit, $cancel;

	// If user author change or mod author change
	if (($mybb->input['changeauthor'] == 1 && $mybb->settings['aj_changeauthor'] == 1) || ($mybb->input['adminauthor'] == 1 && $mybb->settings['aj_admin_changeauthor'] == 1))
	{
		// No post author and no mod permissions?
		if ($mybb->user['uid'] != $post['uid'] && !is_moderator($post['fid']))
		{
			error_no_permission();
		}

		if (!isset($lang->aj_changeauthor_headline))
		{
			$lang->load("accountswitcher");
		}

		$pid = (int)$pid;
		$postlink = htmlspecialchars_decode(get_post_link($pid, $tid).'#pid'.$pid);
		$author_admin = $author = '';
		$cancel = '$.modal.close(); return false;';

		// Get the attached users
		if ($mybb->user['uid'] != 0)
		{
			// Get the number of users attached to this account
			$count = $eas->get_attached($post['uid']);

			// Author moderation
			if ($mybb->input['adminauthor'] == 1 && $mybb->settings['aj_admin_changeauthor'] == 1)
			{
				// Search und set new author
				$lang->load("global");
				$author_admin .= '<div class="modal">'.eval($templates->render('accountswitcher_author_admin')).'</div>';
			}
			// Change author to attached user
			elseif ($mybb->input['changeauthor'] == 1)
			{
				$selected = '';
				// If there are users attached and the current user can use the Enhanced Account Switcher...
				if ($mybb->usergroup['as_canswitch'] == 1 && $count > 0)
				{
					$userUid = (int)$mybb->user['uid'];
					$attachedUser = htmlspecialchars_uni($mybb->user['username']);
					$as_author_userbit .= eval($templates->render('accountswitcher_author_selfbit'));
					$accounts = $eas->accountswitcher_cache;
					if (is_array($accounts))
					{
						// Sort accounts by first, secondary, shared accounts and by uid or username
						$accounts = $eas->sort_attached();

						// Get all attached accounts
						foreach ($accounts as $key => $account)
						{
							if ($account['as_uid'] == $mybb->user['uid'])
							{
								if ($count > 0)
								{
									$userUid = (int)$account['uid'];
									$attachedUser = htmlspecialchars_uni($account['username']);
									$as_author_userbit .= eval($templates->render('accountswitcher_author_userbit'));
								}
							}
						}
					}
				}

				// If there are no users attached to current account but the current account is attached to another user
				if ($count == 0 && $mybb->user['as_uid'] != 0)
				{
					// Get the master
					$master = get_user($mybb->user['as_uid']);
					// Get masters permissions
					$permission = user_permissions($master['uid']);

					// If the master has permission to use the Enhanced Account Switcher, get the userlist
					if ($permission['as_canswitch'] == 1)
					{
						// Create link to master
						$userUid = (int)$master['uid'];
						$attachedUser = htmlspecialchars_uni($master['username']);
						$as_author_userbit .= eval($templates->render('accountswitcher_author_userbit'));

						// Get all users attached to master from the cache
						$accounts = $eas->accountswitcher_cache;
						if (is_array($accounts))
						{
							foreach ($accounts as $key => $account)
							{
								// Leave current user out
								if ($account['uid'] == $mybb->user['uid'])
								{
									continue;
								}
								if ($account['as_uid'] == $master['uid'])
								{
									$userUid = (int)$account['uid'];
									$attachedUser = htmlspecialchars_uni($account['username']);
									$as_author_userbit .= eval($templates->render('accountswitcher_author_userbit'));
								}
							}
						}
					}
				}
			}
			// Build the page
			$author .= '<div class="modal">'.eval($templates->render('accountswitcher_author_change')).'</div>';
			// For author moderation check permissions and use another form
			if ($mybb->input['adminauthor'] == 1)
			{
				if (($mybb->settings['aj_admin_changegroup'] == 'admin' && $mybb->usergroup['cancp'] != 1)
				|| ($mybb->settings['aj_admin_changegroup'] == 'supermods' && $mybb->usergroup['issupermod'] != 1)
				|| ($mybb->settings['aj_admin_changegroup'] == 'mods' && !is_moderator($post['fid'])))
				{
					error_no_permission();
				}
				$author = $author_admin;
			}
			echo $author;
			exit;
		}
	}
}

// Hook for the author change function
$plugins->add_hook("editpost_start", "accountswitcher_author_change");
/**
 * Changes the author of the post.
 *
 *
 */
function accountswitcher_author_change()
{
	global $mybb, $db, $eas;

	// Change action
	if ($mybb->input['action'] == "do_author" && $mybb->request_method == "post" && ($mybb->settings['aj_changeauthor'] == 1 || $mybb->settings['aj_admin_changeauthor'] == 1))
	{
		// Verify incoming POST request
		verify_post_check($mybb->get_input('my_post_key'));

		// Get the current author of the post
		$pid = $mybb->get_input('pid', MyBB::INPUT_INT);
		$post = get_post($pid);
		$tid = (int)$post['tid'];
		$thread = get_thread($tid);
		$forum = get_forum($post['fid']);
		$oldauthor = get_user((int)$post['uid']);

		// Get the new user
		if (is_numeric($mybb->input['authorswitch']))
		{	// Input is uid from change author
			$newuid = $mybb->get_input('authorswitch', MyBB::INPUT_INT);
			$newauthor = get_user($newuid);
		}
		else
		{	// Input is username from author moderation
			$newname = htmlspecialchars_uni($mybb->get_input('authorswitch'));
			$newauthor = get_user_by_username($newname);
			$newauthor = get_user((int)$newauthor['uid']);
		}

		// New user doesn't exist? Redirect back to the post without changes
		if ($newauthor['uid'] == 0)
		{
			redirect(htmlentities($_POST['p_link']));
			return;
		}

		// Subtract from the users post count
		// Update the post count if this forum allows post counts to be tracked
		if ($forum['usepostcounts'] != 0)
		{
			if ($oldauthor['postnum'] > 0)
			{
				$postnum_old_array = array(
					"postnum" => "-1",
				);
				update_user_counters($post['uid'], $postnum_old_array);
			}
			$postnum_new_array = array(
				"postnum" => "+1",
			);
			update_user_counters($newauthor['uid'], $postnum_new_array);
		}

		// Subtract from the users thread count
		// Update the thread count if this forum allows thread counts to be tracked
		if ($pid == $thread['firstpost'] && $forum['usethreadcounts'] != 0 && substr($thread['closed'], 0, 6) != 'moved|')
		{
			if ($thread['visible'] == 1)
			{
				if ($oldauthor['threadnum'] > 0)
				{
					$threadnum_old_array = array(
						"threadnum" => "-1",
					);
					update_user_counters($post['uid'], $threadnum_old_array);
				}
				$threadnum_new_array = array(
					"threadnum" => "+1",
				);
				update_user_counters($newauthor['uid'], $threadnum_new_array);
			}
		}

		$updated_record = array(
			"uid" => (int)$newauthor['uid'],
			"username" => $db->escape_string($newauthor['username'])
		);
		if ($db->update_query("posts", $updated_record, "pid='".(int)$post['pid']."'"))
		{
			global $lang;
			if (!isset($lang->aj_author_change_log))
			{
				$lang->load("accountswitcher");
			}
			// Update first/last post info, log moderator action, redirect back to the post
			update_thread_data($tid);
			update_forum_lastpost((int)$post['fid']);
			$lang->aj_author_change_log = $lang->sprintf($lang->aj_author_change_log, (int)$post['pid'], htmlspecialchars_uni($post['username']), htmlspecialchars_uni($newauthor['username']));
			log_moderator_action(array("pid" => $post['pid']), $lang->aj_author_change_log);

			// Send pm to old and new author after moderation
			if ($post['uid'] != $mybb->user['uid'] && $mybb->settings['aj_admin_changeauthor'] == 1)
			{
				if ($mybb->settings['aj_authorpm'] == 1)
				{
					// Send PM
					require_once MYBB_ROOT."inc/datahandlers/pm.php";
					$pmhandler = new PMDataHandler();

					$lang->aj_author_change_pm_body = $lang->sprintf($lang->aj_author_change_pm_body, htmlspecialchars_uni($mybb->user['username']), $mybb->settings['bburl'].'/'.htmlentities($_POST['p_link']), htmlspecialchars_uni($post['subject']), htmlspecialchars_uni($post['username']), htmlspecialchars_uni($newauthor['username']));
					$subject = $lang->aj_author_change_pm_subject;
					$body = $lang->aj_author_change_pm_body;

					$pm = array(
						'subject' => $subject,
						'message' => $body,
						'icon' => '',
						'toid' => array($post['uid'], $newauthor['uid']),
						'fromid' => $mybb->user['uid'],
						"do" => '',
						"pmid" => '',

					);

					$pm['options'] = array(
					'signature' => '0',
					'savecopy' => '0',
					'disablesmilies' => '0',
					'readreceipt' => '0',
					);

					$pmhandler->set_data($pm);
					$valid_pm = $pmhandler->validate_pm();

					if ($valid_pm)
					{
						$pmhandler->insert_pm();
					}
				}

				// Show alert
				if ($mybb->settings['aj_myalerts'] == 1 && isset($mybb->user['myalerts_disabled_alert_types']))
				{
					$alertType = MybbStuff_MyAlerts_AlertTypeManager::getInstance()->getByCode('accountswitcher_author');
					$alerts = array();
					$subject = htmlspecialchars_uni($post['subject']);

					$alert_old = new MybbStuff_MyAlerts_Entity_Alert((int)$post['uid'], $alertType, $tid);
					$alert_old->setExtraDetails(
					array(
						'thread_title' => $subject,
						'pid' => $pid,
						'tid' => $tid,
						'olduser' => htmlspecialchars_uni($post['username']),
						'newuser' => htmlspecialchars_uni($newauthor['username'])
						)
					);
					$alerts[] = $alert_old;

					$alert_new = new MybbStuff_MyAlerts_Entity_Alert((int)$newauthor['uid'], $alertType, $tid);
					$alert_new->setExtraDetails(
					array(
						'thread_title' => $subject,
						'pid' => $pid,
						'tid' => $tid,
						'olduser' => htmlspecialchars_uni($post['username']),
						'newuser' => htmlspecialchars_uni($newauthor['username'])
						)
					);
					$alerts[] = $alert_new;

					if (!empty($alerts))
					{
						MybbStuff_MyAlerts_AlertManager::getInstance()->addAlerts($alerts);
					}
				}
			}
			$eas->update_accountswitcher_cache();
			redirect(htmlentities($_POST['p_link']));
		}
	}
	else {
		return;
	}
}

// Hook for the author change link function
$plugins->add_hook("postbit", "accountswitcher_author_change_button", 50);
/**
 * Shows a link for changing the author of the post and accountlist in postbit.
 *
 * @param array The post data.
 */
function accountswitcher_author_change_button(&$post)
{
	global $mybb, $theme, $lang, $admin_change, $author_change, $templates;

	if (!isset($lang->aj_changeauthor_postbit))
	{
		$lang->load("accountswitcher");
	}

	// Post author change button
	if (($mybb->user['uid'] != 0 && $mybb->user['uid'] == $post['uid']  && ($mybb->usergroup['as_canswitch'] == 1 || $mybb->user['as_uid'] != 0) && $mybb->settings['aj_changeauthor'] == 1)
		|| ($mybb->settings['aj_admin_changeauthor'] == 1 && $mybb->settings['aj_admin_changegroup'] == 'admin' && $mybb->usergroup['cancp'] == 1)
		|| ($mybb->settings['aj_admin_changeauthor'] == 1 && $mybb->settings['aj_admin_changegroup'] == 'supermods' && ($mybb->usergroup['issupermod'] == 1))
		|| ($mybb->settings['aj_admin_changeauthor'] == 1 && $mybb->settings['aj_admin_changegroup'] == 'mods' && is_moderator($post['fid']))
		)
	{
		// Declare variables
		$post['authorchange'] = $author_change = $admin_change = '';
		$post['pid'] = (int)$post['pid'];

		// Edit time limit
		$editlimit = ((int)$mybb->usergroup['edittimelimit']*60) + (int)$post['dateline'];
		if (!is_moderator($post['fid']) && $mybb->usergroup['edittimelimit'] != 0 && (TIME_NOW > $editlimit)) return;

		// Dropdown item for author moderation
		if ($mybb->settings['aj_admin_changeauthor'] == 1)
		{
			if (($mybb->settings['aj_admin_changegroup'] == 'admin' && $mybb->usergroup['cancp'] == 1)
				|| ($mybb->settings['aj_admin_changegroup'] == 'supermods' && ($mybb->usergroup['issupermod'] == 1))
				|| ($mybb->settings['aj_admin_changegroup'] == 'mods' && is_moderator($post['fid'])))
				{
					$admin_change = eval($templates->render('accountswitcher_author_button_admin'));
				}
		}
		// Dropdown item for change to attached users
		if ($mybb->user['uid'] == $post['uid'] && ($mybb->usergroup['as_canswitch'] == 1 || $mybb->user['as_uid'] != 0))
		{
			$author_change = eval($templates->render('accountswitcher_author_button_attached'));
		}
		// Create the menu
		$post['authorchange'] .= eval($templates->render('accountswitcher_author_button'));
	}
}

// Show attached accounts in user profile
$plugins->add_hook('member_profile_end', 'accountswitcher_profile');
/**
 * Shows the attached accounts in user profile.
 *
 *
 */
function accountswitcher_profile()
{
	global $mybb,  $db, $memprofile, $theme, $eas, $profile_attached, $lang, $user_sec_reason, $as_profile_userbit, $as_profile_hidden, $attachedUser, $userUid, $masterUid, $templates, $attachedPostUser, $avadims, $userAvatar;

	// Get the attached users
	if ($memprofile['uid'] != 0 && $mybb->settings['aj_profile'] == 1)
	{
		// Get usergroup permissions
		$permissions = user_permissions((int)$memprofile['uid']);

		// Get the number of users attached to this account
		$count = $eas->get_attached($memprofile['uid']);
		$hidden = $eas->get_hidden($memprofile['uid']);
		$profile_attached = $as_profile_userbit = $as_profile_hidden = '';
		$avadims = 'width="32" height="32"';

		// Hide users with privacy setting enabled
		if ($mybb->usergroup['cancp'] != 1 && $mybb->user['uid'] != $memprofile['uid'] && $mybb->settings['aj_privacy'] == 1 && $memprofile['as_privacy'] == 1)
		{
			if (($mybb->user['as_uid'] != 0 && $mybb->user['as_uid'] != $memprofile['as_uid'] && $mybb->user['as_uid'] != $memprofile['uid'])
			|| ($mybb->user['as_uid'] == 0 && $mybb->user['uid'] != $memprofile['as_uid']))
			{
				return;
			}
		}

		// If there are users attached and the current user can use the Enhanced Account Switcher...
		if ($permissions['as_canswitch'] == 1 && $count > 0)
		{
			$as_profile_userbit = $eas->master_userlist($memprofile['uid']);
		}

		// If there are no users attached to current account but the current account is attached to another user
		if ($count == 0 && $memprofile['as_uid'] != 0)
		{
			// Get the master
			$master = get_user((int)$memprofile['as_uid']);
			$hidden = $eas->get_hidden($master['uid']);
			// Get masters permissions
			$permission = user_permissions((int)$master['uid']);

			// If master has permission to use the Enhanced Account Switcher, get the userlist
			if ($permission['as_canswitch'] == 1)
			{
				$userUid = $masterUid = (int)$master['uid'];
				$attachedPostUser = htmlspecialchars_uni($master['username']);
				$userAvatar = $eas->attached_avatar($master['avatar'], $master['avatardimensions']);

				// Create link to master
				if ($memprofile['uid'] == $mybb->user['uid'])
				{
					$attachedUser = '<span style="font-weight: bold;" title="Master Account">'.format_name($attachedPostUser, (int)$master['usergroup'], (int)$master['displaygroup']).'</span>';
					$as_profile_userbit .= eval($templates->render('accountswitcher_profile_switch'));
				}
				else
				{
					// Hide users with privacy setting enabled
					if (($mybb->usergroup['cancp'] != 1 && $mybb->user['uid'] != $master['uid'] && $mybb->settings['aj_privacy'] == 1 && $master['as_privacy'] == 1)
					&& (($mybb->user['as_uid'] > 0 && $mybb->user['as_uid'] != $master['uid'])
					|| ($mybb->user['as_uid'] == 0 && $mybb->user['uid'] != $master['as_uid'])))
					{
							++$hidden;
							$as_profile_userbit .= '';
					}
					else
					{
						$attachedUser = '<span style="font-weight: bold;" title="Master Account">'.build_profile_link(format_name($attachedPostUser, (int)$master['usergroup'], (int)$master['displaygroup']), $userUid).'</span>';
						$as_profile_userbit .= eval($templates->render('accountswitcher_profile_link'));
					}
				}
				// Get all users attached to master from the cache
				$as_profile_userbit .= $eas->attached_userlist($memprofile['uid']);
			}
		}

		if ($count > 0 || $count == 0 && $memprofile['as_uid'] != 0)
		{
			if (!isset($lang->aj_profile))
			{
				$lang->load('accountswitcher');
			}
			// Show hidden accounts if current user is post author or attached to hidden account
			if ($memprofile['uid'] == $mybb->user['uid'] || $memprofile['uid'] == $mybb->user['as_uid'] || $memprofile['as_uid'] == $mybb->user['uid'])
			{
				$hidden = 0;
			}
			if ($hidden > 0)
			{
				$as_profile_hidden = $lang->sprintf($lang->aj_hidden, $hidden);
			}
			$profile_attached .= eval($templates->render('accountswitcher_profile'));
		}
	}
}

// Hook for attached accounts in postbit
$plugins->add_hook('postbit', 'accountswitcher_postuser');
/**
 * Shows the attached accounts and added post count in postbit.
 *
 * @param array The post data.
 */
function accountswitcher_postuser(&$post)
{
	global $mybb, $db, $theme, $lang, $eas, $as_postuser_userbit, $as_postuser_hidden, $user_sec_reason, $attachedUser, $userUid, $masterUid, $postId, $templates, $attachedPostUser, $numaccounts, $as_postcount, $user_profilefield;

	// Get the attached users
	if ($post['uid'] != 0 && $mybb->settings['aj_postuser'] == 1 || $mybb->settings['aj_postcount'] == 1)
	{
		// Get usergroup permissions
		$permissions = user_permissions((int)$post['uid']);

		// Get the number of users attached to this account
		$count = $eas->get_attached($post['uid']);
		$hidden = $eas->get_hidden($post['uid']);
		$postId = (int)$post['pid'];
		$mybb_asset_url = $mybb->asset_url;
		$as_postcount = (int)str_replace($mybb->settings['thousandssep'], '', $post['postnum']);
		$post['attached_accounts'] = $as_postuser_userbit = $as_postuser_hidden = $numaccounts = '';

		// Hide users with privacy setting enabled
		if ($mybb->usergroup['cancp'] != 1 && $mybb->user['uid'] != $post['uid'] && $mybb->settings['aj_privacy'] == 1 && $post['as_privacy'] == 1)
		{
			if (($mybb->user['as_uid'] != 0 && $mybb->user['as_uid'] != $post['as_uid'] && $mybb->user['as_uid'] != $post['uid'])
			|| ($mybb->user['as_uid'] == 0 && $mybb->user['uid'] != $post['as_uid']))
			{
				return;
			}
		}

		// If there are users attached and the current user can use the Enhanced Account Switcher...
		if ($permissions['as_canswitch'] == 1 && $count > 0)
		{
			$as_postuser_userbit = $eas->master_userlist($post['uid']);
		}

		// If there are no users attached to current account but the current account is attached to another user
		if ($count == 0 && $post['as_uid'] != 0)
		{
			// Get the master
			$master = get_user((int)$post['as_uid']);
			$hidden = $eas->get_hidden($master['uid']);
			// Get masters permissions
			$permission = user_permissions((int)$master['uid']);

			// If master has permission to use the Enhanced Account Switcher, get the userlist
			if ($permission['as_canswitch'] == 1)
			{
				$userUid = $masterUid = (int)$master['uid'];
				$attachedPostUser = htmlspecialchars_uni($master['username']);
				$as_postcount += (int)$master['postnum'];
				$userAvatar = $eas->attached_avatar($master['avatar'], $master['avatardimensions']);
				// Create link to master
				if ($post['uid'] == $mybb->user['uid'])
				{
					$postId = (int)$post['pid'];
					$attachedUser = '<span style="font-weight: bold;" title="Master Account">'.format_name($attachedPostUser, (int)$master['usergroup'], (int)$master['displaygroup']).'</span>';
					$as_postuser_userbit .= eval($templates->render('accountswitcher_postbit_switch'));
				}
				else
				{
					// Hide users with privacy setting enabled
					if (($mybb->usergroup['cancp'] != 1 && $mybb->user['uid'] != $master['uid'] && $mybb->settings['aj_privacy'] == 1 && $master['as_privacy'] == 1)
					&& (($mybb->user['as_uid'] > 0 && $mybb->user['as_uid'] != $master['uid'])
					|| ($mybb->user['as_uid'] == 0 && $mybb->user['uid'] != $master['as_uid'])))
					{
							++$hidden;
							$as_postuser_userbit .= '';
					}
					else
					{
						$attachedUser = '<span style="font-weight: bold;" title="Master Account">'.build_profile_link(format_name($attachedPostUser, (int)$master['usergroup'], (int)$master['displaygroup']), $userUid).'</span>';
						$as_postuser_userbit .= eval($templates->render('accountswitcher_postbit_link'));
					}
				}
				// Get all users attached to master from the cache
				$as_postuser_userbit .= $eas->attached_userlist($post['uid']);
			}
		}

		if ($count > 0 || $count == 0 && $post['as_uid'] != 0)
		{
			if (!isset($lang->aj_profile))
			{
				$lang->load('accountswitcher');
			}
			// Show hidden accounts if current user is post author or attached to hidden account
			if ($post['uid'] == $mybb->user['uid'] || $post['uid'] == $mybb->user['as_uid'] || $post['as_uid'] == $mybb->user['uid'])
			{
				$hidden = 0;
			}
			// Accountlist link in postbit
			if ($mybb->user['uid'] != 0 && $post['uid'] != 0 && $mybb->settings['aj_postuser'] == 1)
			{
				if ($count == 1)
				{
					$numaccounts = $count;
					$lang->aj_memberlist = $lang->aj_memberlist_one;
					$numaccounts .= ' ';
				}
				elseif ($count > 1)
				{
					$numaccounts = $count;
					$numaccounts .= ' ';
					$lang->aj_memberlist = $lang->aj_memberlist_more;
				}
				else
				{
					$lang->aj_memberlist = $lang->aj_memberlist_linked;
				}
				// Hidden accounts attached?
				if ($hidden > 0)
				{
					$as_postuser_hidden .= $lang->sprintf($lang->aj_hidden, $hidden);
				}
				$post['attached_accounts'] .= eval($templates->render('accountswitcher_postbit'));
			}
			if ($mybb->settings['aj_postcount'] == 1)
			{
				$post['user_details'] .= $lang->aj_attached_post_count.my_number_format($as_postcount);
			}
		}
		if ($post['as_share'] != 0)
		{
			$post['attached_accounts'] .= '<br />'.$lang->aj_memberlist_shared;
		}
	}
}

// Hook for attached accounts in memberlist
$plugins->add_hook('memberlist_user', 'accountswitcher_memberlist');
/**
 * Shows the attached accounts in memberlist.
 *
 * @param array The user data.
 */
function accountswitcher_memberlist(&$user)
{
	global $mybb, $db, $theme, $lang, $eas, $as_user_userbit, $as_user_hidden, $user_sec_reason, $attachedUser, $userUid, $masterUid, $templates, $attachedPostUser, $numaccounts, $user_profilefield;

	// Load profile field  - disabled by default... Example:
	//$user['profilefield'] = $eas->get_profilefield($user['uid']);

	$user['attached_accounts'] = $as_user_userbit = $as_user_hidden = $numaccounts = '';
	$mybb_asset_url = $mybb->asset_url;

	// Get the attached users
	if ($user['uid'] != 0 && $mybb->settings['aj_memberlist'] == 1)
	{
		// Get usergroup permissions
		$permissions = user_permissions((int)$user['uid']);

		// Get the number of users attached to this account
		$count = $eas->get_attached($user['uid']);
		$hidden = $eas->get_hidden($user['uid']);

		// Hide users with privacy setting enabled
		if ($mybb->usergroup['cancp'] != 1 && $mybb->user['uid'] != $user['uid'] && $mybb->settings['aj_privacy'] == 1 && $user['as_privacy'] == 1)
		{
			if (($mybb->user['as_uid'] != 0 && $mybb->user['as_uid'] != $user['as_uid'] && $mybb->user['as_uid'] != $user['uid'])
			|| ($mybb->user['as_uid'] == 0 && $mybb->user['uid'] != $user['as_uid']))
			{
				return;
			}
		}

		// If there are users attached and the current user can use the Enhanced Account Switcher...
		if ($permissions['as_canswitch'] == 1 && $count > 0)
		{
			$as_user_userbit = $eas->master_userlist($user['uid']);
		}

		// If there are no users attached to current account but the current account is attached to another user
		if ($count == 0 && $user['as_uid'] != 0)
		{
			// Get the master
			$master = get_user((int)$user['as_uid']);
			$hidden = $eas->get_hidden($master['uid']);
			// Get masters permissions
			$permission = user_permissions((int)$master['uid']);

			// If master has permission to use the Enhanced Account Switcher, get the userlist
			if ($permission['as_canswitch'] == 1)
			{
				$userUid = $masterUid = (int)$master['uid'];
				$attachedPostUser = htmlspecialchars_uni($master['username']);
				$userAvatar = $eas->attached_avatar($master['avatar'], $master['avatardimensions']);

				// Create link to master
				if ($user['uid'] == $mybb->user['uid'])
				{
					$attachedUser = '<span style="font-weight: bold;" title="Master Account">'.format_name($attachedPostUser, (int)$master['usergroup'], (int)$master['displaygroup']).'</span>';
					$as_user_userbit .= eval($templates->render('accountswitcher_memberlist_switch'));
				}
				else
				{
					// Hide users with privacy setting enabled
					if (($mybb->usergroup['cancp'] != 1 && $mybb->user['uid'] != $master['uid'] && $mybb->settings['aj_privacy'] == 1 && $master['as_privacy'] == 1)
					&& (($mybb->user['as_uid'] > 0 && $mybb->user['as_uid'] != $master['uid'])
					|| ($mybb->user['as_uid'] == 0 && $mybb->user['uid'] != $master['as_uid'])))
					{
						++$hidden;
						$as_user_userbit .= '';
					}
					else
					{
						$attachedUser = '<span style="font-weight: bold;" title="Master Account">'.build_profile_link(format_name($attachedPostUser, (int)$master['usergroup'], (int)$master['displaygroup']), $userUid).'</span>';
						$as_user_userbit .= eval($templates->render('accountswitcher_memberlist_link'));
					}
				}
				// Get all users attached to master from the cache
				$as_user_userbit .= $eas->attached_userlist($user['uid']);
			}
		}

		if ($count > 0 || $count == 0 && $user['as_uid'] != 0)
		{
			if (!isset($lang->aj_profile))
			{
				$lang->load('accountswitcher');
			}
			// Show hidden accounts if current user is post author or attached to hidden account
			if ($user['uid'] == $mybb->user['uid'] || $user['uid'] == $mybb->user['as_uid'] || $user['as_uid'] == $mybb->user['uid'])
			{
				$hidden = 0;
			}
			// Accountlist link in postbit
			if ($mybb->user['uid'] != 0 && $user['uid'] != 0 && $mybb->settings['aj_memberlist'] == 1)
			{
				if ($count == 1)
				{
					$numaccounts = $count;
					$lang->aj_memberlist = $lang->aj_memberlist_one;
					$numaccounts .= ' ';
				}
				elseif ($count > 1)
				{
					$numaccounts = $count;
					$numaccounts .= ' ';
					$lang->aj_memberlist = $lang->aj_memberlist_more;
				}
				else
				{
					$lang->aj_memberlist = $lang->aj_memberlist_linked;
				}
				if ($hidden > 0)
				{
					$as_user_hidden .= $lang->sprintf($lang->aj_hidden, $hidden);
				}
				$user['attached_accounts'] .= eval($templates->render('accountswitcher_memberlist'));
			}
		}
		if ($user['as_share'] != 0)
		{
			$user['attached_accounts'] .= eval($templates->render('accountswitcher_memberlist_shared'));
		}
	}
}

// Hook for load script in footer
$plugins->add_hook("global_end", "accountswitcher_footer");
/**
 * Adds the script for the ajax request to the footer
 *
 */
function accountswitcher_footer()
{
	global $mybb, $footer, $headerinclude, $header, $eas, $lang, $templates, $mybbUid, $mybbUsername, $as_postreply, $as_canswitch, $as_reload, $as_script, $as_footer;

	// Add sidebar css to headerinclude
	if ($mybb->settings['minifycss'] == 1 && $mybb->settings['aj_sidebar'] == 1)
	{ // Minified css
		$headerinclude .= '
		<link type="text/css" rel="stylesheet" href="'.$mybb->asset_url.'/jscripts/accountswitcher/sidebar.min.css?v='.$eas->version.'" />
		';
	}
	elseif ($mybb->settings['aj_sidebar'] == 1)
	{
		$headerinclude .= '
		<link type="text/css" rel="stylesheet" href="'.$mybb->asset_url.'/jscripts/accountswitcher/sidebar.css?v='.$eas->version.'" />
		';
	}

	// Javascript disabled?
	$header .= '
		<noscript>
			<p style="font-weight: bold; font-size: 1.2em;">'.$lang->aj_script_error.'</p>
		</noscript>
		';

	$as_postreply = '';

	// Load language file
	if (!isset($lang->aj_submit_button))
	{
		$lang->load("accountswitcher");
	}
	if (THIS_SCRIPT == "showthread.php")
	{
		$lang->load("showthread");
	}

	if (isset($lang->post_reply))
	{
		$as_postreply = $lang->post_reply;
	}

	// Put the script in the footer
	$mybbUid = (int)$mybb->user['uid'];
	$mybbUsername = htmlspecialchars_uni($mybb->user['username']);
	$as_canswitch = (int)$mybb->usergroup['as_canswitch'];
	$as_reload = (int)$mybb->settings['aj_reload'];
	$as_script = THIS_SCRIPT;

	$as_footer = eval($templates->render('accountswitcher_footer'));
	$footer = str_replace($footer, $footer.$as_footer, $footer);
}

// Hook for header link to accountlist
$plugins->add_hook("global_intermediate", "accountswitcher_header_link");
/**
 * Adds a link to accountlist.php in header menu
 *
 */
function accountswitcher_header_link()
{
	global $mybb, $lang, $menu_memberlist, $menu_accountlist, $templates;

	// Load language file
	if (!isset($lang->aj_accountlist))
	{
		$lang->load("accountswitcher");
	}

	// Header link to accountlist
	$menu_accountlist = '';
	if ($mybb->settings['aj_list'] == 1)
	{
		$menu_accountlist .= eval($templates->render('accountswitcher_header_accountlist'));
	}
}

// Hook for logout of a shared account
$plugins->add_hook("member_logout_start", "accountswitcher_share_logout");
/**
 * Delete the as_shareuid of the shared account on logout.
 *
 */
function accountswitcher_share_logout()
{
	global $mybb, $db, $eas;

	$updated_as_shareuid = array(
		"as_shareuid" => 0,
	);
	$db->update_query('users', $updated_as_shareuid, "uid='".(int)$mybb->user['uid']."'");
	$db->update_query('users', $updated_as_shareuid, "as_shareuid='".(int)$mybb->user['uid']."'");

	$eas->update_accountswitcher_cache();
}

// Hook for the switch function
$plugins->add_hook("xmlhttp", "accountswitcher_switch");
/**
 * The switch function deletes the mybbuser cookie, sets a new cookie for the selected account and starts a new session.
 * Function is called by ajax request and sends the new users post key.
 *
 */
function accountswitcher_switch()
{
	global $db, $mybb, $lang, $charset, $cache, $templates;

	if ($mybb->user['uid'] != 0 && isset($mybb->input['switchuser']) && $mybb->input['switchuser'] == 1 && $mybb->request_method == "post")
	{
		require_once MYBB_ROOT."/inc/plugins/accountswitcher/class_accountswitcher.php";
		$eas = new AccountSwitcher($mybb, $db, $cache, $templates);

		// Get permissions for this user
		$userPermission = user_permissions($mybb->user['uid']);

		// Get permissions for the master. First get the master
		$master = get_user((int)$mybb->user['as_uid']);

		// Get his permissions
		$masterPermission = user_permissions($master['uid']);

		// If one of both has the permission allow to switch
		if ($userPermission['as_canswitch'] == 1 || $masterPermission['as_canswitch'] == 1)
		{
			if (!isset($lang->as_invaliduser))
			{
				$lang->load("accountswitcher");
			}

			verify_post_check($mybb->get_input('my_post_key'));

			// Get user info
			$user = get_user($mybb->get_input('uid', MyBB::INPUT_INT));

			// Check if user exists
			if (!$user)
			{
				error($lang->as_invaliduser);
			}

			// Can the new account be shared?
			if ($user['as_share'] != 0 && $mybb->settings['aj_shareuser'] == 1)
			{
				// Account already used by another user?
				if ($user['as_shareuid'] != 0)
				{
					log_moderator_action(array('uid' => $user['uid'], 'username' => $user['username']), $lang->aj_switch_invalid_log);
					return;
				}

				// Account only shared by buddies?
				if ($user['as_buddyshare'] != 0)
				{
					// No buddy - no switch
					if ($user['buddylist'] != '')
					{
						$buddylist = explode(",", $user['buddylist']);
					}
					if (empty($buddylist) || (!empty($buddylist) && !in_array($mybb->user['uid'], $buddylist)))
					{
						log_moderator_action(array('uid' => $user['uid'], 'username' => $user['username']), $lang->aj_switch_invalid_log);
						return;
					}
				}

				// Shared account is free - set share uid
				if ($user['as_shareuid'] == 0)
				{
					$updated_shareuid = array(
						"as_shareuid" => (int)$mybb->user['uid']
					);
					$db->update_query("users", $updated_shareuid, "uid='".(int)$user['uid']."'");
					$eas->update_accountswitcher_cache();
					$user['as_shareuid'] = (int)$mybb->user['uid'];
				}
			}

			// Make sure you can switch to an attached account only
			if ($user['as_uid'] == $mybb->user['uid'] // from master to an attached account
				|| ($user['as_uid'] != 0 && $user['as_uid'] == $mybb->user['as_uid']) // from attached to another attached account - both must have the same master
				|| $user['uid'] == $mybb->user['as_uid'] // from attached to the master account
				|| $user['as_shareuid'] == $mybb->user['uid'] // to a free shared account
				|| $user['uid'] == $mybb->user['as_shareuid']) // return from a shared account to the account you switched from
			{
				// Is the current account shared?
				if ($mybb->user['as_share'] != 0)
				{
					// Account used by another user?
					if ($mybb->user['as_shareuid'] == 0)
					{
						log_moderator_action(array('uid' => $user['uid'], 'username' => $user['username']), $lang->aj_switch_invalid_log);
						return;
					}
					// Reset share uid
					if ($mybb->user['as_shareuid'] != 0)
					{
						$updated_shareuid = array(
							"as_shareuid" => 0
						);
						$db->update_query("users", $updated_shareuid, "uid='".(int)$mybb->user['uid']."'");
						$eas->update_accountswitcher_cache();
					}
				}

				// Log the old user out
				my_unsetcookie("mybbuser");
				my_unsetcookie("sid");

				if ($mybb->user['uid'])
				{
					$time = TIME_NOW;
					// Run this after the shutdown query from session system
					$db->shutdown_query("UPDATE ".TABLE_PREFIX."users SET lastvisit='{$time}', lastactive='{$time}' WHERE uid='{$mybb->user['uid']}'");
					$db->delete_query("sessions", "sid = '{$session->sid}'");
				}

				// Now let the login datahandler do the work
				require_once MYBB_ROOT."inc/datahandlers/login.php";
				$loginhandler = new LoginDataHandler("get");
				$mybb->input['remember'] = "yes";
				$loginhandler->set_data($user);
				$validated = $loginhandler->validate_login();
				$loginhandler->complete_login();

				// Create session for this user
				require_once MYBB_ROOT."inc/class_session.php";
				$session = new session;
				$session->init();
				$mybb->session = &$session;
				$mybb->post_code = generate_post_check();

				// Send new users post code
				header("Content-type: text/plain; charset={$charset}");
				echo $mybb->post_code;
				exit;
			}
			else
			{
				log_moderator_action(array('uid' => $user['uid'], 'username' => $user['username']), $lang->aj_switch_invalid_log);
				error($lang->as_notattacheduser);
			}
		}
	}
}

// Hook for the clear shared function
$plugins->add_hook("member_do_login_end", "accountswitcher_clear_shared");
/**
 * For security reasons detach a shared account from current master account when a shared user is logging in.
 *
 */
function accountswitcher_clear_shared()
{
	global $db, $eas, $mybb;

	$user = get_user_by_username($mybb->get_input('username'));
	if ($user['uid'] != 0)
	{
		$shareduser = get_user((int)$user['uid']);
		if ($shareduser['as_share'] == 1 && $shareduser['as_shareuid'] > 0)
		{
			$updated_shareuid = array(
				"as_shareuid" => 0
			);
			$db->update_query("users", $updated_shareuid, "uid='".(int)$shareduser['uid']."'");
			$eas->update_accountswitcher_cache();
		}
	}
}
