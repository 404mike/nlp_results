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
    // reset articles
    $this->articles = [];

    // format Solr query
    $url = $this->solr. "select?indent=true&q.op=OR&q=qid_s:$qid&group=true&group.field=art_type_s&group.limit=2";

    // make request
    $responseJson = file_get_contents($url);

    $responseData = json_decode($responseJson,true);

    // format response
    $this->loopSolrResponse($responseData, $qid);

    // return all the articles generated
    $this->wirteQidManifest($qid, $qidName, false);
    // return all the articles generated, linking to full newspaper pages
    $this->wirteQidManifest($qid, $qidName, true);
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
    // echo "Welll\n";
    // echo '<pre>' , print_r($this->articles) , '</pre>';
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
        $this->journal->getManifest($v['art_id_s'], $filename, $qid);
      }

      $journals[] = [
        'article' => $manifest_name,
        'date' => $v['date_pdate']
      ];
    }

    $this->articles['journal'] = $journals;

    $this->writeArticleIndexManifest($qid, $journals, 'journal', false);
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

      $artcile_name = $v['art_id_s'];
      // manifest filename
      $filename = "../data/qids/$qid/newspaper/$artcile_name.json";

      // check if manifest has been created previously
      if(!file_exists($filename."gg")) {

        // break article ID into parts
        $artParts = explode('-',$v['art_id_s']);
        $parent_id = $artParts[0];
        $art_id = $artParts[2];
        $targetArt = str_replace('modsarticle','',$artParts[1]);
        
        // generate the manifest
        $this->alto->getManifest($art_id, $targetArt, $parent_id, $filename, $v['date_pdate']);
      }

      // save article manifest filename
      $newspapers[] = [
        'article' => $artcile_name,
        'date' => $v['date_pdate']
      ];
    }
    
    // add all newspaper articles to the articles array
    $this->articles['newspaper'] = $newspapers;

    // output manifest linking to cropped images
    $this->writeArticleIndexManifest($qid, $newspapers, 'newspaper', false);
    // output manifest linking to full images
    $this->writeArticleIndexManifest($qid, $newspapers, 'newspaper', true);
  }

  /**
   * 
   */
  private function writeArticleIndexManifest($qid, $manifests, $type, $fullNewspaper)
  {
    $type_title = ucfirst($type);
    // default variables
    $manifestUrl = "https://404mike.github.io/nel_results/data/qids/$qid/$type/manifest.json";
    $fileLocation = "../data/qids/$qid/$type/manifest.json";

    // overrides if dealing with full page newspaper
    if($fullNewspaper) {
      $manifestUrl = "https://404mike.github.io/nel_results/data/qids/$qid/$type/full-manifest.json";
        $fileLocation = "../data/qids/$qid/$type/full-manifest.json";
    }

    echo "1. Outputting Article Collection Manifest $type\n";
    $arr = [
      "@context" => "http://iiif.io/api/presentation/3/context.json",
      "id" => "$manifestUrl",
      "type" => "Collection",
      "label" => [
        "en" => ["$type_title"]
      ],
      "summary" => [
        "en" => ["Collection of $type_title"]
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

    foreach($manifests as $k => $v) {

      $title = date('Y-m-d', strtotime($v['date'][0]));
      // default variable
      $id = "https://404mike.github.io/nel_results/data/qids/$qid/$type/$v[article].json";
      // if full page newspaper
      if($fullNewspaper) {
        $id = "https://404mike.github.io/nel_results/data/qids/$qid/$type/full-".$v['article'].".json";
      }
      $arr['items'][] = [
        "id" => $id,
        "type" => "Collection",
        "label" => [
          "en" => [$title]
        ]
      ];
    }

    file_put_contents($fileLocation,json_encode($arr,JSON_PRETTY_PRINT));
  }

  /**
   * 
   */
  private function wirteQidManifest($qid, $qidName, $fullNewspaper)
  {
    echo "2. Outputting Main Index Manifest\n";

    $manifestUrl = "https://404mike.github.io/nel_results/data/qids/$qid/manifest.json";
    $filename = "../data/qids/$qid/manifest.json";
    
    if($fullNewspaper) {
      $manifestUrl = "https://404mike.github.io/nel_results/data/qids/$qid/full-manifest.json";$filename = "../data/qids/$qid/full-manifest.json";
    }

    $arr = [
      "@context" => "http://iiif.io/api/presentation/3/context.json",
      "id" => $manifestUrl,
      "type" => "Collection",
      "label" => [
        "en" => ["Collections for $qidName"]
      ],
      "summary" => [
        "en" => ["Collection Summary for $qidName"]
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

      if(empty($v)) continue;
      if(count($v) == 0) continue;

      $id = "https://404mike.github.io/nel_results/data/qids/$qid/$k/manifest.json";

      if($fullNewspaper) {
        $id = "https://404mike.github.io/nel_results/data/qids/$qid/$k/full-manifest.json";
      }      
      // echo count($v) . "\n";
      $arr['items'][] = [
        "id" => $id,
        "type" => "Collection",
        "label" => [
          "en" => [ucfirst($k) . " articles"]
        ]
      ];
    }

    file_put_contents($filename,json_encode($arr,JSON_PRETTY_PRINT));
  }

}