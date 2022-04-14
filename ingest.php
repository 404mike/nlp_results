<?php

class IngestData {

  public function __construct()
  {
    // $this->ingestJournals();
    $this->ingestNewspapers();
  }

  private function ingestJournals()
  {
    $files = glob('journals/data/*.{json}', GLOB_BRACE);
    shuffle($files);

    $numFiles = count($files);
    $loopCount = 1;

    foreach($files as $key => $file) {

      $filename = $this->cleanKeyVal($file);

      $remaining = round(($loopCount / $numFiles) * 100,2);
      echo "Running file Journal - $loopCount ($filename.json) out of $numFiles ($remaining%)\n";

      $progress = $this->checkProgress("journal",$filename);

      if($progress == 0) {
        $this->formatJournalsData($file);
        $this->updateProgress("journal",$filename);
      }
      $loopCount++;
    }
  }

  private function ingestNewspapers()
  {
    $files = glob('newspaper/data/*.{json}', GLOB_BRACE);
    $numFiles = count($files);
    shuffle($files);

    $loopCount = 1;
    foreach($files as $key => $file) {

      $filename = $this->cleanKeyVal($file);

      $remaining = round(($loopCount / $numFiles) * 100,2);
      echo "Running file Newspaper - $loopCount ($filename.json) out of $numFiles ($remaining%)\n";

      $progress = $this->checkProgress("newspaper",$filename);

      if($progress == 0) {
        $this->formatNewspaperData($file);
        $this->updateProgress("newspaper",$filename);
      }

      $loopCount++;
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

    $arr = [];
    // loop articles in file
    foreach($data as $k => $v) {

      $artKey = $v[0];
      $date = $v[1];

      foreach($v[2] as $peopleKey => $peopleVal) {
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
          "art_id_s"      => $data['id'],
          "date_pdate"    => $data['date'],
          "name_t"        => $data['name'],
          "start_char_s"  => $data['start_char'],
          "end_char_s"    => $data['end_char'],
          "qid_s"         => $data['qid'],
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
  }

  private function checkProgress($type, $key)
  {
    $url = "http://localhost:8983/solr/amp/select?indent=true&q.op=OR&q=progress_s%3A%22{TYPE}_{KEY}%22";
    $url = str_replace('{TYPE}',$type,$url);
    $url = str_replace('{KEY}',$key,$url);
    $responseJson = file_get_contents($url);
    $response = json_decode($responseJson,true);
    return $response['response']['numFound'];
  }

  private function updateProgress($type, $key)
  {
    $ch = curl_init("http://localhost:8983/solr/amp/update?wt=json");

    $data = [
      "add" => [
        "doc" => [
          "progress_s"  => "$type" ."_" ."$key",
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
    
    // print_r($response);
  }

  private function cleanKeyVal($val)
  {
    return str_replace(
      ['.json',
       'journals/data/',
       'newspaper/data/'],'',$val);

  }
} 

(new IngestData());