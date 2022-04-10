<?php

class AllItems {

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

  public function getAllItems()
  {
    $results = $this->db->query('SELECT * FROM `queue` ORDER BY `id` DESC LIMIT 50');

    $items = [];
    while ($row = $results->fetchArray()) {
      $items[] = $row;
    }
    return $items;
  }

  
}

(new AllItems());