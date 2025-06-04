<?php

namespace Drupal\giv_din_stemme\Plugin\jsonapi\FieldEnhancer;

use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\jsonapi_extras\Plugin\ResourceFieldEnhancerBase;
use Shaper\Util\Context;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Add URL aliases to links.
 *
 * @ResourceFieldEnhancer(
 *   id = "absolute_url_file",
 *   label = @Translation("Absolute URL for file (file uri field only)"),
 *   description = @Translation("Use absolute url for file fields.")
 * )
 */
class AbsoluteUrlFileEnhancer extends ResourceFieldEnhancerBase implements ContainerFactoryPluginInterface {

  /**
   * The logger service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * Constructs AbsoluteUrlFileEnhancer.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    array $plugin_definition,
    LoggerChannelFactoryInterface $logger_factory,
    protected FileUrlGeneratorInterface $fileUrlGenerator
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->logger = $logger_factory->get('jsonapi_extras');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('logger.factory'),
      $container->get('file_url_generator')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function doUndoTransform($data, Context $context) {
    if (isset($data['value'])) {
      try {
        $data['absolute_url'] = $this->fileUrlGenerator->generateAbsoluteString($data['value']);
      }
      catch (\Exception $e) {
        $this->logger->error('Failed to create a URL from uri @uri. Error: @error', [
          '@uri' => $data['uri'],
          '@error' => $e->getMessage(),
        ]);
      }
    }

    return $data;
  }

  /**
   * {@inheritdoc}
   */
  protected function doTransform($value, Context $context) {
  }

  /**
   * {@inheritdoc}
   */
  public function getOutputJsonSchema() {
    return [
      'type' => 'object',
      'properties' => [
        'uri' => ['type' => 'string'],
        'title' => [
          'anyOf' => [
            ['type' => 'null'],
            ['type' => 'string'],
          ],
        ],
        'options' => [
          'anyOf' => [
            ['type' => 'array'],
            ['type' => 'object'],
          ],
        ],
        'url' => ['type' => 'string'],
      ],
    ];
  }

}
