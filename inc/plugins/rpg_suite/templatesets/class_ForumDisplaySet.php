<?php
require_once MYBB_ROOT."/inc/plugins/rpg_suite/templatesets/class_TemplateSet.php";
require_once MYBB_ROOT."/inc/plugins/rpg_suite/templatesets/class_Template.php";

if(!defined("IN_MYBB"))
{
  die("You Cannot Access This File Directly. Please Make Sure IN_MYBB Is Defined.");
}
class ForumDisplaySet extends TemplateSet {
  /**
  Creates and destroys the external facing Group Management CP templates!
  Set Title: RPG Suite - Group Management CP
  **/
  Const SET_TITLE = 'RPG Suite - Forum Display Addition';
  Const SET_PREFIX = 'rpgforumdisplay';

  protected function build_templates() {
    $templatearray = array();

    // Create the IC Forum Addon
    $templatearray[] = new Template('icforum',
    '<table class="tborder" border="0" cellpadding="{$theme[\'tablespace\']}" cellspacing="{$theme[\'borderwidth\']}">
	<tbody>
	<tr>
		<td class="thead" align=center><strong>{$group[\'title\']} Pack Summary</strong></td>
	</tr>
	<tr>
		<td class="trow1"><strong>Members:</strong> {$group[\'membertotal\']}</td>
	</tr>
	<tr>
		<td class="trow2"><strong>Founded on</strong> {$group[\'founded_date\']}</td>
	</tr>
	<tr>
		<td class="trow1"><strong>Pack led by</strong> {$moderators}</td>
	</tr>
  <tr>
    <td class="trow2"><a href="index.php?action=showranks&gid={$group[\'gid\']}">View Pack Ranks</a></td>
  </tr>
	</tbody>
    </table>
<br>');

    // Create the OOC MO Forum Addon
    $templatearray[] = new Template('moforum',
    '<table class="tborder" border="0" cellpadding="{$theme[\'tablespace\']}" cellspacing="{$theme[\'borderwidth\']}">
	<tbody>
  <tr>
		<td class="thead" align=center><strong>{$group[\'title\']} Members</strong></td>
	</tr>
	<tr>
		<td class="trow1">Pack Quick Links perhaps?</td>
	</tr>
	</tbody>
    </table>
<br>');

    return $templatearray;
  }

}
