<?php

namespace Drupal\Tests\sul_helper\Kernel;

use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\jsonapi_extras\Entity\JsonapiResourceConfig;
use Drupal\KernelTests\KernelTestBase;
use Drupal\next\Entity\NextEntityTypeConfig;
use Drupal\next\Entity\NextSite;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\paragraphs\Entity\ParagraphsType;
use Drupal\sul_helper\SulSerializer;
use Drupal\user\Entity\User;

class SulSerializerTest extends KernelTestBase {

  protected static $modules = [
    'jsonapi',
    'next',
    'node',
    'paragraphs',
    'serialization',
    'sul_helper',
    'system',
    'file',
    'jsonapi_extras',
    'jsonapi_defaults',
    'user',
  ];

  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('paragraph');
    $this->installEntitySchema('user');
    \Drupal::configFactory()
      ->getEditable('next.settings')
      ->set('preview_url_generator', 'simple_oauth')
      ->save();
    ParagraphsType::create([
      'id' => 'card',
      'label' => 'Card',
    ])->save();

    $user = User::create([
      'name' => 'bob',
      'mail' => 'bob@example.com',
    ]);
    $user->save();
    \Drupal::currentUser()->setAccount($user);

    JsonapiResourceConfig::create([
      'id' => 'paragraph--card',
      'disabled' => FALSE,
      'path' => 'paragraph/card',
      'resourceType' => 'paragraph--card',
      'resourceFields' => [],
      'third_party_settings' => [
        'jsonapi_defaults' => [
          'default_include' => [
            'paragraph_type',
          ],
        ],
      ],
    ])->save();
  }

  public function testSerialization() {
    /** @var \Drupal\sul_helper\SulSerializer $serializer */
    $serializer = \Drupal::service('sul_helper.jsonapi_serializer');
    $this->assertInstanceOf(SulSerializer::class, $serializer);

    $build = ['foo' => 'bar'];
    $display = $this->createMock(EntityViewDisplayInterface::class);

    $entity = Paragraph::create(['type' => 'card']);
    $serializer->alterEntityBuild($build, $entity, $display);
    $this->assertArrayHasKey('foo', $build);

    $local_site = NextSite::create([
      'label' => 'Localhost',
      'id' => 'localhost',
      'base_url' => 'https://localhost.com',
      'preview_url' => 'https://localhost.com/api/preview',
      'preview_secret' => 'two',
    ]);
    $local_site->save();
    $next_site = NextEntityTypeConfig::create([
      'id' => 'paragraph.card',
      'site_resolver' => 'site_selector',
      'configuration' => [
        'sites' => [
          'localhost' => 'localhost',
        ],
      ],
    ]);
    $next_site->save();

    $serializer->alterEntityBuild($build, $entity, $display);
    $this->assertArrayNotHasKey('foo', $build);
    $this->assertStringContainsString('https://localhost.com/api/preview', $build['localhost']['#attributes']['src']);
    $data = json_decode($build['localhost']['#attributes']['edit-data'], TRUE);
    $this->assertEquals('paragraph--card', $data['data']['type']);
  }

}
