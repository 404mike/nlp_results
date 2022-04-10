<?php

class ProcessQuery {

  private $db;

  public function __construct()
  {
    $this->db = new SQLite3('database/amp.sqlite', SQLITE3_OPEN_CREATE | SQLITE3_OPEN_READWRITE);

    $this->getQueryData();

    // close connection
    $this->db->close();
  }

  private function getQueryData()
  {
    $query_type = $_POST['query_type'];
    $query = $_POST['sparql'];
    $uid = uniqid();

    $data = [
      'project' => $uid,
      'type' => $query_type,
      'query' => $query
    ];

    $response =$this->createQueue($data);

    $this->redirect($response);
  }

  private function createQueue($data)
  {
    $statement = $this->db->prepare('INSERT INTO "queue" ("project", "query_type", "query", "status")
    VALUES (:project, :query_type, :query, :status)');
    $statement->bindValue(':project', $data['project']);
    $statement->bindValue(':query_type', $data['type']);
    $statement->bindValue(':query', $data['query']);
    $statement->bindValue(':status', "0");
    $statement->execute();

    // return insert ID
    return $this->db->lastInsertRowID();
  }

  private function redirect($id)
  {
    header('Location: results.php?id='.$id);
  }

}

(new ProcessQuery());