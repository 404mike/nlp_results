<?php

include_once 'alto.php';
include_once 'journal.php';

class SolrSearch {

  private $solr;
  private $alto;
  private $journal;

  private $articles = [];

  public function __construct()
  {
    $this->solr = 'http://localhost:8983/solr/amp/';
    $this->alto = new AltoImage();
    $this->journal = new ParseJournalManifest();
  }

  /**
   * Check to see if QID exists within Solr
   * returns number of articles
   */
  public function doesQidExist($qid)
  {
    $url = $this->solr . "select?indent=true&q.op=OR&q=qid_s%3A$qid";

    $responseJson = file_get_contents($url);

    $responseData = json_decode($responseJson,true);

    return $responseData['response']['numFound'];
  }

  // group by https://solr.apache.org/guide/6_6/result-grouping.html
  public function search($qid, $project, $qidName)
  {
    // format Solr query
    $url = $this->solr. "select?indent=true&q.op=OR&q=qid_s:$qid&group=true&group.field=art_type_s&group.limit=2";

    // make request
    $responseJson = file_get_contents($url);

    $responseData = json_decode($responseJson,true);

    // format response
    $this->loopSolrResponse($responseData, $qid);

    // return all the articles generated
    $this->wirteQidManifest($qid, $qidName);
  }

  public function getQidName($qid)
  {
    $url = $this->solr . "select?indent=true&q.op=OR&q=qid_s%3A$qid";

    $responseJson = file_get_contents($url);

    $responseData = json_decode($responseJson,true);

    return $responseData['response']['docs'][0]['name_t'];
  }

  /**
   * Loop Solr response and create 
   * either Newspaper manifest
   * or Journal manifest
   * for each article
   */
  private function loopSolrResponse($data, $qid)
  {
    foreach($data['grouped']['art_type_s']['groups'] as $k => $v) {
      $type = $v['groupValue'];

      if($type == 'journal') $this->parseJournal($v, $qid);
      if($type == 'newspaper') $this->parseNewspaper($v, $qid);
    }
  }

  private function parseJournal($data, $qid)
  {
    echo "Parsing Journals\n";
    $journals = [];

    foreach($data['doclist']['docs'] as $k => $v) {
      $manifest_name = str_replace(":","",$v['art_id_s']);
      $filename = "../data/qids/$qid/journal/$manifest_name.json";
      
      if(!file_exists($filename)) {
        $this->journal->getManifest($v['art_id_s'], $filename);
      }

      $journals[] = $filename;
    }

    $this->articles['journals'] = $journals;
  }

  /**
   * Generate manifest for each Newspaper article
   */
  private function parseNewspaper($data, $qid)
  {
    echo "Parsing Newspapers\n";
    $newspapers = [];

    // loop response
    foreach($data['doclist']['docs'] as $k => $v) {

      // manifest filename
      $filename = "../data/qids/$qid/newspaper/$v[art_id_s].json";

      // check if manifest has been created previously
      if(!file_exists($filename)) {

        // break article ID into parts
        $artParts = explode('-',$v['art_id_s']);
        $parent_id = $artParts[0];
        $art_id = $artParts[2];
        $targetArt = str_replace('modsarticle','',$artParts[1]);
        
        // generate the manifest
        $this->alto->getManifest($art_id, $targetArt, $parent_id, $filename);
      }

      // save article manifest filename
      $newspapers[] = $v['art_id_s'];
    }

    // add all newspaper articles to the articles array
    $this->articles['newspapers'] = $newspapers;
  }

  private function wirteQidManifest($qid, $qidName)
  {
    echo "Outputting Manifest\n";
    $arr = [
      "@context" => "http://iiif.io/api/presentation/3/context.json",
      "id" => "https://404mike.github.io/IIIF-Content-State/collection-v3.json",
      "type" => "Collection",
      "label" => [
        "en" => "Collections for $qidName"
      ],
      "summary" => [
        "en" => "Collection Summary for $qidName"
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

    foreach($this->articles as $k => $v) {
      $arr['items'][] = [
        "id" => "https://damsssl.llgc.org.uk/iiif/2.0/4627582/manifest.json",
        "type" => "Manifest",
        "label" => [
          "en" => ["Example"]
        ]
      ];
    }

    file_put_contents("../data/qids/$qid/manifest.json",json_encode($arr,JSON_PRETTY_PRINT));
  }

}