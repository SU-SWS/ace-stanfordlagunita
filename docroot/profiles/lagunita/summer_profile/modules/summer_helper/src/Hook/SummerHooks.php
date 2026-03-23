<?php

declare(strict_types=1);

namespace Drupal\summer_helper\Hook;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileExists;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Logger\LoggerChannelTrait;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\media\MediaInterface;
use FFMpeg\Coordinate\TimeCode;
use FFMpeg\FFMpeg;
use FFMpeg\FFProbe;
use FFMpeg\Filters\Audio\SimpleFilter;
use FFMpeg\Format\Video\X264;

/**
 * Summer hooks.
 */
class SummerHooks {

  use LoggerChannelTrait;
  use MessengerTrait;
  use StringTranslationTrait;

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected FileSystemInterface $fileSystem
  ) {}

  /**
   * Implements hook_viewfield_argument_suggestion_vocabs_alter().)
   */
  #[Hook('viewfield_argument_suggestion_vocabs_alter')]
  public function viewfieldArgVocabs(array &$vocabs, array $view) {
    if ($view['view'] == 'sum_courses') {
      $vocabs[] = 'sum_course_learner';
    }
  }

  /**
   * Implements hook_ENTITY_TYPE_presave().
   */
  #[Hook('media_presave')]
  public function mediaPresave(MediaInterface $media) {
    if (
      $media->bundle() == 'video' &&
      $media->get('sum_video_file')->count()
    ) {
      $fid = $media->get('sum_video_file')
        ->get(0)
        ->get('target_id')
        ->getString();

      $fileStorage = $this->entityTypeManager->getStorage('file');

      /** @var \Drupal\file\FileInterface $videoFile */
      $videoFile = $fileStorage->load($fid);
      // If the video doesn't exist, or it's already 5 seconds long, no need to
      // clip the video.
      if (!$videoFile || $this->getVideoDuration($videoFile->getFileUri()) <= 5) {
        return;
      }

      try {
        $this->clipVideoFile($videoFile->getFileUri());
        $videoFile->save();
      }
      catch (\Exception $e) {
        $this->messenger()
          ->addError($this->t('An error occurred when trying to create a clip from the video.'));
        $this->getLogger('summer_helper')
          ->error($this->t('An error occurred when trying to create a clip from the video: @message', ['@message' => $e->getMessage()]));
      }
    }
  }

  /**
   * Get the duration in seconds of the video.
   *
   * @param string $videoPath
   *   Local path uri.
   *
   * @return int
   *   Duration of the video in seconds.
   */
  protected function getVideoDuration(string $videoPath):int {
    $realPath = $this->fileSystem->realpath($videoPath);
    return (int) FFProbe::create()->format($realPath)->get('duration');
  }

  /**
   * Create a 5-second clip from the video file and return the file entity.
   *
   * @param string $videoPath
   *   Drupal path to the video file.
   */
  protected function clipVideoFile(string $videoPath) {
    try {
      $realPath = $this->fileSystem->realpath($videoPath);
      $tempPath = $this->fileSystem->tempnam('temporary://', 'video-') . '.mp4';
      $realTempPath = $this->fileSystem->realpath($tempPath);

      $ffmpeg = FFMpeg::create();
      $video = $ffmpeg->open($realPath);

      $video->clip(TimeCode::fromSeconds(0), TimeCode::fromSeconds(5))
        ->addFilter(new SimpleFilter(['-an']))
        ->save(new X264(), $realTempPath);

      $this->fileSystem->move($tempPath, $videoPath, FileExists::Replace);
    }
    catch (\Exception $e) {
      throw $e;
    }
    finally {
      if (file_exists($this->fileSystem->realpath($tempPath))) {
        $this->fileSystem->delete($tempPath);
      }
    }
  }

}
