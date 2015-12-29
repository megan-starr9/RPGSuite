<?php
if(!defined("IN_MYBB"))
{
    die("You Cannot Access This File Directly. Please Make Sure IN_MYBB Is Defined.");
}
class Template {
  /**
  Template class (helper)
  **/
  private $name;
  private $contents;

  public function __construct($name = null, $contents = null) {
    $this->name = $name;
    $this->contents = $contents;
  }

  public function getName() {
    return $this->name;
  }
  public function getContents() {
    return $this->contents;
  }

  public function setName($name) {
    $this->name = $name;
    return;
  }
  public function setContents($content) {
    $this->contents = $content;
    return;
  }
}
