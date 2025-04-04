<?php

namespace Drupal\giv_din_stemme\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for the content_entity_example entity edit forms.
 *
 * @ingroup content_entity_example
 */
class GivDinStemmeForm extends ContentEntityForm {

  public function __construct(
    EntityRepositoryInterface $entity_repository,
    EntityTypeBundleInfoInterface $entity_type_bundle_info,
    TimeInterface $time,
    private readonly FileUrlGeneratorInterface $fileUrlGenerator,
  ) {
    parent::__construct($entity_repository, $entity_type_bundle_info, $time);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.repository'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time'),
      $container->get('file_url_generator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\giv_din_stemme\Entity\GivDinStemme $entity */
    $form = parent::buildForm($form, $form_state);
    $entity = $this->entity;

    if ($file = $entity->getFile()) {
      $form['audio_player'] = [
        '#theme' => 'giv_din_stemme_audio',
        '#src' => $this->fileUrlGenerator->generate($file->getFileUri())->toString(TRUE)->getGeneratedUrl(),
        '#text' => $entity->getText(),
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $form_state->setRedirect('entity.gds.collection');
    /** @var \Drupal\giv_din_stemme\Entity\GivDinStemme $entity */
    $entity = $this->getEntity();

    return $entity->save();
  }

}
