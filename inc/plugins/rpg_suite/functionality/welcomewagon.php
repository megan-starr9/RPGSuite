<?php
/**
 * MyBB 1.8
 * Copyright 2014 MyBB Group, All Rights Reserved
 *
 * Website: http://www.mybb.com
 * License: http://www.mybb.com/about/license
 *
 */

require_once MYBB_ROOT."/inc/plugins/rpg_suite/models/class_UserGroup.php";
require_once MYBB_ROOT."/inc/plugins/rpg_suite/models/class_GroupMember.php";
require_once MYBB_ROOT."/inc/plugins/rpg_suite/models/class_RPGSuite.php";
require_once MYBB_ROOT."/inc/plugins/rpg_suite/rpgsuite_defaults.php";
require_once MYBB_ROOT."inc/datahandlers/pm.php";

 /**
 For sending welcome pm and placing in default usergroup on activation
 **/

// When user is ready, send PM notifying that admin will approve and add to Awaiting Activation group
function apply() {
  global $mybb, $db, $cache, $lang;
  if($mybb->settings['rpgsuite_approval']) {
    $user = new GroupMember($mybb, $db, $cache, $mybb->user);

    $user->update_member(array('usergroup' => Groups::WAITING));

    header('Location: '.$_SERVER['REQUEST_URI']);

    if(!empty($mybb->settings['rpgsuite_approval_registerpm'])) {
      $pm_handler = new PMDataHandler();
      $pm_handler->admin_override = true;
      $pm = array(
          "subject" => $mybb->settings['rpgsuite_approval_registerpm_subj'],
          "message" => $mybb->settings['rpgsuite_approval_registerpm'],
        "fromid" => Accounts::ADMIN,
        "options" => array(
          "savecopy" => "0"),
        );
      $pm['to'] = array($user_info['username']);
      $pm_handler->set_data($pm);

      if(!$pm_handler->validate_pm())
      {
         //bad pm. oops. lol
      } else {
         $pm_handler->insert_pm();
      }
    } 
  }
}


// On approval, add to group and send PM
function approve($userid, $username, $type) {
  global $mybb, $db, $cache, $lang;
  $user = new GroupMember($mybb, $db, $cache);
  $user->initialize($userid);

  $user->update_member(array('usergroup' => Groups::MEMBER));

  //determine the group
  $gid = Groups::IC_DEFAULT;
  if(strpos($type,'Nonwolf') !== false) {
    // Character is wild fauna
    $gid = Groups::WILDFAUNA;
  } else if(strpos($type,'Lurker') !== false) {
    // Character is Lurker
    $gid = Groups::LURKER;
  } else if(strpos($type,'Inactive') !== false) {
    // Character is inactive reservation
    $gid = Groups::MEMBER;
  }
  $group = new UserGroup($mybb, $db, $cache);
  $group->initialize($gid);
  $group->add_member($userid);

  if(!empty($mybb->settings['rpgsuite_approval_approvepm'])) {
    $pm_handler = new PMDataHandler();
    $pm_handler->admin_override = true;
    $pm = array(
        "subject" => $mybb->settings['rpgsuite_approval_approvepm_subj'],
        "message" => $mybb->settings['rpgsuite_approval_approvepm'],
      "fromid" => Accounts::ADMIN,
      "options" => array(
        "savecopy" => "0"),
      );
    $pm['to'] = array($username);
    $pm_handler->set_data($pm);

    if(!$pm_handler->validate_pm())
    {
       //bad pm. oops. lol
    } else {
       $pm_handler->insert_pm();
    }
  }
}

// On deny, simply add to default OOC group & send pm
function deny($userid, $username) {
  global $mybb, $db, $cache;

  $user = new GroupMember($mybb, $db, $cache);
  $user->initialize($userid);

  $user->update_member(array('usergroup' => Groups::UNAPPROVED));

  if(!empty($mybb->settings['rpgsuite_approval_denypm'])) {
    $pm_handler = new PMDataHandler();
    $pm_handler->admin_override = true;
    $pm = array(
        "subject" => $mybb->settings['rpgsuite_approval_denypm_subj'],
        "message" => $mybb->settings['rpgsuite_approval_denypm'],
      "fromid" => Accounts::ADMIN,
      "options" => array(
        "savecopy" => "0"),
      );
    $pm['to'] = array($username);
    $pm_handler->set_data($pm);

    if(!$pm_handler->validate_pm())
    {
       //bad pm. oops. lol
    } else {
       $pm_handler->insert_pm();
    }
  }
}

$plugins->add_hook('global_start', 'display_queue');
function display_queue() {
  global $mybb, $db, $cache, $templates, $approvalalert, $parser;
  if($mybb->settings['rpgsuite_approval']) {
    $currentuser = new GroupMember($mybb, $db, $cache, $mybb->user);
    if($currentuser->is_admin()) {
      $rpgsuite = new RPGSuite($mybb, $db, $cache);
      $waiting_count = count($rpgsuite->get_awaiting_approval());
      if($waiting_count > 0) {
        eval("\$approvalalert = \"".$templates->get('rpgapprove_notification')."\";");
      }
    } else if($currentuser->get_info()['usergroup'] == Groups::UNAPPROVED) {
      if(isset($mybb->input['apply'])) {
        apply();
      }
    }
  }
}

$plugins->add_hook('index_start', 'queue_details');
function queue_details() {
  global $mybb, $db, $cache, $templates, $approval_page, $theme, $lang, $header, $headerinclude, $footer, $parser;
  if($mybb->settings['rpgsuite_approval']) {
    $currentuser = new GroupMember($mybb, $db, $cache, $mybb->user);
    if($currentuser->is_admin() && $mybb->input['action'] == 'activationqueue') {

      if($mybb->request_method == "post") {
        $userid = (int) $mybb->input['userid'];
        $username = $db->escape_string($mybb->input['username']);
        $type = $mybb->input['type'];
        if(isset($mybb->input['approve'])) {
          approve($userid, $username, $type);
        } else if(isset($mybb->input['deny'])) {
          deny($userid, $username);
        }
      }
      add_breadcrumb('Approve New Members');

      $rpgsuite = new RPGSuite($mybb, $db, $cache);
      $accounts = $rpgsuite->get_awaiting_approval();

      foreach($accounts as $user) {
        eval("\$userlist .= \"".$templates->get("rpgapprove_user")."\";");
      }

      eval("\$approval_page = \"".$templates->get("rpgapprove_page")."\";");

      output_page($approval_page);
      exit;
    }
  }
}
