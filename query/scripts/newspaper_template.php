<?php

class NewspaperTemplate {
  

  public function __construct()
  {

  }

  public function getTemplate($url, $title, $pid)
  {
    $template = $this->getBasicTemplate();

    $template = str_replace('{URL}',$url,$template);
    $template = str_replace('{TITLE}',$title,$template);
    $template = str_replace('{PID}',$pid,$template);

    return $template;
  }

  public function getBasicTemplate()
  {
    $template = 
    '{
      "@context": "http://iiif.io/api/presentation/2/context.json",
      "@type": "sc:Manifest",
      "@id": "{URL}",
      "label": "[{TITLE}]",
      "metadata": [
        {
          "label": [
            {
              "@value": "Title",
              "@language": "en"
            },
            {
              "@value": "Teitl",
              "@language": "cy-GB"
            }
          ],
          "value": "[{TITLE}]"
        },
        {
          "label": [
            {
              "@value": "Author",
              "@language": "en"
            },
            {
              "@value": "Awdur",
              "@language": "cy-GB"
            }
          ],
          "value": "{TITLE}"
        },
        {
          "label": [
            {
              "@value": "Date",
              "@language": "en"
            },
            {
              "@value": "Dyddiad",
              "@language": "cy-GB"
            }
          ],
          "value": ""
        },
        {
          "label": [
            {
              "@value": "Physical description",
              "@language": "en"
            },
            {
              "@value": "Disgrifiad ffisegol",
              "@language": "cy-GB"
            }
          ],
          "value": ""
        },
        {
          "label": "",
          "value": [
            {
              "@value": "",
              "@language": "en"
            },
            {
              "@value": "",
              "@language": "cy-GB"
            }
          ]
        },
        {
          "label": [
            {
              "@value": "Permalink",
              "@language": "en"
            },
            {
              "@value": "Dolen barhaol",
              "@language": "cy-GB"
            }
          ],
          "value": "<a href=\"http://hdl.handle.net/10107/4624345\">http://hdl.handle.net/10107/4624345</a>"
        },
        {
          "label": [
            {
              "@value": "Repository",
              "@language": "en"
            },
            {
              "@value": "Ystorfa",
              "@language": "cy-GB"
            }
          ],
          "value": [
            {
              "@value": "This content has been digitised by The National Library of Wales",
              "@language": "en"
            },
            {
              "@value": "Digidwyd y cynnwys hwn gan Lyfrgell Genedlaethol Cymru",
              "@language": "cy-GB"
            }
          ]
        }
      ],
      "license": "http://rightsstatements.org/page/InC/1.0/",
      "logo": {
        "@id": "https://damsssl.llgc.org.uk/iiif/2.0/image/logo/full/400,/0/default.jpg",
        "service": {
          "@context": "http://iiif.io/api/image/2/context.json",
          "@id": "https://damsssl.llgc.org.uk/iiif/2.0/image/logo",
          "profile": "http://iiif.io/api/image/2/level1.json"
        }
      },
      "attribution": [
        {
          "@value": "The National Library of Wales",
          "@language": "en"
        },
        {
          "@value": "Llyfrgell Genedlaethol Cymru",
          "@language": "cy-GB"
        }
      ],
      "sequences": [
        {
          "@id": "{PID}",
          "@type": "sc:Sequence",
          "label": "Current Page Order",
          "viewingDirection": "left-to-right",
          "viewingHint": "paged",
          "canvases": [
            {
              "@id": "{PID}",
              "@type": "sc:Canvas",
              "label": "{TITLE}",
              "height": 3580,
              "width": 2572,
              "images": [
                {
                  "@id": "{PID}",
                  "@type": "oa:Annotation",
                  "motivation": "sc:painting",
                  "resource": {
                    "@id": "https://damsssl.llgc.org.uk/iiif/2.0/4624345/res/4624345.jpg",
                    "@type": "dctypes:Image",
                    "format": "image/jpeg",
                    "service": {
                      "@context": "http://iiif.io/api/image/2/context.json",
                      "@id": "{PID}",
                      "profile": "http://iiif.io/api/image/2/level1.json",
                      "height": 3580,
                      "width": 2572,
                      "tiles": [
                        {
                          "width": 256,
                          "scaleFactors": [
                            1,
                            2,
                            4,
                            8,
                            16
                          ]
                        }
                      ]
                    },
                    "height": 3580,
                    "width": 2572
                  },
                  "on": "{PID}"
                }
              ]
            }
          ]
        }
      ]
    }';

    return $template;
  }

  // private function 
}