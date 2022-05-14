<?php
include_once 'newspaper_template.php';

class AltoImage {

  private $pid;
  private $targetArt;
  private $template;

  public function __construct()
  {
    $this->template = new NewspaperTemplate();
  }

  public function getManifest($artId, $canvas_id, $targetArt, $parent_id, $filename, $date)
  {
    $this->pid = $canvas_id;
    $this->targetArt = $artId;

    $url = "https://newspapers.library.wales/iiif/2.0/image/" . $this->pid . "/info.json";

    $manifest = json_decode(file_get_contents($url),true);
    
    $width = $manifest['width'];
    $height = $manifest['height'];
    $dime = ['width' => $width, 'height' => $height];

    $coord = $this->getAlto($width,$height);

    $this->writeContentStateManifest($coord, $canvas_id, $parent_id, $filename, $dime);

    // whole newspaper page 
    $this->prepareWholePage($canvas_id, $filename, $date);
  }

  private function getAlto($width, $height)
  {
    $url = 'http://newspapers.library.wales/json/viewarticledata/llgc-id%3A'.$this->pid;
    // echo "$url\n";
    // echo $this->targetArt . "\n";
    // die();

    $alto = json_decode(file_get_contents($url),true);

    $positionKey = $this->findArticleAlto($alto);

    if(empty($alto[$positionKey]['textBlocks'])) {
      return;
    }

    $numberArticles = count($alto[$positionKey]['textBlocks']);

    if($numberArticles > 1) {
      $cord = $this->multiplePosition($alto[$positionKey]);
      return $this->calculate($width,$height,$cord,$this->pid);
    }else{
      $cord = $this->singlePosition($alto[$positionKey]);
      return $this->calculate($width,$height,$cord,$this->pid);
    }
  }

  private function findArticleAlto($alto)
  {
    foreach($alto as $k => $v) {
      $id = $v['id'];
      if($id == $this->targetArt) return $k;
    }
  }

  private function calculate($width,$height,$value,$pid)
  {
    $newX = $value['x'] * $width;
    $newY = $value['y'] * $width;
    $newW = $value['w'] * $width;
    $newH = $value['h'] * $height;
    
    // override for the viewer
    // $newH = 900;

    return [$newX,$newY,$newW,$newH];
  
  }

  private function singlePosition($alto)
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

  private function writeContentStateManifest($coord, $canvas_id, $parent_id, $filename, $dime)
  {
    // if the $coord is empty the alto may be on the previous page
    if(empty($coord)) {
      $this->pid = ((int)$this->pid - 1);
      $coord = $this->getAlto($dime['width'], $dime['height']);
    }
    // print_r($coord);
    // die();
    $xyhw = implode(',',$coord);

    $canvas_id = 'http://dams.llgc.org.uk/iiif/' . $parent_id . '/canvas/'.$canvas_id.'#xywh='.$xyhw;

    $parent_id = 'https://damsssl.llgc.org.uk/iiif/newspaper/issue/'.$parent_id.'/manifest.json';

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

  private function prepareWholePage($canvas_id, $filename, $date)
  {
    $newFilename = str_replace('newspaper/','newspaper/full-',$filename);

    $filenameParts = explode('/',$newFilename);
    // print_r($filenameParts);
    $qid = $filenameParts[3];
    $manifest = $filenameParts[5];

    $url = "https://404mike.github.io/nel_results/data/qids/$qid/newspaper/$manifest";
    $pid = "https://newspapers.library.wales/iiif/2.0/image/$canvas_id";
    $title = date('Y-m-d',strtotime($date[0]));

    $template = $this->template->getTemplate($url, $title, $pid);
    
    $this->writeWholePage($newFilename, $template);
  }

  private function writeWholePage($filename, $code)
  {
    file_put_contents($filename,$code);
  }
}