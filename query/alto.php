<?php

class AltoImage {

  private $pid = 3419617;
  private $targetArt = 95;

  public function __construct()
  {
    $this->getManifest();
  }

  private function getManifest()
  {
    $url = "https://newspapers.library.wales/iiif/2.0/image/" . $this->pid . "/info.json";

    $manifest = json_decode(file_get_contents($url),true);
    
    $width = $manifest['width'];
    $height = $manifest['height'];

    $this->getAlto($width,$height);
  }

  private function getAlto($width, $height)
  {
    $url = 'http://newspapers.library.wales/json/viewarticledata/llgc-id%3A'.$this->pid;

    $alto = json_decode(file_get_contents($url),true);

    $positionKey = $this->findArticleAlto($alto);

    $numberArticles = count($alto[$positionKey]['textBlocks']);

    if($numberArticles > 1) {
      $cord = $this->multiplePosition($alto[$positionKey]);
      $this->calculate($width,$height,$cord,$this->pid);
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
  
    $image = 'http://dams.llgc.org.uk/iiif/2.0/image/'.$pid.'/%d,%d,%d,%d/700,/0/default.jpg';
    
    $newX = $value['x'] * $width;
    $newY = $value['y'] * $width;
    $newW = $value['w'] * $width;
    $newH = $value['h'] * $height;
    
    // echo $newX . ' ';
    
    echo sprintf($image,$newX,$newY,$newW,$newH) . "\n";
  
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
}

(new AltoImage());