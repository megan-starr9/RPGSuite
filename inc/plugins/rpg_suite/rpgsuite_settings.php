<?php
if(!defined("IN_MYBB"))
{
    die("You Cannot Access This File Directly. Please Make Sure IN_MYBB Is Defined.");
}

/**
Plugin Setting List!
*/
function settings() {
  $settingarray = array();
  $settingarray[] = array(
    'name'         => 'activitycheck',
    'title'            => 'Run Activity Checks?',
    'description'    => 'Determines if you wish to automatically remove inactive members from IC groups.',
    'type'    => 'yesno',
    'default'        => '0'
  );
  $settingarray[] = array(
    'name'        => 'activitycheck_freq',
    'title'            => 'Activity Check Frequency',
    'description'    => 'Days to wait between checks.',
    'type'    => 'text',
    'default'        => '7'
  );
  $settingarray[] = array(
    'name'        => 'activitycheck_period',
    'title'            => 'Activity Check Period',
    'description'    => 'If a post isn\'\'t made in this many days, remove the player.',
    'type'    => 'text',
    'default'        => '14'
  );
  $settingarray[] = array(
    'name'        => 'activitycheck_leaderperiod',
    'title'            => 'Activity Check Period For Leader',
    'description'    => 'If a post isn\'\'t made in this many days, demote the leader.',
    'type'    => 'text',
    'default'        => '7'
  );
  $settingarray[] = array(
    'name'        => 'activitycheck_absence',
    'title'            => 'Activity Check Absence Length',
    'description'    => 'Grace period to add on for characters on absence.',
    'type'    => 'text',
    'default'        => '30'
  );
  $settingarray[] = array(
    'name'        => 'activitycheck_joingraceperiod',
    'title'            => 'Activity Check Grace Period',
    'description'    => 'Days after joining a group that a character is immune to the checker.',
    'type'    => 'text',
    'default'        => '7'
  );
  $settingarray[] = array(
    'name'        => 'grouppoints',
    'title'            => 'Group Points?',
    'description'    => 'Determines if you wish to reward (or penalize) certain IC groups.',
    'type'    => 'yesno',
    'default'        => '0'
  );
  $settingarray[] = array(
    'name'        => 'grouppoints_freq',
    'title'            => 'Group Points Frequency',
    'description'    => 'Days to wait between point distribution.',
    'type'    => 'text',
    'default'        => '7'
  );
  $settingarray[] = array(
    'name'        => 'grouppoints_max',
    'title'            => 'Group Points Maximum',
    'description'    => 'Points a character starts with.',
    'type'    => 'text',
    'default'        => '4'
  );
  $settingarray[] = array(
    'name'        => 'threadlog',
    'title'            => 'Threadlog?',
    'description'    => 'Display a log of users\'\' IC threads.',
    'type'    => 'yesno',
    'default'        => '1'
  );
  $settingarray[] = array(
    'name'        => 'threadlog_perpage',
    'title'            => 'Threadlog Threads per Page',
    'description'    => 'Number of threads to list on each page of the threadlog.',
    'type'    => 'text',
    'default'        => '100'
  );
  $settingarray[] = array(
    'name'        => 'groupranks',
    'title'            => 'Group Ranks?',
    'description'    => 'Allow internal ranks within groups.',
    'type'    => 'yesno',
    'default'        => '1'
  );
  $settingarray[] = array(
    'name'        => 'groupranks_custom',
    'title'            => 'Custom Ranks?',
    'description'    => 'Allow each group to define their own, custom hierarchy.',
    'type'    => 'yesno',
    'default'        => '0'
  );
  $settingarray[] = array(
    'name'        => 'groupranks_delimeter',
    'title'            => 'Rank Delimeter',
    'description'    => 'Delimiter for multi-member ranks (if not splitting duplicates).',
    'type'    => 'text',
    'default'        => ', '
  );
  $settingarray[] = array(
    'name'        => 'groupranks_perpage',
    'title'            => 'Group Members per Page',
    'description'    => 'On user listing with no ranks, how many users per page.',
    'type'    => 'text',
    'default'        => '25'
  );
  $settingarray[] = array(
    'name'        => 'groupmanagecp',
    'title'            => 'Group Management CP?',
    'description'    => 'Enable Group Managers access to a control panel for easier group management.',
    'type'    => 'yesno',
    'default'        => '1'
  );
  $settingarray[] = array(
    'name'        => 'approval',
    'title'            => 'Approve Users?',
    'description'    => 'Replace the default activation with a separate user approval process (still utilizes the Awaiting Approval usergroup).',
    'type'    => 'yesno',
    'default'        => '0'
  );
  $settingarray[] = array(
    'name'        => 'approval_registerpm_subj',
    'title'            => 'Registration PM Subject',
    'description'    => 'Subject of PM sent after successful registration.',
    'type'    => 'text',
    'default'        => ''
  );
  $settingarray[] = array(
    'name'        => 'approval_registerpm',
    'title'            => 'Registration PM',
    'description'    => 'Contents of PM sent right after successful registration - leave empty to disable.',
    'type'    => 'textarea',
    'default'        => ''
  );
  $settingarray[] = array(
    'name'        => 'approval_approvepm_subj',
    'title'            => 'Approval PM Subject',
    'description'    => 'Subject of PM sent after account is approved.',
    'type'    => 'text',
    'default'        => ''
  );
  $settingarray[] = array(
    'name'        => 'approval_approvepm',
    'title'            => 'Approval PM',
    'description'    => 'Contents of PM sent right after account is approved - leave empty to disable.',
    'type'    => 'textarea',
    'default'        => ''
  );
  $settingarray[] = array(
    'name'        => 'approval_denypm_subj',
    'title'            => 'Deny PM Subject',
    'description'    => 'Subject of PM sent after account is denied.',
    'type'    => 'text',
    'default'        => ''
  );
  $settingarray[] = array(
    'name'        => 'approval_denypm',
    'title'            => 'Deny PM',
    'description'    => 'Contents of PM sent right after account is denied - leave empty to disable.',
    'type'    => 'textarea',
    'default'        => ''
  );
  return $settingarray;
}
