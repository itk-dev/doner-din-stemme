<?php

namespace Drupal\giv_din_stemme\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\file\Entity\File;

/**
 * Defines the Giv Din Stemme data object.
 *
 * @ingroup gds
 *
 * @ContentEntityType(
 *   id = "gds",
 *   label = @Translation("Giv din stemme"),
 *   base_table = "gds",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 *   admin_permission = "administer gds entity",
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\giv_din_stemme\Entity\Controller\GivDinStemmeListBuilder",
 *     "form" = {
 *       "default" = "Drupal\giv_din_stemme\Form\GivDinStemmeForm",
 *       "delete" = "Drupal\giv_din_stemme\Form\GivDinStemmeDeleteForm",
 *     },
 *     "access" = "Drupal\giv_din_stemme\GivDinStemmeAccessControlHandler",
 *   },
 *   list_cache_contexts = { "user" },
 *   links = {
 *     "canonical" = "/giv_din_stemme/{gds}",
 *     "edit-form" = "/giv_din_stemme/{gds}/edit",
 *     "delete-form" = "/giv_din_stemme/{gds}/delete",
 *     "collection" = "/giv_din_stemme/list"
 *   }
 * )
 */
class GivDinStemme extends ContentEntityBase implements ContentEntityInterface {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {

    // Standard field, used as unique if primary index.
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the GivDinStemme entity.'))
      ->setReadOnly(TRUE);

    // Standard field, unique outside of the scope of the current project.
    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the GivDinStemme entity.'))
      ->setReadOnly(TRUE);

    // Metadata should contain
    // text part being read
    // user metadata
    // id for text.
    $fields['metadata'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Metadata'))
      ->setDescription(t('The metadata of the GivDinStemme entity.'));

    $fields['file'] = BaseFieldDefinition::create('file')
      ->setLabel(t('File'))
      ->setDescription(t('The recording file of the GivDinStemme entity.'))
      ->setSettings([
        'uri_scheme' => 'private',
        'file_directory' => 'audio',
      ]);

    $fields['user_hash'] = BaseFieldDefinition::create('string')
      ->setLabel(t('User hash'))
      ->setDescription(t('A hash of user'));

    $fields['collection_delta'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Delta'))
      ->setDescription(t('Delta'));

    // Standard field, unique outside of the scope of the current project.
    $fields['collection_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Collection UUID'))
      ->setDescription(t('The UUID of a collection of GivDinStemme entities.'));

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    $fields['whisper_guess'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Whisper Guess'))
      ->setDescription(t('What whisper thinks is being said'));

    $fields['whisper_guess_word_error_rate'] = BaseFieldDefinition::create('float')
      ->setLabel(t('Word error rate'))
      ->setDescription(t('Word error rate between original text and whisper guess'));

    $fields['whisper_guess_character_error_rate'] = BaseFieldDefinition::create('float')
      ->setLabel(t('Character error rate'))
      ->setDescription(t('Character error rate between original text and whisper guess'));

    return $fields;
  }

  /**
   * Get file.
   */
  public function getFile(): ?File {
    $files = $this->get('file')->referencedEntities();

    return reset($files) ?: NULL;
  }

  /**
   * Get metadata.
   */
  public function getMetadata(): array {
    return json_decode($this->get('metadata')->getString(), TRUE) ?: [];
  }

  /**
   * Get text.
   */
  public function getText(): ?string {
    return $this->getMetadata()['text'] ?? NULL;
  }

  /**
   * Get created.
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * Set metadata.
   *
   * @param array $metadata
   *   The metadata as an array.
   */
  public function setMetadata(array $metadata): void {
    $this->set('metadata', json_encode($metadata));
  }

  /**
   * Get whisper guess.
   */
  public function getWhisperGuess() {
    return $this->get('whisper_guess')->value;
  }

  /**
   * Set whisper guess.
   */
  public function setWhisperGuess(?string $whisperGuess): void {
    $this->set('whisper_guess', $whisperGuess);
  }

  /**
   * Get whisper word error rate.
   */
  public function getWhisperGuessWordErrorRate() {
    return $this->get('whisper_guess_word_error_rate')->value;
  }

  /**
   * Set whisper word error rate.
   */
  public function setWhisperGuessWordErrorRate(?float $rate): void {
    $this->set('whisper_guess_word_error_rate', $rate);
  }

  /**
   * Get whisper character error rate.
   */
  public function getWhisperGuessCharacterErrorRate() {
    return $this->get('whisper_guess_character_error_rate')->value;
  }

  /**
   * Set whisper character error rate.
   */
  public function setWhisperGuessCharacterErrorRate(?float $rate): void {
    $this->set('whisper_guess_character_error_rate', $rate);
  }

}
