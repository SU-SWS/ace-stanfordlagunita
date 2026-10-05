<?php

namespace Drupal\sul_helper\Plugin\Filter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\filter\Attribute\Filter;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Drupal\filter\Plugin\FilterInterface;
use Drupal\media\MediaInterface;
use Drupal\media\Plugin\media\Source\Image as ImageSource;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Adds the media image credit onto the caption of embedded images.
 *
 * The credit lives on the image media entity, but it should be displayed to the
 * site visitor alongside the caption the editor typed when embedding the image.
 * Rather than building new markup, the credit is appended to the embed's
 * `data-caption` attribute so that core's caption filter renders it in the
 * `<figcaption>` it already builds.
 *
 * @see \Drupal\filter\Plugin\Filter\FilterCaption
 * @see \Drupal\media\Plugin\Filter\MediaEmbed
 */
#[Filter(
  id: "sul_image_credit",
  title: new TranslatableMarkup("Image credit"),
  description: new TranslatableMarkup("Appends the <em>Image Credit</em> of an embedded image onto its caption. Must run before <em>Caption images</em>."),
  type: FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
  weight: -48,
)]
class SulImageCredit extends FilterBase implements ContainerFactoryPluginInterface {

  /**
   * Field on the image media bundle that holds the credit.
   */
  const CREDIT_FIELD = 'sul_image_credit';

  /**
   * String displayed between the caption and the credit.
   */
  const SEPARATOR = ' | ';

  /**
   * Alt text token the legacy embed dialog used to flag decorative images.
   *
   * @see \Drupal\stanford_media\Plugin\MediaEmbedDialog\Image::DECORATIVE
   */
  const DECORATIVE = '[decorative]';

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('entity.repository')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected EntityRepositoryInterface $entityRepository,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    $result = new FilterProcessResult($text);
    if (stripos($text, '<drupal-media') === FALSE) {
      return $result;
    }

    $dom = Html::load($text);
    $xpath = new \DOMXPath($dom);
    $embeds = $xpath->query('//drupal-media[@data-entity-type="media"][@data-entity-uuid]');
    if (!$embeds->length) {
      return $result;
    }

    $images = $this->loadImageMedia($embeds, $langcode);
    $changed = FALSE;

    /** @var \DOMElement $node */
    foreach ($embeds as $node) {
      $media = $images[$node->getAttribute('data-entity-uuid')] ?? NULL;
      if (!$media) {
        continue;
      }

      // The credit is read off the media entity, so the filtered text has to be
      // invalidated whenever that entity changes.
      $result->addCacheableDependency($media);
      $access = $media->access('view', NULL, TRUE);
      $result->addCacheableDependency($access);
      if (!$access->isAllowed()) {
        continue;
      }

      $credit = trim((string) $media->get(self::CREDIT_FIELD)->value);
      if ($credit === '' || $this->isDecorative($node, $media)) {
        continue;
      }

      // The caption filter treats `data-caption` as html, so the credit is
      // escaped to keep it displaying as the plain text it was entered as.
      $credit = Html::escape($credit);
      $caption = trim($node->getAttribute('data-caption'));
      $node->setAttribute('data-caption', $caption === '' ? $credit : $caption . self::SEPARATOR . $credit);
      $changed = TRUE;
    }

    if ($changed) {
      $result->setProcessedText(Html::serialize($dom));
    }
    return $result;
  }

  /**
   * Load the image media entities for the embeds that can have a credit.
   *
   * @param \DOMNodeList $embeds
   *   The drupal-media elements found in the text.
   * @param string|null $langcode
   *   Language of the text being filtered.
   *
   * @return \Drupal\media\MediaInterface[]
   *   Image media entities that have a credit field, keyed by uuid.
   */
  protected function loadImageMedia(\DOMNodeList $embeds, ?string $langcode): array {
    $uuids = [];
    /** @var \DOMElement $node */
    foreach ($embeds as $node) {
      $uuids[] = $node->getAttribute('data-entity-uuid');
    }

    $media_entities = $this->entityTypeManager->getStorage('media')
      ->loadByProperties(['uuid' => array_unique($uuids)]);

    $images = [];
    foreach ($media_entities as $media) {
      if (
        !$media instanceof MediaInterface ||
        !$media->getSource() instanceof ImageSource ||
        !$media->hasField(self::CREDIT_FIELD)
      ) {
        continue;
      }
      $translation = $this->entityRepository->getTranslationFromContext($media, $langcode);
      $images[$media->uuid()] = $translation instanceof MediaInterface ? $translation : $media;
    }
    return $images;
  }

  /**
   * Check if the embedded image is decorative.
   *
   * @param \DOMElement $node
   *   The drupal-media element.
   * @param \Drupal\media\MediaInterface $media
   *   The image media entity.
   *
   * @return bool
   *   True if no credit should be displayed.
   *
   * @see \Drupal\media\Plugin\Filter\MediaEmbed::applyPerEmbedMediaOverrides()
   * @see \Drupal\stanford_media\Plugin\MediaEmbedDialog\Image::embedAlter()
   */
  protected function isDecorative(\DOMElement $node, MediaInterface $media): bool {
    if ($node->hasAttribute('alt')) {
      $alt = $node->getAttribute('alt');
      if ($alt === '""' || $alt === self::DECORATIVE) {
        return TRUE;
      }
      return trim($alt) === '';
    }

    $source_field = $media->getSource()
      ->getSourceFieldDefinition($media->bundle->entity)
      ?->getName();
    if (!$source_field || !$media->hasField($source_field)) {
      return TRUE;
    }
    return trim((string) $media->get($source_field)->alt) === '';
  }

}
