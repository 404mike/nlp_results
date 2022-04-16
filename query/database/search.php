<?php

class ItemSearch {

  private $db;

  public function __construct()
  {
    // import database
    $this->db = new SQLite3('database/amp.sqlite', SQLITE3_OPEN_CREATE | SQLITE3_OPEN_READWRITE);
  }

  public function __destruct()
  {
    // close connection
    $this->db->close();
  }

  public function viewItem($id)
  {
    $results = $this->db->query("SELECT * FROM `queue` WHERE `project` = '$id'");

    $row = $results->fetchArray();

    return $row;
  }

  
}

(new ItemSearch());