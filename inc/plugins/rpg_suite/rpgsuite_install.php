<?php
if(!defined("IN_MYBB"))
{
    die("You Cannot Access This File Directly. Please Make Sure IN_MYBB Is Defined.");
}

/**
Install the Plugin
*/
function rpgsuite_install() {
  global $db;
  // Create Settings
  $settinggroup = array(
      'name'  => 'rpgsuite',
      'title'      => 'RPG Suite',
      'description'    => 'Settings For RPG Suite',
      'disporder'    => "1",
      'isdefault'  => "0",
  );
  $db->insert_query('settinggroups', $settinggroup);
  $gid = $db->insert_id();

  $settings = build_settings($gid);
  foreach($settings as $setting) {
		$db->insert_query('settings', $setting);
	}
	rebuild_settings();

  // Create the custom tables (but first ensure they aren't there already)
  destroy_tables();
  create_tables();

  // We are going to have multiple groups, so we will split this off into another file
  create_templates();
}

/**
Check if Plugin is installed
*/
function rpgsuite_is_installed() {
  global $db;
	return $db->simple_select("settinggroups", "*","name ='rpgsuite'")->num_rows;
}

/**
Uninstall
*/
function rpgsuite_uninstall() {
  global $db;
  // Delete settings
	$db->query("DELETE FROM ".TABLE_PREFIX."settings WHERE name LIKE 'rpgsuite_%'");
	$db->query("DELETE FROM ".TABLE_PREFIX."settinggroups WHERE name='rpgsuite'");
	rebuild_settings();

  // Get rid of custom tables
  destroy_tables();
  // Get rid of templates!
   destroy_templates();
}

/**
Activate Plugin
*/
function rpgsuite_activate() {
  global $db;

  reverse_template_edits();
  apply_template_edits();

  // Add any tables for upgrading
  if(!$db->table_exists("otms"))
    $db->write_query("CREATE TABLE ".TABLE_PREFIX."otms (id int(11) NOT NULL AUTO_INCREMENT, name VARCHAR(500), type VARCHAR(100), value VARCHAR(2000), PRIMARY KEY(id))");
  if (!$db->field_exists("last_activated", "users"))
    $db->write_query("ALTER TABLE `".TABLE_PREFIX."users` ADD COLUMN `last_activated` BIGINT(30)");

  // If we have new settings, add them!
  $settinggroup = $db->simple_select('settinggroups','gid','name = \'rpgsuite\'');
  $group = $db->fetch_array($settinggroup);
  $settings = build_settings($group['gid']);
  foreach($settings as $setting) {
    $settingquery = $db->simple_select('settings','sid','name = \''.$setting['name'].'\'');
    if(!$settingquery->num_rows) {
      $db->insert_query('settings', $setting);
    }
	}
	rebuild_settings();

  // If we have new templates, add them, but only if they exist!
  // Lonely Thread Templates
  require_once MYBB_ROOT."/inc/plugins/rpg_suite/templatesets/class_LonelyThreadSet.php";
  $templateset = new LonelyThreadSet($db);
  $templateset->create();
  // OTM Templates
  require_once MYBB_ROOT."/inc/plugins/rpg_suite/templatesets/class_OtmSet.php";
  $templateset = new OtmSet($db);
  $templateset->create();
}

/**
Deactivate Plugin
*/
function rpgsuite_deactivate() {
  reverse_template_edits();
}

/**
Helper functions!
**/
/**
Build out the settings array
**/
function build_settings($gid) {
  require_once MYBB_ROOT."/inc/plugins/rpg_suite/rpgsuite_settings.php";
  $settingarray = array();
  $settings = settings();
  $i = 0;
  foreach($settings as $setting) {
    $settingarray[] = array(
      'name'        => 'rpgsuite_'.$setting['name'],
      'title'            => $setting['title'],
      'description'    => $setting['description'],
      'optionscode'    => $setting['type'],
      'value'        => $setting['default'],
      'disporder'        => $i++,
      'gid'            => intval($gid)
    );
  }
  return $settingarray;
}

/**
Create any custom tables or columns
**/
function create_tables() {
  global $db;
  //Create tables
  $db->write_query("CREATE TABLE ".TABLE_PREFIX."tickers (id INT(4), last_run BIGINT(30))");
  $db->write_query("CREATE TABLE ".TABLE_PREFIX."icgroups (gid INT(11), fid INT(11), mo_fid INT(11), founded BIGINT(30), activitycheck INT(1) DEFAULT 1, grouppoints INT(11) DEFAULT 0, hasranks INT(1) DEFAULT 1, activityperiod INT(11) DEFAULT 7, defaultrank INT(11) DEFAULT 0)");
  $db->write_query('CREATE TABLE '.TABLE_PREFIX.'groupfields (fid INT(11) NOT NULL AUTO_INCREMENT, name VARCHAR(200), description VARCHAR(500), disporder INT(11), type VARCHAR(100), onlyadmin INT(1) DEFAULT 0, PRIMARY KEY(fid))');
  $db->write_query('CREATE TABLE '.TABLE_PREFIX.'groupfield_values (gid INT(11))');
  $db->write_query("CREATE TABLE ".TABLE_PREFIX."groupranks (id int(11) NOT NULL AUTO_INCREMENT, seq int(11) NOT NULL DEFAULT '0', tid int(11) NOT NULL DEFAULT '0', label varchar(200), visible int(1) NOT NULL DEFAULT '1', split_dups int(1) NOT NULL DEFAULT '1', dups int(11) NOT NULL DEFAULT '1', ignoreactivitycheck int(1) NOT NULL DEFAULT 0, PRIMARY KEY(id))");
  $db->write_query("CREATE TABLE ".TABLE_PREFIX."grouptiers (id int(11) NOT NULL AUTO_INCREMENT, seq int(11) NOT NULL DEFAULT '0', label varchar(200), gid int(11) NOT NULL DEFAULT 0, PRIMARY KEY(id))");
  $db->write_query("CREATE TABLE ".TABLE_PREFIX."otms (id int(11) NOT NULL AUTO_INCREMENT, name VARCHAR(500), type VARCHAR(100), value VARCHAR(2000), PRIMARY KEY(id))");

  //Add Columns
  $db->write_query("ALTER TABLE `".TABLE_PREFIX."forums` ADD COLUMN `icforum` INT(1) NOT NULL DEFAULT '0'");
  $db->write_query("ALTER TABLE `".TABLE_PREFIX."usergroups` ADD COLUMN `icgroup` INT(1) NOT NULL DEFAULT '0'");
  $db->write_query("ALTER TABLE `".TABLE_PREFIX."users` ADD COLUMN `grouprank` INT(11) NOT NULL DEFAULT '0'");
  $db->write_query("ALTER TABLE `".TABLE_PREFIX."users` ADD COLUMN `grouprank_text` VARCHAR(100) NOT NULL DEFAULT ''");
  $db->write_query("ALTER TABLE `".TABLE_PREFIX."users` ADD COLUMN `group_dateline` BIGINT(30)");
  $db->write_query("ALTER TABLE `".TABLE_PREFIX."users` ADD COLUMN `last_activated` BIGINT(30)");

  //wolf_specific_inserts();
}
/**
Delete any custom tables or columns
**/
function destroy_tables() {
  global $db;
  //Delete tables
  $db->write_query("DROP TABLE IF EXISTS ".TABLE_PREFIX."tickers");
  $db->write_query("DROP TABLE IF EXISTS ".TABLE_PREFIX."icgroups");
  $db->write_query("DROP TABLE IF EXISTS ".TABLE_PREFIX."groupfields");
  $db->write_query("DROP TABLE IF EXISTS ".TABLE_PREFIX."groupfield_values");
  $db->write_query("DROP TABLE IF EXISTS ".TABLE_PREFIX."groupranks");
  $db->write_query("DROP TABLE IF EXISTS ".TABLE_PREFIX."grouptiers");
  $db->write_query("DROP TABLE IF EXISTS ".TABLE_PREFIX."otms");

  //Delete columns
  if($db->field_exists("icforum", "forums"))
    $db->write_query("ALTER TABLE `".TABLE_PREFIX."forums` DROP COLUMN `icforum`");
  if($db->field_exists("icgroup", "usergroups"))
    $db->write_query("ALTER TABLE `".TABLE_PREFIX."usergroups` DROP COLUMN `icgroup`");
  if ($db->field_exists("grouprank", "users"))
    $db->write_query("ALTER TABLE `".TABLE_PREFIX."users` DROP COLUMN `grouprank`");
  if ($db->field_exists("grouprank_text", "users"))
    $db->write_query("ALTER TABLE `".TABLE_PREFIX."users` DROP COLUMN `grouprank_text`");
  if ($db->field_exists("group_dateline", "users"))
    $db->write_query("ALTER TABLE `".TABLE_PREFIX."users` DROP COLUMN `group_dateline`");
  if ($db->field_exists("last_activated", "users"))
    $db->write_query("ALTER TABLE `".TABLE_PREFIX."users` DROP COLUMN `last_activated`");
}

/**
Call the separate template group creations
**/
function create_templates() {
  global $db;
  $templatesets = array();

  // Ranktable Templates
  require_once MYBB_ROOT."/inc/plugins/rpg_suite/templatesets/class_RanktableSet.php";
  $templatesets[] = new RanktableSet($db);
  // Threadlog Templates
  require_once MYBB_ROOT."/inc/plugins/rpg_suite/templatesets/class_ThreadlogSet.php";
  $templatesets[] = new ThreadlogSet($db);
  // Group Manage CP Templates
  require_once MYBB_ROOT."/inc/plugins/rpg_suite/templatesets/class_GroupManageCPSet.php";
  $templatesets[] = new GroupManageCPSet($db);
  // Group Stats Templates
  require_once MYBB_ROOT."/inc/plugins/rpg_suite/templatesets/class_GroupStatsSet.php";
  $templatesets[] = new GroupStatsSet($db);
  // Forum Display Templates
  require_once MYBB_ROOT."/inc/plugins/rpg_suite/templatesets/class_ForumDisplaySet.php";
  $templatesets[] = new ForumDisplaySet($db);
  // Account Approval Templates
  require_once MYBB_ROOT."/inc/plugins/rpg_suite/templatesets/class_WelcomeWagonSet.php";
  $templatesets[] = new WelcomeWagonSet($db);
  // Misc Templates
  require_once MYBB_ROOT."/inc/plugins/rpg_suite/templatesets/class_MiscSet.php";
  $templatesets[] = new MiscSet($db);
  // Lonely Thread Templates
  require_once MYBB_ROOT."/inc/plugins/rpg_suite/templatesets/class_LonelyThreadSet.php";
  $templatesets[] = new LonelyThreadSet($db);
  // OTM Templates
  require_once MYBB_ROOT."/inc/plugins/rpg_suite/templatesets/class_OtmSet.php";
  $templatesets[] = new OtmSet($db);

  foreach($templatesets as $templateset) {
    $templateset->destroy(); // make sure it's not there first!
    $templateset->create();
  }
}
/**
Call the separate template group deletions
**/
function destroy_templates() {
  global $db;
  $templatesets = array();

  // Ranktable Templates
  require_once MYBB_ROOT."/inc/plugins/rpg_suite/templatesets/class_RanktableSet.php";
  $templatesets[] = new RanktableSet($db);
  // Threadlog Templates
  require_once MYBB_ROOT."/inc/plugins/rpg_suite/templatesets/class_ThreadlogSet.php";
  $templatesets[] = new ThreadlogSet($db);
  // Group Manage CP Templates
  require_once MYBB_ROOT."/inc/plugins/rpg_suite/templatesets/class_GroupManageCPSet.php";
  $templatesets[] = new GroupManageCPSet($db);
  // Group Stats Templates
  require_once MYBB_ROOT."/inc/plugins/rpg_suite/templatesets/class_GroupStatsSet.php";
  $templatesets[] = new GroupStatsSet($db);
  // Forum Display Templates
  require_once MYBB_ROOT."/inc/plugins/rpg_suite/templatesets/class_ForumDisplaySet.php";
  $templatesets[] = new ForumDisplaySet($db);
  // Account Approval Templates
  require_once MYBB_ROOT."/inc/plugins/rpg_suite/templatesets/class_WelcomeWagonSet.php";
  $templatesets[] = new WelcomeWagonSet($db);
  // Misc Templates
  require_once MYBB_ROOT."/inc/plugins/rpg_suite/templatesets/class_MiscSet.php";
  $templatesets[] = new MiscSet($db);
  // Lonely Thread Templates
  require_once MYBB_ROOT."/inc/plugins/rpg_suite/templatesets/class_LonelyThreadSet.php";
  $templatesets[] = new LonelyThreadSet($db);
  // OTM Templates
  require_once MYBB_ROOT."/inc/plugins/rpg_suite/templatesets/class_OtmSet.php";
  $templatesets[] = new OtmSet($db);


  foreach($templatesets as $templateset) {
    $templateset->destroy();
  }
}

/**
Apply any edits to existing templates
**/
function apply_template_edits() {
  //forumdisplay modification

  //stats modification

  //header approval notification

}
/**
Reverse any edits made to existing templates
**/
function reverse_template_edits() {
  //forumdisplay modification

  //stats modification

  //header approval notification

}

function wolf_specific_inserts() {
  global $db;
  $db->insert_query('groupfields', array('name' => 'Primary Color', 'type' => 'text', 'description' => 'The primary color for the group.', 'onlyadmin' => 1));
  $db->insert_query('groupfields', array('name' => 'Secondary Color', 'type' => 'text', 'description' => 'The secondary color for the group.', 'onlyadmin' => 1));
  $db->insert_query('groupfields', array('name' => 'Pack Banner URL', 'type' => 'text', 'description' => 'The url for the group banner.', 'onlyadmin' => 1));

  $db->write_query("ALTER TABLE `".TABLE_PREFIX."groupfield_values` ADD COLUMN `fid1` VARCHAR(500)");
  $db->write_query("ALTER TABLE `".TABLE_PREFIX."groupfield_values` ADD COLUMN `fid2` VARCHAR(500)");
  $db->write_query("ALTER TABLE `".TABLE_PREFIX."groupfield_values` ADD COLUMN `fid3` VARCHAR(500)");

  $db->insert_query('grouptiers', array('seq' => 1, 'label' => 'Upper Tier'));
  $db->insert_query('grouptiers', array('seq' => 2, 'label' => 'Middle Tier'));
  $db->insert_query('grouptiers', array('seq' => 3, 'label' => 'Lower Tier'));
  $db->insert_query('grouptiers', array('seq' => 4, 'label' => 'Youth Tier'));
  $db->insert_query('grouptiers', array('seq' => 5, 'label' => 'Non-Player Characters'));

  $db->insert_query('groupranks', array('seq' => 1, 'label' => 'Alpha', 'tid' => 1, 'dups' => 2));
  $db->insert_query('groupranks', array('seq' => 2, 'label' => 'Beta', 'tid' => 1, 'dups' => 2));
  $db->insert_query('groupranks', array('seq' => 3, 'label' => 'Gamma', 'tid' => 2));
  $db->insert_query('groupranks', array('seq' => 4, 'label' => 'Delta', 'tid' => 2));
  $db->insert_query('groupranks', array('seq' => 5, 'label' => 'Epsilon', 'tid' => 2));
  $db->insert_query('groupranks', array('seq' => 6, 'label' => 'Zeta', 'tid' => 2));
  $db->insert_query('groupranks', array('seq' => 7, 'label' => 'Eta', 'tid' => 3));
  $db->insert_query('groupranks', array('seq' => 8, 'label' => 'Theta', 'tid' => 3));
  $db->insert_query('groupranks', array('seq' => 9, 'label' => 'Iota', 'tid' => 3));
  $db->insert_query('groupranks', array('seq' => 10, 'label' => 'Kappa', 'tid' => 3));
  $db->insert_query('groupranks', array('seq' => 11, 'label' => 'Lambda', 'tid' => 3, 'visible' => 0));
  $db->insert_query('groupranks', array('seq' => 12, 'label' => 'Mu', 'tid' => 3, 'visible' => 0));
  $db->insert_query('groupranks', array('seq' => 13, 'label' => 'Xi', 'tid' => 4));
  $db->insert_query('groupranks', array('seq' => 14, 'label' => 'Omicron', 'tid' => 4));
  $db->insert_query('groupranks', array('seq' => 15, 'label' => 'Pi', 'tid' => 4));
  $db->insert_query('groupranks', array('seq' => 16, 'label' => 'Rho', 'tid' => 4));
  $db->insert_query('groupranks', array('seq' => 17, 'label' => 'Sigma', 'tid' => 4));
  $db->insert_query('groupranks', array('seq' => 18, 'label' => 'Tau', 'tid' => 4));
  $db->insert_query('groupranks', array('seq' => 19, 'label' => 'PPC', 'tid' => 5, 'visible' => 0, 'ignoreactivitycheck' => 1));

}
