<?php

namespace Drupal\giv_din_stemme\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\State\State;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Api docs controller.
 */
class ApiDocsController extends ControllerBase {

  /**
   * Givdinstemme constructor.
   *
   * @param \Drupal\Core\State\State $state
   *   The object state.
   */
  public function __construct(
    protected State $state,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): ApiDocsController {
    return new static(
      $container->get('state'),
    );
  }

  /**
   * Landing page.
   */
  public function docs(Request $request): array {
    return [
      '#theme' => 'api_docs',
      '#values' => ['text' => $this->state->get('giv_din_stemme.api_docs_text')],
    ];
  }

  /**
   * Consent page.
   */
  public function swagger(Request $request): array {
    return [
      '#theme' => 'api_swagger',
      '#values' => ['data-swagger-def' => Url::fromUserInput('/swagger/swagger.yaml', ['absolute' => TRUE])->toString()],
      '#attached' => [
        'library' => [
          'giv_din_stemme/swagger_ui_integration',
        ],
      ],
    ];
  }

}
