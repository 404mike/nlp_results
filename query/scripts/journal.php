<?php

class ParseJournalManifest {

  private $target = '2093726';

  public function __construct()
  {
    $this->getManifest();
  }


  /**
   * "art_id_s": "llgc-id:2092910_llgc-id:2093726",
   * Get manifest  https://damsssl.llgc.org.uk/iiif/2.0/2092910/manifest.json
   * Search for https://damsssl.llgc.org.uk/iiif/2.0/2093726/manifest.json
   */

  private function getManifest()
  {
    $json = file_get_contents('manifest.json');
    $data = json_decode($json,true);
    $this->cleanData($data);
  }

  private function cleanData($manifest)
  {
    // print_r($manifest);

    // remove manifests
    $target_manifest = '';
    foreach($manifest['manifests'] as $man_k => $man_v) {

      $target = "https://damsssl.llgc.org.uk/iiif/2.0/" . $this->target . "/manifest.json";

      $id = $man_v['@id'];

      if($id == $target) $target_manifest = $manifest['manifests'][$man_k];
    }

    // remove members 
    $target_members = '';
    foreach($manifest['members'] as $man_k => $man_v) {

      $target = "https://damsssl.llgc.org.uk/iiif/2.0/" . $this->target . "/manifest.json";

      $id = $man_v['@id'];

      if($id == $target) $target_members = $manifest['members'][$man_k];
    }


    $this->formatManifest($manifest, $target_manifest, $target_members);
  }

  private function formatManifest($manifest, $target_manifest, $target_members)
  {
    unset($manifest['manifests']);
    unset($manifest['members']);

    $manifest['manifests'][] = $target_manifest;
    $manifest['members'][] = $target_members;

    file_put_contents('new_manifest.json',json_encode($manifest,JSON_PRETTY_PRINT));
  }
}

(new ParseJournalManifest());