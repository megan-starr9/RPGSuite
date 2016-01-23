<?php
require_once MYBB_ROOT."/inc/plugins/rpg_suite/templatesets/class_TemplateSet.php";
require_once MYBB_ROOT."/inc/plugins/rpg_suite/templatesets/class_Template.php";

if(!defined("IN_MYBB"))
{
  die("You Cannot Access This File Directly. Please Make Sure IN_MYBB Is Defined.");
}
class GroupManageCPSet extends TemplateSet {
  /**
  Creates and destroys the external facing Group Management CP templates!
  Set Title: RPG Suite - Group Management CP
  **/
  Const SET_TITLE = 'RPG Suite - Group Management CP';
  Const SET_PREFIX = 'rpggroupmanagecp';

  protected function build_templates() {
    $templatearray = array();

    // Create full cp
    $templatearray[] = new Template('full',
    '<html>
	      <head>
	        <title>{$title}</title>
	        {$headerinclude}
        </head>
	      <body>
	        {$header}
			  <a href="modcp.php?action=managegroup{$groupnav}">Manage Ranks</a> &mdash;
			  <a href="modcp.php?action=managegroup&section=groupoptions{$groupnav}">Manage Options</a> &mdash;
			  <a href="modcp.php?action=managegroup&section=groupmembers{$groupnav}">Manage Members</a> &mdash;
        {$customranklink}
              {$cpcontent}
          {$footer}
	      </body>
      </html>');

    // Create Rank Management CP
    $templatearray[] = new Template('user_rank_cp',
    '<form method="post" action="">
    <table class="tborder" border="0" cellpadding="{$theme[\'tablespace\']}" cellspacing="{$theme[\'borderwidth\']}">
    <thead>
    <tr>
    <td class="thead" colspan=6>{$group[\'title\']} Rankings</td>
    </tr>
    <tr align="center">
    <td class="tcat">User</td>
    <td class="tcat">New Posts</td>
    <td class="tcat">New Threads</td>
    <td class="tcat">Last IC Post</td>
    <td class="tcat">Joined</td>
    <td class="tcat">Rank</td>
    </tr>
    </thead>
    <tbody>
    {$ranklist}
    <tr align="center">
    <td colspan=6><input type="submit" value="Update Ranks"></td>
    </tr>
    </tbody>
    </table>
    </form>');
    // Create User Rank Bit
    $templatearray[] = new Template('user_rank_row',
    '<tr align="center" class="{$user_row}">
    <td><a href="member.php?action=profile&amp;uid={$user[\'uid\']}">{$user[\'username\']}</a></td>
    <td>{$activitystats[\'postcount\']}</td>
    <td>{$activitystats[\'threadcount\']}</td>
    <td>{$user[\'lasticpost\']}</td>
    <td>{$user[\'groupjoindate\']}</td>
    <td>{$rankselect}</td>
    </tr>');

    // Create User Options CP
    $templatearray[] = new Template('user_manage_cp',
    '<form method="post" action="">
    <table class="tborder" border="0" cellpadding="{$theme[\'tablespace\']}" cellspacing="{$theme[\'borderwidth\']}">
    <thead>
    <tr>
    <td class="thead" colspan=2>{$group[\'title\']} Members</td>
    </tr>
    </thead>
    <tbody>
    {$memberlist}
    <tr align="center">
      <td><input type="submit" name="member_update" value="Update Members"></td>
      <td>
        <input type="text" class="textbox" name="username" id="username" style="width: 40%; margin-top: 4px;" value="Select User" />
        <input type="submit"  name="member_add" value="Add Member">
      </td>
    </tr>
    </tbody>
    </table>
    </form>

    <link rel="stylesheet" href="{$mybb->asset_url}/jscripts/select2/select2.css">
    <script type="text/javascript" src="{$mybb->asset_url}/jscripts/select2/select2.min.js?ver=1804"></script>
    <script type="text/javascript">
    <!--
    if(use_xmlhttprequest == "1")
    {
      MyBB.select2();
      $("#username").select2({
        placeholder: "{$lang->search_user}",
        minimumInputLength: 3,
        maximumSelectionSize: 3,
        multiple: false,
        ajax: { // instead of writing the function to execute the request we use Select2\'s convenient helper
          url: "xmlhttp.php?action=get_users",
          dataType: \'json\',
          data: function (term, page) {
            return {
              query: term, // search term
            };
          },
          results: function (data, page) { // parse the results into the format expected by Select2.
            // since we are using custom formatting functions we do not need to alter remote JSON data
            return {results: data};
          }
        },
        initSelection: function(element, callback) {
          var value = $(element).val();
          if (value !== "") {
            callback({
              id: value,
              text: value
            });
          }
        },
        // Allow the user entered text to be selected as well
        createSearchChoice:function(term, data) {
          if ( $(data).filter( function() {
            return this.text.localeCompare(term)===0;
          }).length===0) {
            return {id:term, text:term};
          }
        },
      });

      $(\'[for=username]\').click(function(){
        $("#username").select2(\'open\');
        return false;
      });
    }
    // -->
    </script>');
    // Create User Options Bit
    $templatearray[] = new Template('user_manage_row',
    '<tr class="{$user_row}">
    <td>{$user[\'username\']}</td>
    <td>{$memberoptions}
      <div style="float: left;width:200px;">
        Remove Member <input type="checkbox" name="delete_member[]" value="{$user[\'uid\']}" \>
      </div>
    </td>
    </tr>');
    // Create Setting Bit
    $templatearray[] = new Template('user_manage_setting',
    '<div style="float: left;width:200px;">
    {$setting[\'label\']}: {$setting[\'form\']}
    </div>');

    // Create Setting CP
    $templatearray[] = new Template('group_setting_cp',
    '<form method="post" action="">
    <table class="tborder" border="0" cellpadding="{$theme[\'tablespace\']}" cellspacing="{$theme[\'borderwidth\']}">
    <thead>
    <tr>
    <td class="thead" colspan=2>{$group[\'title\']} Options</td>
    </tr>
    </thead>
    <tbody>
    {$settinglist}
    <tr align="center">
    <td colspan=6><input type="submit" value="Update Settings"></td>
    </tr>
    </tbody>
    </table>
    </form>');
    // Create Group Setting Bit
    $templatearray[] = new Template('group_setting_row',
    '<tr class="{$setting_row}">
    <td align="center" width="50%"><strong>{$setting[\'label\']}</strong><br><small>{$setting[\'description\']}</small></td>
    <td>{$setting[\'form\']}</td>
    </tr>');

    return $templatearray;
  }

}
