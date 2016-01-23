<?php
if(!defined("IN_MYBB"))
{
    die("You Cannot Access This File Directly. Please Make Sure IN_MYBB Is Defined.");
}
abstract class TemplateSet {
  /**
  Template Set Master Class
  **/

  // MYBB Database instance
  private $db;

  public function __construct($db) {
    $this->db = $db;
  }

  public function create() {

    $tgroup = $this->db->simple_select('templategroups', '*', 'prefix = \''.$this::SET_PREFIX.'\'');
    if(!$tgroup->num_rows) {
      $templategroup = array(
    		'prefix' => $this::SET_PREFIX,
    		'title'  => $this::SET_TITLE,
    		'isdefault' => 1
    	);
    	$this->db->insert_query("templategroups", $templategroup);
    }

    foreach($this->build_templates() as $template) {
      $temp = $this->db->simple_select('templates', '*', 'title = \''.$this::SET_PREFIX.'_'.$template->getName().'\'');
      if(!$temp->num_rows) {
        $array = array(
      			"title" 	=> $this::SET_PREFIX.'_'.$template->getName(),
      			"template"	=> $this->db->escape_string($template->getContents()),
      			"sid"		=> -2,
      			"version"	=> 1.0,
      			"dateline"	=> TIME_NOW
      		);
        $this->db->insert_query('templates', $array);
      }
    }
  }

  public function destroy() {
    // Delete any templates
  	$this->db->delete_query("templates", "`title` LIKE '".$this::SET_PREFIX."_%'");
  	$this->db->delete_query("templategroups", "`prefix` = '".$this::SET_PREFIX."'");
  }
}
