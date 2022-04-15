<?php

class SolrSearch {

  private $solr;

  public function __construct()
  {
    $this->solr = 'http://localhost:8983/solr/amp/';
  }

  // group by https://solr.apache.org/guide/6_6/result-grouping.html
  public function search($qid)
  {
    $url = $this->solr. "select?indent=true&q.op=OR&q=qid_s:$qid&group=true&group.field=art_type_s&group.limit=10";
    $responseJson = file_get_contents($url);
    return $responseJson;
  }

}