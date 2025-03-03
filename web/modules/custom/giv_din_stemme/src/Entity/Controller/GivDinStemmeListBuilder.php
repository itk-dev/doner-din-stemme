<?php

namespace Drupal\giv_din_stemme\Entity\Controller;

use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides a list controller for giv_din_stemme entity.
 *
 * @ingroup giv_din_stemme
 */
class GivDinStemmeListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity_type.manager')->getStorage($entity_type->id()),
      $container->get('date.formatter'),
      $container->get('file_url_generator'),
      $container->get('request_stack'),
    );
  }

  /**
   * Constructs a new GdsListBuilder object.
   */
  public function __construct(
    EntityTypeInterface $entity_type,
    EntityStorageInterface $storage,
    private readonly DateFormatterInterface $dateFormatter,
    private readonly FileUrlGeneratorInterface $fileUrlGenerator,
    private readonly RequestStack $requestStack,
  ) {
    parent::__construct($entity_type, $storage);
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    return [
      'created' => [
        'data' => $this->t('Created'),
        'field' => 'created',
        'sort' => 'desc',
      ],
      'file' => $this->t('File'),
      'whisper_guess' => $this->t('Whisper guess'),
      'whisper_guess_word_error_rate' => [
        'data' => $this->t('Word error rate'),
        'field' => 'whisper_guess_word_error_rate',
      ],
      'whisper_guess_character_error_rate' => [
        'data' => $this->t('Character error rate'),
        'field' => 'whisper_guess_character_error_rate',
      ],
    ] + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\giv_din_stemme\Entity\GivDinStemme $entity */
    $row['created'] = $this->dateFormatter->format($entity->getCreatedTime(), 'short');

    $file = $entity->getFile();
    $row['file'] = $file ? [
      '#theme' => 'giv_din_stemme_audio',
      '#src' => $this->fileUrlGenerator->generate($file->getFileUri())->toString(TRUE)->getGeneratedUrl(),
      '#text' => $entity->getText(),
    ] : '';
    if (is_array($row['file'])) {
      $row['file'] = \Drupal::service('renderer')->render($row['file']);
    }

    $row['whisper_guess'] = $entity->getWhisperGuess() ?? '-';
    $row['word_error_rate'] = !is_null($entity->getWhisperGuessWordErrorRate()) ? round($entity->getWhisperGuessWordErrorRate(), 2) : '-';
    $row['character_error_rate'] = !is_null($entity->getWhisperGuessCharacterErrorRate()) ? round($entity->getWhisperGuessCharacterErrorRate(), 2) : '-';

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  #[\Override]
  protected function getEntityListQuery(): QueryInterface {
    // Calling parent::getEntityListQuery() here would add sorting on an entity
    // key, but we don't want that, so we built the query ourselves.
    $query = $this->getStorage()->getQuery()
      ->accessCheck(TRUE);

    // Only add the pager if a limit is specified.
    if ($this->limit) {
      $query->pager($this->limit);
    }

    // List only entities with a file.
    $query->condition('file', NULL, 'IS NOT NULL');

    if ($request = $this->requestStack->getCurrentRequest()) {

      if ($order = $request->get('order')) {
        $headers = [];
        $sort = $request->get('sort');
        foreach ($this->buildHeader() as $name => $field) {
          if (is_array($field) && (string) $field['data'] === $order) {
            $headers[$name] = $field + [
              'specifier' => $name,
              'sort' => $sort ?? $field['sort'] ?? 'asc',
            ];
          }
        }
        $query->tableSort($headers);
      }
    }

    return $query;
  }

}
