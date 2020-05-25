<?php

/**
 * @file
 * Drush integration for the tipser client module.
 */

use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\taxonomy\Entity\Term;

use GuzzleHttp\Exception\RequestException;

/**
 * Implements hook_drush_command().
 */
function tipser_client_drush_command() {
  $items = [];

  $items['tipser-categories'] = [
    'description' => dt('Create and upate the tipser categories on the current site.'),
    'options' => [],
    'drupal dependencies' => ['tipser_client'],
  ];

  return $items;
}

/**
 * Implements hook_drush_help().
 */
function tipser_client_drush_help($section) {
  switch ($section) {
    case 'drush:tipser-categories':
      return dt('Create or update categories provided by tipser on our site.');
  }
}

/**
 * Drush callback.
 */
function drush_tipser_client_tipser_categories() {
  $vocabulary_id = \Drupal::config('tipser_client.config')->get('vocabulary');
  if (!$vocabulary_id) {
    drush_set_error('ERROR', dt('You must configure a vocabulary to use'));
    return;
  }

  $vocabulary = Vocabulary::load($vocabulary_id);
  if (!$vocabulary) {
    drush_set_error('ERROR', dt('Vocabulary not found'));
    return;
  }

  $tipser_api = \Drupal::config('tipser_client.config')->get('tipser_api');
  $location = $tipser_api . '/v4/categories?market=de';
  try {
    $json = \Drupal::httpClient()
      ->get($location)
      ->getBody();
  }
  catch (RequestException $exception) {
    drush_log('tipser_client', $exception);
  }
  $data = json_decode($json);
  foreach ($data->departments as $idx => $department) {
    if ($department->name) {
      $department_term = advertising_products_find_term($vocabulary_id, $department->id);
      if ($department_term) {
        $department_term->get('name')->value = $department->name;
      }
      else {
        $department_term = Term::create([
          'name' => $department->name,
          'status' => 0,
          'field_original_id' => $department->id,
          'vid' => $vocabulary_id,
        ]);
      }
      $department_term->save();
      foreach ($department->sections as $sdx => $section) {
        if ($section->name) {
          $section_term = advertising_products_find_term($vocabulary_id, $section->id);
          if ($section_term) {
            $section_term->get('name')->value = $section->name;
          }
          else {
            $section_term = Term::create([
              'name' => $section->name,
              'status' => 0,
              'field_original_id' => $section->id,
              'vid' => $vocabulary_id,
            ]);
          }
          $section_term->parent = [$department_term->id()];
          $section_term->save();
          foreach ($section->categories as $cdx => $category) {
            if ($category->name) {
              $term = advertising_products_find_term($vocabulary_id, $category->id);
              if ($term) {
                $term->get('name')->value = $category->name;
              }
              else {
                $term = Term::create([
                  'name' => $category->name,
                  'status' => 0,
                  'field_original_id' => $category->id,
                  'vid' => $vocabulary_id,
                ]);
              }
              $term->parent = [$section_term->id()];
              $term->save();
            }
          }
        }
      }
    }
  }
}
