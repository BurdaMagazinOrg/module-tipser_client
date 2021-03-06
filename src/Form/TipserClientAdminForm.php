<?php

namespace Drupal\tipser_client\Form;

use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class TipserClientAdminForm.
 *
 * @package Drupal\tipser_client\Form
 */
class TipserClientAdminForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'tipser_client.config',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'tipser_client_admin_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('tipser_client.config');

    $form['tipser_activated'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Activate tipser'),
      '#description' => $this->t('En- / Disable the tipser integration.'),
      '#default_value' => $config->get('tipser_activated'),
    ];

    $form['tipser_shopping_cart_icon_activated'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Activate tipser shopping cart icon'),
      '#description' => $this->t('En- / Disable the tipser shopping cart.'),
      '#default_value' => $config->get('tipser_shopping_cart_icon_activated'),
    ];

    $library_source = \Drupal::config('tipser_client.settings')->get('library_source');
    $library_source_description = [
      'The source of the tipser SDK library.',
      'This value is normally set in settings.php and will not be stored.',
      'You can override it here for one update.',
      'To download the new SDK source you have to check the "Update tipser SDK" checkbox below.',
    ];

    $description = implode(' ', $library_source_description);
    $form['library_source'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Tipser SDK source'),
      '#description' => $this->t('@description', ['@descritpion' => $description]),
      '#default_value' => isset($library_source) ? $library_source : $config->get('library_source'),
    ];

    $form['library_update'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Update tipser SDK'),
      '#description' => $this->t('Download a new version of the tipser SDK.'),
      '#default_value' => FALSE,
    ];

    $form['tipser_api'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Tipser API'),
      '#description' => $this->t('URL of the tipser API. Include the API version, no / at the end.'),
      '#maxlength' => 128,
      '#required' => TRUE,
      '#default_value' => $config->get('tipser_api'),
    ];

    $environments = [
      'prod' => $this->t('Production'),
      'stage' => $this->t('Staging'),
      'dev' => $this->t('Development'),
    ];
    $form['tipser_env'] = [
      '#type' => 'select',
      '#title' => $this->t('Tipser Environment'),
      '#description' => $this->t('Choose the Tipser Environment.'),
      '#options' => $environments,
      '#default_value' => $config->get('tipser_env'),
    ];

    $form['tipser_pos'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Tipser key'),
      '#description' => $this->t('Site specific user key from Tipser.'),
      '#maxlength' => 128,
      '#required' => TRUE,
      '#default_value' => $config->get('tipser_pos'),
    ];

    $form['tipser_apikey'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Tipser API key'),
      '#description' => $this->t('Site specific API key from Tipser. Very long.'),
      '#maxlength' => 128,
      '#required' => TRUE,
      '#default_value' => $config->get('tipser_apikey'),
    ];

    $form['shop_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Tipser Shop'),
      '#description' => $this->t('URL of the tipser shop. No "/" at the end.'),
      '#maxlength' => 128,
      '#required' => TRUE,
      '#default_value' => $config->get('shop_url'),
    ];

    $vocabularies_options = [0 => $this->t('None')];
    $field_map = \Drupal::entityManager()->getFieldMap();
    $term_field_map = $field_map['taxonomy_term'];
    if (isset($term_field_map['field_original_id'])) {
      $vocabularies = Vocabulary::loadMultiple();
      foreach ($vocabularies as $id => $entity) {
        if (isset($term_field_map['field_original_id']['bundles'][$entity->id()])) {
          $vocabularies_options[$entity->id()] = $entity->label();
        }
      }
    }

    $form['vocabulary'] = [
      '#type' => 'select',
      '#title' => $this->t('Vocabulary'),
      '#description' => $this->t('Choose the Drupal vocabulary that tipser should add its category terms to. The vocabulary needs to have a field "original_id". A drush job needs to be run to populate and update this vocabulary with valid terms.'),
      '#options' => $vocabularies_options,
      '#default_value' => $config->get('vocabulary'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->updateTipserActivated($form_state);
    $this->updateTipserShoppingCartIconActivated($form_state);

    $this->config('tipser_client.config')
      ->set('tipser_api', $form_state->getValue('tipser_api'))
      ->save();
    $this->config('tipser_client.config')
      ->set('tipser_pos', $form_state->getValue('tipser_pos'))
      ->save();
    $this->config('tipser_client.config')
      ->set('tipser_apikey', $form_state->getValue('tipser_apikey'))
      ->save();
    $this->config('tipser_client.config')
      ->set('tipser_env', $form_state->getValue('tipser_env'))
      ->save();
    $this->config('tipser_client.config')
      ->set('shop_url', $form_state->getValue('shop_url'))
      ->save();
    $this->config('tipser_client.config')
      ->set('vocabulary', $form_state->getValue('vocabulary'))
      ->save();

    if ($form_state->getValue('library_update')) {
      $return = tipser_update_library_source($form_state->getValue('library_source'), 'public://tipser/tipser.sdk.js');
      if ($return === FALSE) {
        drupal_set_message($this->t('The tipser SDK could not be updated.'), 'error');
      }
      else {
        drupal_set_message($this->t('The tipser SDK was updated successfully.'));
      }
    }
  }

  /**
   * Update tipser active state.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state interface.
   */
  protected function updateTipserActivated(FormStateInterface $form_state) {
    $config = $this->config('tipser_client.config');
    $invalidateCache = FALSE;

    // If setting has changed we need to invalidate caches.
    if ($config->get('tipser_activated') !== $form_state->getValue('tipser_activated')) {
      $invalidateCache = TRUE;
    }
    $config->set('tipser_activated', $form_state->getValue('tipser_activated'))
      ->save();

    if ($invalidateCache) {

      /** @var \Drupal\Core\Cache\CacheTagsInvalidator $invalidator */
      $invalidator = \Drupal::service('cache_tags.invalidator');
      $invalidator->invalidateTags(['config:block.block.socialsblock']);
    }
  }

  /**
   * Update tipser shopping card icon.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state interface.
   */
  protected function updateTipserShoppingCartIconActivated(FormStateInterface $form_state) {
    $config = $this->config('tipser_client.config');
    $invalidateCache = FALSE;

    // If setting has changed we need to invalidate caches.
    if ($config->get('tipser_shopping_cart_icon_activated') !== $form_state->getValue('tipser_shopping_cart_icon_activated')) {
      $invalidateCache = TRUE;
    }
    $config->set('tipser_shopping_cart_icon_activated', $form_state->getValue('tipser_shopping_cart_icon_activated'))
      ->save();

    if ($invalidateCache) {

      /** @var \Drupal\Core\Cache\CacheTagsInvalidator $invalidator */
      $invalidator = \Drupal::service('cache_tags.invalidator');
      $invalidator->invalidateTags(['config:block.block.socialsblock']);
    }
  }

}
