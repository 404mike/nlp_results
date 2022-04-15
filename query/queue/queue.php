<?php

include_once '../scripts/sparql.php';
include_once '../scripts/solr.php';

class AmpQueue {

  private $db;
  private $sparql;
  private $solr;

  public function __construct()
  {
    // import database
    $this->db = new SQLite3('../database/amp.sqlite', SQLITE3_OPEN_CREATE | SQLITE3_OPEN_READWRITE);

    $this->sparql = new SPARQLQueryDispatcher();
    $this->solr = new SolrSearch();

    $this->runQueue();
  }

  public function __destruct()
  {
    // close connection
    $this->db->close();
  }

  /**
   * Run queue every 10 seconds
   */
  private function runQueue()
  {
    while(true)
    {
      sleep(10);
      $this->checkQueue();
    }
  }

  /**
   * Check for pending items
   */
  private function checkQueue()
  {
    $results = $this->db->query('SELECT * FROM `queue` WHERE `status` = 0 LIMIT 1');
    while ($row = $results->fetchArray()) {
      $this->processQueueItem($row);
    }
  }

  /**
   * Get Queue item data
   */
  private function processQueueItem($data)
  {
    print_r($data);
    $project = $data['project'];
    $query = $data['query'];
    $query_type = $data['query_type'];

    // create project dir
    $this->createProjectDir($project);

    // make Wikidata query
    if($query_type == 'single') $this->solrQuery($project, $query);
    else $this->wikidataQuery($query,$project);
  }

  /**
   * Create project directory to store manifests
   */
  private function createProjectDir($dir)
  {
    $path = "../results/$dir";
    $pathNewspaper = $path ."/newspaper";
    $pathJournal = $path ."/journal";

    // create main dir
    if (!file_exists($path)) {
      mkdir($path, 0777, true);
    }

    // newspapers
    if (!file_exists($pathNewspaper)) {
      mkdir($pathNewspaper, 0777, true);
    }

    // journals
    if (!file_exists($pathJournal)) {
      mkdir($pathJournal, 0777, true);
    }
  }

  /**
   * Sparql query to Wikidata
   */
  private function wikidataQuery($query, $project)
  {
    $query_file = "../results/$project/sparql.query";

    if(file_exists($query_file)) {
      $response = file_get_contents($query_file);
      $this->parseWikidataResponse($response);
    }else{
      // make request
      $response = $this->sparql->query($query);

      // save response
      file_put_contents($query_file,$response);

      $this->parseWikidataResponse($response);
    }

  }

  /**
   * Parse Wikidata response
   */
  private function parseWikidataResponse($response)
  {
    $response = json_decode($response,true);
    print_r($response);
    die();
  }

  /**
   * Solr Query
   */
  private function solrQuery($project, $qid)
  {
    $solrResponse = $this->solr->search($qid);

    file_put_contents("../results/$project/solr.response",$solrResponse);
  }
}

(new AmpQueue());