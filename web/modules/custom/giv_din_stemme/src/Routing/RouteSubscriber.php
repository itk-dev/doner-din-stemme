<?php

namespace Drupal\giv_din_Stemme\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    // Allow fetching files through key authentication.
    // see giv_din_stemme_file_download() for permission check.
    if ($route = $collection->get('system.files')) {
      $route->setOption('_auth', ['key_auth']);
    }

    if ($route = $collection->get('entity.gds.canonical')) {
      $route->setOption('_auth', ['key_auth']);
    }

    // Setup access to api routes through key auth.
    foreach ($collection as $route) {
      $defaults = $route->getDefaults();
      if (!empty($defaults[ '_is_jsonapi' ])) {
        $route->setOption('_auth', ['key_auth']);
      }
    }
  }

}
