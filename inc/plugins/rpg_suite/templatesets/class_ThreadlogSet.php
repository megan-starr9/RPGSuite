<?php
require_once MYBB_ROOT."/inc/plugins/rpg_suite/templatesets/class_TemplateSet.php";
require_once MYBB_ROOT."/inc/plugins/rpg_suite/templatesets/class_Template.php";

if(!defined("IN_MYBB"))
{
    die("You Cannot Access This File Directly. Please Make Sure IN_MYBB Is Defined.");
}
class ThreadlogSet extends TemplateSet {
  /**
  Creates and destroys the external facing Rank Table templates!
       Set Title: RPG Suite - Group/Rank View
  **/
  Const SET_TITLE = 'RPG Suite - Threadlog';
  Const SET_PREFIX = 'rpgthreadlog';

  protected function build_templates() {
    $templatearray = array();
    // Create full threadlog page
    $templatearray[] = new Template('page',
    '<html>
        <head><title>{$mybb->settings[\'bbname\']} - {$user[\'username\']}\'s Threadlog</title>
              {$headerinclude}
        </head>
        <body>
          {$header}
          {$multipage}

          <table id="threadlog" class="tborder" border="0" cellpadding="{$theme[\'tablespace\']}" cellspacing="{$theme[\'borderwidth\']}">
            <thead>
                <tr>
                    <td class="thead" colspan="4">{$user[\'username\']}\'s Threadlog</td>
                </tr>
                <tr>
                    <td class="tcat">Thread</td>
                    <td class="tcat" align="center">Participants</td>
                    <td class="tcat" align="center">Replies</td>
                    <td class="tcat" align="right">Last Post</td>
                </tr>
            </thead>
            <tbody>
                {$threadlog_list}
            </tbody>
            <tfoot>
                <tr><td class="tfoot" colspan="4" align="center">
                <a href="#" id="active">{$active_count} active</a> &middot;
                <a href="#" id="closed">{$closed_count} closed</a> &middot;
                <a href="#" id="need-replies">{$reply_count} need replies</a> &middot;
                <a href="#" id="show-all">{$total_count} total</a>
                </td></tr>
            </tfoot>
        </table>

        {$multipage}
        {$footer}
        <script type="text/javascript" src="{$mybb->settings[\'bburl\']}/inc/plugins/rpg_suite/scripts/threadlog.js"></script>
      </body>
    </html>');

    // Add Threadlog Row Bit
    $templatearray[] = new Template('row',
    '<tr class="{$thread_status}"><td class="{$thread_row}">{$thread_prefix} {$thread_title}</td>
    <td class="{$thread_row}" align="center">{$thread_participants}</td>
    <td class="{$thread_row}" align="center"><a href="javascript:MyBB.whoPosted({$thread['tid']});">{$thread[\'replies\']}</a></td>
    <td class="{$thread_row}" align="right">Last post by {$thread_latest_poster}<div class="smalltext">on {$thread_latest_date}</div></td></tr>');

    // Add Threadlog No Threads Bit
    $templatearray[] = new Template('nothreads',
    '<tr><td colspan="4">No threads to speak of.</td></tr>');


    return $templatearray;
  }
}
