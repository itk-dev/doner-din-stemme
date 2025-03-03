<?php

namespace Drupal\giv_din_stemme\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\File\FileExists;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Site\Settings;
use Drupal\Core\State\State;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\file\Entity\File;
use Drupal\giv_din_stemme\Entity\GivDinStemme;
use Drupal\giv_din_stemme\Exception\InvalidRequestException;
use Drupal\giv_din_stemme\Helper\Helper;
use Drupal\openid_connect\OpenIDConnectSessionInterface;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

/**
 * Givdinstemme controller.
 */
class GivDinStemmeController extends ControllerBase {

  /**
   * Givdinstemme constructor.
   *
   * @param \Drupal\giv_din_stemme\Helper\Helper $helper
   *   The helper.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   * @param \Drupal\Core\File\FileSystemInterface $fileSystem
   *   The file system.
   * @param \Drupal\Core\Site\Settings $settings
   *   Settings.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager.
   * @param \Drupal\openid_connect\OpenIDConnectSessionInterface $session
   *   The OpenID Connect session service.
   * @param \Drupal\Core\Session\AccountInterface $currentUser
   *   The account interface.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack.
   * @param \Drupal\Core\State\State $state
   *   The object state.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   The logger.
   */
  public function __construct(
    protected Helper $helper,
    protected Connection $connection,
    protected FileSystemInterface $fileSystem,
    protected Settings $settings,
    protected $entityTypeManager,
    protected OpenIDConnectSessionInterface $session,
    protected $currentUser,
    protected RequestStack $requestStack,
    protected State $state,
    protected LoggerChannelInterface $logger,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): GivDinStemmeController {
    return new static(
      $container->get(Helper::class),
      $container->get('database'),
      $container->get('file_system'),
      $container->get('settings'),
      $container->get('entity_type.manager'),
      $container->get('openid_connect.session'),
      $container->get('current_user'),
      $container->get('request_stack'),
      $container->get('state'),
      $container->get('logger.channel.giv_din_stemme'),
    );
  }

  /**
   * Landing page.
   */
  public function landing(Request $request): array {
    return [
      '#theme' => 'landing_page',
      '#values' => $this->helper->getFrontpageValues(),
      '#front_page_text' => $this->state->get('giv_din_stemme.front_page_text'),
    ];
  }

  /**
   * Consent page.
   */
  public function consent(Request $request): array {
    return [
      '#theme' => 'consent_page',
      '#consent_text' => $this->state->get('giv_din_stemme.terms_text'),
    ];
  }

  /**
   * Login through OIDC if consent is given.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return \Drupal\Core\Routing\TrustedRedirectResponse|\Symfony\Component\HttpFoundation\RedirectResponse
   *   The response.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function login(Request $request): TrustedRedirectResponse|RedirectResponse {
    // Get login URL.
    $client_name = 'generic';
    $this->session->saveOp('login');
    $client = $this->entityTypeManager->getStorage('openid_connect_client')->loadByProperties(['id' => $client_name])[$client_name];
    $plugin = $client->getPlugin();
    $scopes = 'openid profile';
    $response = $plugin->authorize($scopes);
    $url = $response->getTargetUrl();

    // Go to login or front page if no consent.
    if ('consent_given' === $request->get('consent') && isset($url)) {
      return new TrustedRedirectResponse($url);
    }
    else {
      return $this->redirect('giv_din_stemme.landing');
    }
  }

  /**
   * Profile page.
   */
  public function givDinStemmeProfile(Request $request): array {
    return [
      '#theme' => 'giv_din_stemme_profile_form',
    ];
  }

  /**
   * Permissions page.
   */
  public function permissions(Request $request): array {
    return [
      '#theme' => 'permissions_page',
      '#permissions_help_page_node' => $this->state->get('giv_din_stemme.permissions_help_page'),
    ];
  }

  /**
   * Test page.
   */
  public function test(Request $request): array {
    $userAgent = $_SERVER['HTTP_USER_AGENT'];
    $settings = Settings::get('giv_din_stemme');
    // https://www.whatismybrowser.com/guides/the-latest-user-agent/safari
    $pattern = $settings['require_additional_microphone_permissions_pattern']
      // Match iPhone and Safari in any order and ignoring case
      // (cf. https://stackoverflow.com/a/4389683/2502647).
      ?? '/^(?=.*\biPhone\b)(?=.*\bSafari\b).*$/i';
    $helpPageId = $this->state->get('giv_din_stemme.additional_microphone_permissions_help_page') ?? NULL;

    return [
      '#theme' => 'test_page',
      '#require_additional_microphone_permissions' => (bool) preg_match($pattern, $userAgent),
      '#additional_microphone_permissions_help_url' => $helpPageId
        ? Url::fromRoute('entity.node.canonical', ['node' => $helpPageId])->toString(TRUE)->getGeneratedUrl()
        : NULL,
    ];
  }

  /**
   * Donate page.
   */
  public function donate(Request $request): array {
    return [
      '#theme' => 'donate_page',
      '#donate_page_text' => $this->state->get('giv_din_stemme.donate_page_text'),
    ];
  }

  /**
   * Thank you page.
   */
  public function thankYou(Request $request): array {
    return [
      '#theme' => 'thank_you_page',
      '#thank_you_text' => $this->state->get('giv_din_stemme.thank_you_text'),
    ];
  }

  /**
   * Start donating page.
   *
   * Creates a collection of GivDinStemme entities
   * and redirects to the first.
   */
  public function startDonating(Request $request): RedirectResponse {
    $text = $this->helper->getRandomPublishedText();
    $collectionId = $this->helper->generateUuid();
    $delta = 0;

    $parts = $text->get('field_text_parts');
    $numberOfParts = count($parts);

    if ($numberOfParts < 1) {
      throw new \Exception('A text should contain at least one part');
    }

    // Create a GivDinStemme entity per text part.
    foreach ($parts as $part) {
      $entity = GivDinStemme::create();

      $user = User::load($this->currentUser->id());

      $hashedAccountName = sha1($user->getAccountName());

      $entity->set('collection_id', $collectionId);
      $entity->set('collection_delta', $delta++);
      $entity->set('user_hash', $hashedAccountName);

      // Collect metadata.
      $partTextToRead = $part->getValue()['value'];
      $textId = $text->id();
      $accent = $user->get('field_accent')->value;
      $birthYear = $user->get('field_birth_year')->value;
      $dialect = $user->get('field_dialects')->value;
      $gender = $user->get('field_gender')->value;

      $entity->set('metadata', json_encode([
        'text' => $partTextToRead,
        'text_id' => $textId,
        'user' => $hashedAccountName,
        'number_of_parts' => $numberOfParts,
        'birth_year' => $birthYear,
        'dialect' => $dialect,
        'gender' => $gender,
        'accent' => $accent,
      ]));

      $entity->save();
    }

    return $this->redirect('giv_din_stemme.read', [
      'collection_id' => $collectionId,
      'delta' => 0,
    ]);

  }

  /**
   * Read page.
   *
   * Handles 'GET' and 'POST' method.
   */
  public function read(Request $request, string $collection_id, string $delta): array|Response {
    if ('POST' === $request->getMethod()) {
      return $this->handleReadPost($request, $collection_id, $delta);
    }
    else {
      return $this->handleReadGet($request, $collection_id, $delta);
    }
  }

  /**
   * Handles read 'GET' method.
   */
  private function handleReadGet(Request $request, string $collection_id, int $delta, ?TranslatableMarkup $message = NULL, string $messageType = 'succes'): array {
    $givDinStemme = $this->helper->getGivDinStemmeByCollectionIdAndDelta($collection_id, $delta);

    $metadata = json_decode($givDinStemme->get('metadata')->getValue()[0]['value'], TRUE);
    $textToRead = $metadata['text'];
    $count = $metadata['number_of_parts'];

    $messages = [];
    if (NULL !== $message) {
      $messages[] = [
        'message' => $message,
        'type' => $messageType,
      ];
    }

    return [
      '#theme' => 'read_page',
      '#textToRead' => $textToRead,
      '#currentText' => $delta + 1,
      '#totalTexts' => $count,
      '#messages' => $messages,
      '#attached' => [
        'library' => ['giv_din_stemme/giv_din_stemme'],
      ],
    ];
  }

  /**
   * Handles read 'POST' method.
   */
  private function handleReadPost(Request $request, string $collection_id, int $delta): RedirectResponse|array {
    try {
      $directory = 'private://audio/';
      $this->fileSystem->prepareDirectory($directory, FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS);

      // Load GivDinStemme.
      $givDinStemme = $this->helper->getGivDinStemmeByCollectionIdAndDelta($collection_id, $delta);

      /** @var \Symfony\Component\HttpFoundation\File\UploadedFile $file */
      $file = $request->files->get('file');

      if (!$file) {
        throw new InvalidRequestException('No file found');
      }

      // Copy audio file to private files.
      $destination = $directory . '/' . $file->getClientOriginalName();
      $newFilename = $this->fileSystem->copy($file->getPathname(), $destination, FileExists::Rename);

      // Create new file.
      $file = File::create([
        'filename' => basename($newFilename),
        'uri' => $directory . basename($newFilename),
        // Make file permanent.
        'status' => 1,
      ]);
      $file->save();

      // Attach file.
      $givDinStemme->set('file', $file);

      // Add file metadata.
      $metadata = json_decode($givDinStemme->get('metadata')->getValue()[0]['value'], TRUE);
      $metadata['durationInSeconds'] = $request->request->get('duration');
      $metadata['audioMimeType'] = $file->getMimeType();
      $givDinStemme->set('metadata', json_encode($metadata));

      // Update donation state counter and duration.
      $this->helper->updateTotalDonationDuration((int) $request->request->get('duration'));
      $this->helper->updateTotalNumberOfDonations();

      $givDinStemme->save();

      $finish = 'finish' === $request->get('action');

      // Redirect based on whether another text part exists.
      $nextDelta = $delta + 1;
      $countOfParts = $this->helper->getCountOfGivDinStemmeByCollectionId($collection_id);

      if (!$finish && $nextDelta < $countOfParts) {
        return $this->redirect('giv_din_stemme.read', [
          'collection_id' => $collection_id,
          'delta' => $nextDelta,
        ]);
      }
      else {
        return $this->redirect('giv_din_stemme.thank_you');
      }
    }
    catch (\Exception $exception) {
      $this->logger->error('handleReadPost: @message; file: @file; @file_error', [
        '@message' => $exception->getMessage(),
        '@exception' => $exception,
        '@file' => var_export($file ?? NULL, TRUE),
        '@file_error' => isset($file) && UPLOAD_ERR_OK !== $file?->getError() ? $file->getErrorMessage() : NULL,
      ]);

      return $this->handleReadGet($request, $collection_id, $delta,
        message: $this->t('Something went wrong submitting your recording. Please try again.'),
        messageType: 'danger'
      );
    }
  }

}
