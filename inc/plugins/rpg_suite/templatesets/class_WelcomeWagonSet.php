<?php
require_once MYBB_ROOT."/inc/plugins/rpg_suite/templatesets/class_TemplateSet.php";
require_once MYBB_ROOT."/inc/plugins/rpg_suite/templatesets/class_Template.php";

if(!defined("IN_MYBB"))
{
    die("You Cannot Access This File Directly. Please Make Sure IN_MYBB Is Defined.");
}
class WelcomeWagonSet extends TemplateSet {
  /**
  Creates and destroys the external facing Rank Table templates!
       Set Title: RPG Suite - Group/Rank View
  **/
  Const SET_TITLE = 'RPG Suite - Approval Process';
  Const SET_PREFIX = 'rpgapprove';

  protected function build_templates() {
    $templatearray = array();
    // Create full threadlog page
    $templatearray[] = new Template('page',
    '<html>
        <head><title>Approve Users</title>
              {$headerinclude}
        </head>
        <body>
          {$header}
          {$multipage}

          <h2>Users Awaiting Approval</h2>

          {$userlist}

        {$multipage}
        {$footer}
      </body>
    </html>');

    $templatearray[] = new Template('user',
    '<form method="post" action="">
<table id="threadlog" class="tborder" border="0" cellpadding="{$theme[\'tablespace\']}" cellspacing="{$theme[\'borderwidth\']}">
	<thead>
		<tr>
			<td class="thead" colspan=2>
				<strong><a target="_blank" href="member.php?action=profile&uid={$user[\'uid\']}">{$user[\'username\']}</a></strong> &mdash; {$user[\'fid38\']} <small>(Played by {$user[\'fid3\']})</small>
				<div style="float:right;">
					<input type="hidden" name="userid" value="{$user[\'uid\']}">
          <input type="hidden" name="username" value="{$user[\'username\']}">
					<input type="hidden" name="type" value="{$user[\'fid38\']}">
					<input type="submit" class="submit" name="approve" value="Approve">
					<input type="submit" class="submit" name="deny" value="Deny">
				</div>
			</td>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td>
				<fieldset><legend><strong>Details</strong></legend>
					<strong>Name:</strong> {$user[\'fid5\']} <br>
					<strong>Gender:</strong> {$user[\'fid6\']}<br>
					<strong>Species:</strong> {$user[\'fid7\']}<br>
					<strong>Age:</strong> {$user[\'fid8\']} ({$user[\'fid9\']})<br>
					<strong>Birthplace:</strong> {$user[\'fid10\']}
				</fieldset>
			</td>
			<td>
				<fieldset><legend><strong>At A Glance</strong></legend>
					{$user[\'fid17\']}
				</fieldset>
			</td>
		</tr>
		<tr>
			<td colspan=2>
				<fieldset><legend><strong>Appearance</strong></legend>
					{$user[\'fid11\']}
				</fieldset>
			</td>
		</tr>
		<tr>
			<td colspan=2>
				<fieldset><legend><strong>Personality</strong></legend>
					{$user[\'fid12\']}
				</fieldset>
			</td>
		</tr>
		<tr>
			<td colspan=2>
				<fieldset><legend><strong>History</strong></legend>
					{$user[\'fid13\']}
				</fieldset>
			</td>
		</tr>
		<tr>
			<td>
				<fieldset><legend><strong>Relations</strong></legend>
					{$user[\'fid14\']}
				</fieldset>
			</td>
			<td>
				<fieldset><legend><strong>Pack History</strong></legend>
					{$user[\'fid18\']}
				</fieldset>
			</td>
		</tr>
	</tbody>
</table>
</form>');

    $templatearray[] = new Template('notification',
    '<a href="index.php?action=activationqueue">
	<div style="width:200px;height:30px;border-radius:10px;background-color:#bbb;line-height:30px;text-align:center;"><strong>{$waiting_count}</strong> Users are awaiting activation</div>
</a>');

    return $templatearray;
  }
}
