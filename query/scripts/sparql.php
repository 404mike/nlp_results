<?php

class SPARQLQueryDispatcher
{
  private $endpointUrl;

  public function __construct()
  {
      $this->endpointUrl = 'https://query.wikidata.org/sparql';
  }

  public function query(string $sparqlQuery)
  {
    $opts = [
        'http' => [
            'method' => 'GET',
            'header' => [
                'Accept: application/sparql-results+json',
                'User-Agent: WDQS-example PHP/' . PHP_VERSION, // TODO adjust this; see https://w.wiki/CX6
            ],
        ],
    ];
    $context = stream_context_create($opts);

    $url = $this->endpointUrl . '?query=' . urlencode($sparqlQuery);

    $response = file_get_contents($url, false, $context);
    return $response;
  }
}