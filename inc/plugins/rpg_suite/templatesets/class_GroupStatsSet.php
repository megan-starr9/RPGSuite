<?php
require_once MYBB_ROOT."/inc/plugins/rpg_suite/templatesets/class_TemplateSet.php";
require_once MYBB_ROOT."/inc/plugins/rpg_suite/templatesets/class_Template.php";

if(!defined("IN_MYBB"))
{
    die("You Cannot Access This File Directly. Please Make Sure IN_MYBB Is Defined.");
}
class GroupStatsSet extends TemplateSet {
  /**
  Creates and destroys the external facing Rank Table templates!
       Set Title: RPG Suite - Group/Rank View
  **/
  Const SET_TITLE = 'RPG Suite - Group Statistics';
  Const SET_PREFIX = 'rpggroupstats';

  protected function build_templates() {
    $templatearray = array();
    // Create full stats listing
    $templatearray[] = new Template('full',
    '<style>
    	.overmax {
    		color: red;
    	}
    	.atmax {
    		color: darkred;
    	}
    	.high {
    		color: orange;
    	}
    	.okay {
    		color: green;
    	}
    </style>

    <table class="tborder" border="0" cellpadding="5" cellspacing="0">
    <tbody>
    <tr>
    	<td class="thead" colspan="6"><strong>Game Statistics</strong></td>
    </tr>
    <tr>
    	<td class="tcat" width="40%"><strong>Pack</strong></td>
    	<td class="tcat" width="10%" align="center"><strong>Males</strong></td>
    	<td class="tcat" width="10%" align="center"><strong>Females</strong></td>
    	<td class="tcat" width="10%" align="center"><strong>Adults</strong></td>
    	<td class="tcat" width="10%" align="center"><strong>Youth</strong></td>
    	<td class="tcat" width="20%" align="center"><strong>Total</strong></td>
    </tr>
    {$statlist}
    <tr>
    	<td class="tcat" width="40%" colspan="6"><strong>Game Totals</strong></td>
    </tr>
    <tr class="{$statrow}">
      <td width="40%">Wolf currently has <strong>{$limitgroupcount}</strong> packs and can support up to <strong>{$grouplimit}</strong> total packs.</td>
      <td width="10%" align="center">{$maletot}</td>
      <td width="10%" align="center">{$femaletot}</td>
      <td width="10%" align="center">{$adulttot}</td>
      <td width="10%" align="center">{$youthtot}</td>
      <td width="20%" align="center"><strong>{$overalltot}</strong></td>
    </tr>
    	</tbody>
    </table><br>');

    $templatearray[] = new Template('row_max',
    '<tr class="{$statrow}">
    	<td width="40%"><strong><a href="index.php?action=showranks&gid={$group[\'gid\']}">{$group[\'title\']}</a></strong></td>
    	<td width="10%" align="center">{$malecount}</td>
    	<td width="10%" align="center">{$femalecount}</td>
    	<td width="10%" align="center"><strong><font class="{$adultclass}">{$adultcount}</font></strong>/{$adultlimit}</td>
    	<td width="10%" align="center"><strong><font class="{$youthclass}">{$youthcount}</font></strong>/{$youthlimit}</td>
    	<td width="20%" align="center"><strong><font class="{$totalclass}">{$totalcount}</font>/{$totallimit}</strong></td>
    </tr>');

    $templatearray[] = new Template('row_nomax',
    '<tr class="{$statrow}">
    	<td width="40%"><strong><a href="index.php?action=showranks&gid={$group[\'gid\']}">{$group[\'title\']}</a></strong></td>
    	<td width="10%" align="center">{$malecount}</td>
    	<td width="10%" align="center">{$femalecount}</td>
    	<td width="10%" align="center">{$adultcount}</td>
    	<td width="10%" align="center">{$youthcount}</td>
    	<td width="20%" align="center"><strong>{$totalcount}</strong></td>
    </tr>');

    return $templatearray;
  }

}
