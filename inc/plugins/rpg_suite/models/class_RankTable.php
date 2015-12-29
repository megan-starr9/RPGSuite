<?php
if(!defined("IN_MYBB"))
{
    die("You Cannot Access This File Directly. Please Make Sure IN_MYBB Is Defined.");
}

class RankTable {
  /**
  Rank Table Master Class
  **/

  // MYBB Master Variables
  private $mybb;
  private $db;

  private $tiers = array();
  private $ranks = array();
  private $members = array();

  private $for_administration;

  public function __construct($mybb,$db, $group, $foradmin = 0) {
    $this->mybb = $mybb;
    $this->db = $db;
    $this->for_administration = $foradmin;

    $groupinfo = $group->get_info();

    if($this->mybb->settings['rpgsuite_groupranks_custom'] || $this->for_administration) {
      // Try pulling custom rank tiers!
      $tierquery = $this->db->simple_select('grouptiers','*','gid = '.$groupinfo['gid'], array(
				      "order_by" => 'seq',
				      "order_dir" => 'ASC'));
      while($tier = $tierquery->fetch_array()) {
        $this->tiers[] = $tier;
      }
    }
    if((!isset($tierquery) || !$tierquery->num_rows) && !$this->for_administration) {
      // If not custom and we aren't editing, pull defaults
      $tierquery = $this->db->simple_select('grouptiers','*','gid = 0', array(
				      "order_by" => 'seq',
				      "order_dir" => 'ASC'));
      while($tier = $tierquery->fetch_array()) {
        $this->tiers[] = $tier;
      }
    }


    $tierstring = implode(',',array_column($this->tiers, 'id'));
    if(!empty($tierstring)) {
      $rankquery = $this->db->simple_select('groupranks','*','tid in ('.$tierstring.')', array(
              "order_by" => 'seq',
              "order_dir" => 'ASC'));
      while($rank = $rankquery->fetch_array()) {
          $this->ranks[] = $rank;
        }
    }

    $this->members = $group->get_members();
  }

  public function get_tiers() {
    if(!$this->for_administration) {
      $tierarray = array();
      foreach($this->tiers as $tier) {
        if($this->get_ranks($tier['id'])) {
          $tierarray[] = $tier;
        }
      }
      return $tierarray;
    } else {
      return $this->tiers;
    }
  }
  public function get_ranks($tierid = null) {
    if(isset($tierid)) {
      $rankarray = array();
      foreach($this->ranks as $rank) {
        if($rank['tid'] == $tierid && ($rank['visible'] || $this->get_members($rank['id']))) {
          $rankarray[] = $rank;
        }
      }
      return $rankarray;
    } else {
      return $this->ranks;
    }
  }
  public function get_members($rankid = null) {
    if(isset($rankid)) {
      $memberarray = array();
      foreach($this->members as $member) {
        $memberinfo = $member->get_info();
        if($memberinfo['grouprank'] == $rankid) {
          $memberarray[] = $member;
        }
      }
      return $memberarray;
    } else {
      return $this->members;
    }
  }

  public function update_tier($tierinfo) {
    if(!isset($tierinfo['id'])) {
      // Creating
      $this->db->insert_query('grouptiers',$tierinfo);
    } else {
      //Modifying
      $this->db->update_query('grouptiers',$tierinfo,'id = '.$tierinfo['id']);
    }
  }

  public function update_rank($rankinfo) {
    if(!isset($rankinfo['id'])) {
      // Creating
      $this->db->insert_query('groupranks',$rankinfo);
    } else {
      //Modifying
      $this->db->update_query('groupranks',$rankinfo,'id = '.$rankinfo['id']);
    }
  }

  public function delete_tiers($idstring) {
    if(!empty($idstring)) {
      $this->db->query('DELETE FROM '.TABLE_PREFIX.'grouptiers WHERE id IN ('.$idstring.')');
      $this->db->query('DELETE FROM '.TABLE_PREFIX.'groupranks WHERE tid IN ('.$idstring.')');
    }
  }

  public function delete_ranks($idstring) {
    if(!empty($idstring)) {
      $this->db->query('DELETE FROM '.TABLE_PREFIX.'groupranks WHERE id IN ('.$idstring.')');
    }
  }

  public function generate_tieroptions($selected_tid = 1) {
      $options = '';
      foreach($this->tiers as $tier) {
        if($selected_tid == $tier['id']) {
          $options .= '<option value="'.$tier['id'].'" selected>'.$tier['label'].'</option>';
        } else {
          $options .= '<option value="'.$tier['id'].'">'.$tier['label'].'</option>';
        }
      }
      return $options;
  }

}
