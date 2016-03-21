<?php
require_once MYBB_ROOT."/inc/plugins/rpg_suite/templatesets/class_TemplateSet.php";
require_once MYBB_ROOT."/inc/plugins/rpg_suite/templatesets/class_Template.php";

if(!defined("IN_MYBB"))
{
    die("You Cannot Access This File Directly. Please Make Sure IN_MYBB Is Defined.");
}
class OtmSet extends TemplateSet {
  /**
  Creates and destroys the external facing OTM templates!
       Set Title: RPG Suite - Otm Awards
  **/
  Const SET_TITLE = 'RPG Suite - Otm Awards';
  Const SET_PREFIX = 'rpgotm';

  protected function build_templates() {
    $templatearray = array();
    // Create full stats listing
    $templatearray[] = new Template('index',
    '<h1>Crazy Writers of the Week</h1>
    <ul>
      {$crazyweekwriters}
    </ul>
    <h1>Crazy Writers of the Month</h1>
    <ul>
      {$crazymonthwriters}
    </ul>');

    $templatearray[] = new Template('crazywriter',
    '<li><b>{$user[\'username\']}</b> ($user[\'total\'])</li>');

    return $templatearray;
  }

}
