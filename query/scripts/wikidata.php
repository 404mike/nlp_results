<?php

class Wikidata {

  private $qidData = 'https://www.wikidata.org/w/api.php?action=wbgetentities&ids={QID}&format=json';
  private $imageData = 'https://www.wikidata.org/w/api.php?action=wbgetclaims&property=P18&entity={QID}&format=json';
  private $imagePath = 'https://en.wikipedia.org/w/api.php?action=query&titles=File%3A{IMAGE}&prop=imageinfo&iilimit=50&iiend=2007-12-31T23%3A59%3A59Z&iiprop=timestamp%7Cuser%7Curl&format=json';

  public function getQidData($qid)
  {
    $path = str_replace('{QID}',$qid, $this->qidData);
    $json = file_get_contents($path);
    $data = json_decode($json,true);

    $response = [];
    if(isset($data['entities'][$qid]['descriptions']['en'])) {
      $desc = $data['entities'][$qid]['descriptions']['en']['value'];
      $image = $this->getQidImageData($qid);
      $response = [
        'description' => $desc,
        'image' => $image,
        'dob' => $this->dateOfBirth($data, $qid)
      ];  
    }
    
    return $response;
  }

  public function getQidImageData($qid)
  {
    $path = str_replace('{QID}', $qid, $this->imageData);
    $json = file_get_contents($path);
    $data = json_decode($json,true);
    
    $imagePath = '';
    if(isset($data['claims']['P18'][0]['mainsnak']['datavalue'])) {
      $image = $data['claims']['P18'][0]['mainsnak']['datavalue']['value'];
      $imagePath = $this->getImagePath($image);
    }
    return $imagePath;
  }

  private function getImagePath($image)
  {
    $image = urlencode($image);
    $path = str_replace('{IMAGE}', $image, $this->imagePath);
    $json = file_get_contents($path);
    $data = json_decode($json,true);
    
    $imagePath = '';
    if(isset($data['query']['pages']['-1'])) {
      $imagePath = $data['query']['pages']['-1']['imageinfo'][0]['url'];
    }

    return $imagePath;
  }

  private function dateOfBirth($data, $qid)
  {
    if(isset($data['entities'][$qid]['claims']['P569'])) {
      $date = $data['entities'][$qid]['claims']['P569'][0]['mainsnak']['datavalue']['value']['time'];
      return date('Y',strtotime($date));
    }
  }
}