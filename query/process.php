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
    $project_title = $_POST['project_title'];
    $query = $_POST['sparql'];
    $uid = uniqid();

    $data = [
      'project' => $uid,
      'project_title' => $project_title,
      'type' => $query_type,
      'query' => trim($query)
    ];

    $response =$this->createQueue($data);

    $this->redirect($response);
  }

  private function createQueue($data)
  {
    $statement = $this->db->prepare('INSERT INTO "queue" ("project_title", "project", "query_type", "query", "status", "created", "updated")
    VALUES (:project_title, :project, :query_type, :query, :status, :created, :updated)');
    $statement->bindValue(':project_title', $data['project_title']);
    $statement->bindValue(':project', $data['project']);
    $statement->bindValue(':query_type', $data['type']);
    $statement->bindValue(':query', $data['query']);
    $statement->bindValue(':status', "0");
    $statement->bindValue(':created', date('Y-m-d H:i:s'));
    $statement->bindValue(':updated', date('Y-m-d H:i:s'));
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