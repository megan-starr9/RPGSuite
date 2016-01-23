<?php
if(!defined("IN_MYBB"))
{
    die("You Cannot Access This File Directly. Please Make Sure IN_MYBB Is Defined.");
}

require_once MYBB_ROOT."/inc/plugins/rpg_suite/models/class_RPGSuite.php";

/**
Functionality behind the lonely thread list
  This system allows members to view a list of ic threads with no replies
**/

$plugins->add_hook('misc_start', 'lonelythread_init');

function lonelythread_init() {
  global $mybb, $db, $cache, $templates, $threadpage, $header, $headerinclude, $footer;

  if($mybb->get_input('action') == 'lonelythreads') {
    $rpgsuite = new RPGSuite($mybb, $db, $cache);
    $threadlist = "";
    $count = 0;

    if(isset($mybb->input['gid'])) {
      $threads = $rpgsuite->get_lonely_threads((int) $mybb->input['gid']);
    } else {
      $threads = $rpgsuite->get_lonely_threads();
    }

    $groupfilters = "";
    eval("\$groupfilters = \"".$templates->get("rpglonelythread_groupfilter_nogroup")."\";");
    foreach($rpgsuite->get_icgroups('fid <> 0') as $group) {
      $groupinfo = $group->get_info();
      eval("\$groupfilters .= \"".$templates->get("rpglonelythread_groupfilter_group")."\";");
    }

    foreach($threads as $thread) {
      $trow = ($count % 2) ? "trow2" : "trow1";
      $threaddate = date($mybb->settings['dateformat'], $thread['dateline']);
      eval("\$threadlist .= \"".$templates->get("rpglonelythread_row")."\";");
      $count++;
    }

    eval("\$threadpage = \"".$templates->get("rpglonelythread_page")."\";");

    output_page($threadpage);
    exit;
  }
}
