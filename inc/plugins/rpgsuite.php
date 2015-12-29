<?php
/**
* RPG Suite Plugin
* Created by Megan Lyle, for free use
* A compilation of functionality for enhancing a roleplay-based mybb board
*
*/

if(!defined("IN_MYBB"))
{
	die("You Cannot Access This File Directly. Please Make Sure IN_MYBB Is Defined.");
}

//Cache our templates!
global $templatelist;
if(isset($templatelist)) {
	$templatelist .= ",";
}
if(THIS_SCRIPT == 'misc.php') {
	//Threadlog
	$templatelist .= "rpgthreadlog_nothreads,rpgthreadlog_page,rpgthreadlog_row,";
} else if(THIS_SCRIPT == 'index.php') {
	//Show Ranks & Activation Queue
	$templatelist .= "rpggroupview_noranks_full,rpggroupview_noranks_user,rpggroupview_ranks_emptyuser,";
	$templatelist .= "rpggroupview_ranks_full,rpggroupview_ranks_overflowtier,rpggroupview_ranks_overflowuser,";
	$templatelist .= "rpggroupview_ranks_rank,rpggroupview_ranks_tier,rpggroupview_ranks_user,rpgmisc_groupstyle,";
	$templatelist .= "rpgapprove_page,rpgapprove_user,";
} else if(THIS_SCRIPT == 'modcp.php') {
	//GroupManager CP
	$templatelist .= "rpggroupmanagecp_full,rpggroupmanagecp_group_setting_cp,rpggroupmanagecp_group_setting_row,";
	$templatelist .= "rpggroupmanagecp_user_manage_cp,rpggroupmanagecp_user_manage_row,rpggroupmanagecp_user_manage_setting,";
	$templatelist .= "rpggroupmanagecp_user_rank_cp,rpggroupmanagecp_user_rank_row,rpgmisc_groupstyle,";
} else if(THIS_SCRIPT == 'forumdisplay.php') {
	//Forum View
	$templatelist .= "rpgforumdisplay_icforum,rpgforumdisplay_moforum,rpgmisc_groupstyle,";
} else if(THIS_SCRIPT == 'stats.php') {
	//Pack Stats
	$templatelist .= "rpggroupstats_full,rpggroupstats_row_max,rpggroupstats_row_nomax,";
} else if(THIS_SCRPT == 'showthread.php' || THIS_SCRPT == 'newthread.php' || THIS_SCRPT == 'newreply.php') {
	$templatelist .= "rpgmisc_groupstyle,";
}
//Global...
$templatelist .= "rpgapprove_notification";

function rpgsuite_info() {
    return array(
        'name' => 'RPG Suite',
        'description' => 'Enhances a MYBB board for use in a roleplay creative writing capacity.',
        'website' => '',
        'author' => 'Megan Lyle',
        'authorsite' => 'http://megstarr.com',
        'version' => '1.0.0',
        'compatibility' => '18*',
    );
}

// Load the administrator functionality
if (defined("IN_ADMINCP")) {
	require_once MYBB_ROOT."inc/plugins/rpg_suite/rpgsuite_install.php";
	require_once MYBB_ROOT."inc/plugins/rpg_suite/functionality/admincp_additions.php";

} else { // Otherwise load user and site functionality
	require_once MYBB_ROOT."inc/plugins/rpg_suite/functionality/modcp_additions.php";
	require_once MYBB_ROOT."inc/plugins/rpg_suite/functionality/activitycheck.php";
	require_once MYBB_ROOT."inc/plugins/rpg_suite/functionality/grouppoints.php";
	require_once MYBB_ROOT."inc/plugins/rpg_suite/functionality/threadlog.php";
	require_once MYBB_ROOT."inc/plugins/rpg_suite/functionality/viewranks.php";
	require_once MYBB_ROOT."inc/plugins/rpg_suite/functionality/groupstats.php";
	require_once MYBB_ROOT."inc/plugins/rpg_suite/functionality/groupmanagecp.php";
	require_once MYBB_ROOT."inc/plugins/rpg_suite/functionality/onlinelist.php";
	require_once MYBB_ROOT."inc/plugins/rpg_suite/functionality/forumdisplay.php";
	require_once MYBB_ROOT."inc/plugins/rpg_suite/functionality/welcomewagon.php";
}

// Load general files (used anywhere)
require_once MYBB_ROOT."inc/plugins/rpg_suite/functionality/displaygroupfix.php";
require_once MYBB_ROOT."inc/plugins/rpg_suite/functionality/xhttp_functions.php";
