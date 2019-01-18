<?php
/**
 * @file
 * Contains \Drupal\mbta_routes\Controller\MBTARoutesController.
 */
namespace Drupal\mbta_routes\Controller;

use Drupal\Component\Serialization\Json;
use GuzzleHttp\Exception\RequestException;


class MBTARoutesController {

  public function buildList($number, $title){
    // I had trouble with exception handling and Guzzle, so this will break if
    // There's any issue with the API

    /** @var \GuzzleHttp\Client $client */
    $client = \Drupal::service('http_client_factory')->fromOptions([
      'base_uri' => 'https://api-v3.mbta.com/'
    ]);

    //Example: https://api-v3.mbta.com/routes?filter[type]=3
    $response = $client->get('routes', [
      'query' => [
        'filter[type]' => $number,
      ]
    ]);

    $routes = Json::decode($response->getBody());

    $items = [];

    $routes = $routes['data'];

    //Loop through the decoded JSON to take out the info we need (name, colors, etc)
    foreach ($routes as $route) {
      //$items[] = $route['attributes']['long_name'];

      $long_name = $route['attributes']['long_name'];
      $color = $route['attributes']['color'];
      $text_color = $route['attributes']['text_color'];

      // Include numbers for bus routes (ie, [7] Monroe Ave)
      if($number == 3){
        $name = '['.$route['attributes']['short_name'].'] '.$route['attributes']['long_name'];
      }
      else{
        $name = $route['attributes']['long_name'];
      }

      //Set a CSS class for every color. You will see below you can also do this inline very easily
      switch($color){
        case 'DA291C':
          $color_class = 'red';
          break;
        case 'ED8B00':
          $color_class = 'orange';
          break;
        case '00843D';
          $color_class = 'green';
          break;
        case '003DA5';
          $color_class = 'blue';
          break;
        case '7C878E':
          $color_class = 'silver';
          break;
        case 'FFC72C':
          $color_class = 'bus';
          break;
        case '80276C':
          $color_class = 'commuter';
          break;
        default:
          $color_class = "";
          break;
      }

      // $items is an array of arrays (which also contain an array...)
      $items[] = [
        '#children' => $name,

        // Instead of #children, we can use #markup to build a link to page that will have schedule table
        // We'd then make a new route that sets a controller for those pages
        //
        // '#markup' => '<a href="'.$route['id'].'">'.$name.'</a>',

        //
        '#wrapper_attributes' =>  [
          // We can actually get all the colors from the API and just style inline if we wanted...
          //'style' => 'background-color: #'.$color.'; color: #'.$text_color.';',
          'class' =>  $color_class
        ]
       ];

    }

    return [
      '#theme' => 'item_list',
      '#title' => $title,
      '#items' => $items
    ];

  }

  public function content() {
    $items = [];

    // Build a page that has list-item sections with separate headers
    // Using custom titles because the API's categorization is odd and nothing
    // really matches the Sample Output

    $items[] = $this->buildList("0,1", "Subway");
    $items[] = $this->buildList(2, "Commuter Rail");
    $items[] = $this->buildList(3, "Silver Line & Bus");


    return [
      '#theme' => 'item_list',
      '#items' => $items,
      '#attached' => array(
        'library' => array(
          'mbta_routes/routes_list',
        ),
      ),
    ];

  }
}
