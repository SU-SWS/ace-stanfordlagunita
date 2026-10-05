<?php

namespace Drupal\Tests\sul_helper\Kernel\Plugin\Filter;

use Drupal\Component\Serialization\Yaml;
use Drupal\Core\Render\RenderContext;
use Drupal\KernelTests\KernelTestBase;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;
use Drupal\media\Entity\MediaType;
use Drupal\media\MediaInterface;
use Drupal\sul_helper\Plugin\Filter\SulImageCredit;
use Drupal\user\RoleInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the image credit filter.
 *
 * @coversDefaultClass \Drupal\sul_helper\Plugin\Filter\SulImageCredit
 */
#[RunTestsInSeparateProcesses]
class SulImageCreditTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'field',
    'file',
    'image',
    'media',
    'filter',
    'sul_helper',
    'jsonapi',
    'serialization',
    'next',
  ];

  /**
   * The filter under test.
   *
   * @var \Drupal\sul_helper\Plugin\Filter\SulImageCredit
   */
  protected SulImageCredit $filter;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installEntitySchema('file');
    $this->installEntitySchema('media');
    $this->installSchema('file', ['file_usage']);
    $this->installConfig(['field', 'system', 'user', 'image', 'media']);

    // The site lets anonymous visitors view media, and the filter only adds a
    // credit for media the viewer is allowed to see.
    user_role_grant_permissions(RoleInterface::ANONYMOUS_ID, ['view media']);

    $this->createImageMediaType();

    /** @var \Drupal\filter\FilterPluginManager $filter_manager */
    $filter_manager = \Drupal::service('plugin.manager.filter');
    $this->filter = $filter_manager->createInstance('sul_image_credit');
  }

  /**
   * Text without any embedded media is left alone.
   */
  public function testTextWithoutMedia(): void {
    $text = '<p>Nothing to see here.</p>';
    $this->assertEquals($text, (string) $this->filter->process($text, 'en'));
  }

  /**
   * An unknown media uuid doesn't break the filter.
   */
  public function testUnknownMedia(): void {
    $text = $this->embed('11111111-2222-3333-4444-555555555555', ['data-caption' => 'A caption']);
    $this->assertStringContainsString('data-caption="A caption"', (string) $this->filter->process($text, 'en'));
  }

  /**
   * The credit is appended to an existing caption with a pipe.
   */
  public function testCreditAppendedToCaption(): void {
    $media = $this->createImageMedia('Some alt text', 'Jane Photographer');
    $text = $this->embed($media->uuid(), ['data-caption' => 'A caption']);

    $this->assertStringContainsString('data-caption="A caption | Jane Photographer"', (string) $this->filter->process($text, 'en'));
  }

  /**
   * Without a caption, the credit is displayed on its own.
   */
  public function testCreditWithoutCaption(): void {
    $media = $this->createImageMedia('Some alt text', 'Jane Photographer');
    $text = $this->embed($media->uuid());

    $this->assertStringContainsString('data-caption="Jane Photographer"', (string) $this->filter->process($text, 'en'));
  }

  /**
   * An empty credit field leaves the caption alone.
   */
  public function testNoCredit(): void {
    $media = $this->createImageMedia('Some alt text', '');
    $text = $this->embed($media->uuid(), ['data-caption' => 'A caption']);
    $processed = (string) $this->filter->process($text, 'en');

    $this->assertStringContainsString('data-caption="A caption"', $processed);
    $this->assertStringNotContainsString('|', $processed);
  }

  /**
   * Media that isn't an image is ignored.
   */
  public function testNonImageMedia(): void {
    $media = $this->createFileMedia();
    $text = $this->embed($media->uuid(), ['data-caption' => 'A caption']);
    $processed = (string) $this->filter->process($text, 'en');

    $this->assertStringContainsString('data-caption="A caption"', $processed);
    $this->assertStringNotContainsString('|', $processed);
  }

  /**
   * The credit of media the viewer can't see is not disclosed.
   */
  public function testUnpublishedMedia(): void {
    $media = $this->createImageMedia('Some alt text', 'Secret Photographer');
    $media->setUnpublished()->save();
    $text = $this->embed($media->uuid(), ['data-caption' => 'A caption']);
    $processed = (string) $this->filter->process($text, 'en');

    $this->assertStringContainsString('data-caption="A caption"', $processed);
    $this->assertStringNotContainsString('Secret Photographer', $processed);
  }

  /**
   * Decorative images never get a credit.
   *
   * @param string $media_alt
   *   Alt text stored on the media entity.
   * @param string|null $embed_alt
   *   Alt text override on the embed, or NULL for no override.
   */
  #[DataProvider('decorativeDataProvider')]
  public function testDecorativeImage(string $media_alt, ?string $embed_alt): void {
    $media = $this->createImageMedia($media_alt, 'Jane Photographer');
    $attributes = ['data-caption' => 'A caption'];
    if ($embed_alt !== NULL) {
      $attributes['alt'] = $embed_alt;
    }
    $processed = (string) $this->filter->process($this->embed($media->uuid(), $attributes), 'en');

    $this->assertStringContainsString('data-caption="A caption"', $processed);
    $this->assertStringNotContainsString('Jane Photographer', $processed);
  }

  /**
   * Data for the decorative image test.
   */
  public static function decorativeDataProvider(): array {
    return [
      'no alt text on the media' => ['', NULL],
      'alt text emptied by the embed' => ['Some alt text', '""'],
      'empty alt attribute on the embed' => ['Some alt text', ''],
      'whitespace only alt on the embed' => ['Some alt text', '   '],
      'legacy decorative token' => ['Some alt text', '[decorative]'],
    ];
  }

  /**
   * An embed alt override makes an otherwise decorative image creditable.
   */
  public function testEmbedAltOverridesDecorativeMedia(): void {
    $media = $this->createImageMedia('', 'Jane Photographer');
    $text = $this->embed($media->uuid(), ['alt' => 'Described by the embed']);

    $this->assertStringContainsString('data-caption="Jane Photographer"', (string) $this->filter->process($text, 'en'));
  }

  /**
   * Credits are escaped, because the caption filter parses them as html.
   *
   * @param string $credit
   *   Credit entered on the media entity.
   * @param string $expected
   *   Text the visitor should see after the caption filter has run.
   */
  #[DataProvider('escapingDataProvider')]
  public function testCreditIsEscaped(string $credit, string $expected): void {
    $media = $this->createImageMedia('Some alt text', $credit);
    $text = $this->embed($media->uuid(), ['data-caption' => 'A caption']);

    $this->assertStringContainsString(
      '<figcaption>A caption | ' . $expected . '</figcaption>',
      $this->renderCaptions((string) $this->filter->process($text, 'en'))
    );
  }

  /**
   * Data for the credit escaping test.
   */
  public static function escapingDataProvider(): array {
    return [
      'angle brackets are kept as text' => ['Smith <smith@example.edu>', 'Smith &lt;smith@example.edu&gt;'],
      'ampersands are kept as text' => ['Smith &amp; Co', 'Smith &amp;amp; Co'],
      'markup is not rendered' => ['<em>Jane</em>', '&lt;em&gt;Jane&lt;/em&gt;'],
      'links are not rendered' => ['<a href="http://evil.example">x</a>', '&lt;a href="http://evil.example"&gt;x&lt;/a&gt;'],
    ];
  }

  /**
   * Every embed in the text is handled and the rest of it is untouched.
   */
  public function testMultipleEmbeds(): void {
    $first = $this->createImageMedia('Some alt text', 'First Photographer');
    $second = $this->createImageMedia('Some alt text', 'Second Photographer');
    $text = '<h2>Heading &amp; more</h2>'
      . $this->embed($first->uuid(), ['data-caption' => 'One'])
      . '<p>Text between the images.</p>'
      . $this->embed($second->uuid())
      . '<p>Trailing text.</p>';
    $processed = (string) $this->filter->process($text, 'en');

    $this->assertStringContainsString('data-caption="One | First Photographer"', $processed);
    $this->assertStringContainsString('data-caption="Second Photographer"', $processed);
    $this->assertStringContainsString('<h2>Heading &amp; more</h2>', $processed);
    $this->assertStringContainsString('<p>Text between the images.</p>', $processed);
    $this->assertStringContainsString('<p>Trailing text.</p>', $processed);
  }

  /**
   * The media is a cache dependency even before it has a credit.
   */
  public function testCacheability(): void {
    $media = $this->createImageMedia('Some alt text', '');
    $result = $this->filter->process($this->embed($media->uuid()), 'en');

    $this->assertContains('media:' . $media->id(), $result->getCacheTags());
  }

  /**
   * The caption filter renders the credit into the figcaption.
   */
  public function testRenderedWithCaptionFilter(): void {
    $media = $this->createImageMedia('Some alt text', 'Jane Photographer');
    $text = $this->embed($media->uuid(), ['data-caption' => 'A caption']);

    $this->assertStringContainsString(
      '<figcaption>A caption | Jane Photographer</figcaption>',
      $this->renderCaptions((string) $this->filter->process($text, 'en'))
    );
  }

  /**
   * The shipped config runs this filter before the caption filter.
   *
   * The filter rewrites `data-caption`, which `filter_caption` consumes, so
   * reordering the two silently stops credits from being displayed.
   */
  public function testShippedConfigRunsBeforeCaptionFilter(): void {
    $config_dir = DRUPAL_ROOT . '/' . \Drupal::service('extension.list.module')->getPath('sul_helper') . '/../../config/sync';
    $format = Yaml::decode(file_get_contents($config_dir . '/filter.format.stanford_html.yml'));
    $patch = Yaml::decode(file_get_contents($config_dir . '/split/library/config_split.patch.filter.format.stanford_html.yml'));

    $credit = $patch['adding']['filters']['sul_image_credit'];
    $this->assertTrue($credit['status']);
    $this->assertTrue($format['filters']['filter_caption']['status']);
    $this->assertLessThan($format['filters']['filter_caption']['weight'], $credit['weight']);
    // The media embed filter replaces the element the caption filter needs.
    $this->assertLessThan($format['filters']['media_embed']['weight'], $credit['weight']);
  }

  /**
   * Build a drupal-media embed tag.
   *
   * @param string $uuid
   *   Uuid of the media entity.
   * @param array $attributes
   *   Additional attributes for the element.
   *
   * @return string
   *   The embed markup.
   */
  protected function embed(string $uuid, array $attributes = []): string {
    $attributes = ['data-entity-type' => 'media', 'data-entity-uuid' => $uuid] + $attributes;
    $rendered = '';
    foreach ($attributes as $name => $value) {
      $rendered .= sprintf(' %s="%s"', $name, htmlspecialchars($value, ENT_QUOTES));
    }
    return '<drupal-media' . $rendered . '></drupal-media>';
  }

  /**
   * Run core's caption filter over already filtered text.
   *
   * @param string $text
   *   Text that this filter has processed.
   *
   * @return string
   *   Text with the captions rendered.
   */
  protected function renderCaptions(string $text): string {
    /** @var \Drupal\filter\FilterPluginManager $filter_manager */
    $filter_manager = \Drupal::service('plugin.manager.filter');
    $caption_filter = $filter_manager->createInstance('filter_caption');

    // The caption filter renders a template, so it needs a render context.
    return \Drupal::service('renderer')
      ->executeInRenderContext(new RenderContext(), fn() => (string) $caption_filter->process($text, 'en'));
  }

  /**
   * Create the image media type with its source and credit fields.
   */
  protected function createImageMediaType(): void {
    MediaType::create([
      'id' => 'image',
      'label' => 'Image',
      'source' => 'image',
      'source_configuration' => ['source_field' => 'field_media_image'],
    ])->save();

    FieldStorageConfig::create([
      'entity_type' => 'media',
      'field_name' => 'field_media_image',
      'type' => 'image',
    ])->save();
    FieldConfig::create([
      'entity_type' => 'media',
      'bundle' => 'image',
      'field_name' => 'field_media_image',
      'label' => 'Image',
    ])->save();

    FieldStorageConfig::create([
      'entity_type' => 'media',
      'field_name' => SulImageCredit::CREDIT_FIELD,
      'type' => 'string',
    ])->save();
    FieldConfig::create([
      'entity_type' => 'media',
      'bundle' => 'image',
      'field_name' => SulImageCredit::CREDIT_FIELD,
      'label' => 'Image Credit',
    ])->save();
  }

  /**
   * Create an image media entity.
   *
   * @param string $alt
   *   Alt text for the image.
   * @param string $credit
   *   Image credit.
   *
   * @return \Drupal\media\MediaInterface
   *   The saved media entity.
   */
  protected function createImageMedia(string $alt, string $credit): MediaInterface {
    $file = File::create(['uri' => 'public://test.png', 'filename' => 'test.png']);
    $file->setPermanent();
    $file->save();

    $media = Media::create([
      'bundle' => 'image',
      'name' => 'Test image',
      'status' => 1,
      'field_media_image' => ['target_id' => $file->id(), 'alt' => $alt],
      SulImageCredit::CREDIT_FIELD => $credit,
    ]);
    $media->save();
    return $media;
  }

  /**
   * Create a media entity that isn't an image.
   *
   * @return \Drupal\media\MediaInterface
   *   The saved media entity.
   */
  protected function createFileMedia(): MediaInterface {
    MediaType::create([
      'id' => 'document',
      'label' => 'Document',
      'source' => 'file',
      'source_configuration' => ['source_field' => 'field_media_file'],
    ])->save();
    FieldStorageConfig::create([
      'entity_type' => 'media',
      'field_name' => 'field_media_file',
      'type' => 'file',
    ])->save();
    FieldConfig::create([
      'entity_type' => 'media',
      'bundle' => 'document',
      'field_name' => 'field_media_file',
      'label' => 'File',
    ])->save();

    $file = File::create(['uri' => 'public://test.txt', 'filename' => 'test.txt']);
    $file->setPermanent();
    $file->save();

    $media = Media::create([
      'bundle' => 'document',
      'name' => 'Test document',
      'status' => 1,
      'field_media_file' => ['target_id' => $file->id()],
    ]);
    $media->save();
    return $media;
  }

}
