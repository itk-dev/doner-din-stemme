<?php

namespace Drupal\giv_din_stemme;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultAllowed;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the gds entity type.
 */
class GdsAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResultAllowed|AccessResultInterface {
    // Check the operation being performed.
    switch ($operation) {
      case 'view':
        return AccessResult::allowedIf($account->hasPermission('access giv din stemme view'));

      default:
        // For other operations, fall back to the default checkAccess method.
        return parent::checkAccess($entity, $operation, $account);
    }
  }

}
