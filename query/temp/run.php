<?php

include_once '../scripts/alto.php';
// include_once '../scripts/journal.php';
class ParseData {
  private $alto;
  private $journal;

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
    foreach($data['doclist']['docs'] as $k => $v) {
      $artParts = explode('-',$v['art_id_s']);

      print_r($artParts);
      $parent_id = $artParts[0];
      $art_id = $artParts[2];
      $targetArt = str_replace('modsarticle','',$artParts[1]);

      $this->alto->getManifest($pid,$targetArt);
    }
  }
}

(new ParseData());