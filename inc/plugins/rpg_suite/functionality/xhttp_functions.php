<?php
if(!defined("IN_MYBB"))
{
    die("You Cannot Access This File Directly. Please Make Sure IN_MYBB Is Defined.");
}

require_once MYBB_ROOT."/inc/plugins/rpg_suite/models/class_RPGSuite.php";

/**
Handle XMLHTTP requests
 */

$plugins->add_hook('xmlhttp', 'handle_rpgsuite_ajax_request');

function handle_rpgsuite_ajax_request() {
  global $mybb;
	if($mybb->input['action'] == 'getprefixes') {
		// Retrieve prefixes for creation
		get_prefix_list();
	}
}

function get_prefix_list() {
  global $mybb, $db, $cache;
  $rpgsuite = new RPGSuite($mybb,$db, $cache);
  echo $rpgsuite->generate_prefixselect($mybb->input['region']);
}
