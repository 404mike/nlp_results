<?php

include_once '../scripts/alto.php';
// include_once '../scripts/journal.php';
class ParseData {

  private $alto;
  private $journal;
  private $articles = [];

  public function __construct()
  {
    $this->alto = new AltoImage();
    // $this->journal = new ParseJournalManifest();

    $this->loadResponse();
  }

  private function loadResponse()
  {
    $json = file_get_contents('solr.json');
    $data = json_decode($json,true);

    $this->loopResponse($data);
  }

  private function loopResponse($data)
  {
    foreach($data['grouped']['art_type_s']['groups'] as $k => $v) {
      $type = $v['groupValue'];

      if($type == 'journal') $this->parseJournal($v);
      if($type == 'newspaper') $this->parseNewspaper($v);
    }
  }

  private function parseJournal($data)
  {
    
  }

  private function parseNewspaper($data)
  {
    $newspapers = [];

    foreach($data['doclist']['docs'] as $k => $v) {

      $filename = "../data/qids/Q274339/newspaper/$v[art_id_s].json";

      if(!file_exists($filename)) {
        $artParts = explode('-',$v['art_id_s']);
        $parent_id = $artParts[0];
        $art_id = $artParts[2];
        $targetArt = str_replace('modsarticle','',$artParts[1]);
        
  
        $this->alto->getManifest($art_id, $targetArt, $parent_id, $filename);
      }

      $newspapers[] = $filename;
    }

    $this->articles['newspapers'] = $newspapers;
  }
}

(new ParseData());