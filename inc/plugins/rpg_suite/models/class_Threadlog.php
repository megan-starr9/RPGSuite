<?php
if(!defined("IN_MYBB"))
{
  die("You Cannot Access This File Directly. Please Make Sure IN_MYBB Is Defined.");
}

class Threadlog {
  /**
  Threadlog Master Class
  **/

  //MYBB Master Variables
  private $db;

  private $user;
  private $threads = array();
  private $thread_participants = array();
  private $thread_notes = array();

  public function __construct($db, $uid) {
    $this->db = $db;

    $userquery = $db->simple_select('users', 'uid, username', 'uid = '. $uid .'');
    $this->user = $userquery->fetch_array();

    $threadquery = $db->simple_select("threads t left join ".TABLE_PREFIX."threadprefixes r on t.prefix = r.pid", "t.*, r.displaystyle as prefixtext",
        't.visible = 1 and exists(select 1 from '.TABLE_PREFIX.'posts p where t.tid = p.tid and p.visible = 1 and p.uid = '.$uid.')
          and exists(select 1 from '.TABLE_PREFIX.'forums f where f.fid = t.fid and f.icforum = 1) ORDER BY t.tid DESC');
    if($threadquery->num_rows) {
      while($thread = $threadquery->fetch_array()) {
        $this->threads[] = $thread;
      }

      $threadstring = implode(',',array_column($this->threads, "tid"));

      // Get Thread Participants!
      $participantquery = $db->simple_select("posts", "DISTINCT tid, uid, username", "uid <>".$this->user['uid']." and tid in (".$threadstring.") and visible = 1");
      while($participant = $participantquery->fetch_array()) {
        if(is_array($this->thread_participants[$participant['tid']])) {
          $this->thread_participants[$participant['tid']][] = $participant;
        } else {
          $this->thread_participants[$participant['tid']] = array($participant);
        }
      }

      // Get Skills/Attributes if they exist!
      if($db->table_exists('usernotes')) {
        $notequery = $db->simple_select("usernotes", "*", "tid in (".$threadstring.") AND uid = ".$this->user['uid']);
        while($note = $notequery->fetch_array()) {
          if(is_array($this->thread_notes[$note['tid']])) {
            $this->thread_notes[$note['tid']][] = $note;
          } else {
            $this->thread_notes[$note['tid']] = array($note);
          }
        }
      }
    }
  }

  /**
  Get user information!
  **/
  public function get_user() {
    return $this->user;
  }

  /**
  Get all threads!
  **/
  public function get_threads() {
    return $this->threads;
  }

  /**
  Get Active Threads
  **/
  public function get_active() {
    $threadarray = array();
    foreach($this->threads as $thread) {
      if($thread['closed'] != 1) {
        $threadarray[] = $thread;
      }
    }
    return $threadarray;
  }
  /**
  Get Closed Threads
  **/
  public function get_closed() {
    $threadarray = array();
    foreach($this->threads as $thread) {
      if($thread['closed'] == 1) {
        $threadarray[] = $thread;
      }
    }
    return $threadarray;
  }
  /**
  Get Threads that Need Replies
  **/
  public function get_need_reply() {
    $threadarray = array();
    foreach($this->threads as $thread) {
      if($thread['closed'] != 1 && $thread['lastposteruid'] != $this->user['uid']) {
        $threadarray[] = $thread;
      }
    }
    return $threadarray;
  }

  /**
  Get Thread Participants
  **/
  public function get_thread_participants($tid) {
    if(is_array($this->thread_participants[$tid]))
      return $this->thread_participants[$tid];
    else
      return array();
  }
  /**
  Get Thread Notes
  **/
  public function get_thread_notes($tid) {
    if(is_array($this->thread_notes[$tid]))
      return $this->thread_notes[$tid];
    else
      return array();
  }
}
