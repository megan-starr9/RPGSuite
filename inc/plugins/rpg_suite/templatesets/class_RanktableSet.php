<?php
require_once MYBB_ROOT."/inc/plugins/rpg_suite/templatesets/class_TemplateSet.php";
require_once MYBB_ROOT."/inc/plugins/rpg_suite/templatesets/class_Template.php";

if(!defined("IN_MYBB"))
{
    die("You Cannot Access This File Directly. Please Make Sure IN_MYBB Is Defined.");
}
class RanktableSet extends TemplateSet {
  /**
  Creates and destroys the external facing Rank Table templates!
       Set Title: RPG Suite - Group/Rank View
  **/
  Const SET_TITLE = 'RPG Suite - Group/Rank View';
  Const SET_PREFIX = 'rpggroupview';

  protected function build_templates() {
    $templatearray = array();
    // Create full rank page
    $templatearray[] = new Template('ranks_full',
    '<html>
	      <head>
	        <title>{$title}</title>
	        {$headerinclude}
        </head>
	      <body>
	        {$header}
          <table class="tborder" border="0" cellpadding="{$theme[\'tablespace\']}" cellspacing="{$theme[\'borderwidth\']}">
            <thead>
              <tr>
                <td class="thead" colspan=4>{$group[\'title\']} Ranks</td>
              </tr>
              <tr>
                <td class="tcat">Rank</td>
                <td class="tcat">Username</td>
                <td class="tcat">Posts</td>
                <td class="tcat">Last IC Post</td>
              </tr>
					  </thead>
            <tbody>
              {$tierlist}
              {$unrankedlist}
            </tbody>
					</table>
          {$footer}
	      </body>
      </html>');

    //Create Tier Bit
    $templatearray[] = new Template('ranks_tier',
    '<tr><th class="trow_sep" colspan=4>{$tier[\'label\']}</th></tr>
    {$ranklist}');

    //Create Rank Bit
    $templatearray[] = new Template('ranks_rank',
    '<tr>
      <td class="{$rowstyle}">{$rank[\'label\']}</td>
      {$userlist}
    </tr>');

    //Create Rank User Bit
    $templatearray[] = new Template('ranks_user',
    '<td class="{$rowstyle}"><a href="member.php?action=profile&amp;uid={$user[\'uid\']}">{$user[\'username\']}</a></td>
    <td class="{$rowstyle}">{$user[\'postnum\']}</td>
    <td class="{$rowstyle}">{$user[\'lasticpost\']}</td>');

    //Create Rank Empty User Bit
    $templatearray[] = new Template('ranks_emptyuser',
    '<td class="{$rowstyle}">&mdash;</td>
    <td class="{$rowstyle}">&mdash;</td>
    <td class="{$rowstyle}">&mdash;</td>');

    //Create Overflow Tier
    $templatearray[] = new Template('ranks_overflowtier',
    '<tr><th class="trow_sep" colspan=4>To Be Determined</th></tr>
    {$unrankedmemberlist}');

    //Create Overflow User
    $templatearray[] = new Template('ranks_overflowuser',
    '<tr>
      <td class="{$rowstyle}">TBD</td>
      <td class="{$rowstyle}"><a href="member.php?action=profile&amp;uid={$user[\'uid\']}">{$user[\'username\']}</a></td>
    <td class="{$rowstyle}">{$user[\'postnum\']}</td>
    <td class="{$rowstyle}">{$user[\'lasticpost\']}</td>
    </tr>');

    //Create full no-rank page
    $templatearray[] = new Template('noranks_full',
    '<html>
	      <head>
	        <title>{$title}</title>
	        {$headerinclude}
        </head>
	      <body>
	        {$header}
          {$multipage}
          <table class="tborder" border="0" cellpadding="{$theme[\'tablespace\']}" cellspacing="{$theme[\'borderwidth\']}">
            <thead>
              <tr>
                <td class="thead" colspan=3>{$group[\'title\']} Members</td>
              </tr>
              <tr>
                <td class="tcat">Username</td>
                <td class="tcat">Posts</td>
                <td class="tcat">Last IC Post</td>
              </tr>
					  </thead>
            <tbody>
              {$memberlist}
            </tbody>
					</table>
          {$multipage}
          {$footer}
	      </body>
      </html>');

    //Create No-rank User Bit
    $templatearray[] = new Template('noranks_user',
    '<tr>
      <td class="{$rowstyle}"><a href="member.php?action=profile&amp;uid={$user[\'uid\']}">{$user[\'username\']}</a></td>
      <td class="{$rowstyle}">{$user[\'postnum\']}</td>
      <td class="{$rowstyle}">{$user[\'lasticpost\']}</td>
    </tr>');


    return $templatearray;
  }
}
