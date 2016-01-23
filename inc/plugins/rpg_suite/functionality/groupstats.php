<?php
if(!defined("IN_MYBB"))
{
    die("You Cannot Access This File Directly. Please Make Sure IN_MYBB Is Defined.");
}

require_once MYBB_ROOT."/inc/plugins/rpg_suite/models/class_RPGSuite.php";
require_once MYBB_ROOT."/inc/plugins/rpg_suite/rpgsuite_defaults.php";

/**
For the group-specific statistics on the stats page!
**/

$plugins->add_hook("stats_start","groupstats_display");

function groupstats_display() {
  global $mybb, $db, $cache, $templates, $groupstats, $statlist, $maletot, $femaletot, $youthtot, $adulttot, $limitgroupcount;
  $maletot = $femaletot = $youthtot = $adulttot = $overalltot = $limitgroupcount = 0;

  $adultlimit = Stats::ADULTCAP;
  $youthlimit = Stats::YOUTHCAP;

  $rpg = new RPGSuite($mybb, $db,$cache);
  eval("\$statlist = \"\";");
  $rowcount = 0;
  foreach($rpg->get_icgroups_members() as $id => $usergroup) {
    $statlist .= build_row($usergroup, $rowcount, $adultlimit, $youthlimit);
    $rowcount++;
  }

  $overalltot = $maletot + $femaletot;
  $grouplimit = ceil($overalltot / $adultlimit);

  eval("\$groupstats = \"".$templates->get('rpggroupstats_full')."\";");
}

function build_row($usergroup, $rowcount) {
  global $templates, $statlist, $maletot, $femaletot, $youthtot, $adulttot, $limitgroupcount;
  $malecount = $femalecount = $youthcount = $adultcount = $totalcount = 0;

  $adultlimit = Stats::ADULTCAP;
  $youthlimit = Stats::YOUTHCAP;

  $totallimit = $adultlimit + $youthlimit;

  $statrow = ($rowcount % 2) ? "trow2" : "trow1";

  $group = $usergroup->get_info();

  foreach($usergroup->get_members() as $member) {
    $user = $member->get_info();
    $rank = $member->get_rank();
    if($user[Fields::GENDER] == 'Male' && !$rank['ignoreactivitycheck']) {
      $malecount++;
    } else if($user[Fields::GENDER] == 'Female' && !$rank['ignoreactivitycheck']) {
      $femalecount++;
    }

    // Adults are 9 months of age
    $adultdate = strtotime('-'.Stats::ADULTAGE.' month');
    $age = $user[Fields::AGE] * 365;
    $dob = (!empty($user[Fields::BDATE]) && strtotime($user[Fields::BDATE])) ? strtotime($user[Fields::BDATE]) : strtotime('-'.$user[Fields::AGE].' years');
    if(($dob < $adultdate || ($age > (time() - $adultdate) / 86000)) && !$rank['ignoreactivitycheck']) {
      $adultcount++;
    } else if(!$rank['ignoreactivitycheck']){
      $youthcount++;
    }
  }

  $totalcount = $youthcount + $adultcount;

  /** Set fonts! **/
  if ($totalcount > $totallimit) {
    $totalclass = "overmax";
  } else if ($totalcount == $totallimit) {
    $totalclass = "atmax";
  } else if ($totalcount >= $totallimit*Stats::PERCENTAGE) {
    $totalclass = "high";
  } else {
    $totalclass = "okay";
  }

  if ($youthcount > $youthlimit) {
    $youthclass = "overmax";
  } else if ($youthcount == $youthlimit) {
    $youthclass = "atmax";
  } else if ($youthcount >= $youthlimit*Stats::PERCENTAGE) {
    $youthclass = "high";
  } else {
    $youthclass = "okay";
  }

  if ($adultcount > $adultlimit) {
    $adultclass = "overmax";
  } else if ($adultcount == $adultlimit) {
    $adultclass = "atmax";
  } else if ($adultcount >= $adultlimit*Stats::PERCENTAGE) {
    $adultclass = "high";
  } else {
    $adultclass = "okay";
  }

  // Add total counts
  $maletot += $malecount;
  $femaletot += $femalecount;
  $adulttot += $adultcount;
  $youthtot += $youthcount;

  if($group['hasranks']) {
    eval("\$statlist .= \"".$templates->get('rpggroupstats_row_max')."\";");
    $limitgroupcount++;
  } else {
    eval("\$statlist .= \"".$templates->get('rpggroupstats_row_nomax')."\";");
  }

}
