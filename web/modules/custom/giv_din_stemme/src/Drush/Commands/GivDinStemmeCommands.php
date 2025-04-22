<?php

namespace Drupal\giv_din_stemme\Drush\Commands;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Site\Settings;
use Drupal\giv_din_stemme\Entity\GivDinStemme;
use Drush\Attributes as CLI;
use Drush\Commands\AutowireTrait;
use Drush\Commands\DrushCommands;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Utils;
use ItkDev\SentenceSimilarityMetrics\CharacterErrorRate;
use ItkDev\SentenceSimilarityMetrics\WordErrorRate;

/**
 * GivDinStemme commandfile.
 */
final class GivDinStemmeCommands extends DrushCommands {

  /**
   * Whisper API endpoint.
   *
   * @var string
   */
  private string $whisperApiEndpoint;

  /**
   * Whisper API key.
   *
   * @var string
   */
  private string $whisperApiKey;

  use AutowireTrait;

  /**
   * Constructs a GivDinStemmeCommands object.
   */
  public function __construct(
    private readonly ClientInterface $client,
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly Connection $connection,
    private readonly FileSystemInterface $fileSystem,
  ) {
    parent::__construct();
    $this->whisperApiEndpoint = Settings::get('itkdev_whisper_api_endpoint', 'https://whisper.itkdev.dk/');
    $this->whisperApiKey = Settings::get('itkdev_whisper_api_key', 'SOME_API_KEY');
  }

  /**
   * Transcribe donations.
   */
  #[CLI\Command(name: 'giv-din-stemme:qualify:transcribe')]
  #[CLI\Option(name: 're-qualify', description: 'Re-qualify donations')]
  #[CLI\Usage(name: 'giv-din-stemme:qualify:transcribe', description: 'Transcribe donations')]
  public function transcribeDonations($options = ['re-qualify' => FALSE]): void {

    $storage = $this->entityTypeManager->getStorage('gds');
    $query = $storage->getQuery();

    $query->exists('file__target_id');

    if (!$options['re-qualify']) {
      $query->notExists('whisper_guess');
    }

    $donationIds = $query->accessCheck()->execute();

    if (empty($donationIds)) {
      $this->io()->success('No donations for transcription detected');
      return;
    }

    $numberOfGds = count($donationIds);

    $this->io()->writeln('Number of donations being handled:' . $numberOfGds);

    $counter = 1;

    foreach ($donationIds as $id) {

      $this->io()->writeln('Handling donation ' . $counter . ' of ' . $numberOfGds);

      /** @var \Drupal\giv_din_stemme\Entity\GivDinStemme $gds */
      $gds = $storage->load($id);

      try {
        $this->transcribeDonation($gds);
      }
      catch (\Exception $exception) {
        $this->logger()->log('error', $exception->getMessage());
        $this->io->writeln('Failed transcribing donation with id: ' . $gds->id() . '. Continuing...');
      }

      $counter++;
    }

    $this->io->success('Finished transcribing donations');
  }

  /**
   * Transcribe donation by id.
   */
  #[CLI\Command(name: 'giv-din-stemme:qualify:transcribe:id')]
  #[CLI\Usage(name: 'giv-din-stemme:qualify:transcribe:id', description: 'Qualify donation by id')]
  public function transcribeDonationById($id = 1): void {

    /** @var \Drupal\giv_din_stemme\Entity\GivDinStemme[] $donations */
    $donations = $this->entityTypeManager->getStorage('gds')->loadByProperties(['id' => $id]);

    if (empty($donations)) {
      $this->io()->error(sprintf('No donation with id %d found.', $id));
      return;
    }

    $this->io()->writeln('Transcribing donation with id: ' . $id);

    foreach ($donations as $donation) {

      // Although this would get caught by the subsequent try-catch,
      // we explicitly handle this to help the user.
      if (!$donation->getFile()) {
        $this->io()->error(sprintf('Donation with id %d does not have an attached donation file.', $id));
        return;
      }

      try {
        $this->transcribeDonation($donation);
        $this->io->success('Finished transcribing');
      }
      catch (\Exception $exception) {
        $this->logger()->log('error', $exception->getMessage());
        $this->io->error('Failed transcribing donation with id: ' . $donation->id());
      }

    }
  }

  /**
   * Transcribe donation.
   */
  private function transcribeDonation(GivDinStemme $gds): void {
    $realpath = $this->fileSystem->realpath($gds->getFile()->getFileUri());

    $headers = [
      'x-api-key' => $this->whisperApiKey,
      'Accept' => 'application/json',
    ];

    $options = [
      'multipart' => [
        [
          'name' => 'audio_file',
          'contents' => Utils::tryFopen($realpath, 'r'),
          'filename'  => $gds->getFile()->getFilename(),
        ],
      ],
    ];

    $request = new Request('POST', $this->whisperApiEndpoint, $headers);

    /** @var \GuzzleHttp\Psr7\Response $response */
    $response = $this->client->sendAsync($request, $options)->wait();

    // Whisper tends to prefix transcribed text with a space.
    $whisperGuess = trim(json_decode($response->getBody()->getContents(), TRUE)['text']);

    $gds->setWhisperGuess($whisperGuess);

    $gds->save();
  }

  /**
   * Calculates word error rates (WER).
   */
  #[CLI\Command(name: 'giv-din-stemme:qualify:wer')]
  #[CLI\Option(name: 're-calculate', description: 're-calculate word error rates')]
  #[CLI\Usage(name: 'giv-din-stemme:qualify:wer', description: 'Calculate word error rates on all donations')]
  public function calculateWordErrorRates($options = ['re-calculate' => FALSE]): void {
    $werService = new WordErrorRate();

    $storage = $this->entityTypeManager->getStorage('gds');
    $query = $storage->getQuery();

    $query->exists('whisper_guess');

    if (!$options['re-calculate']) {
      $query->notExists('whisper_guess_word_error_rate');
    }

    $donationIds = $query->accessCheck()->execute();

    if (empty($donationIds)) {
      $this->io()->success('No donations for qualification detected');
      return;
    }

    $numberOfGds = count($donationIds);

    $this->io()->writeln('Number of donations being handled:' . $numberOfGds);

    $counter = 1;

    foreach ($donationIds as $id) {

      $this->io()->writeln('Handling donation ' . $counter . ' of ' . $numberOfGds);

      /** @var \Drupal\giv_din_stemme\Entity\GivDinStemme $gds */
      $gds = $storage->load($id);

      $metadata = $gds->getMetadata();
      $originalText = $metadata['text'];

      $wer = $werService->wer($originalText, $gds->getWhisperGuess());

      $gds->setWhisperGuessWordErrorRate($wer);

      $gds->save();

      $counter++;
    }

    $this->io->success('Finished qualifying donations');
  }

  /**
   * Calculate character error rates (CER).
   */
  #[CLI\Command(name: 'giv-din-stemme:qualify:cer')]
  #[CLI\Option(name: 're-calculate', description: 're-calculate character error rates')]
  #[CLI\Usage(name: 'giv-din-stemme:qualify:wer', description: 'Calculate character error rates on all donations')]
  public function calculateCharacterErrorRates($options = ['re-calculate' => FALSE]): void {
    $cerService = new CharacterErrorRate();

    $storage = $this->entityTypeManager->getStorage('gds');
    $query = $storage->getQuery();

    $query->exists('whisper_guess');

    if (!$options['re-calculate']) {
      $query->notExists('whisper_guess_character_error_rate');
    }

    $donationIds = $query->accessCheck()->execute();

    if (empty($donationIds)) {
      $this->io()->success('No donations for qualification detected');
      return;
    }

    $numberOfGds = count($donationIds);

    $this->io()->writeln('Number of donations being handled:' . $numberOfGds);

    $counter = 1;

    foreach ($donationIds as $id) {

      $this->io()->writeln('Handling donation ' . $counter . ' of ' . $numberOfGds);

      /** @var \Drupal\giv_din_stemme\Entity\GivDinStemme $gds */
      $gds = $storage->load($id);

      $metadata = $gds->getMetadata();
      $originalText = $metadata['text'];

      $cer = $cerService->cer($originalText, $gds->getWhisperGuess());
      $gds->setWhisperGuessCharacterErrorRate($cer);

      $gds->save();

      $counter++;
    }

    $this->io->success('Finished qualifying donations');
  }

}
