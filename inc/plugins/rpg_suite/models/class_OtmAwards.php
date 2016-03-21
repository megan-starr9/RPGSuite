<?php
if(!defined("IN_MYBB"))
{
    die("You Cannot Access This File Directly. Please Make Sure IN_MYBB Is Defined.");
}

require_once MYBB_ROOT."/inc/plugins/rpg_suite/rpgsuite_defaults.php";

class OtmAwards {
  /**
  OTM Award Master Class
  **/

  // MYBB Master Variables
  private $mybb;
  private $db;
  private $cache;

  public function __construct($mybb,$db, $cache) {
    $this->mybb = $mybb;
    $this->db = $db;
    $this->cache = $cache;
  }

  /**
  Get the users with the most posts in the time frame
  **/
  public function crazy_writers($since, $number) {
    $userarray = array();

    $postquery = "(SELECT pid, uid FROM ".TABLE_PREFIX."posts p WHERE p.visible = 1 AND p.dateline > ".$since."
       AND exists(select 1 from ".TABLE_PREFIX."forums f WHERE f.icforum = 1 and f.fid = p.fid))";

    $countquery = "(SELECT email, count(*) as total FROM ".TABLE_PREFIX."users u INNER JOIN ".$postquery." p ON p.uid = u.uid
          GROUP BY email ORDER BY total DESC LIMIT ".$number.")";

    $userquery = "SELECT u.*, c.total FROM $countquery c INNER JOIN
            (SELECT * FROM ".TABLE_PREFIX."users WHERE lastpost > $since AND as_uid = 0 ORDER BY uid ASC) u
            ON c.email = u.email GROUP BY u.email ORDER BY total DESC LIMIT $number";

    $results = $this->db->query($userquery);
    while($user = $this->db->fetch_array($results)) {
      $userarray[] = $user;
    }
    return $userarray;
  }

  /**
  Returns a prettier version of the otms based on type for display!
  */
  public function get_display_otms() {
    $otmarray = array();
    $otmquery = $this->db->simple_select('otms','*');
    while($otm = $this->db->fetch_array($otmquery)) {
      if($otm['type'] == OTMTypes::USER) {
        // If it is an OOC name, we just put the name
        $otmarray[$otm['name']] = $otm['value'];
      } else if($otm['type'] == OTMTypes::CHARACTER) {
        // If it is a character, we build the link!
        $query = $this->db->simple_select('users','*','username = \''.$otm['value'].'\'');
        if($query->num_rows) {
          $user = $query->fetch_assoc();
          $otmarray[$otm['name']] = '<a href="/member.php?action=profile&uid='.$user['uid'].'">'.$user['username'].'</a>';
        } else {
          $otmarray[$otm['name']] = $otm['value'];
        }
      } else if($otm['type'] == OTMTypes::GROUP) {
        // For groups, we link to the stats
        $query = $this->db->simple_select('usergroups','*','title = \''.$otm['value'].'\'');
        if($query->num_rows) {
          $group = $query->fetch_assoc();
          $otmarray[$otm['name']] = '<a href="/index.php?action=showranks&gid='.$group['gid'].'">'.$group['title'].'</a>';
        } else {
          $otmarray[$otm['name']] = $otm['value'];
        }
      } else if($otm['type'] == OTMTypes::THREAD) {
        // For threads, we build the link!
        $query = $this->db->simple_select('threads','*','tid = '.$otm['value'].'');
        if($query->num_rows) {
          $thread = $query->fetch_assoc();
          $otmarray[$otm['name']] = '<a href="/showthread.php?tid='.$thread['tid'].'">'.$thread['subject'].'</a>';
        } else {
          $otmarray[$otm['name']] = $otm['value'];
        }
      } else if($otm['type'] == OTMTypes::POST) {
        // For posts, we build the link!
        $query = $this->db->simple_select('posts','*','pid = '.$otm['value'].'');
        if($query->num_rows) {
          $post = $query->fetch_assoc();
          $title = str_replace($post['subject'],'RE: ','');
          $otmarray[$otm['name']] = '<a href="/showthread.php?tid='.$post['tid'].'&pid='.$post['pid'].'#pid'.$post['pid'].'">'.$title.'</a> by '.$post['username'];
        } else {
          $otmarray[$otm['name']] = $otm['value'];
        }
      }
    }
    return $otmarray;
  }

  /**
  Simply returns a list of all otm information
  */
  public function get_otms() {
    $otmarray = array();
    $otmquery = $this->db->simple_select('otms','*');
    while($otm = $this->db->fetch_array($otmquery)) {
      $otmarray[] = $otm;
    }
    return $otmarray;
  }

  /**
  Manage Awards
  */
  public function update_otm($award) {
    if(!isset($award['id'])) {
      // Creating
      $this->db->insert_query('otms',$award);
    } else {
      //Modifying
      $this->db->update_query('otms',$award,'id = '.$award['id']);
    }
  }
  public function delete_otms($idstring) {
    if(!empty($idstring)) {
      $this->db->query('DELETE FROM '.TABLE_PREFIX.'otms WHERE id IN ('.$idstring.')');
    }
  }

  /**
  Return a list of type options for awards!
  */
  public function otm_type_options($type = null) {
    $types = new ReflectionObject(new OTMTypes);
    foreach($types->getConstants() as $key => $value) {
      $selected = "";
      if($type && $type == $value)
        $selected = "selected";
      $optionstring .= "<option value='$value' $selected>$value</option>";
    }
    return $optionstring;
  }



}
