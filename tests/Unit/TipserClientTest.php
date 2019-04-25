<?php

namespace Drupal\Tests\tipser_client\Unit;

use Drupal\Component\Serialization\Json;
use Drupal\Tests\UnitTestCase;
use Drupal\tipser_client\TipserClient;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;


/**
 * @file
 * PHPUnit tests for the Tipser Client.
 */
class TipserClientTest extends UnitTestCase {

  /**
   * Mock of http_client service.
   *
   * @var \GuzzleHttp\Client
   */
  protected $mockHttp;

  /**
   * Mock handler.
   *
   * @var \Drupal\Core\State\State
   */
  protected $mockhandler;

  /**
   * Mock of config.factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $mockConfigFactory;

  /**
   * String translation mock.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $stringTranslation;

    /**
   * Set up test environment.
   */
  public function setUp() {
    parent::setUp();

    $this->mockHandler = new MockHandler();
    $handler = HandlerStack::create($this->mockHandler);
    $this->mockHttp = new HttpClient(['handler' => $handler]);
    $this->stringTranslation = $this->getStringTranslationStub();

    $config = [
      'tipser_client.config' => [
        'tipser_activated' =>  true,
        'tipser_shopping_cart_icon_activated' =>  true,
        'shop_url' =>  'https => //www.tipser.com',
        'tipser_api' =>  'https => //t3-prod-api.tipser.com',
        'tipser_pos' =>  'foo',
        'tipser_apikey' =>  'very_long_key',
        'tipser_env' =>  'prod',
        'vocabulary' =>  'tipser_categories',
      ],
    ];
    $mockConfigFactory = $this->getConfigFactoryStub($config);

    $this->mockConfigFactory = $mockConfigFactory;
  }

  /**
   * Test the callAPI method.
   */
  public function testcallAPI() {

    $body = file_get_contents(__DIR__ . '/Mocks/kleid.json');
    // This sets up the mock client to respond to the first request it gets
    // with an HTTP 200 containing your mock json body.
    $this->mockHandler->append(new Response(200, [], $body));

    $tipser = new TipserClient(
      $this->mockHttp,
      $this->mockConfigFactory,
    );

    $params = $items = $messages = [];
    $params['product_id'] = '1234';
    $tipser_response = $tipser->callAPI($params, $items, $messages);
    $tipser_result  = Json::decode($tipser_response->getBody());

    $this->assertNotEmpty($tipser_result);
    $this->assertNotEmpty($messages['url']);
    $this->assertEquals($tipser_result, Json::decode($body));
  }
}
