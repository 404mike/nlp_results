<?php

class ParseJournalManifest {


  public function __construct()
  {
  }

  /**
   * "art_id_s": "llgc-id:2092910_llgc-id:2093726",
   * Get manifest  https://damsssl.llgc.org.uk/iiif/2.0/2092910/manifest.json
   * Search for https://damsssl.llgc.org.uk/iiif/2.0/2093726/manifest.json
   */
  public function getManifest($article_id, $filename)
  {
    $article_parts = explode('_',$article_id);
    
    $original_manifest = str_replace('llgc-id:','',$article_parts[0]);
    $target = str_replace('llgc-id:','',$article_parts[1]);

    $manifest_url = 'https://damsssl.llgc.org.uk/iiif/2.0/'.$original_manifest.'/manifest.json';

    $json = file_get_contents($manifest_url);
    $data = json_decode($json,true);
    $this->cleanData($data, $target, $filename);
  }

  private function cleanData($manifest, $target, $filename)
  {
    // remove manifests
    $target_manifest = '';
    foreach($manifest['manifests'] as $man_k => $man_v) {

      $target_manifest_pid = "https://damsssl.llgc.org.uk/iiif/2.0/" . $target . "/manifest.json";

      $id = $man_v['@id'];

      if($id == $target_manifest_pid) $target_manifest = $manifest['manifests'][$man_k];
    }

    // remove members 
    $target_members = '';
    foreach($manifest['members'] as $man_k => $man_v) {

      $target = "https://damsssl.llgc.org.uk/iiif/2.0/" . $this->target . "/manifest.json";

      $id = $man_v['@id'];

      if($id == $target_manifest_pid) $target_members = $manifest['members'][$man_k];
    }


    $this->formatManifest($manifest, $target_manifest, $target_members, $filename);
  }

  private function formatManifest($manifest, $target_manifest, $target_members, $filename)
  {
    unset($manifest['manifests']);
    unset($manifest['members']);

    $manifest['manifests'][] = $target_manifest;
    $manifest['members'][] = $target_members;

    file_put_contents($filename,json_encode($manifest,JSON_PRETTY_PRINT));
  }
}

(new ParseJournalManifest());