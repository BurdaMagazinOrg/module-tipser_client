<?php

namespace Drupal\tipser_client\Controller;

use Drupal\Core\Render\Renderer;
use Symfony\Component\HttpFoundation\Response;

class TipserProductController
{
  public function show($productId)
  {
    $build = [
      '#theme' => 'tipser_product_page',
      '#productId' => $productId,
      '#article_url' => $_GET['article'],
    ];

    return $build;
  }
}
