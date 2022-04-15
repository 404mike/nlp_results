<?php

class ItemSearch {

  private $db;

  public function __construct()
  {
    // import database
    $this->db = new SQLite3('amp.sqlite', SQLITE3_OPEN_CREATE | SQLITE3_OPEN_READWRITE);

    $this->deleteItem($_GET['id']);
  }

  public function __destruct()
  {
    // close connection
    $this->db->close();
  }

  public function deleteItem($id)
  {
    $this->db->exec("DELETE FROM `queue` WHERE `project` = '$id'");
    header('Location: http://localhost:8000/');
  }

  
}

(new ItemSearch());