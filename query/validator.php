<?php

class ResultsValidator {

  private $project;
  private $missing = [];

  public function __construct()
  {
    $this->project = 'data/manifests/62599b02598a4/index.json';

    $this->loadMainManifest();

    print_r($this->missing);
  }

  private function loadMainManifest()
  {
    $json = file_get_contents($this->project);
    $data = json_decode($json,true);
    
    foreach($data['items'] as $k => $v) {
      $this->loadPersonManifest($this->cleanPath($v['id']));
    }
  }

  private function loadPersonManifest($file)
  {
    $json = file_get_contents($file);
    $data = json_decode($json,true);
    foreach($data['items'] as $k => $v){
      $this->validateManifestCollection($this->cleanPath($v['id']));
    }
  }

  private function validateManifestCollection($file)
  {
    if(!file_exists($file)) {
      die("no $file\n");
    }

    $json = file_get_contents($file);
    $data = json_decode($json,true);

    if(!isset($data['items'])) {
      print_r($data);
      die();
    }

    foreach($data['items'] as $k => $v){
      // print_r($v);
      $this->validateManifest($this->cleanPath($v['id']));
    }
  }

  private function validateManifest($file)
  {
    if(!file_exists($file)) {
      $this->missing[] = $file;
    }
  }

  private function cleanPath($path)
  {
    return str_replace('https://404mike.github.io/nel_results/','',$path);
  }

}

(new ResultsValidator());