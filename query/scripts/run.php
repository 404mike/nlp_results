<?php
include_once 'sparql.php';
include_once 'solr.php';

class SearchQids {

  private $solr;
  private $results = [];

  public function __construct()
  {
    $this->solr = new SolrSearch();

    // $this->runSparqlSearch();

    $this->searchSinglePerson();

    file_put_contents('results.json', json_encode($this->results,JSON_PRETTY_PRINT));
  }

  private function searchSinglePerson()
  {
    $qid = 'Q179374';
    $response = $this->solrSearch($qid);
    $this->results[$qid] = $response;
  }

  private function runSparqlSearch()
  {
    $endpointUrl = 'https://query.wikidata.org/sparql';
    $sparqlQueryString = <<< 'SPARQL'
    SELECT ?item ?itemLabel ?itemDescription
    WHERE 
    {
      ?item wdt:P27 wd:Q145;
            wdt:P106 wd:Q901;
            wdt:P21 wd:Q6581072.
      SERVICE wikibase:label { bd:serviceParam wikibase:language "[AUTO_LANGUAGE],en". }
    }

    SPARQL;

    $queryDispatcher = new SPARQLQueryDispatcher($endpointUrl);
    $queryResult = $queryDispatcher->query($sparqlQueryString);

    $this->loopResults($queryResult['results']);
  }

  private function loopResults($results)
  {
    foreach($results['bindings'] as $k => $v) {
      // print_r($v);
      $name = $v['itemLabel']['value'];
      $desc = isset($v['itemDescription']['value']) ? $v['itemDescription']['value'] : '';
      $qid = str_replace('http://www.wikidata.org/entity/','',$v['item']['value']);
      
      $docs = $this->solrSearch($qid);

      $this->results[$qid] = [
        'name' => $name,
        'qid' => $qid,
        'desc' => $desc,
        'docs' => $docs
      ];
    }
  }

  private function solrSearch($qid)
  {
    $response = $this->solr->search($qid);

    return $response;
  }
}

(new SearchQids());
