<?php

/**
 * @file
 * Contains \Drupal\tipser_client\Http\TCClientFactory.
 */

namespace Drupal\tipser_client\Http;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Site\Settings;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;

/**
 * Helper class to construct a HTTP client with Drupal specific config.
 */
class TipserClientFactory {

  /**
   * The handler stack.
   *
   * @var \GuzzleHttp\HandlerStack
   */
  protected $stack;

  /**
   * Constructs a new ClientFactory instance.
   *
   * @param \GuzzleHttp\HandlerStack $stack
   *   The handler stack.
   */
  public function __construct(HandlerStack $stack) {
    $this->stack = $stack;
  }

  /**
   * Constructs a new client object from some configuration.
   *
   * @param array $config
   *   The config for the client.
   *
   * @return \GuzzleHttp\Client
   *   The HTTP client.
   */
  public function fromOptions(array $config = []) {
    $client_config = \Drupal::config('tipser_client.config');
    $tipser_api = $client_config->get('tipser_api');

    $default_config = [
      // Security consideration: we must not use the certificate authority
      // file shipped with Guzzle because it can easily get outdated if a
      // certificate authority is hacked. Instead, we rely on the certificate
      // authority file provided by the operating system which is more likely
      // going to be updated in a timely fashion. This overrides the default
      // path to the pem file bundled with Guzzle.
      'verify' => TRUE,
      'timeout' => 30,
      'headers' => [
        'User-Agent' => 'Drupal/' . \Drupal::VERSION . ' (+https://www.drupal.org/) ' . \GuzzleHttp\default_user_agent(),
      ],
      'handler' => $this->stack,
      'version' => 1.0,
      'http_errors' => TRUE,
      'base_uri' => $tipser_api,
    ];

    $config = NestedArray::mergeDeep($default_config, Settings::get('http_client_config', []), $config);

    return new Client($config);
  }

}
