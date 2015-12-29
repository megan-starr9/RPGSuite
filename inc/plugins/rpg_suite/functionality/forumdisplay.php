<?php
if(!defined("IN_MYBB"))
{
  die("You Cannot Access This File Directly. Please Make Sure IN_MYBB Is Defined.");
}

require_once MYBB_ROOT."/inc/plugins/rpg_suite/models/class_RPGSuite.php";

/**
Functionality behind displaying group information in their forums (incl styles)
**/

$plugins->add_hook('forumdisplay_end', 'add_forum_display');

function add_forum_display() {
  global $mybb, $db, $cache, $templates, $foruminfo, $icforumdisplay, $theme, $lang, $moderators, $headerinclude, $parser;

  $rpgsuite = new RPGSuite($mybb, $db, $cache);
  $usergroup = $rpgsuite->retrieve_forum_group($foruminfo['fid']);
  if($usergroup) {
    $group = $usergroup->get_info();
    $group['membertotal'] = count($usergroup->get_members());
    $group['founded_date'] = date($mybb->settings['dateformat'], $group['founded']);
    if($group['fid'] == $foruminfo['fid']) {
      // We are in the group's IC forum!
      eval("\$icforumdisplay = \"".$templates->get('rpgforumdisplay_icforum')."\";");

    } else if($group['mo_fid'] == $foruminfo['fid']) {
      // We are in the group's members only forum
      eval("\$icforumdisplay = \"".$templates->get('rpgforumdisplay_moforum')."\";");

    }
    eval("\$headerinclude .= \"".$templates->get('rpgmisc_groupstyle')."\";");
  }
}

$plugins->add_hook('showthread_end', 'add_thread_display');

function add_thread_display() {
  global $mybb, $db, $cache, $templates, $thread, $headerinclude;

  $rpgsuite = new RPGSuite($mybb, $db, $cache);
  $usergroup = $rpgsuite->retrieve_forum_group($thread['fid']);
  if($usergroup) {
    $group = $usergroup->get_info();
    eval("\$headerinclude .= \"".$templates->get('rpgmisc_groupstyle')."\";");
  }
}

$plugins->add_hook('newthread_end', 'add_newthread_display');

function add_newthread_display() {
  global $mybb, $db, $cache, $templates, $forum, $headerinclude;

  $rpgsuite = new RPGSuite($mybb, $db, $cache);
  $usergroup = $rpgsuite->retrieve_forum_group($forum['fid']);
  if($usergroup) {
    $group = $usergroup->get_info();
    eval("\$headerinclude .= \"".$templates->get('rpgmisc_groupstyle')."\";");
  }
}
$plugins->add_hook('newreply_end', 'add_newreply_display');

function add_newreply_display() {
  global $mybb, $db, $cache, $templates, $thread, $headerinclude;

  $rpgsuite = new RPGSuite($mybb, $db, $cache);
  $usergroup = $rpgsuite->retrieve_forum_group($thread['fid']);
  if($usergroup) {
    $group = $usergroup->get_info();
    eval("\$headerinclude .= \"".$templates->get('rpgmisc_groupstyle')."\";");
  }
}
