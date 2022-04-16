<?php

class AltoImage {

  private $pid;
  private $targetArt;

  public function __construct()
  {
  }

  public function getManifest($pid, $targetArt)
  {
    $this->pid = $pid;
    $this->targetArt = $targetArt;

    $url = "https://newspapers.library.wales/iiif/2.0/image/" . $this->pid . "/info.json";

    $manifest = json_decode(file_get_contents($url),true);
    
    $width = $manifest['width'];
    $height = $manifest['height'];

    $coord = $this->getAlto($width,$height);

    print_r($coord);
    die();
  }

  private function getAlto($width, $height)
  {
    $url = 'http://newspapers.library.wales/json/viewarticledata/llgc-id%3A'.$this->pid;

    $alto = json_decode(file_get_contents($url),true);

    $positionKey = $this->findArticleAlto($alto);

    $numberArticles = count($alto[$positionKey]['textBlocks']);

    if($numberArticles > 1) {
      $cord = $this->multiplePosition($alto[$positionKey]);
      return $this->calculate($width,$height,$cord,$this->pid);
    }
  }

  private function findArticleAlto($alto)
  {
    foreach($alto as $k => $v) {
      $id = $v['id'];
      if($id == "ART" .$this->targetArt) return $k;
    }
  }

  private function calculate($width,$height,$value,$pid)
  {
    $newX = $value['x'] * $width;
    $newY = $value['y'] * $width;
    $newW = $value['w'] * $width;
    $newH = $value['h'] * $height;
    
    // override for the viewer
    $newH = 900;
    
    return [$newX,$newY,$newW,$newH];
  
  }

  private function singlePosition()
  {

  }

  private function multiplePosition($alto)
  {
    // default
    $x = [];
    $y = [];
    $h = 0;
    $w = [];

    foreach($alto['textBlocks'] as $k => $v) {
      $x[] = $v['x'];
      $y[] = $v['y'];
      $h += $v['h'];
      $w[] = $v['w'];
    }

    $_x = min($x);
    $_y = min($y);
    $_w = max($w);

    $cord = [
      'x' => $_x,
      'y' => $_y,
      'h' => $h,
      'w' => $_w
    ];

    return $cord;
  }

  private function writeContentStateManifest($filename, $canvas_id, $parent_id)
  {
    $arr = [
      "type" => "Annotation",
      "motivation" => "highlighting",
      "target" => [
        "id" => $canvas_id,
        "type" => "Canvas",
        "partOf" => [
          "id" => $parent_id,
          "type" => "Manifest"
        ]
      ]
    ];

    file_put_contents($filename,json_encode($arr));
  }
}