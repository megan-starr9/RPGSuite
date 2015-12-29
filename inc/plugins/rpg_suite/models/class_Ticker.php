<?php
if(!defined("IN_MYBB"))
{
    die("You Cannot Access This File Directly. Please Make Sure IN_MYBB Is Defined.");
}
class Ticker {
  /**
  Ticker Master Class
  **/

  // MYBB Master Variables
  private $db;

  //ticker variables
  private $id;
  private $info;
  private $freq;

  public function __construct($db, $id, $freq) {
    $this->id = $id;
    $this->db = $db;
    $this->freq = $freq;

    if(!$this->db->simple_select('tickers','*', 'id = '.$id)->num_rows) {
      // If we don't have a record of for the ticker, insert one!
      $this->db->insert_query('tickers', array('last_run' => time(), 'id' => $id));
    }

    $query = $this->db->simple_select("tickers", "*", "id=".$id);
    $this->info = $this->db->fetch_array($query);
  }

  /**
  Determine if the process needs run!
  */
  public function needs_run() {
    $today = time();
    $lastrun = $this->info["last_run"];
    $interval = (int)$this->freq * 86400;

    return($today > ($lastrun + $interval));
  }

  /**
  Returns date of next run
  */
  public function next_run() {
    $interval = (int)$this->freq * 86400;
    return $this->info['last_run'] + $interval;
  }

  /**
  Update the timestamp on the last run to current
  */
  public function increment() {
      $this->db->query('UPDATE '.TABLE_PREFIX.'tickers SET last_run = '.time().' WHERE id = '.$this->id);
  }

}
