<?php
if(!defined("IN_MYBB"))
{
    die("You Cannot Access This File Directly. Please Make Sure IN_MYBB Is Defined.");
}

require_once MYBB_ROOT."/inc/plugins/rpg_suite/models/class_GroupMember.php";
require_once MYBB_ROOT."/inc/plugins/rpg_suite/models/class_RankTable.php";
require_once MYBB_ROOT."/inc/plugins/rpg_suite/models/class_RPGSuite.php";
require_once MYBB_ROOT."/inc/plugins/rpg_suite/rpgsuite_defaults.php";

class UserGroup {
  /**
  User Group Master Class
  **/

  // MYBB Master Variables
  private $mybb;
  private $db;
  private $cache;

  private $info;
  private $members;
  private $ranks;

  public function __construct($mybb,$db,$cache, $info = null) {
    $this->mybb = $mybb;
    $this->db = $db;
    $this->cache = $cache;
    $this->info = $info;
  }

  /**
  If info isn't given, retrieve based on id
  */
  public function initialize($id) {
    $query = $this->db->simple_select('usergroups g left join '.TABLE_PREFIX.'icgroups i on g.gid = i.gid
            left join '.TABLE_PREFIX.'groupfield_values f on g.gid = f.gid', '*, g.gid', 'g.gid = '.$id);
    $this->info = $query->fetch_array();
    return $query->num_rows;
  }

  function get_info() {
    return $this->info;
  }

  /**
  Make given array of changes to group
  */
  function update_group($changes) {
    $update = "";
    foreach($changes as $key => $value) {
      $update .= $key." = '".$value."',";
    }

    $update = "SET ".rtrim($update,',');
    $this->db->query('UPDATE '.TABLE_PREFIX.'usergroups g left join '.TABLE_PREFIX.'icgroups i on g.gid = i.gid
              left join '.TABLE_PREFIX.'groupfield_values f on g.gid = f.gid '.$update.' WHERE g.gid ='.$this->info['gid']);

    $this->cache->update_usergroups();
  }

  /**
  Retrieve members and store in class
  */
  public function get_members() {
    if(is_array($this->members)) {
      // If we already retrieved it, don't bother again!
      return $this->members;
    }
    $memberlist = array();
    $query = $this->db->simple_select('users u inner join '.TABLE_PREFIX.'userfields f on u.uid = f.ufid ','*',
                'u.displaygroup = '.$this->info['gid'].' or u.usergroup = '.$this->info['gid']);

    while($user = $this->db->fetch_array($query)) {
      $memberlist[] = new GroupMember($this->mybb, $this->db, $this->cache, $user);
    }
    $this->members = $memberlist;
    return $memberlist;
  }

  /**
  Add a new member to the group
  */
  public function add_member($uid) {

    // Set display group
    $member = new GroupMember($this->mybb, $this->db, $this->cache);
    if($member->initialize($uid)) {
      $user = $member->get_info();

      // If they are a pm of their old group, they won't be now!
      $this->demote_member($uid);
      // If they are in a group currently, remove it from additional
      leave_usergroup($user['uid'], $user['displaygroup']);
      // add new to additional groups
      join_usergroup($user['uid'], $this->info['gid']);

      $updatearray = array('displaygroup' => $this->info['gid'], 'group_dateline' => time());
      $member->update_member($updatearray);
      $member->update_rank($this->info['defaultrank']);
    }
  }
  /**
  Add an existing member to the actual class (not the database)
  */
  public function addmember_nostore(GroupMember $user) {
    $this->members[] = $user;
  }
  /**
  Add a new member to the group by name instead
  */
  public function add_member_byname($username) {
    $userquery = $this->db->simple_select('users', 'uid', 'username = "'.$username.'"');
    $user = $this->db->fetch_array($userquery);

    $this->add_member($user['uid']);
  }

  /**
  Remove a member in an ic capacity (move to default IC group)
  */
  public function remove_member($uid) {
    $this->demote_member($uid);
    $defaultgroup = new UserGroup($this->mybb, $this->db, $this->cache);
    if($defaultgroup->initialize(Groups::IC_DEFAULT)) {
      $defaultgroup->add_member($uid);
    }
  }
  /**
  Remove a member in an ooc capacity (move to default OOC group)
  */
  public function hard_remove_member($uid) {
    $this->demote_member($uid);
    $defaultgroup = new UserGroup($this->mybb, $this->db, $this->cache);
    if($defaultgroup->initialize(Groups::MEMBER)) {
      $defaultgroup->add_member($uid);
    }
  }

  /**
  Make a member a group manager
  */
  public function promote_member($uid) {
    $groupleaderperms = Creation::GROUPLEADER;
    $groupleaderperms['gid'] = $this->info['gid'];
    $groupleaderperms['uid'] = $uid;
    $this->db->insert_query('groupleaders', $groupleaderperms);

    $modperms = Creation::MODERATOR;
    $modperms['id'] = $uid;
    if($this->info['fid']) {
      $modperms['fid'] = $this->info['fid'];
      $this->db->insert_query('moderators', $modperms);
    }
    if($this->info['mo_fid']) {
      $modperms['fid'] = $this->info['mo_fid'];
      $this->db->insert_query('moderators', $modperms);
    }

    $this->db->update_query('users', array('usergroup' => Groups::MOD), 'uid = '.$uid);

    $this->cache->update_moderators();
    $this->cache->update_groupleaders();
  }

  public function promote_member_byname($uname) {
    $query = $this->db->simple_select('users', 'uid', 'username = \''.$uname.'\'');
    $user = $this->db->fetch_array($query);

    $groupleaderperms = Creation::GROUPLEADER;
    $groupleaderperms['gid'] = $this->info['gid'];
    $groupleaderperms['uid'] = $user['uid'];
    $this->db->insert_query('groupleaders', $groupleaderperms);

    $modperms = Creation::MODERATOR;
    $modperms['id'] = $user['uid'];
    if($this->info['fid']) {
      $modperms['fid'] = $this->info['fid'];
      $this->db->insert_query('moderators', $modperms);
    }
    if($this->info['mo_fid']) {
      $modperms['fid'] = $this->info['mo_fid'];
      $this->db->insert_query('moderators', $modperms);
    }

    $this->db->update_query('users', array('usergroup' => Groups::MOD), 'uid = '.$user['uid']);

    $this->cache->update_moderators();
    $this->cache->update_groupleaders();
  }

  /**
  Remove member from management
  */
  public function demote_member($uid) {
    $this->db->query('DELETE FROM '.TABLE_PREFIX.'groupleaders WHERE uid = '.$uid.' AND gid = '.$this->info['gid']);
    if($this->info['fid'])
      $this->db->query('DELETE FROM '.TABLE_PREFIX.'moderators WHERE id = '.$uid.' AND fid = '.$this->info['fid']);
    if($this->info['mo_fid'])
      $this->db->query('DELETE FROM '.TABLE_PREFIX.'moderators WHERE id = '.$uid.' AND fid = '.$this->info['mo_fid']);
    $this->db->update_query('users', array('usergroup' => Groups::MEMBER), 'uid = '.$uid);

    $this->cache->update_moderators();
    $this->cache->update_groupleaders();
  }

  /**
  Get simple count of total members
  */
  public function get_member_count() {
    if(is_array($this->members)) {
      return count($this->members);
    } else {
      $query = $this->db->simple_select('users', 'uid', 'displaygroup = '.$this->info['gid'].' or usergroup = '.$this->info['gid']);
      return $query->num_rows;
    }
  }

  /**
  Get paginated list of members
  */
  public function get_members_offset($offset, $limit) {
    if(is_array($this->members)) {
      // If we already retrieved it, don't bother again!
      return array_slice($this->members, $offset, $limit);
    }
    $memberlist = array();
    $query = $this->db->simple_select('users u inner join '.TABLE_PREFIX.'userfields f on u.uid = f.ufid ','*',
                'u.displaygroup = '.$this->info['gid'].' or u.usergroup = '.$this->info['gid'].' LIMIT '.$offset.', '.$limit);

    while($member = $this->db->fetch_array($query)) {
      $memberlist[] = new GroupMember($this->mybb, $this->db, $this->cache, $member);
    }
    $this->members = $memberlist;
    return $memberlist;
  }

  /**
  Get group's special ranks (returns default if admin set to 0 and no custom ranks exist)
  If admin set to 1, returns empty list of custom (since likely modifying those)
  */
  public function get_ranks($foradmin = 0) {
    if(isset($this->ranks)) {
      return $this->ranks;
    }
    $this->ranks = new RankTable($this->mybb, $this->db, $this, $foradmin);
    return $this->ranks;
  }

  /**
  Remove the group from the game
  */
  public function disband() {
    //remove forums
    $this->remove_forums();

    //Move all members to default group (demote if leader)
    foreach($this->get_members() as $member) {
      $meminfo = $member->get_info();
      if($meminfo['isleader']) {
        $this->demote_member($meminfo['uid']);
      }
      $this->remove_member($meminfo['uid']);
    }

    //Delete usergroup (icgroup and groupfield also)
    $this->db->query('DELETE FROM '.TABLE_PREFIX.'usergroups WHERE gid = '.$this->info['gid']);
    $this->db->query('DELETE FROM '.TABLE_PREFIX.'groupfield_values WHERE gid = '.$this->info['gid']);
    $this->db->query('DELETE FROM '.TABLE_PREFIX.'icgroups WHERE gid = '.$this->info['gid']);

    //Delete any custom ranks
    $ranktable = $this->get_ranks(1);
    foreach($ranktable->get_tiers() as $tier) {
      $tierstring .= $tier['id'].',';
    }
    $ranktable->delete_tiers(trim($tierstring,','));

    $this->cache->update_usergroups();
  }
  /**
  Move group to new location (forums)
  */
  public function relocate($settings) {
    $rpglib = new RPGSuite($this->mybb, $this->db, $this->cache);

    // Remove old forums
    $this->remove_forums();
    // Create new forums
    $ids = $rpglib->create_groupforums($this->info['gid'], $settings);

    // Update group
    $this->update_group(array(
      'title' => $settings['title'],
      'fid' => $ids['fid'],
      'mo_fid' => $ids['mofid']
    ));

    // Set leaders as moderators
    $modperms = Creation::MODERATOR;
    foreach($this->get_members() as $member) {
      $user = $member->get_info();
      if($member->is_leader()) {
        $modperms['id'] = $user['uid'];
        $modperms['fid'] = $ids['fid'];
        $this->db->insert_query('moderators', $modperms);
        $modperms['fid'] = $ids['mofid'];
        $this->db->insert_query('moderators', $modperms);
      }
    }

    $this->cache->update_forums();
    $this->cache->update_moderators();
    $this->cache->update_forumpermissions();
    $this->cache->update_threadprefixes();
  }
  /**
  Remove the group's forums from the game
  */
  public function remove_forums() {
    if($this->info['fid']) {
      $forumquery = $this->db->simple_select('forums', '*', 'fid = '.$this->info['fid']);
      while($forum = $this->db->fetch_array($forumquery)) {
        //Move prefix to parent board
          $prefixquery = $this->db->simple_select('threadprefixes','*', 'CONCAT(\',\',forums,\',\') LIKE \'%,'.$this->info['fid'].',%\'');
          while($prefix = $this->db->fetch_array($prefixquery)) {
            $forums = explode(',', $prefix['forums']);
            foreach($forums as $f) {
              $forumstring .= ($f !== $this->info['fid']) ? $f.',' : $forum['pid'].',';
            }
            $this->db->update_query('threadprefixes', array('forums' => trim($forumstring, ',')), 'pid = '.$prefix['pid']);
          }

          //Move threads to parent board
          $threadquery = $this->db->simple_select('threads', '*', 'fid = '.$this->info['fid']);
          while($thread = $this->db->fetch_array($threadquery)) {
            $threadstring .= $thread['tid'].',';
          }
          $threadstring = rtrim($threadstring, ',');
          if(!empty($threadstring)) {
            $this->db->update_query('threads', array('fid' => $forum['pid']), 'tid IN ('.$threadstring.')');
            $this->db->update_query('posts', array('fid' => $forum['pid']), 'tid IN ('.$threadstring.')');
            update_forum_lastpost($forum['pid']);
          }
      }

      //Delete MO forum
      $this->db->query('DELETE FROM '.TABLE_PREFIX.'forums WHERE fid = '.$this->info['mo_fid']);
      $threadquery = $this->db->simple_select('threads','*','fid = '.$this->info['mo_fid']);
      while($thread = $this->db->fetch_array($threadquery)) {
        delete_thread($thread['tid']);
      }

      //Delete all permissions
      $this->db->query('DELETE FROM '.TABLE_PREFIX.'forumpermissions WHERE fid = '.$this->info['mo_fid']);
      $this->db->query('DELETE FROM '.TABLE_PREFIX.'moderators WHERE fid = '.$this->info['mo_fid']);
      $this->db->query('DELETE FROM '.TABLE_PREFIX.'moderators WHERE fid = '.$this->info['fid']);

      //Delete forum
      $this->db->query('DELETE FROM '.TABLE_PREFIX.'forums WHERE fid = '.$this->info['fid']);
    }

    $this->cache->update_forums();
    $this->cache->update_moderators();
    $this->cache->update_forumpermissions();
    $this->cache->update_threadprefixes();
  }


  /**
  Setting building below!
  */
  public function get_settings_mod() {
    $settings = array_merge($this->build_preset_settings(0), $this->build_custom_settings(0));

    return $settings;
  }
  public function get_settings_admin() {
    $settings = array_merge($this->build_preset_settings(1), $this->build_custom_settings(1));

    return $settings;
  }

  private function build_preset_settings($isadmin) {
    $settings = array();
    $settings[] = array('label' => 'Activity Period',
      'name' => 'activityperiod',
      'description' => 'Number of days to count back for post/thread counts within the ModCP.',
      'form' => '<input type="text" class="text_input textbox" name="activityperiod" value="'.(int)$this->info['activityperiod'].'" />'
    );
    $settings[] = array('label' => 'Date Founded',
      'name' => 'founded',
      'description' => 'The date the pack was founded upon.',
      'form' => '<input type="text" class="text_input textbox" name="founded" value="'.date('m/d/Y', $this->info['founded']).'" />'
    );
    $settings[] = array('label' => 'Joining Blurb',
      'name' => 'description',
      'description' => 'The terms of joining (displays on Usergroup listing in User CP).',
      'form' => '<textarea name="description" rows="5" cols="45">'.htmlspecialchars_uni($this->info['description']).'</textarea>'
    );
    if($isadmin) {
      $settings[] = array('label' => 'IC Forum Id',
        'name' => 'fid',
        'description' => 'ID of group IC Forum.',
        'form' => '<input type="text" class="text_input" name="fid" value="'.(int)$this->info['fid'].'" />'
      );
      $settings[] = array('label' => 'Members Only Forum Id',
        'name' => 'mo_fid',
        'description' => 'ID of group OOC, members only forum.',
        'form' => '<input type="text" class="text_input" name="mo_fid" value="'.(int)$this->info['mo_fid'].'" />'
      );
      $settings[] = array('label' => 'Username Style',
        'name' => 'namestyle',
        'description' => 'Styling of username.  Don\'t forget to leave {username} or it will not work!',
        'form' => '<input type="text" class="text_input" name="namestyle" value="'.htmlspecialchars_uni($this->info['namestyle']).'" />'
      );
      $settings[] = array('label' => 'Group Point Value',
        'name' => 'grouppoints',
        'description' => 'The points rewarded (or subtracted if negative) from the group each time the point checker runs.  Leave zero to ignore group.',
        'form' => '<input type="text" class="text_input" name="grouppoints" value="'.(int)$this->info['grouppoints'].'" />'
      );
      $hasranks = ($this->info['hasranks']) ? 'checked' : '';
      $settings[] = array('label' => 'Group Has Ranks?',
        'name' => 'hasranks',
        'description' => 'Is the group one with an internal hierarchy?',
        'form' => '<input type="checkbox" name="hasranks" value="1" '.$hasranks.'/>'
      );
      $activitycheck = ($this->info['activitycheck']) ? 'checked' : '';
      $settings[] = array('label' => 'Include in Activity Check?',
        'name' => 'activitycheck',
        'description' => 'Should members of this group be removed for inactivity?',
        'form' => '<input type="checkbox" name="activitycheck" value="1" '.$activitycheck.'/>'
      );
    }

    return $settings;
  }

  private function build_custom_settings($isadmin) {
    $rpglib = new RPGSuite($this->mybb, $this->db, $this->cache);

    $customsettings = array();
    $query = $this->db->simple_select('groupfields','*','onlyadmin IN (0, '.$isadmin.')',array(
            "order_by" => 'disporder',
            "order_dir" => 'ASC'));
    while($setting = $query->fetch_array()) {
      $customsettings[] = array('label' => $setting['name'],
        'name' => 'fid'.$setting['fid'],
        'description' => $setting['description'],
        'form' => $rpglib->parse_setting($setting, $this->info['fid'.$setting['fid']])
      );
    }
    return $customsettings;
  }

}
