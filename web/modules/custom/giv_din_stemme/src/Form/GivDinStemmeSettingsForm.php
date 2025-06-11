<?php

namespace Drupal\giv_din_stemme\Form;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\State\State;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Giv din stemme settings form.
 *
 * @package Drupal\itk_admin\Form
 */
class GivDinStemmeSettingsForm extends FormBase {

  /**
   * Form constructor.
   *
   * @param \Drupal\Core\State\State $state
   *   The object state.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(protected State $state, protected EntityTypeManagerInterface $entityTypeManager) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): GivDinStemmeSettingsForm|static {
    // Instantiates this form class.
    return new static(
    // Load the service required to construct this class.
      $container->get('state'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'giv_din_stemme_general_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $references = [];
    $storage = $this->entityTypeManager->getStorage('node');
    foreach ([
      'permissions_help_page',
      'additional_microphone_permissions_help_page',
    ] as $key) {
      try {
        if ($id = $this->state->get('giv_din_stemme.' . $key)) {
          if ($node = $storage->load($id)) {
            $references[$key] = $node;
          }
        }
      }
      catch (InvalidPluginDefinitionException | PluginNotFoundException $e) {
      }
    }

    $form['general_settings'] = [
      '#type' => 'vertical_tabs',
      '#default_tab' => 'edit-footer',
    ];

    $form['texts'] = [
      '#title' => $this->t('Texts'),
      '#type' => 'details',
      '#open' => TRUE,
      '#weight' => '0',
      '#group' => 'general_settings',
    ];

    $form['references'] = [
      '#title' => $this->t('References'),
      '#type' => 'details',
      '#open' => TRUE,
      '#weight' => '0',
      '#group' => 'general_settings',
    ];

    $form['texts']['front_page_text'] = [
      '#title' => $this->t('Front page text (Step one)'),
      '#type' => 'text_format',
      '#format' => 'simpel_html',
      '#default_value' => $this->state->get('giv_din_stemme.front_page_text'),
      '#weight' => '1',
    ];

    $form['texts']['terms_text'] = [
      '#title' => $this->t('Terms text'),
      '#type' => 'text_format',
      '#format' => 'simpel_html',
      '#default_value' => $this->state->get('giv_din_stemme.terms_text'),
      '#weight' => '1',
    ];

    $form['texts']['competition_header_text'] = [
      '#title' => $this->t('Competition header text'),
      '#type' => 'text_format',
      '#format' => 'simpel_html',
      '#default_value' => $this->state->get('giv_din_stemme.competition_header_text'),
      '#weight' => '1',
    ];

    $form['texts']['donate_page_text'] = [
      '#title' => $this->t('Donate page text (Step five)'),
      '#type' => 'text_format',
      '#format' => 'simpel_html',
      '#default_value' => $this->state->get('giv_din_stemme.donate_page_text'),
      '#weight' => '1',
    ];

    $form['texts']['thank_you_text'] = [
      '#title' => $this->t('Thank you text'),
      '#type' => 'text_format',
      '#format' => 'simpel_html',
      '#default_value' => $this->state->get('giv_din_stemme.thank_you_text'),
      '#weight' => '1',
    ];

    $form['texts']['api_docs_text'] = [
      '#title' => $this->t('Api docs text'),
      '#type' => 'text_format',
      '#format' => 'simpel_html',
      '#default_value' => $this->state->get('giv_din_stemme.api_docs_text'),
      '#weight' => '1',
    ];

    $form['references']['permissions_help_page'] = [
      '#type' => 'entity_autocomplete',
      '#target_type' => 'node',
      '#title' => $this->t('Permissions help page'),
      '#default_value' => $references['permissions_help_page'] ?? NULL,
      '#weight' => '0',
    ];

    $form['references']['additional_microphone_permissions_help_page'] = [
      '#type' => 'entity_autocomplete',
      '#target_type' => 'node',
      '#title' => $this->t('Safari on iOS help page'),
      '#default_value' => $references['additional_microphone_permissions_help_page'] ?? NULL,
      '#weight' => '0',
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Save changes'),
      '#weight' => '6',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->messenger()->addStatus('Settings saved');
    $this->state->setMultiple([
      'giv_din_stemme.front_page_text' => $form_state->getValue('front_page_text')['value'],
      'giv_din_stemme.terms_text' => $form_state->getValue('terms_text')['value'],
      'giv_din_stemme.competition_header_text' => $form_state->getValue('competition_header_text')['value'],
      'giv_din_stemme.donate_page_text' => $form_state->getValue('donate_page_text')['value'],
      'giv_din_stemme.thank_you_text' => $form_state->getValue('thank_you_text')['value'],
      'giv_din_stemme.api_docs_text' => $form_state->getValue('api_docs_text')['value'],
      'giv_din_stemme.permissions_help_page' => $form_state->getValue('permissions_help_page'),
      'giv_din_stemme.additional_microphone_permissions_help_page' => $form_state->getValue('additional_microphone_permissions_help_page'),
    ]);

    drupal_flush_all_caches();
  }

}
