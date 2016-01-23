<?php
if(!defined("IN_MYBB"))
{
    die("You Cannot Access This File Directly. Please Make Sure IN_MYBB Is Defined.");
}

require_once MYBB_ROOT."/inc/plugins/rpg_suite/models/class_UserGroup.php";
require_once MYBB_ROOT."/inc/plugins/rpg_suite/models/class_GroupMember.php";
require_once MYBB_ROOT."/inc/plugins/rpg_suite/rpgsuite_defaults.php";

class RPGSuite {
  /**
  RPG Suite Master Class
  **/

  //MYBB Master Variables
  private $mybb;
  private $db;
  private $cache;

  private $icgroups;
  private $icgroups_withmembers;

  function __construct($mybb, $db, $cache) {
    $this->mybb = $mybb;
    $this->db = $db;
    $this->cache = $cache;
  }

  /**
  Given optional criteria, returns listing of ic groups only (no members)
  */
  function get_icgroups($criteria = '1') {
    if(is_array($this->icgroups)) {
      // if these are already set, don't bother
      return $this->icgroups;
    }
    $icgrouparray = array();
    $query = $this->db->simple_select('usergroups u inner join '.TABLE_PREFIX.'icgroups i on u.gid = i.gid','*, u.gid', $criteria,
        array(
          "order_by" => 'hasranks',
          "order_dir" => 'DESC'
        ));
    while($icgroup = $this->db->fetch_array($query)) {
      $icgrouparray[$icgroup['gid']] = new UserGroup($this->mybb, $this->db, $this->cache, $icgroup);
    }
    $this->icgroups = $icgrouparray;
    return $icgrouparray;
  }

  /**
  Given optional criteria, returns listing of ic groups and their members
  */
  function get_icgroups_members($groupcriteria = '1', $membercriteria = '1') {
    if(is_array($this->icgroups_withmembers)) {
      // if these are already set, don't bother
      return $this->icgroups_withmembers;
    }
    $icgrouparray = array();
    $query = $this->db->simple_select('usergroups u inner join '.TABLE_PREFIX.'icgroups i on u.gid = i.gid','*, u.gid', $groupcriteria,
        array(
          "order_by" => 'hasranks',
          "order_dir" => 'DESC'
        ));
    while($icgroup = $this->db->fetch_array($query)) {
      $icgrouparray[$icgroup['gid']] = new UserGroup($this->mybb, $this->db, $this->cache, $icgroup);
    }
    $query = $this->db->simple_select('users u inner join '.TABLE_PREFIX.'userfields f on u.uid = f.ufid',
            '*', $membercriteria." AND exists(select 1 from ".TABLE_PREFIX."icgroups WHERE gid = u.displaygroup or gid = u.usergroup)");
    while($character = $this->db->fetch_array($query)) {
      $group = $icgrouparray[$character['displaygroup']];
      if($group) {
        $group->addmember_nostore(new GroupMember($this->mybb, $this->db, $this->cache, $character));
      }
    }
    $this->icgroups_withmembers = $icgrouparray;
    return $icgrouparray;
  }

  public function retrieve_forum_group($fid) {
    $groupquery = $this->db->simple_select('usergroups g INNER JOIN '.TABLE_PREFIX.'icgroups i ON g.gid = i.gid',
                                'g.gid', 'fid = '.$fid.' OR mo_fid = '.$fid);
    if($groupquery->num_rows) {
      $group = $groupquery->fetch_array();
      $usergroup = new UserGroup($this->mybb, $this->db, $this->cache);
      if($usergroup->initialize($group['gid'])) {
        return $usergroup;
      }
    }
    return false;
  }

  /**
  Retrieve any users who are awaiting activation/approval
  */
  public function get_awaiting_approval() {
    $userquery = $this->db->simple_select('users u INNER JOIN '.TABLE_PREFIX.'userfields f ON u.uid = f.ufid', '*', 'usergroup = '.Groups::WAITING);
    $users = array();
    while($user = $this->db->fetch_array($userquery)) {
      $users[] = $user;
    }
    return $users;
  }

  /**
  Get Lonely Threads
  */
  public function get_lonely_threads($pack = -1) {
    $filter = '';
    if($pack == 0) {
      $filter = ' AND NOT EXISTS (SELECT 1 FROM '.TABLE_PREFIX.'icgroups i WHERE f.fid = i.fid)';
    } else if($pack > 0) {
      $filter = ' AND EXISTS (SELECT 1 FROM '.TABLE_PREFIX.'icgroups i WHERE i.gid = '.$pack.' AND f.fid = i.fid)';
    }
    
    $threadquery = $this->db->simple_select('threads t INNER JOIN '.TABLE_PREFIX.'forums f ON t.fid = f.fid INNER JOIN '.TABLE_PREFIX.'threadprefixes p ON t.prefix = p.pid',
          '*','f.active = 1 AND f.open = 1 AND f.icforum = 1 AND t.closed = 0 AND t.visible = 1 AND t.replies = 0'.$filter,
          array(
            "order_by" => 'dateline',
            "order_dir" => 'DESC'
          ));
    $threads = array();
    while($thread = $this->db->fetch_array($threadquery)) {
      $threads[] = $thread;
    }
    return $threads;
  }

  /**
  Function used to turn settings into form
  */
  public function parse_setting($setting, $value, $id = "") {
    $thing = explode("\n", $setting['type'], "2");
    $type = trim($thing[0]);
    $options = $thing[1];

    if($type == 'text') {
      $form = '<input type="text" class="text_input textbox" name="fid'.$setting['fid'].$id.'" value="'.htmlspecialchars_uni($value).'" />';
    } else if($type == 'select') {
      $expoptions = explode("\n", $options);
      $form = '<select name="fid'.$setting['fid'].$id.'">';
      foreach($expoptions as $option) {
        $sel = '';
        if($option == $value) {
          $sel = ' selected="selected"';
        }
        $form .= '<option value="'.$option.'" '.$sel.'>'.$option.'</option>';
      }
      $form .= '</select>';
    } else if($type == 'textarea') {
      $form = '<textarea name="fid'.$setting['fid'].$id.'" rows="5" cols="45">'.htmlspecialchars_uni($value).'</textarea>';
    }
    return $form;
  }

  function get_groupfields() {
    $fieldarray = array();
    $fieldquery = $this->db->simple_select('groupfields', '*');
    while ($field = $this->db->fetch_array($fieldquery)) {
      $fieldarray[] = $field;
    }
    return $fieldarray;
  }

  function update_groupfield($field) {
    if(!isset($field['fid'])) {
      // Creating
      $this->db->insert_query('groupfields',$field);
      $fid = $this->db->insert_id();
      //Add Column
      if(!$this->db->field_exists("fid".$fid, "groupfield_values")) {
        if($field['type'] == 'text') {
          $type = "VARCHAR(200)";
        } else {
          $type = "VARCHAR(5000)";
        }
        $this->db->write_query("ALTER TABLE `".TABLE_PREFIX."groupfield_values` ADD COLUMN `fid".$fid."` ".$type."");
      }
    } else {
      //Modifying
      $this->db->update_query('groupfields',$field,'fid = '.$field['fid']);
    }
  }

  function delete_groupfields($idstring) {
      if(!empty($idstring)) {
        $this->db->query('DELETE FROM '.TABLE_PREFIX.'groupfields WHERE fid IN ('.$idstring.')');
      }
  }

  /**
  Create a new IC Group from given list of attributes
  */
  public function create_icgroup($settings) {
    // Get array of all current groups
    $othergroups = array();
    $groupquery = $this->db->simple_select('usergroups g left join '.TABLE_PREFIX.'icgroups i on g.gid = i.gid','*, g.gid');
    while($og = $this->db->fetch_array($groupquery)) {
      $othergroups[] = $og;
    }
    // Create the group
    $group = Creation::USERGROUP;
    $group['title'] = $settings['title'];
    $group['description'] = $settings['description'];
    $group['namestyle'] = $settings['namestyle'];
    $group['image'] = $settings['image'];

    $this->db->insert_query('usergroups',$group);
    $gid = $this->db->insert_id();

    // Set permissions on other MO forums to noread
    $mopermissions = Creation::FORUM_PERM_NOREAD;
    $mopermissions['gid'] = $gid;
    foreach($othergroups as $othergroup) {
      if(!empty($othergroup['mo_fid'])) {
        $mopermissions['fid'] = $othergroup['mo_fid'];
        $this->db->insert_query('forumpermissions',$mopermissions);
      }
    }
    // Set staff forums to noread
    $mopermissions['fid'] = Forums::STAFFCATEGORY;
    $this->db->insert_query('forumpermissions', $mopermissions);

    $fid = $mofid = 0;
    if(!empty($settings['region'])) {
      $fid_array = $this->create_groupforums($gid, $settings);
      $fid = $fid_array['fid'];
      $mofid = $fid_array['mofid'];
    }

    //Create IC group entry
    $icgroup = Creation::ICGROUP;
    $icgroup['gid'] = $gid;
    $icgroup['fid'] = $fid;
    $icgroup['mo_fid'] = $mofid;
    $icgroup['founded'] = time();
    $this->db->insert_query('icgroups', $icgroup);

    //Create group customfield entry
    $this->db->insert_query('groupfield_values',array('gid' => $gid));

    $usergroup = new UserGroup($this->mybb, $this->db, $this->cache);
    $usergroup->initialize($gid);
    //Add Members
    $members = explode(',', $settings['members']);
    $pms = explode(',', $settings['pms']);
    foreach(array_merge($members, $pms) as $mname) {
      if(!empty($mname)) {
        $usergroup->add_member_byname($mname);
      }
    }
    //Add & Promote moderators
    foreach($pms as $pm) {
      if(!empty($pm)) {
        $usergroup->promote_member_byname($pm);
      }
    }

    $this->cache->update_usergroups();
  }

  /**
  Create new forums for an IC group based on the given attributes
  */
  public function create_groupforums($gid, $settings) {
    $idarray = array();

    // Configure Prefix & Region
    $prefixquery = $this->db->simple_select('threadprefixes','*', 'pid = '.$settings['prefix']);
    $prefix = $this->db->fetch_array($prefixquery);
    $forumarray = explode(',', $prefix['forums']);
    $forums = '';
    if(!empty($prefix['forums'])) {
      foreach($forumarray as $forum) {
        if($forum != $settings['region']) {
          $forums .= $forum.',';
        }
      }
    }

    // Create the IC Forum for the group
    $forum = Creation::IC_FORUM;
    $forum['name'] = $settings['title'];
    $forum['description'] = 'This pack claims the territory of <strong>'.$prefix['prefix'].'</strong>.
        If you aren\'\'t a member of this pack, posting in this forum means you\'\'re trespassing!';
    $forum['pid'] = $settings['region'];

    $this->db->insert_query('forums', $forum);
    $fid = $this->db->insert_id();
    $idarray['fid'] = $fid;

    $this->db->update_query('forums',array('parentlist' => make_parent_list($fid)), 'fid = '.$fid);

    // Update the prefix with the new forum
    $this->db->update_query('threadprefixes', array('forums' => $forums.$fid), 'pid = '.$settings['prefix']);

    //Move existing prefixed threads to new board
    $threadquery = $this->db->simple_select('threads', '*', 'fid = '.$forum['pid'].' AND prefix = '.$settings['prefix']);
    while($thread = $this->db->fetch_array($threadquery)) {
      $threadstring .= $thread['tid'];
    }
    if(!empty($threadstring)) {
      $this->db->update_query('threads', array('fid' => $fid), 'tid IN ('.$threadstring.')');
      $this->db->update_query('posts', array('fid' => $fid), 'tid IN ('.$threadstring.')');
      update_forum_lastpost($fid);
    }

    $moid = 0;
    // Create the Members only subforum
    $moforum = Creation::OOC_FORUM;
    $moforum['name'] = $settings['title'].' Members Only';
    $moforum['description'] = '';
    $moforum['pid'] = $fid;

    $this->db->insert_query('forums', $moforum);
    $mofid = $this->db->insert_id();
    $idarray['mofid'] = $mofid;

    $this->db->update_query('forums',array('parentlist' => make_parent_list($fid).','.$mofid), 'fid = '.$mofid);

    // Set permissions for other groups to noread
    // Get array of all current groups
    $othergroups = array();
    $groupquery = $this->db->simple_select('usergroups g left join '.TABLE_PREFIX.'icgroups i on g.gid = i.gid','*, g.gid', 'g.gid NOT IN ('.$gid.','.Groups::ADMIN.')');
    while($og = $this->db->fetch_array($groupquery)) {
      $othergroups[] = $og;
    }
    $mopermissions = Creation::FORUM_PERM_NOREAD;
    if(!empty($othergroups)) {
      foreach($othergroups as $othergroup) {
        if(!empty($mofid)) {
          $mopermissions['fid'] = $mofid;
          $mopermissions['gid'] = $othergroup['gid'];
          $this->db->insert_query('forumpermissions',$mopermissions);
        }
      }
    }
    // Set permissions to read
    $momemberpermissions = Creation::FORUM_PERM_READWRITE;
    $momemberpermissions['fid'] = $mofid;
    $momemberpermissions['gid'] = $gid;
    $this->db->insert_query('forumpermissions',$momemberpermissions);
    $momemberpermissions['gid'] = Groups::ADMIN;
    $this->db->insert_query('forumpermissions',$momemberpermissions);

    $this->cache->update_forums();
    $this->cache->update_forumpermissions();
    $this->cache->update_threadprefixes();

    return $idarray;
  }

  /**
  Return a select option list of region boards (IC boards with no group ownership)
  */
  public function generate_regionoptions() {
    $regionquery = $this->db->simple_select('forums f','f.fid, f.name','f.active = 1 AND f.open = 1 AND f.icforum = 1 and
                                    not exists(SELECT 1 FROM '.TABLE_PREFIX.'icgroups i WHERE f.fid = i.fid )');
    $select = '';
    while($region = $this->db->fetch_array($regionquery)) {
      $select .= '<option value="'.$region['fid'].'">'.$region['name'].'</option>';
    }
    return $select;
  }
  /**
  Return a select option list of available territories (prefixes within the given region)
  */
  public function generate_prefixselect($forumid = 0) {
    if(!$forumid) {
      $regionquery = $this->db->query('SELECT f.fid, f.name from '.TABLE_PREFIX.'forums f WHERE f.active = 1 AND f.open = 1 AND f.icforum = 1 and
                                      not exists(SELECT 1 FROM '.TABLE_PREFIX.'icgroups i WHERE f.fid = i.fid) LIMIT 1');
      $result = $this->db->fetch_array($regionquery);
      $forumid = $result['fid'];
    }
    /** MYBB Function for prefix selection **/
    return build_forum_prefix_select($forumid);
  }

  /**
  Convert the given group to an ic group
  */
  public function convert_to_ic($gid) {
    $record_exists = $this->db->simple_select('icgroups','gid','gid = '.$gid);
    if(!$record_exists->num_rows) {
      //Create icgroup record
      $this->db->insert_query('icgroups', array('gid' => $gid));
      //Create group field record
      $this->db->insert_query('groupfield_values', array('gid' => $gid));
    }
  }
  /**
  Revert the given group to an ooc group
  */
  public function revert_to_ooc($gid) {
    //Delete icgroup record
    $this->db->query('delete from '.TABLE_PREFIX.'icgroups where gid = '.$gid);
    //Delete group field record
    $this->db->query('delete from '.TABLE_PREFIX.'groupfield_values where gid = '.$gid);
  }
  /**
  Get the listing of default ranks
  */
  public function get_default_ranktable() {
    $usergroup = new UserGroup($this->mybb, $this->db, $this->cache, array('gid' => '0'));
    return $usergroup->get_ranks(1);
  }

}
