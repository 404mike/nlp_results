<?php

class SolrSearch {

  private $solr;

  public function __construct()
  {
    $this->solr = 'http://localhost:8983/solr/amp/';
  }


  public function search($qid)
  {
    $url = $this->solr. "select?indent=true&rows=1000&q=qid_s%3A".$qid;
    $responseJson = file_get_contents($url);
    $response = json_decode($responseJson,true);
    return $response['response'];
  }

}