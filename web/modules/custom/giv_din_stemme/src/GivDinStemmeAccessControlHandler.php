<?php

namespace Drupal\giv_din_stemme;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access controller for the gds entity.
 */
class GivDinStemmeAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   *
   * Link the activities to the permissions. checkAccess() is called with the
   * $operation as defined in the routing.yml file.
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    // Check the admin_permission as defined in your @ContentEntityType
    // annotation.
    $admin_permission = $this->entityType->getAdminPermission();
    if ($account->hasPermission($admin_permission)) {
      return AccessResult::allowed();
    }
    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view giv din stemme entity');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit giv din stemme entity');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete giv din stemme entity');
    }
    return AccessResult::neutral();
  }

}
