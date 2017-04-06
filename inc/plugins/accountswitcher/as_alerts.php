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

// Define MyAlerts path
if (!defined('MYALERTS_PLUGIN_PATH'))
{
	define('MYALERTS_PLUGIN_PATH', MYBB_ROOT . 'inc/plugins/MyAlerts/');
}

// Error handling
if (!class_exists('MybbStuff_MyAlerts_Formatter_AbstractFormatter'))
{
	global $mybb, $lang;
	$lang->load("accountswitcher");

	die(print($lang->aj_alert_error_admin));

}

// Add hook for alert type settings in User CP
$plugins->add_hook('myalerts_load_lang','accountswitcher_alerts_settings');
/**
 * Change alert settings description for account switcher if user has no permission to use the account switcher
 *
 */
function accountswitcher_alerts_settings()
{
	global $mybb, $lang, $alertSettings;

	if ($mybb->usergroup['as_canswitch'] != 1)
	{
		$lang->myalerts_setting_accountswitcher_pm = '<span style="text-decoration: line-through;">'.$lang->myalerts_setting_accountswitcher_pm.'</span>';
	}

}

// Add hook for displaying author alert
$plugins->add_hook('global_start','accountswitcher_alerts_display_author');

/**
 * Display alert for old and new author after moderating post author.
 *
 */
function accountswitcher_alerts_display_author()
{
	global $mybb, $lang;

	if ($mybb->user['uid'] && $mybb->settings['aj_myalerts'] == 1 && isset($mybb->settings['myalerts_avatar_size']) && class_exists('MybbStuff_MyAlerts_AlertFormatterManager'))
	{
		$formatterManager = MybbStuff_MyAlerts_AlertFormatterManager::getInstance();
		if (!$formatterManager)
		{
			$formatterManager = MybbStuff_MyAlerts_AlertFormatterManager::createInstance($mybb, $lang);
		}
		$formatterManager->registerFormatter(new MybbStuff_MyAlerts_Formatter_AccountswitcherAuthorFormatter($mybb, $lang, 'accountswitcher_author'));
	}
}

/**
 * Formatter for alerts after moderating post author.
 *
 */
class MybbStuff_MyAlerts_Formatter_AccountswitcherAuthorFormatter extends MybbStuff_MyAlerts_Formatter_AbstractFormatter
{
	public function formatAlert(MybbStuff_MyAlerts_Entity_Alert $alert, array $outputAlert)
	{
		$alertContent = $alert->getExtraDetails();
		return $this->lang->sprintf(
		$this->lang->aj_author_change_alert,
		$outputAlert['from_user'],
		$alertContent['thread_title'],
		$alertContent['olduser'],
		$alertContent['newuser']
		);
	}

	public function init()
	{
		if (!isset($this->lang->aj_profile)) {
		$this->lang->load('accountswitcher');
		}
	}

	public function buildShowLink(MybbStuff_MyAlerts_Entity_Alert $alert)
	{
		$alertContent = $alert->getExtraDetails();
		$postLink = $this->mybb->settings['bburl'] . '/' . get_post_link(
		(int)$alertContent['pid'],
		(int)$alertContent['tid']
		) . '#pid' . (int) $alertContent['pid'];
		return $postLink;
	}
}

// Hook for the pm send alert
$plugins->add_hook("private_do_send_end", "accountswitcher_pm_sent_alert");
/**
 * Alert all attached accounts if one of them receives a new pm.
 *
 */
function accountswitcher_pm_sent_alert()
{
	global $mybb, $lang, $pm, $eas;

	if ($mybb->settings['aj_myalerts'] != 1 || !isset($mybb->settings['myalerts_perpage']) || $pm['saveasdraft'] == 1)
	{
		return;
	}

	if (!isset($lang->aj_newpm_switch_notice_one))
	{
		$lang->load('accountswitcher');
	}

	// Get recipients
	if (is_array($pm['bcc']))
	{
		$rec_users = array_merge($pm['to'], $pm['bcc']);
	}
	else
	{
		$rec_users = $pm['to'];
	}
	$pm_users = array_map("trim", $rec_users);

	// Alert Type
	$alertType = MybbStuff_MyAlerts_AlertTypeManager::getInstance()->getByCode('accountswitcher_pm');
	$alerts = array();

	foreach ($pm_users as $recipient)
	{
		$count = 0;
		$pmuser = get_user_by_username($recipient);
		$user = get_user($pmuser['uid']);

		$accounts = $eas->accountswitcher_cache;
		if (is_array($accounts))
		{
			// If recipient is master account send alerts to attached users
			foreach ($accounts as $key => $account)
			{
				if ($user['uid'] == $account['as_uid'])
				{
					++$count;
					if ($count > 0)
					{
						$alert = new MybbStuff_MyAlerts_Entity_Alert((int)$account['uid'], $alertType, 0);
						$alert->setExtraDetails(
						array(
							'uid' => (int)$user['uid'],
							'message' => htmlspecialchars_uni($user['username'])
							)
						);
						$alerts[] = $alert;
					}
				}
			}
		}

		// If there are no users attached to the current account but the current account is attached to another user
		if ($count == 0 && $user['as_uid'] != 0)
		{
			$master = get_user((int)$user['as_uid']);
			// Get the masters permission
			$permission = user_permissions($master['uid']);

			// If the master has permission to use the Enhanced Account Switcher, get the userlist
			if ($permission['as_canswitch'] == 1)
			{
				// If recipient is attached account, alert master account
				if ($master['uid'] == $user['as_uid'])
				{
					$alert = new MybbStuff_MyAlerts_Entity_Alert((int)$master['uid'], $alertType, 0);
					$alert->setExtraDetails(
					array(
						'uid' => (int)$user['uid'],
						'message' => htmlspecialchars_uni($user['username'])
						)
					);
					$alerts[] = $alert;
				}
				if (is_array($accounts))
				{
					// If recipient has the same master account, send alert
					foreach ($accounts as $key => $account)
					{
						// Leave recipient out
						if ($account['uid'] == $user['uid'])
						{
							continue;
						}
						if ($master['uid'] == $account['as_uid'])
						{
							$alert = new MybbStuff_MyAlerts_Entity_Alert((int)$account['uid'], $alertType, 0);
							$alert->setExtraDetails(
							array(
								'message' => htmlspecialchars_uni($user['username'])
								)
							);
							$alerts[] = $alert;
						}
					}
				}
			}
		}
		// If there are no users attached to the a recipient and the recipient isn't attached to another user
		if ($count == 0 && $user['as_uid'] == 0)
		{
			$alert = new MybbStuff_MyAlerts_Entity_Alert((int)$user['uid'], $alertType, 0);
			$alert->setExtraDetails(
			array(
				'message' => htmlspecialchars_uni($user['username'])
				)
			);
			$alerts[] = $alert;
		}
		if (!empty($alerts))
		{
			MybbStuff_MyAlerts_AlertManager::getInstance()->addAlerts($alerts);
		}
	}
}

// Add hook for displaying pm alert
$plugins->add_hook('global_start','accountswitcher_alerts_display_pm');

/**
 * Display alert if an attached account has new pm('s).
 *
 */
function accountswitcher_alerts_display_pm()
{
	global $mybb, $lang;

	if ($mybb->user['uid'] && $mybb->settings['aj_myalerts'] == 1 && isset($mybb->settings['myalerts_avatar_size']) && class_exists('MybbStuff_MyAlerts_AlertFormatterManager'))
	{
		$formatterManager = MybbStuff_MyAlerts_AlertFormatterManager::getInstance();
		if (!$formatterManager)
		{
			$formatterManager = MybbStuff_MyAlerts_AlertFormatterManager::createInstance($mybb, $lang);
		}
		$formatterManager->registerFormatter(new MybbStuff_MyAlerts_Formatter_AccountswitcherPMFormatter($mybb, $lang, 'accountswitcher_pm'));
	}
}

/**
 * Formatter for alerts if an attached account has new pm('s).
 *
 */
class MybbStuff_MyAlerts_Formatter_AccountswitcherPMFormatter extends MybbStuff_MyAlerts_Formatter_AbstractFormatter
{
	public function formatAlert(MybbStuff_MyAlerts_Entity_Alert $alert, array $outputAlert)
	{
		$alertContent = $alert->getExtraDetails();
		return $this->lang->sprintf(
		$this->lang->aj_newpm_switch_notice_one,
		$alertContent['message']
		).' ('.my_date('relative', TIME_NOW).')';
	}

	public function init()
	{
		if (!isset($this->lang->aj_profile)) {
		$this->lang->load('accountswitcher');
		}
	}

	public function buildShowLink(MybbStuff_MyAlerts_Entity_Alert $alert)
	{
		global $eas;

		$alertContent = $alert->getExtraDetails();
		$pmLink = $this->mybb->settings['bburl'] . '/alerts.php';
		return $pmLink;
	}
}
