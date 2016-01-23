<?php
require_once MYBB_ROOT."/inc/plugins/rpg_suite/templatesets/class_TemplateSet.php";
require_once MYBB_ROOT."/inc/plugins/rpg_suite/templatesets/class_Template.php";

if(!defined("IN_MYBB"))
{
    die("You Cannot Access This File Directly. Please Make Sure IN_MYBB Is Defined.");
}
class LonelyThreadSet extends TemplateSet {
  /**
  Creates and destroys the external facing Lonely Thread templates!
       Set Title: RPG Suite - Lonely Threads
  **/
  Const SET_TITLE = 'RPG Suite - Lonely Threads';
  Const SET_PREFIX = 'rpglonelythread';

  protected function build_templates() {
    $templatearray = array();
    // Create full stats listing
    $templatearray[] = new Template('page',
    '<html>
        <head><title>Lonely Threads</title>
              {$headerinclude}
        </head>
        <body>
          {$header}

          {$groupfilters}<br>
          <table class="tborder" border="0" cellpadding="{$theme[\'tablespace\']}" cellspacing="{$theme[\'borderwidth\']}">
            <thead>
                <tr>
                    <td class="thead" colspan="4">Lonely Threads</td>
                </tr>
                <tr>
                    <td class="tcat">Thread</td>
                    <td class="tcat" align="center">Region</td>
                    <td class="tcat" align="center">User</td>
                    <td class="tcat" align="right">Last Post</td>
                </tr>
            </thead>
            <tbody>
                {$threadlist}
            </tbody>
        </table>

        {$footer}
      </body>
    </html>');
    $templatearray[] = new Template('row',
    '<tr>
      <td class="{$trow}"><a href="showthread.php?tid={$thread[\'tid\']}">{$thread[\'subject\']}</a></td>
      <td class="{$trow}" align="center"><strong>{$thread[\'name\']}</strong><br><small>{$thread[\'prefix\']}</small></td>
      <td class="{$trow}" align="center"><a href="member.php?action=profile&uid={$thread[\'uid\']}" >{$thread[\'username\']}</a></td>
      <td class="{$trow}" align="right">{$threaddate}</td>
    </tr>');
    $templatearray[] = new Template('groupfilter_nogroup',
    '<a href="misc.php?action=lonelythreads">View All</a> &middot; <a href="misc.php?action=lonelythreads&gid=0">Neutral Ground</a>');
    $templatearray[] = new Template('groupfilter_group',
    ' &middot; <a href="misc.php?action=lonelythreads&gid={$group[\'gid\']}">{$group[\'title\']}</a>');

    return $templatearray;
  }

}
