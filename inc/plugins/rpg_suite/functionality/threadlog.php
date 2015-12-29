<?php
if(!defined("IN_MYBB"))
{
    die("You Cannot Access This File Directly. Please Make Sure IN_MYBB Is Defined.");
}

require_once MYBB_ROOT."/inc/plugins/rpg_suite/models/class_Threadlog.php";

/**
Functionality behind the threadlog
  This system allows members to view a list of their current and past IC threads
**/

$plugins->add_hook('misc_start', 'threadlog_init');

function threadlog_init() {
  global $mybb;
  if($mybb->settings['rpgsuite_threadlog'] && $mybb->get_input('action') == 'threadlog') {

    // check for a UID
    if(isset($mybb->input['uid'])) {
      $uid = intval($mybb->input['uid']);
    } elseif(isset($mybb->user['uid'])) {
      $uid = $mybb->user['uid'];
    } else {
      exit;
    }

    build_threadlog($uid);
  }
}

/**
Threadlog functionality - build!
**/
function build_threadlog($uid) {
  global $mybb, $db, $templates, $theme, $lang, $header, $headerinclude, $footer, $threadlog_list;

  $threadlog = new Threadlog($db, $uid);

  //Define Variables
  $user = $threadlog->get_user();
  $threads = $threadlog->get_threads();

  //Add breadcrubs
  add_breadcrumb($user['username'].'\'s Profile', "member.php?action=profile&uid=".$user['uid']);
  add_breadcrumb($user['username'] .'\'s Threadlog', "misc.php?action=threadlog");

  // set up the pager
  $multipage = setup_threadlog_pages($uid, $threads, $start);

  // setup thread counts
  $active_count = count($threadlog->get_active());
  $closed_count = count($threadlog->get_closed());
  $reply_count = count($threadlog->get_need_reply());
  $total_count = count($threads);

  // Print out the rows!
  $rowiterator = 0;
  if($total_count < 1) {
    eval("\$threadlog_list .= \"". $templates->get("rpgthreadlog_nothreads") ."\";");
  }
  $threads = array_slice($threads, $start, $mybb->settings['rpgsuite_threadlog_perpage']);
  foreach($threads as $thread) {
    $participants = $threadlog->get_thread_participants($thread['tid']);
    $notes = $threadlog->get_thread_notes($thread['tid']);
    setup_threadlog_row($user, $thread, $participants, $notes, $rowiterator);
    $rowiterator++;
  }

  eval("\$threadlog_page = \"".$templates->get("rpgthreadlog_page")."\";");

  output_page($threadlog_page);
  exit;
}

/**
Helper - setup paging
**/
function setup_threadlog_pages($uid, $threads, &$start) {
  global $mybb;

  $threadlog_url = htmlspecialchars_uni("misc.php?action=threadlog&uid=". $uid);
  $per_page = intval($mybb->settings['rpgsuite_threadlog_perpage']);

  $page = $mybb->get_input('page', MyBB::INPUT_INT);
  if($page && $page > 0) {
      $start = ($page - 1) * $per_page;
  } else {
      $start = 0;
      $page = 1;
  }
  return multipage(count($threads), $per_page, $page, $threadlog_url);
}

/**
Helper - setup row
**/
function setup_threadlog_row($user, $thread, $participants, $notes, $rownum) {
  global $mybb, $templates, $threadlog_list;
  $thread_row = ($rownum % 2) ? "trow2" : "trow1";

  if($thread['closed'] == 1) {
    $thread_status = 'closed';
  } else if($thread['lastposteruid'] != $user['uid']) {
    $thread_status = 'active needs_reply';
  } else {
    $thread_status = 'active';
  }

  $thread_title = "<a href=\"{$mybb->settings['bburl']}/showthread.php?tid=". $thread['tid'] ."\">". $thread['subject'] ."</a>";
  $thread_date = date($mybb->settings['dateformat'], $thread['dateline']);

  $thread_latest_poster = "<a href=\"{$mybb->settings['bburl']}/member.php?action=profile&uid=". $thread['lastposteruid'] ."\">". $thread['lastposter'] ."</a>";
  $thread_latest_date = date($mybb->settings['dateformat'], $thread['lastpost']);

  $thread_prefix = ($thread['prefix'] != 0) ? $thread['prefixtext'] : '';

  $usernotes = $notes;
  $thread_participants = "";
  foreach($participants as $participant) {
    if(strlen($thread_participants)) {
      $thread_participants .= ", ";
    }
    $thread_participants .= "<a href=\"{$mybb->settings['bburl']}/member.php?action=profile&uid=". $participant['uid'] ."\">". $participant['username'] ."</a>";
  }

  // add the row to the list
  eval("\$threadlog_list .= \"".$templates->get("rpgthreadlog_row")."\";");
}
