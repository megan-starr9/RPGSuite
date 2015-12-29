<?php
if(!defined("IN_MYBB"))
{
  die("You Cannot Access This File Directly. Please Make Sure IN_MYBB Is Defined.");
}

require_once MYBB_ROOT."/inc/plugins/rpg_suite/models/class_UserGroup.php";

/**
Functionality behind the rank/member view for a group
This system allows members to view a list of group members and, when applicable, the hierarchy
**/

$plugins->add_hook('index_end', 'viewranks_init');

function viewranks_init() {
  global $mybb, $db, $cache, $templates, $header, $footer, $headerinclude, $title, $theme, $parser;

  if($mybb->settings['rpgsuite_groupranks'] && $mybb->input['action'] == "showranks") {

    // Get group id
    if($mybb->input['gid'] != '') {
      $gid = intval($mybb->input['gid']);
    } else {
      $gid = $mybb->user['displaygroup'];
    }

    $usergroup = new UserGroup($mybb, $db, $cache);
    if($usergroup->initialize($gid)) {
      $group = $usergroup->get_info();
      // Add Group Styling
      eval("\$headerinclude .= \"".$templates->get('rpgmisc_groupstyle')."\";");

      if($group['hasranks']) {

        $title = $group['title']." Ranks";
        if($group['fid']) {
          add_breadcrumb($group['title'].' Forum', 'forumdisplay.php?fid='.(int)$group['fid']);
        }
        add_breadcrumb($group['title'].' Ranks');

        $tierlist = build_ranks($usergroup);
        $unrankedlist = build_unranked($usergroup);
        eval("\$rankpage = \"".$templates->get('rpggroupview_ranks_full')."\";");
        output_page($rankpage);

      } else {

        $title = $group['title']." Members";
        if($group['fid']) {
          add_breadcrumb($group['title'].' Forum', 'forumdisplay.php?fid='.(int)$group['fid']);
        }
        add_breadcrumb($group['title'].' Members');

        // set up the pager
        $multipage = setup_viewgroup_pages($group['gid'], $usergroup->get_member_count(), $start);

        $memberlist = build_members($usergroup, $start);
        eval("\$memberpage = \"".$templates->get('rpggroupview_noranks_full')."\";");
        output_page($memberpage);

      }
      exit;
    }
  }
}

/**
Actual Functionality - building of ranks!
**/
function build_ranks($usergroup) {
  global $templates, $mybb, $parser, $plugins, $user;

  $ranktable = $usergroup->get_ranks();
  eval("\$tierlist = \"\";");

  foreach($ranktable->get_tiers() as $tier) {
    $rowiterator = 0;
    eval("\$ranklist = \"\";");

    foreach($ranktable->get_ranks($tier['id']) as $rank) {
      $rowstyle = ($rowiterator % 2) ? "trow2" : "trow1";
      eval("\$userlist = \"\";");

      $membercount = 0;
      foreach($ranktable->get_members($rank['id']) as $member) {
        $user = $member->get_info();
        $plugins->run_hooks("groupranks_user");
        $user['isleader'] = $member->is_leader();
        $lastpost = $member->get_last_icpost();
        $user['lasticpost'] = ($lastpost) ? time2str($lastpost['dateline']) : 'Never';

        eval("\$userlist .= \"".$templates->get('rpggroupview_ranks_user')."\";");

        if($rank['split_dups']) {
          // If each member is on separate row, add rank list as separate every time
          eval("\$ranklist .= \"".$templates->get('rpggroupview_ranks_rank')."\";");
          eval("\$userlist = \"\";");
          $rowiterator++;
          $rowstyle = ($rowiterator % 2) ? "trow2" : "trow1";
        }
        $membercount++;
      } // End Members

      while(($rank['split_dups'] && $membercount < $rank['dups']) || (!$rank['split_dups'] && $membercount == 0)) {
        // If we didn't add separately, add at end
        eval("\$userlist .= \"".$templates->get('rpggroupview_ranks_emptyuser')."\";");
        if($rank['split_dups']) {
          eval("\$ranklist .= \"".$templates->get('rpggroupview_ranks_rank')."\";");
          eval("\$userlist = \"\";");
          $rowiterator++;
          $rowstyle = ($rowiterator % 2) ? "trow2" : "trow1";
        }
        $membercount++;
      }
      if(!$rank['split_dups']) {
        eval("\$ranklist .= \"".$templates->get('rpggroupview_ranks_rank')."\";");
        $rowiterator++;
      }
    } // End ranks

    eval("\$tierlist .= \"".$templates->get('rpggroupview_ranks_tier')."\";");
  } // End tiers

  return $tierlist;
}

function build_unranked($usergroup) {
  global $templates, $mybb, $parser, $plugins, $user;
  $ranktable = $usergroup->get_ranks();
  eval("\$unrankedmemberlist = \"\";");

  $rowiterator = 0;
  foreach($ranktable->get_members(0) as $member) {
    $user = $member->get_info();
    $plugins->run_hooks("groupranks_user");
    $user['isleader'] = $member->is_leader();
    $lastpost = $member->get_last_icpost();
    $user['lasticpost'] = ($lastpost) ? time2str($lastpost['dateline']) : 'Never';
    $rowstyle = ($rowiterator % 2) ? "trow2" : "trow1";

    eval("\$unrankedmemberlist .= \"".$templates->get('rpggroupview_ranks_overflowuser')."\";");
    $rowiterator++;
  } // End Members

  if(!empty($unrankedmemberlist)) {
    eval("\$unrankedlist .= \"".$templates->get('rpggroupview_ranks_overflowtier')."\";");
  }
  return $unrankedlist;
}

/**
Actual Functionality - building of member rows!
**/
function build_members($usergroup, $start) {
  global $templates, $mybb, $parser, $plugins, $user;

  $rowiterator = 0;
  eval("\$memberlist = \"\";");
  foreach($usergroup->get_members_offset($start, $mybb->settings['rpgsuite_groupranks_perpage']) as $member) {
    $rowstyle = ($rowiterator % 2) ? "trow2" : "trow1";
    $user = $member->get_info();
    $plugins->run_hooks("groupranks_user");
    $user['isleader'] = $member->is_leader();
    $lastpost = $member->get_last_icpost();
    $user['lasticpost'] = ($lastpost) ? time2str($lastpost['dateline']) : 'Never';
    eval("\$memberlist .= \"".$templates->get('rpggroupview_noranks_user')."\";");
    $rowiterator++;
  }
  return $memberlist;
}

/**
Helper - setup paging
**/
function setup_viewgroup_pages($gid, $membercount, &$start) {
  global $mybb, $parser;

  $grouplist_url = htmlspecialchars_uni("index.php?action=showranks&gid=". $gid);
  $per_page = intval($mybb->settings['rpgsuite_groupranks_perpage']);

  $page = $mybb->get_input('page', MyBB::INPUT_INT);
  if($page && $page > 0) {
      $start = ($page - 1) * $per_page;
  } else {
      $start = 0;
      $page = 1;
  }
  return multipage($membercount, $per_page, $page, $grouplist_url);
}

/**
Function taken from StackOverflow
http://stackoverflow.com/questions/2690504/php-producing-relative-date-time-from-timestamps
*/
function time2str($ts)
{
    if(!ctype_digit($ts))
        $ts = strtotime($ts);

    $diff = time() - $ts;
    if($diff == 0)
        return 'Now';
    elseif($diff > 0)
    {
        $day_diff = floor($diff / 86400);
        if($day_diff == 0)
        {
            if($diff < 60) return 'Just Now';
            if($diff < 120) return '1 Minute Ago';
            if($diff < 3600) return floor($diff / 60) . ' Minutes Ago';
            if($diff < 7200) return '1 Hour Ago';
            if($diff < 86400) return floor($diff / 3600) . ' Hours Ago';
        }
        if($day_diff == 1) return 'Yesterday';
        if($day_diff < 7) return $day_diff . ' Days Ago';
        if($day_diff < 31) return number_format($day_diff / 7, 1) + 0 . ' Weeks Ago';
        if($day_diff < 60) return 'Last Month';
        return date('F Y', $ts);
    }
    else
    {
        $diff = abs($diff);
        $day_diff = floor($diff / 86400);
        if($day_diff == 0)
        {
            if($diff < 120) return 'in a minute';
            if($diff < 3600) return 'in ' . floor($diff / 60) . ' minutes';
            if($diff < 7200) return 'in an hour';
            if($diff < 86400) return 'in ' . floor($diff / 3600) . ' hours';
        }
        if($day_diff == 1) return 'Tomorrow';
        if($day_diff < 4) return date('l', $ts);
        if($day_diff < 7 + (7 - date('w'))) return 'next week';
        if(ceil($day_diff / 7) < 4) return 'in ' . ceil($day_diff / 7) . ' weeks';
        if(date('n', $ts) == date('n') + 1) return 'next month';
        return date('F Y', $ts);
    }
}
