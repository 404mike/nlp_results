<?php

class AmpQueue {

  private $db;

  public function __construct()
  {
    // import database
    $this->db = new SQLite3('database/amp.sqlite', SQLITE3_OPEN_CREATE | SQLITE3_OPEN_READWRITE);

    $this->runQueue();
  }

  public function __destruct()
  {
    // close connection
    $this->db->close();
  }

  private function runQueue()
  {
    while(true)
    {
      sleep(10);
      $this->checkQueue();
    }
  }

  private function checkQueue()
  {
    $results = $this->db->query('SELECT * FROM `queue` WHERE `status` = 0 LIMIT 1');
    while ($row = $results->fetchArray()) {
      $this->processQueueItem($row);
    }
  }

  private function processQueueItem($data)
  {
    print_r($data);
  }
}

(new AmpQueue());