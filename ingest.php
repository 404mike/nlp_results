<?php

class IngestData {

  public function __construct()
  {
    $this->ingestJournals();
    // $this->ingestNewspapers();
  }

  private function ingestJournals()
  {
    $files = glob('journals/data/*.{json}', GLOB_BRACE);
    foreach($files as $file) {
      $this->formatJournalsData($file);
    }
  }

  private function ingestNewspapers()
  {
    $files = glob('newspaper/data/*.{json}', GLOB_BRACE);
    foreach($files as $file) {
      $this->formatNewspaperData($file);
    }
  }

  private function formatJournalsData($data)
  {
    $json = file_get_contents($data);
    $data = json_decode($json,true);
    // print_r($data);

    $arr = [];
    // loop articles in file
    foreach($data as $k => $v) {

      $artKey = $v[0] . '_' . $v[1];
      $date = $v[2];

      foreach($v[4] as $peopleKey => $peopleVal) {
        $arr[] = [
          'id' => $artKey,
          'date' => $date,
          'name' => $peopleVal[0],
          'start_char' => $peopleVal[1],
          'end_char' => $peopleVal[2],
          'type' => $peopleVal[3],
          'qid' => $peopleVal[4],
          'art_type' => $peopleVal[5],
        ];
      }
    }

    $this->parseFormatedData($arr);
  }

  private function formatNewspaperData($data)
  {
    $json = file_get_contents($data);
    $data = json_decode($json,true);
    print_r($data);
    die();
  }


  private function parseFormatedData($data)
  {
    foreach($data as $k => $v) {
      $this->ingest($v);
    }
  }

  private function ingest($data)
  {
    $ch = curl_init("http://localhost:8983/solr/amp/update?wt=json");

    $data = [
      "add" => [
        "doc" => [
          "id_s"          => $data['id'],
          "date_pdate"    => $data['date'],
          "name_t"        => $data['name'],
          "start_char_s"  => $data['start_char'],
          "end_char_s"    => $data['end_char'],
          "qid_S"         => $data['qid'],
          "art_type_s"    => $data['art_type']
        ],
        "commitWithin" => 1000,
      ],
    ];
    $data_string = json_encode($data);
    
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_POST, TRUE);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type: application/json'));
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
    
    $response = curl_exec($ch);
    
    print_r($response);
    
  }

} 

(new IngestData());