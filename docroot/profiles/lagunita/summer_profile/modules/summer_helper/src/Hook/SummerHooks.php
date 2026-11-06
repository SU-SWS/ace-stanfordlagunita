<?php

declare(strict_types=1);

namespace Drupal\summer_helper\Hook;

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\File\FileExists;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Logger\LoggerChannelTrait;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\media\MediaInterface;
use Drupal\node\NodeInterface;
use Drupal\summer_helper\Plugin\paragraphs\Behavior\SummerCardBehaviors;
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
    protected FileSystemInterface $fileSystem,
    protected ModuleExtensionList $moduleExtensionList,
  ) {}

  /**
   * Implements hook_paragraphs_behavior_info_alter().
   */
  #[Hook('paragraphs_behavior_info_alter')]
  public function paragraphsBehaviorInfoAlter(array &$paragraphs_behavior): void {
    $paragraphs_behavior['su_card_styles']['class'] = SummerCardBehaviors::class;
  }

  /**
   * Implements hook_page_attachments().
   */
  #[Hook('page_attachments')]
  public function pageAttachments(array &$attachments): void {
    $attachments['#attached']['library'][] = 'summer_helper/admin';
  }

  /**
   * Implements hook_library_info_alter().
   */
  #[Hook('library_info_alter')]
  public function libraryInfoAlter(array &$libraries, string $extension): void {
    if ($extension === 'ckeditor5') {
      $module_path = $this->moduleExtensionList->getPath('summer_helper');
      $libraries['internal.drupal.ckeditor5.stylesheets'] = [
        'css' => [
          'theme' => [
            "/$module_path/styles/dist/css/ckeditor.css" => [],
          ],
        ],
      ];
    }
  }

  /**
   * Implements hook_form_BASE_FORM_ID_alter().
   */
  #[Hook('form_summer_entity_form_alter')]
  public function summerEntityFormAlter(array &$form, FormStateInterface $form_state, string $form_id): void {
    $status_element = &$form['status']['widget']['value'];
    $status_element['#states'] = [
      'disabled' => [':input[name="publish_on[0][value][time]"]' => ['filled' => TRUE]],
    ];
  }

  /**
   * Implements hook_preprocess_HOOK().
   */
  #[Hook('preprocess_paragraph')]
  public function preprocessParagraph(array &$variables): void {
    /** @var \Drupal\paragraphs\ParagraphInterface $paragraph */
    $paragraph = $variables['paragraph'];
    $variables['attributes']['class'][] = Html::cleanCssIdentifier('paragraph-' . $paragraph->bundle());
  }

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
  protected function getVideoDuration(string $videoPath): int {
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

  /**
   * Clear the algolia index item immediately.
   *
   * @param \Drupal\node\NodeInterface $node
   *   Node to delete.
   */
  protected static function clearAlgolia(NodeInterface $node) {
    if (\Drupal::hasService('search_api_algolia.helper')) {
      \Drupal::service('search_api_algolia.helper')->entityDelete($node);
    }
  }

}
