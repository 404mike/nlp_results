<?php

include_once '../scripts/sparql.php';
include_once '../scripts/solr.php';

class AmpQueue {

  private $db;
  private $sparql;
  private $solr;
  private $qids = [];

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
      // sleep(10);
      $this->checkQueue();
      die();
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
    $project = $data['project'];
    $project_title = $data['project_title'];
    $query = $data['query'];
    $query_type = $data['query_type'];

    // make Wikidata query
    if($query_type == 'single') $this->solrQuery($project, $query);
    else $this->wikidataQuery($query,$project);

    //
    $this->writeMainManifestCollection($project, $project_title,false);
    // fullpage newspaper
    $this->writeMainManifestCollection($project, $project_title,true);
  }

  /**
   * Create project directory to store manifests
   */
  private function createQidDir($qid)
  {
    $path = "../data/qids/$qid";

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

  private function createManifestDir($project)
  {
    $path = "../data/manifests/$project";
    if (!file_exists($path)) {
      mkdir($path, 0777, true);
    }
  }

  /**
   * Sparql query to Wikidata
   */
  private function wikidataQuery($query, $project)
  {
    $query_file = "../data/manifests/$project/sparql.json";

    if(file_exists($query_file)) {
      $response = file_get_contents($query_file);
      $this->parseWikidataResponse($response, $project);
    }else{
      // make request
      $response = $this->sparql->query($query);

      $this->createManifestDir($project);

      // save response
      file_put_contents($query_file,$response);

      $this->parseWikidataResponse($response, $project);
    }

  }

  /**
   * Parse Wikidata response
   */
  private function parseWikidataResponse($response, $project)
  {
    $response = json_decode($response,true);

    foreach($response['results']['bindings'] as $k => $v) {
      $this->solrQuery($project, $v['itemLabel']['value']);
    }
  }

  /**
   * Solr Query
   */
  private function solrQuery($project, $qid)
  {
    // get number of articles for QID
    $numQids = $this->solr->doesQidExist($qid);

    // if Solr contains articles for QID
    if($numQids > 0) {

      echo "Creating directory for $qid\n";

      $qidName = $this->solr->getQidName($qid);

      // create QID directories
      $this->createQidDir($qid);

      // search Solr for Qid
      $solrResponse = $this->solr->search($qid, $project, $qidName);

      $this->qids[] = [
        'qid' => $qid,
        'name' => $qidName
      ];
    }
  }

  /**
   * 
   */
  private function writeMainManifestCollection($project, $project_title, $fullNewspaper)
  {
    echo "Outputting Main Manifest\n";

    $manifestUrl = "https://404mike.github.io/nel_results/data/manifests/$project/index.json";
    $manifestFileName = "../data/manifests/$project/index.json";
    
    if($fullNewspaper) {
      $manifestUrl = "https://404mike.github.io/nel_results/data/manifests/$project/full-index.json";
      $manifestFileName = "../data/manifests/$project/full-index.json";
    }

    $arr = [
      "@context" => "http://iiif.io/api/presentation/3/context.json",
      "id" => $manifestUrl,
      "type" => "Collection",
      "label" => [
        "en" => "Collections for $project_title"
      ],
      "summary" => [
        "en" => "Collection Summary for $project_title"
      ],
      "requiredStatement" => [
        "label" => [
          "en" => ["Attribution"]
        ],
        "value" => [
          "en" => ["Provided by Example Organization"]
        ]
      ],
      "items" => []
    ];

    foreach($this->qids as $k => $v) {
      $id = "https://404mike.github.io/nel_results/data/qids/$v[qid]/manifest.json";

      if($fullNewspaper) {
        $id = "https://404mike.github.io/nel_results/data/qids/$v[qid]/full-manifest.json";
      }

      $arr['items'][] = [
        "id" => $id,
        "type" => "Collection",
        "label" => [
          "en" => ["Collections for $v[name]"]
        ]
      ];
    }

    $this->createManifestDir($project);

    file_put_contents($manifestFileName,json_encode($arr,JSON_PRETTY_PRINT));

    // $this->db->exec("UPDATE `queue` SET status='1' WHERE `project` = '$project'");
  }
}

(new AmpQueue());