<?php

namespace Drupal\tipser_client\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;
use Drupal\user\Entity\User;

/**
 * Class TipserProductController.
 *
 * @package Drupal\tipser_client\Controller
 */
class TipserProductController {

  /**
   * Show tipser product with related article URL.
   *
   * @param int $productId
   *   The tipser product ID.
   *
   * @return array
   *   Render array for tipser product page.
   */
  public function show($productId) {
    $build = [
      '#theme' => 'tipser_product_page',
      '#productId' => $productId,
      '#article_url' => $_GET['article'],
    ];

    if (
      (isset($_GET['parent_id']) && is_numeric($_GET['parent_id']) && $parent_id = $_GET['parent_id']) &&
      (isset($_GET['parent_type']) && is_string($_GET['parent_type']) && $parent_type = $_GET['parent_type'])
    ) {
      if (
        ($parent_type == 'node' && $entity = Node::load($parent_id))
        ||
        ($parent_type == 'taxonomy_term' && $entity = Term::load($parent_id))
        ||
        ($parent_type == 'user' && $entity = User::load($parent_id))
      ) {
        $view_mode = 'full';
        $datalayer_variables = infinite_datalayer_get_variables($entity, $view_mode);
        infinite_datalayer_add($build, $entity->uuid(), $datalayer_variables);
      }
    }
    return $build;
  }

  /**
   * Get title from node or term entity.
   *
   * @return array|string[]
   *   Return title of node or term as markup render array.
   */
  public function setTitle() {
    $title = \Drupal::request()->query->get('title');
    if (NULL === $title) {

      // Try to load the node / page / term and get the title from there.
      $alias = explode($_SERVER['HTTP_HOST'], \Drupal::request()->query->get('article'))[1];
      if (NULL === $alias) {
        return ['#markup' => ''];
      }

      /** @var \Drupal\Core\Path\AliasManager $aliasManager */
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
          }
          else {
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
