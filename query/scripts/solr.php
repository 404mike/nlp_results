<?php

include_once 'alto.php';
include_once 'journal.php';
include_once 'wikidata.php';

class SolrSearch {

  private $solr;
  private $alto;
  private $journal;
  private $wiki;
  private $personData;

  private $articles = [];

  public function __construct()
  {
    $this->solr = 'http://solr:8983/solr/amp/';
    $this->alto = new AltoImage();
    $this->journal = new ParseJournalManifest();
    $this->wiki = new Wikidata();
  }

  /**
   * Check to see if QID exists within Solr
   * returns number of articles
   */
  public function doesQidExist($qid)
  {
    $url = $this->solr . "select?indent=true&q.op=OR&q=qid_s%3A$qid";

    $responseJson = file_get_contents($url);

    $responseData = json_decode($responseJson, true);

    return $responseData['response']['numFound'];
  }

  // group by https://solr.apache.org/guide/6_6/result-grouping.html
  public function search($qid, $project, $qidName)
  {
    // get person details from wikidata
    $this->personData = $this->wiki->getQidData($qid);

    // person dob
    $dob = $this->personData['dob'];

    // reset articles
    $this->articles = [];

    // format Solr query
    $url = $this->solr. "select?indent=true&q.op=OR&q=qid_s:$qid&group=true&group.field=art_type_s&group.limit=5";

    // make request
    $responseJson = file_get_contents($url);

    $responseData = json_decode($responseJson,true);

    // format response
    $this->loopSolrResponse($responseData, $qid, $dob);

    // return all the articles generated
    $this->wirteQidManifest($qid, $qidName, false);
    // return all the articles generated, linking to full newspaper pages
    $this->wirteQidManifest($qid, $qidName, true);

    // if($qid == 'Q1886336') {
    //   print_r($this->articles);

    //   if(empty($this->articles['journal']) && empty($this->articles['newspaper'])) {
    //     echo "NFAFFING\n";
    //   }else{
    //     echo "SOMETHINMG\n";
    //   }

    //   die('no more');
    // }

    if(empty($this->articles['journal']) && empty($this->articles['newspaper'])) {
      return false;
    }
    else return true;
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
  private function loopSolrResponse($data, $qid, $dob)
  {
    foreach($data['grouped']['art_type_s']['groups'] as $k => $v) {
      $type = $v['groupValue'];

      // echo "$type\n";

      if($type == 'journal') $this->parseJournal($v, $qid, $dob);
      if($type == 'newspaper') $this->parseNewspaper($v, $qid, $dob);
    }
  }

  private function parseJournal($data, $qid, $dob)
  {
    echo "Parsing Journals\n";
    $journals = [];
    $completedPids = [];

    foreach($data['doclist']['docs'] as $k => $v) {


      // print_r($v);
      // echo "$dob\n";
      // die();

      // check if article was published before/after dob
      if(!$this->checkPublishedDateAgainstDOB($v['date_pdate'], $dob)) continue;

      // print_R($v);
      $pids = explode('llgc-id:',$v['art_id_s']);
      $artPid = str_replace('_','',$pids[2]);

      // there may be duplicates, ignore second occurence
      if(in_array($artPid,$completedPids)) continue;

      $manifest_name = "https://damsssl.llgc.org.uk/iiif/2.0/$artPid/manifest.json";

      $completedPids[] = $artPid;
      // print_r($pids);
      // die('end');

      // $manifest_name = str_replace(":","",$v['art_id_s']);
      // $filename = "../data/qids/$qid/journal/$manifest_name.json";
      
      // if(!file_exists($filename)) {
      //   $this->journal->getManifest($v['art_id_s'], $filename, $qid);
      // }

      $journals[] = [
        'article' => $manifest_name,
        'date' => $v['date_pdate']
      ];
    }

    $this->articles['journal'] = $journals;
    // die();

    if(!empty($journals)) {
      $this->writeArticleIndexManifest($qid, $journals, 'journal', false);
    }

  }

  /**
   * Generate manifest for each Newspaper article
   */
  private function parseNewspaper($data, $qid, $dob)
  {
    echo "Parsing Newspapers\n";
    $newspapers = [];

    // loop response
    foreach($data['doclist']['docs'] as $k => $v) {

      // check if article was published before/after dob
      if(!$this->checkPublishedDateAgainstDOB($v['date_pdate'], $dob)) continue;

      $artcile_name = $v['art_id_s'];
      // manifest filename
      $filename = "../data/qids/$qid/newspaper/$artcile_name.json";

      // check if manifest has been created previously
      if(!file_exists($filename)) {

        // break article ID into parts
        $artParts = explode('-',$v['art_id_s']);
        $parent_id = $artParts[0];
        $art_id = $artParts[4];
        $canvas_id = $artParts[2];
        $targetArt = str_replace('modsarticle','',$artParts[1]);
        
        // generate the manifest
        $this->alto->getManifest($art_id, $canvas_id, $targetArt, $parent_id, $filename, $v['date_pdate']);
      }

      // save article manifest filename
      $newspapers[] = [
        'article' => $artcile_name,
        'date' => $v['date_pdate']
      ];
    }
    
    // add all newspaper articles to the articles array
    $this->articles['newspaper'] = $newspapers;

    if(!empty($newspapers)) {
      // output manifest linking to cropped images
      $this->writeArticleIndexManifest($qid, $newspapers, 'newspaper', false);
      // output manifest linking to full images
      $this->writeArticleIndexManifest($qid, $newspapers, 'newspaper', true);
    }

  }

  /**
   * Check if article was published before DOB
   */
  private function checkPublishedDateAgainstDOB($pubDate, $dob)
  {
    if(empty($dob)) return true;

    $pubDateYear = date('Y',strtotime($pubDate[0]));

    // echo "comparing DOB ($dob) against PUB($pubDateYear)\n";

    if((int)$pubDateYear > (int)$dob) return true;

    return false;
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
      // $manifesttype = ($type == 'newspaper') ? "Manifest" : "Collection";
      $manifesttype = "Manifest";

      // if full page newspaper
      if($fullNewspaper) {
        $id = "https://404mike.github.io/nel_results/data/qids/$qid/$type/full-".$v['article'].".json";
      }

      if($type == 'journal') {
        $id = $v['article'];
      }

      $arr['items'][] = [
        "id" => $id,
        "type" => $manifesttype,
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
    echo "2. Outputting Main Index Manifest for $qid ($qidName)\n";

    // get person data from Wikidata
    $personData = $this->personData;

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

    // add person description if we have one
    if(isset($personData['description'])) {
      $arr["personDescription"] = ['en' => [$personData['description']]];
    }

    // add person image if we have one
    if(isset($personData['image'])) {
      $arr["personImage"] = ['en' => [$personData['image']]];
    }

    // add linked articles
    foreach($this->articles as $k => $v) {

      if(empty($v)) continue;
      if(count($v) == 0) continue;

      $id = "https://404mike.github.io/nel_results/data/qids/$qid/$k/manifest.json";

      if($fullNewspaper && $k == 'newspaper') {
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