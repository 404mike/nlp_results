<?php

class IngestData {

  private $solr = 'http://solr';

  public function __construct()
  {
    $this->ingestNewspapers();
  }

  private function ingestNewspapers()
  {
    $files = glob('newspaper/complete/*.{json}', GLOB_BRACE);
    shuffle($files);

    $numFiles = count($files);
    $loopCount = 1;

    foreach($files as $key => $file) {

      $filename = $this->cleanKeyVal($file);

      $remaining = round(($loopCount / $numFiles) * 100,2);
      $time = date('Y-m-d H:i:s');
      echo "Running file Newspaper - $loopCount ($filename.json) out of $numFiles ($remaining%) - at $time\n";

      $progress = $this->checkProgress("newspaper",$filename);

      if($progress == 0) {
        // echo "no file\n";
        $this->formatNewspaperData($file);
        $this->updateProgress("newspaper",$filename);
      }
      $loopCount++;
    }
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
    $solr = [];

    foreach($data as $k => $v) {
      $arr = [
        "art_id_s"      => $v['id'],
        "date_pdate"    => $v['date'],
        "name_t"        => $v['name'],
        "start_char_s"  => $v['start_char'],
        "end_char_s"    => $v['end_char'],
        "qid_s"         => $v['qid'],
        "art_type_s"    => $v['art_type']
      ];

      $solr[] = $arr;
    }

    // $solr[] = [
    //   'commitWithin' => 1000,
    //   'overwrite' => true
    // ];

    $this->ingest($solr);
  }

  private function ingest($data)
  {
    $ch = curl_init($this->solr . ":8983/solr/amp/update?wtjson&commitWithin=1000&overwrite=true");

    $data_string = json_encode($data);
    // echo $data_string;

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_POST, TRUE);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type: application/json'));
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
    
    $response = curl_exec($ch);   
    // echo $response;
    // die(); 
  }

  private function checkProgress($type, $key)
  {
    $url = $this->solr . ":8983/solr/amp/select?indent=true&q.op=OR&q=progress_s:{TYPE}_{KEY}";
    $url = str_replace('{TYPE}',$type,$url);
    $url = str_replace('{KEY}',$key,$url);
    $responseJson = file_get_contents($url);
    
    $response = json_decode($responseJson,true);
    return $response['response']['numFound'];
  }

  private function updateProgress($type, $key)
  {
    $ch = curl_init($this->solr . ":8983/solr/amp/update?wt=json");

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
       'newspaper/complete/'],'',$val);
  }
} 

(new IngestData());