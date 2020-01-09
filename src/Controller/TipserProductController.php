<?php

namespace Drupal\tipser_client\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Path\AliasManager;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;

class TipserProductController
{
  public function show($productId)
  {
    $build = [
      '#theme' => 'tipser_product_page',
      '#productId' => $productId,
      '#article_url' => $_GET['article'],
    ];

    if (isset($_GET['parent_id']) && is_numeric($_GET['parent_id']) && $parent_id = $_GET['parent_id']) {
      $entity = Node::load($parent_id);
      $view_mode = 'full';
      $datalayer_variables = infinite_datalayer_get_variables($entity, $view_mode);
      infinite_datalayer_add($build, $entity->uuid(), $datalayer_variables);
    }

    return $build;
  }

  public function setTitle()
  {
    $title = \Drupal::request()->query->get('title');
    if (null === $title) {
      // try to load the node / page / term and get the title from there
      $alias = explode($_SERVER['HTTP_HOST'], \Drupal::request()->query->get('article'))[1];
      if (null === $alias) {
        return ['#markup' => ''];
      }
      /** @var AliasManager $aliasManager */
      $aliasManager = \Drupal::service('path.alias_manager');
      $path = $aliasManager->getPathByAlias($alias);
      $e = explode('/', $path);
      switch ($e[1]) {
        case 'node':
          $entity = Node::load($e[2]);
          $title = $entity->get('field_seo_title')->value;
          break;
        case 'taxonomy':
          $entity = Term::load($e[3]);
          $metaTags = unserialize($entity->get('field_meta_tags')->value);
          if (isset($metaTags['title']) && strlen($metaTags['title'])) {
            $title = $metaTags['title'];
          } else {
            $title = $entity->getName();
          }
          break;
        default:
          return ['#markup' => ''];
      }
    }
    return ['#markup' => $title, '#allowed_tags' => Xss::getHtmlTagList()];
  }
}
