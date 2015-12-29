<?php
require_once MYBB_ROOT."/inc/plugins/rpg_suite/templatesets/class_TemplateSet.php";
require_once MYBB_ROOT."/inc/plugins/rpg_suite/templatesets/class_Template.php";

if(!defined("IN_MYBB"))
{
  die("You Cannot Access This File Directly. Please Make Sure IN_MYBB Is Defined.");
}
class MiscSet extends TemplateSet {
  /**
  Creates and destroys the external facing Miscellaneous templates
  Set Title: RPG Suite - Miscellaneous
  **/
  Const SET_TITLE = 'RPG Suite - Miscellaneaous';
  Const SET_PREFIX = 'rpgmisc';

  protected function build_templates() {
    $templatearray = array();

    // Create the group style addition
    $templatearray[] = new Template('groupstyle',
    '<style>

    </style>');

    return $templatearray;
  }

}
