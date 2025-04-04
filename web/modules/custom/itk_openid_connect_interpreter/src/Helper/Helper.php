<?php

namespace Drupal\itk_openid_connect_interpreter\Helper;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * A helper.
 */
class Helper {
  use StringTranslationTrait;

  /**
   * Implements hook_openid_connect_userinfo_alter().
   *
   * @todo Make it generic and reusable across multiple sites?
   */
  public function openidConnectUserinfoAlter(array &$userinfo, array $context) {
    $encryptedUuid = Crypt::hashBase64($userinfo['signinname'] ?? '');
    $userinfo['email'] = $encryptedUuid . '@example.com';
    $userinfo['name'] = $encryptedUuid;
  }

  /**
   * Change display of username.
   *
   * @param string $name
   *   The name.
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The user.
   */
  public function alterUserName(string &$name, AccountInterface $user): void {
    // Alter name if only authenticated and not super admin.
    if (count($user->getRoles()) === 1 && $user->id() > 1) {
      $name = $this->t('Profile');
    }
  }

}
