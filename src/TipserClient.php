<?php

/**
 * @file
 * Contains Drupal\tipser_client\TipserClient.
 */

namespace Drupal\tipser_client;

use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\tipser_client\Exception\ImportException;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;

/**
 * Class TipserClient.
 *
 * @package Drupal\tipser_client
 */
class TipserClient {

  const TIPSER_CLIENT_MAX_ITEMS = 10;
  const TIPSER_IMAGE_STYLES = [
    'original',
    '960x',
    '450x',
    '250x',
  ];

  /** @var  ClientInterface */
  protected $httpClient;

  protected $config;

  /**
   * @param \GuzzleHttp\ClientInterface $httpClient
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   */
  public function __construct(ClientInterface $httpClient,  ConfigFactoryInterface $configFactory) {
    $this->httpClient = $httpClient;
    $this->config = $configFactory->get('tipser_client.config');
  }


  /**
   * @param $params
   * @param $items
   * @param $messages
   * @return \Psr\Http\Message\ResponseInterface
   * @throws GuzzleException
   */
  protected function callAPI($params, $items, &$messages) {
    $client_config = \Drupal::config('tipser_client.config');
    $tipser_pos = $client_config->get('tipser_pos');

    if (isset($params['product_id'])) {
      $url = '/v4/products/';
      $url .= $params['product_id'];
    }

    $options = [];
    $options['query'] = [
      'market' => 'de',
      'pos' => $tipser_pos,
    ];
    $messages['url'] = $url;

    return $this->httpClient->request('GET', $url, $options);
  }

  /**
   * Retrieve product from API and creates or updates the entities.
   *
   * @param $query
   * @param int $items
   *   Number of items to be fetched
   * @return array
   *   Array of products
   * @throws GuzzleException
   */
  public function queryProducts($query, $items = TipserClient::TIPSER_CLIENT_MAX_ITEMS) {
    $active_products = array();
    $data = $this->callAPI($query, $items, $messages);
    $result = Json::decode($data->getBody());
    \Drupal::logger('tipser')->notice('Result @message', array('@message' => print_r($result, TRUE)));

    if(isset($result['id'])){
      $shop_url = $this->config->get('shop_url');
      $result['detailpageurl'] = $shop_url . '/products/' . $result['id'];
      $result['shop'] = '';
      $result['name'] = $result['title'];

      $result['oldprice'] = '';
      if (isset($result['categories']) && isset($result['categories'][0]) && isset($result['categories'][0])) {
        $category = FALSE;
        if (isset($result['categories'][0]['productType'])) {
          $category = $result['categories'][0]['productType'];
        }
        else if (isset($result['categories'][0]['section'])) {
          $category = $result['categories'][0]['section'];
        }
        else if (isset($result['categories'][0]['department'])) {
          $category = $result['categories'][0]['department'];
        }
        if ($category) {
          $vocabulary_id = $this->config->get('vocabulary');
          if ($vocabulary_id) {
            $term = advertising_products_find_term($vocabulary_id, $result['category']);
            if ($term) {
              $result['category_target_id'] = $term->id();
            }
          }
        }
      }
      if (isset($result['discountPriceIncVat']['value']) && $result['discountPriceIncVat']['value'] > 0) {
        $result['cross_price'] = $result['priceIncVat']['value'];
        $result['currency'] = $result['priceIncVat']['currency'];
        $result['formattedprice'] = $result['discountPriceIncVat']['formatted'];
        $result['price'] = $result['discountPriceIncVat']['value'];
      }
      else {
        $result['currency'] = $result['priceIncVat']['currency'];
        $result['formattedprice'] = $result['priceIncVat']['formatted'];
        $result['price'] = $result['priceIncVat']['value'];
        $result['cross_price'] = '';
      }
      $result['available'] = $result['isInStock'];
      $result['active'] = 1;
      $active_products[$result['id']] = $result;
    }
    elseif(isset($query['product_id'])) {
      $active_products[$query['product_id']] = ['active' => FALSE];
    }
    else {
      return FALSE;
    }

    return $active_products;
  }

  /**
   * Fetch product image from api.
   *
   * @param array $product
   *   A product array fetched from the api
   *   Path of the image which should be fetched. API Docs
   * @return bool|\Psr\Http\Message\ResponseInterface
   *   Response from api
   * @throws \Exception
   */
  public function retrieveImage($product) {
    \Drupal::logger('retrieveImage')
      ->notice('Product @message', array('@message' => print_r($product, TRUE)));
    if(!isset($product['images'])) {
      return FALSE;
    }

    $image = $this->fetchImage($product);

    if (!$image->getBody()) {
      $error_msg = 'Error Message: ' . $image->getStatusCode() ? $image->getStatusCode() : "Couldn't retrieve image";
      throw new \Exception($error_msg);
    }

    if (!in_array($image->getHeader('content-type')[0], array('image/png', 'image/jpeg'))) {
      $error_msg = 'Error Message: Unexpected content type "' . $image->getHeader('content-type')[0] . '"';
      throw new \Exception($error_msg);
    }

    return $image;
  }

  /**
   * Check if tipser has been activated for this site.
   *
   * @return bool
   */
  public static function isActivated() {
      return (bool) \Drupal::config('tipser_client.config')->get('tipser_activated');
  }

  /**
   * Check if tipser has been activated for this site.
   *
   * @return bool
   */
  public static function iconIsActivated() {
      return (bool) \Drupal::config('tipser_client.config')->get('tipser_shopping_cart_icon_activated');
  }

  /**
   * Fetch image from tipser / cloudinary. Try all image styles defined in TIPSER_IMAGE_STYLES
   * in descending order. If all fail, throw exception.
   *
   * @param $product
   * @param int $imageStyleI
   * @return \Psr\Http\Message\ResponseInterface
   * @throws ImportException
   */
  protected function fetchImage($product, $imageStyleI = 0): \Psr\Http\Message\ResponseInterface
  {
    // already tried all image styles -> throw exception
    if (false === isset(self::TIPSER_IMAGE_STYLES[$imageStyleI])) {
      throw new ImportException(sprintf(
        'Unable to fetch image for tipser product "%s" with the id "%s" (%s)',
        $product['title'],
        $product['id'],
        $product['detailpageurl']
      ));
    }
    $imageUrl = $product['images'][0][self::TIPSER_IMAGE_STYLES[$imageStyleI]];
    $parsedUrl = parse_url($imageUrl);
    $baseUri = $parsedUrl['scheme'] . '://' . $parsedUrl['host'];
    \Drupal::logger('base_url')->notice(' @message', array('@message' => $baseUri));

    try {
      $timeout = 10;
      return $this->httpClient->request(
        'GET',
        $parsedUrl['path'],
        [
          'base_uri' => $baseUri,
          RequestOptions::CONNECT_TIMEOUT => $timeout,
          RequestOptions::TIMEOUT => $timeout,
          RequestOptions::READ_TIMEOUT => $timeout,
        ]
      );
    } catch (ClientException $e) {
      \Drupal::logger('tipser_image')->notice('Client exception @message', array('@message' => $e->getMessage()));
      return $this->fetchImage($product, $imageStyleI + 1);
    } catch (GuzzleException $e) {
      \Drupal::logger('tipser_image')->notice('Client exception @message', array('@message' => $e->getMessage()));
      return $this->fetchImage($product, $imageStyleI + 1);
    }
  }
}
