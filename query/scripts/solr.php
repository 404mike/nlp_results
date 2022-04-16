<?php

include_once 'alto.php';
include_once 'journal.php';

class SolrSearch {

  private $solr;
  private $alto;
  private $journal;

  public function __construct()
  {
    $this->solr = 'http://localhost:8983/solr/amp/';
    $this->alto = new AltoImage();
    $this->journal = new ParseJournalManifest();
  }

  // group by https://solr.apache.org/guide/6_6/result-grouping.html
  public function search($qid, $project)
  {
    // format Solr query
    $url = $this->solr. "select?indent=true&q.op=OR&q=qid_s:$qid&group=true&group.field=art_type_s&group.limit=100";

    // make request
    $responseJson = file_get_contents($url);

    // save solr response
    $this->saveSolrResponse($qid, $responseJson);

    // format response
    $this->loopSolrResponse($responseJson);
  }

  /**
   * 
   */
  private function loopSolrResponse($response)
  {

  }

  /**
   * 
   */
  private function getAlto($artid, $qid)
  {

  }

  /**
   * 
   */
  private function getJournal($artid, $qid)
  {

  }

  /**
   * Save Solr response
   */
  private function saveSolrResponse($qid, $reponse)
  {
    file_put_contents("../../data/qid/$qid/solr.json",$response);
  }

}