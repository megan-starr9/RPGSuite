<?php
if(!defined("IN_MYBB"))
{
    die("You Cannot Access This File Directly. Please Make Sure IN_MYBB Is Defined.");
}

require_once MYBB_ROOT."/inc/plugins/rpg_suite/models/class_RPGSuite.php";
require_once MYBB_ROOT."/inc/plugins/rpg_suite/rpgsuite_defaults.php";

class GroupMember {
  /**
   Group Member Master Class
  **/

  // MYBB Master Variables
  private $mybb;
  private $db;
  private $cache;

  private $info;
  private $rank;

  public function __construct($mybb,$db, $cache, $info = null) {
    $this->mybb = $mybb;
    $this->db = $db;
    $this->cache = $cache;
    $this->info = $info;
  }

  public function initialize($id) {
    if($id) {
      $query = $this->db->simple_select('users u left join '.TABLE_PREFIX.'userfields f on u.uid = f.ufid', '*', 'u.uid = '.$id);
      $this->info = $query->fetch_array();
      return $query->num_rows;
    }
    return 0;
  }

  function update_member($changes) {
    $update = "";
    foreach($changes as $key => $value) {
      $update .= $key." = '".$value."',";
    }
    $update = "SET ".rtrim($update,',');
    $this->db->query('UPDATE '.TABLE_PREFIX.'users u left join '.TABLE_PREFIX.'userfields f on u.uid = f.ufid
               '.$update.' WHERE u.uid ='.$this->info['uid']);
  }

  public function get_info() {
    return $this->info;
  }

  public function is_admin() {
    $groups = explode(',', $this->info['additionalgroups']);
    $groups[] = $this->info['usergroup'];

    return in_array(Groups::ADMIN, $groups);
  }

  public function is_leader() {
    $groups = explode(',', $this->info['additionalgroups']);
    $groups[] = $this->info['usergroup'];

    return in_array(Groups::MOD, $groups);
  }

  public function get_rank() {
    $query = $this->db->simple_select('groupranks','*','id ='.$this->info['grouprank']);
    return $query->fetch_array();
  }

  public function generate_rank_select() {
      $rankopts = array(
        0 => 'None'
      );

      // get current group
      $gid = $this->info['displaygroup'];

      // see if current group has any custom tiers
      $i = 0;
      $query = $this->db->simple_select('grouptiers', '*', 'gid = '. $gid);
      while($tier = $query->fetch_assoc()) {
        $i++;
      }

      // set to zero if there are no custom tiers
      if ($i == 0) {
        $gid = 0;
      }

      // print select options for this group
      $query = $this->db->simple_select('groupranks', '*', 'exists(select 1 from '.TABLE_PREFIX.'grouptiers t where t.gid = '. $gid .' and tid = t.id)', array(
        "order_by" => 'seq',
        "order_dir" => 'ASC'));
        $options = '<option value="0">None</option>';
        while($rank = $query->fetch_assoc()) {
          if($this->info['grouprank'] == $rank['id']) {
            $options .= '<option value="'.$rank['id'].'" selected>'.$rank['label'].'</option>';
          } else {
            $options .= '<option value="'.$rank['id'].'">'.$rank['label'].'</option>';
          }
        }
        return "<select name='rank_uid".$this->info['uid']."'>".$options."</select>";
  }

  public function update_rank($rankid) {
    // Get rank text also for display purposes
    $query = $this->db->simple_select('groupranks', 'label', 'id = "'.$rankid.'"');
    $rankinfo = $query->fetch_assoc();

    if(isset($rankinfo['label'])) {
      $ranktext = $rankinfo['label'];
    } else {
      $ranktext = '';
    }
    $this->db->update_query('users',array('grouprank' => $rankid, 'grouprank_text' => $ranktext), 'uid = '.$this->info['uid']);
  }

  public function get_last_icpost() {
    $query = $this->db->query('select p.* from '.TABLE_PREFIX.'posts p where uid = '.$this->info['uid'].'
                    and exists(select 1 from '.TABLE_PREFIX.'forums f WHERE f.icforum = 1 and f.fid = p.fid) ORDER BY p.dateline DESC LIMIT 1');
    return $query->fetch_array();
  }

  /**
  Return stats for activity period for user
  */
  public function get_stats_for($days) {
    $datefrom = time() - ($days * 86400);
    // Count Posts
    $posts = array();
    $query = $this->db->simple_select('posts p',' DISTINCT p.pid,p.tid','p.uid = '.$this->info['uid'].' AND p.visible = 1 AND p.dateline > '.$datefrom.'
                    and exists(select 1 from '.TABLE_PREFIX.'forums f WHERE f.icforum = 1 and f.fid = p.fid)');
    while($post = $query->fetch_array()) {
        $posts[] = $post;
    }
    $result['postcount'] = count($posts);

    $threadcount = 0;
    if($result['postcount'] > 0) {
      $threadstring = implode(',',array_column($posts, "tid"));
      // Count Threads
      $query = $this->db->simple_select('threads t','DISTINCT t.tid','t.tid IN ('.$threadstring.')
                      AND not exists(select 1 from '.TABLE_PREFIX.'posts p WHERE p.tid = t.tid AND p.dateline < '.$datefrom.')');
      $threadcount = $query->num_rows;
    }
    $result['threadcount'] = $threadcount;
    return $result;
  }

  public function get_settings_mod() {
    $rpglib = new RPGSuite($this->mybb, $this->db, $this->cache);

    $customsettings = array();
    $customsettings[] = array('label' => "Date Joined",
            'name' => 'group_dateline',
            'form' => '<input type="text" class="textbox" name="group_datelineu'.$this->info['uid'].'" value="'.date($this->mybb->settings['dateformat'], $this->info['group_dateline']).'">');
    $query = $this->db->simple_select('profilefields','*', 'CONCAT(\',\',editableby,\',\') LIKE \'%,'.Groups::MOD.',%\'',array(
            "order_by" => 'disporder',
            "order_dir" => 'ASC'));
    while($setting = $query->fetch_array()) {
      $customsettings[] = array('label' => $setting['name'],
        'name' => 'fid'.$setting['fid'],
        'form' => $rpglib->parse_setting($setting, $this->info['fid'.$setting['fid']], 'u'.$this->info['uid'])
      );
    }
    return $customsettings;
  }

}
