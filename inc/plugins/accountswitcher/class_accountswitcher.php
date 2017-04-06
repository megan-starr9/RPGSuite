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

class AccountSwitcher {
	/**
	 * The version number of EAS.
	 *
	 * @var string
	 */
	public $release = "2.0.8";

	/**
	 * The version code of EAS.
	 *
	 * @var integer
	 */
	public $version = 2008;

	/**
	 * The account switcher cache
	 *
	 * @var array
	 */
	public $accountswitcher_cache = array();

	/**
	 * The user fields cache
	 *
	 * @var array
	 */
	public $userfields_cache = array();

	/**
	 * Use MyBB core objects
	 *
	 */
	private $mybb;
	private $db;
	private $cache;
	private $templates;

	/*
	 * __construct()
	 *
	 * Initialise the account switcher
	 *
	 * @param The MyBB core object
	 * @param The MyBB database connection
	 * @param The MyBB cache
	 * @param The MyBB templates
	 *
	*/
	public function __construct($mybb, $db, $cache, $templates)
	{
		$this->mybb = $mybb;
		$this->db = $db;
		$this->cache = $cache;
		$this->templates = $templates;

		// Accounts and user field cache
		$this->accountswitcher_cache = $this->cache->read('accountswitcher');
		$this->userfields_cache = $this->cache->read('accountswitcher_fields');
	}

	/*
	* Returns the version of Enhanced Account Switcher
	*
	* @ return The version number.
	*/
	public function get_version()
	{
		return $this->version;
	}

	/**
	 * Build and empty the account switcher cache.
	 *
	 * @param boolean If true clear the cache, otherwise build the cache.
	 */
	public function update_accountswitcher_cache($clear=false)
	{
		if ($clear == true)
		{
			$this->cache->update('accountswitcher',false);
		}
		else
		{
			$switchers = array();
			$query = $this->db->simple_select('users','uid,usergroup,displaygroup,username,postnum,avatar,avatardimensions,as_uid,pmnotice,unreadpms,buddylist,as_share,as_shareuid,as_sec,as_privacy,as_buddyshare,as_secreason','as_uid != 0 OR as_share != 0');
			while ($switcher = $this->db->fetch_array($query))
			{
				$switchers[$switcher['uid']] = $switcher;
			}
			$this->cache->update('accountswitcher', $switchers);
		}
	}

	/**
	 * Cache the specified profile field of the users.
	 *
	 * @param boolean If true clear the cache, otherwise build the cache.
	 */
	public function update_userfields_cache($clear=false)
	{
		if ($clear == true)
		{
			$this->cache->update('accountswitcher_fields', false);
		}
		else
		{
			// Get profile field id from plugin settings
			$field_id = (int)$this->mybb->settings['aj_profilefield_id'];
			// Save user fields in array
			$query = $this->db->simple_select("userfields", "*");
			while ($ufid = $this->db->fetch_array($query))
			{
				$ufields[] = array(
					'uid' => $ufid['ufid'],
					'fid' => $ufid['fid'.$field_id.'']
					);
			}
			$this->cache->update('accountswitcher_fields', $ufields);
		}
	}

	/**
	 * Get switch link.
	 *
	 * @param int The user id
	 * @return string The switch link.
	 */
	public function switch_link($user)
	{
		$account = get_user($user);
		$switch_link = '<a id="switch_'.$user.' href="#switch" class="switchlink">'.format_name(htmlspecialchars_uni($account['username']), (int)$account['usergroup'], (int)$account['displaygroup']).'</a>';

		return $switch_link;
	}

	/**
	 * Sort attached users.
	 *
	 * @return array The sorted account array.
	 */
	public function sort_attached()
	{
		$as_share = $as_sec = $as_username = $as_uid = array();
		$accounts = $this->accountswitcher_cache;
		if (is_array($accounts))
		{
			// Find all attached accounts
			foreach ($accounts as $key => $account)
			{
				$as_share[$key] = $account['as_share'];
				$as_sec[$key] = $account['as_sec'];
				$as_username[$key] = strtolower($account['username']);
				$as_uid[$key] = $account['uid'];
			}
			// Sort by username
			if (isset($this->mybb->settings['aj_sortuser']) && $this->mybb->settings['aj_sortuser'] == 'uname')
			{
				array_multisort($as_share, SORT_ASC, $as_sec, SORT_ASC, $as_username, SORT_ASC, $accounts);
			}
			else // Sort by uid
			{
				array_multisort($as_share, SORT_ASC, $as_sec, SORT_ASC, $as_uid, SORT_ASC, $accounts);
			}
			return $accounts;
		}
	}

	/**
	 * Format avatars for account lists.
	 *
	 * @param string The avatar file name
	 * @param string Dimensions of the avatar, width x height (e.g. 44|44)
	 * @return string The formatted avatar
	 */
	public function attached_avatar($avatar, $dimensions)
	{
		global $avadims, $attachedPostUser;

		// Set the max. dimensions
		$maxdims = $this->mybb->settings['maxavatardims'];
		if (THIS_SCRIPT == "showthread.php" || THIS_SCRIPT == "private.php" || THIS_SCRIPT == "portal.php" || THIS_SCRIPT == "newreply.php")
		{
			$maxdims = $this->mybb->settings['postmaxavatarsize'];
		}
		if (THIS_SCRIPT == "memberlist.php")
		{
			$maxdims = $this->mybb->settings['memberlistmaxavatarsize'];
		}

		// Format the avatar
		$ava = format_avatar($avatar, $dimensions, $maxdims);
		$userAvatar = htmlspecialchars_uni($ava['image']);
		// Load the avatar template
		$userAvatar = eval($this->templates->render('accountswitcher_avatar'));

		return $userAvatar;
	}

	/**
	 * Get number of attached accounts for master.
	 *
	 * @param int The user uid
	 * @return int The count of the attached users
	 */
	public function get_attached($user)
	{
		$count = 0;
		$accounts = $this->accountswitcher_cache;
		if (is_array($accounts))
		{
			// Get all attached accounts
			foreach ($accounts as $key => $account)
			{
				if ($account['as_uid'] == $user)
				{
					++$count;
				}
			}
		}
		return $count;
	}

	/**
	 * Get number of hidden accounts for master.
	 *
	 * @param int The user uid
	 * @return int The count of the hidden users
	 */
	public function get_hidden($user)
	{
		$hidden = 0;
		$accounts = $this->accountswitcher_cache;
		if (is_array($accounts))
		{
			// Get all attached accounts
			foreach ($accounts as $key => $account)
			{
				if ($user == $account['as_uid'] && $account['as_privacy'] == 1)
				{
					++$hidden;
				}
			}
		}
		return $hidden;
	}

	/**
	 * Show attached accounts for master.
	 *
	 * @param int The user uid
	 * @return string The attached users
	 */
	public function master_userlist($user)
	{
		global $attachedPostUser, $userUid, $userAvatar, $attachedUser, $as_postcount, $user_profilefield, $mybb_asset_url;

		$as_user_userbit = '';
		$mybb_asset_url = $this->mybb->asset_url;
		$accounts = $this->accountswitcher_cache;
		if (is_array($accounts))
		{
			// Sort accounts by first, secondary, shared accounts and by uid or username
			$accounts = $this->sort_attached();

			// Get all attached accounts
			foreach ($accounts as $key => $account)
			{
				$userUid = (int)$account['uid'];
				$attachedPostUser = htmlspecialchars_uni($account['username']);
				$userAvatar = $this->attached_avatar($account['avatar'], $account['avatardimensions']);

				if ($account['as_uid'] == $user)
				{
					// Hide users with privacy setting enabled
					if ($this->mybb->usergroup['cancp'] != 1 && $this->mybb->user['uid'] != $account['uid'] && $this->mybb->settings['aj_privacy'] == 1 && $account['as_privacy'] == 1)
					{
						if (($this->mybb->user['as_uid'] != 0 && $this->mybb->user['as_uid'] != $account['as_uid'] && $this->mybb->user['as_uid'] != $account['uid'])
						|| ($this->mybb->user['as_uid'] == 0 && $this->mybb->user['uid'] != $account['as_uid']))
						{
							continue;
						}
					}

					if (THIS_SCRIPT == "showthread.php")
					{	// Add to postcount
						global $postId;
						$as_postcount += (int)$account['postnum'];
					}

					if ($user == $this->mybb->user['uid'])
					{
						// Set username styles
						if ($this->mybb->settings['aj_sharestyle'] == 1 && $account['as_share'] != 0)
						{
							$attachedUser = eval($this->templates->render('accountswitcher_shared_accountsbit'));
						}
						elseif ($this->mybb->settings['aj_secstyle'] == 1 && $account['as_sec'] != 0 && $account['as_share'] == 0)
						{
							$user_sec_reason = htmlspecialchars_uni($account['as_secreason']);
							$attachedUser = eval($this->templates->render('accountswitcher_sec_accountsbit'));
						}
						else
						{
							$attachedUser = format_name($attachedPostUser, (int)$account['usergroup'], (int)$account['displaygroup']);
						}

						// Load userbits
						if (THIS_SCRIPT == "member.php" && $this->mybb->input['action'] == 'profile')
						{
							$as_user_userbit .= eval($this->templates->render('accountswitcher_profile_switch'));
						}
						if (THIS_SCRIPT == "memberlist.php")
						{
							$as_user_userbit .= eval($this->templates->render('accountswitcher_memberlist_switch'));
						}
						if (THIS_SCRIPT == "showthread.php" || THIS_SCRIPT == "private.php")
						{
							$as_user_userbit .= eval($this->templates->render('accountswitcher_postbit_switch'));
						}
					}
					else
					{
						// Set username styles
						if ($this->mybb->settings['aj_sharestyle'] == 1 && $account['as_share'] != 0)
						{
							$attachedUser = eval($this->templates->render('accountswitcher_shared_accountsbit'));
						}
						elseif ($this->mybb->settings['aj_secstyle'] == 1 && $account['as_sec'] != 0 && $account['as_share'] == 0)
						{
							$user_sec_reason = htmlspecialchars_uni($account['as_secreason']);
							$attachedUser = eval($this->templates->render('accountswitcher_sec_accountsbit'));
						}
						else
						{
							$attachedUser = format_name($attachedPostUser, (int)$account['usergroup'], (int)$account['displaygroup']);
						}
						// Load userbits
						$attachedUser = build_profile_link($attachedUser, $userUid);
						if (THIS_SCRIPT == "member.php" && $this->mybb->input['action'] == 'profile')
						{
							$as_user_userbit .= eval($this->templates->render('accountswitcher_profile_link'));
						}
						if (THIS_SCRIPT == "memberlist.php")
						{
							$as_user_userbit .= eval($this->templates->render('accountswitcher_memberlist_link'));
						}
						if (THIS_SCRIPT == "showthread.php" || THIS_SCRIPT == "private.php")
						{
							$as_user_userbit .= eval($this->templates->render('accountswitcher_postbit_link'));
						}
					}
				}
			}
		}
		return $as_user_userbit;
	}

	/**
	 * Show master and attached accounts if account is not a master.
	 *
	 * @param int The user uid
	 * @return string The attached users
	 */
	public function attached_userlist($user)
	{
		global $attachedPostUser, $userUid, $masterUid, $userAvatar, $attachedUser, $as_postcount, $mybb_asset_url;

		$as_user_userbit = '';
		$accounts = $this->accountswitcher_cache;
		$mybb_asset_url = $this->mybb->asset_url;
		if (is_array($accounts))
		{
			// Sort accounts by first, secondary, shared accounts and by uid or username
			$accounts = $this->sort_attached();

			// Get all attached accounts
			foreach ($accounts as $key => $account)
			{
				$userUid = (int)$account['uid'];
				$attachedPostUser = htmlspecialchars_uni($account['username']);
				$userAvatar = $this->attached_avatar($account['avatar'], $account['avatardimensions']);

				// Leave current user out
				if ($account['uid'] == $user)
				{
					continue;
				}

				if ($account['as_uid'] == $masterUid)
				{
					// Hide users with privacy setting enabled
					if ($this->mybb->usergroup['cancp'] != 1 && $this->mybb->user['uid'] != $account['uid'] && $this->mybb->settings['aj_privacy'] == 1 && $account['as_privacy'] == 1)
					{
						if (($this->mybb->user['as_uid'] != 0 && $this->mybb->user['as_uid'] != $account['as_uid'] && $this->mybb->user['as_uid'] != $account['uid'])
						|| ($this->mybb->user['as_uid'] == 0 && $this->mybb->user['uid'] != $account['as_uid']))
						{
							continue;
						}
					}

					if (THIS_SCRIPT == "showthread.php")
					{	// Add to postcount
						global $postId;
						$as_postcount += (int)$account['postnum'];
					}
					if ($user == $this->mybb->user['uid'])
					{
						// Set username styles
						if ($this->mybb->settings['aj_sharestyle'] == 1 && $account['as_share'] != 0)
						{
							$attachedUser = eval($this->templates->render('accountswitcher_shared_accountsbit'));
						}
						elseif ($this->mybb->settings['aj_secstyle'] == 1 && $account['as_sec'] != 0 && $account['as_share'] == 0)
						{
							$user_sec_reason = htmlspecialchars_uni($account['as_secreason']);
							$attachedUser = eval($this->templates->render('accountswitcher_sec_accountsbit'));
						}
						else
						{
							$attachedUser = format_name($attachedPostUser, (int)$account['usergroup'], (int)$account['displaygroup']);
						}

						// Load userbits
						if (THIS_SCRIPT == "member.php" && $this->mybb->input['action'] == 'profile')
						{
							$as_user_userbit .= eval($this->templates->render('accountswitcher_profile_switch'));
						}
						if (THIS_SCRIPT == "memberlist.php")
						{
							$as_user_userbit .= eval($this->templates->render('accountswitcher_memberlist_switch'));
						}
						if (THIS_SCRIPT == "showthread.php" || THIS_SCRIPT == "private.php")
						{
							$as_user_userbit .= eval($this->templates->render('accountswitcher_postbit_switch'));
						}
					}
					else
					{
						// Set username styles
						if ($this->mybb->settings['aj_sharestyle'] == 1 && $account['as_share'] != 0)
						{
							$attachedUser = eval($this->templates->render('accountswitcher_shared_accountsbit'));
						}
						elseif ($this->mybb->settings['aj_secstyle'] == 1 && $account['as_sec'] != 0 && $account['as_share'] == 0)
						{
							$user_sec_reason = htmlspecialchars_uni($account['as_secreason']);
							$attachedUser = eval($this->templates->render('accountswitcher_sec_accountsbit'));
						}
						else
						{
							$attachedUser = format_name($attachedPostUser, (int)$account['usergroup'], (int)$account['displaygroup']);
						}
						// Load userbits
						$attachedUser = build_profile_link($attachedUser, $userUid);
						if (THIS_SCRIPT == "member.php" && $this->mybb->input['action'] == 'profile')
						{
							$as_user_userbit .= eval($this->templates->render('accountswitcher_profile_link'));
						}
						if (THIS_SCRIPT == "memberlist.php")
						{
							$as_user_userbit .= eval($this->templates->render('accountswitcher_memberlist_link'));
						}
						if (THIS_SCRIPT == "showthread.php" || THIS_SCRIPT == "private.php")
						{
							$as_user_userbit .= eval($this->templates->render('accountswitcher_postbit_link'));
						}
					}
				}
			}
		}
		return $as_user_userbit;
	}

	/**
	 * Show shared accounts.
	 *
	 * @param int The userbit id
	 * @return string The shared users
	 */
	public function shared_userlist($userbit)
	{
		global $attachedPostUser, $userUid, $userAvatar, $attachedUser, $mybb_asset_url, $buddylist;

		$as_user_sharebit = '';
		$accounts = $this->accountswitcher_cache;
		$mybb_asset_url = $this->mybb->asset_url;
		if (is_array($accounts))
		{
			// Sort accounts by first, secondary, shared accounts and by uid or username
			$accounts = $this->sort_attached();

			// Get all attached accounts
			foreach ($accounts as $key => $account)
			{
				$userUid = (int)$account['uid'];
				$attachedPostUser = htmlspecialchars_uni($account['username']);
				$userAvatar = $this->attached_avatar($account['avatar'], $account['avatardimensions']);

				// Get shared accounts buddylist
				if ($account['as_share'] == 1 && $account['as_buddyshare'] == 1)
				{
					if ($account['buddylist'] != '')
					{
						$buddylist = explode(",", $account['buddylist']);
					}
				}

				// Leave current user out
				if ($account['uid'] == $this->mybb->user['uid'])
				{
					continue;
				}
				if ($this->mybb->settings['aj_shareuser'] == 1 && (($account['as_share'] != 0 && $account['as_shareuid'] == 0 && $account['as_buddyshare'] == 0) || ($account['as_shareuid'] != 0 && $account['as_shareuid'] == $this->mybb->user['uid']) || ($account['as_share'] != 0 && $account['as_shareuid'] == 0 && $account['as_buddyshare'] != 0 && !empty($buddylist) && in_array($this->mybb->user['uid'], $buddylist))))
				{
					// Set username styles
					if ($this->mybb->settings['aj_sharestyle'] == 1 && $account['as_share'] != 0)
					{
						$attachedUser = eval($this->templates->render('accountswitcher_shared_accountsbit'));
					}
					elseif ($this->mybb->settings['aj_secstyle'] == 1 && $account['as_sec'] != 0 && $account['as_share'] == 0)
					{
						$user_sec_reason = htmlspecialchars_uni($account['as_secreason']);
						$attachedUser = eval($this->templates->render('accountswitcher_sec_accountsbit'));
					}
					else
					{
						$attachedUser = format_name($attachedPostUser, (int)$account['usergroup'], (int)$account['displaygroup']);
					}
					// Load userbits
					switch($userbit)
					{
						case 1:
						{
							$as_user_sharebit .= eval($this->templates->render('accountswitcher_header_userbit'));
						}
						break;
						case 2:
						{
							$as_user_sharebit .= eval($this->templates->render('accountswitcher_header_dropdown_userbit'));
						}
						break;
						case 3:
						{
							$as_user_sharebit .= eval($this->templates->render('accountswitcher_sidebar_userbit'));
						}
						break;
						case 4:
						{
							$as_user_sharebit .= eval($this->templates->render('accountswitcher_post_userbit'));
						}
						break;
					}
				}
			}
		}
		return $as_user_sharebit;
	}

	/**
	 * Get the specified profile field of the user.
	 *
	 * @param int The user uid
	 * @param boolean Whether or not the account is attached
	 * @return string The profile field
	 */
	public function get_profilefield($user, $attached=false)
	{
		global $profilefield_attached, $profile_name, $profile_head;

		$profilefield = $profilefield_attached = $profile_name = '';
		if ($this->mybb->settings['aj_profilefield'] == 1 && (int)$this->mybb->settings['aj_profilefield_id'] > 0)
		{
			// Get profile field id from plugin settings
			$field_id = (int)$this->mybb->settings['aj_profilefield_id'];

			// Get profile field parser options from cache
			$pfcache = $this->cache->read('profilefields');

			if (is_array($pfcache))
			{
				foreach ($pfcache as $customfield)
				{
					// We need only the selected field
					if ($customfield['fid'] == $field_id)
					{
						$parser_options = array(
							"allow_html" => $customfield['allowhtml'],
							"allow_mycode" => $customfield['allowmycode'],
							"allow_smilies" => $customfield['allowsmilies'],
							"allow_imgcode" => $customfield['allowimgcode'],
							"allow_videocode" => $customfield['allowvideocode'],
							#"nofollow_on" => 1,
							"filter_badwords" => 1
						);
						$profile_name = htmlspecialchars_uni($customfield['name']);
						$profile_head = eval($this->templates->render('accountswitcher_profilefield_head'));
						$viewableby = $customfield['viewableby'];
					}
				}
			}

			// Get profile field
			$ufields = $this->userfields_cache;
			if (is_array($ufields))
			{
				foreach ($ufields as $field)
				{
					if ($field['uid'] == $user)
					{
						$userfield = $field['fid'];
					}
				}
				if (!empty($userfield) && trim($userfield) != '')
				{
					require_once MYBB_ROOT."inc/class_parser.php";
					$parser = new postParser;
					$profilefield = $parser->parse_message($userfield, $parser_options);
				}
			}
			if ($attached == true)
			{
				$profilefield_attached = eval($this->templates->render('accountswitcher_profilefield_attached'));
			}
			$profilefield = eval($this->templates->render('accountswitcher_profilefield'));
		}
		return $profilefield;
	}
}
