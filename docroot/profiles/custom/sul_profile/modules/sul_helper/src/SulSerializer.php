<?php

namespace Drupal\sul_helper;

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\jsonapi\IncludeResolver;
use Drupal\jsonapi\JsonApiResource\JsonApiDocumentTopLevel;
use Drupal\jsonapi\JsonApiResource\LinkCollection;
use Drupal\jsonapi\JsonApiResource\ResourceObject;
use Drupal\jsonapi\JsonApiResource\ResourceObjectData;
use Drupal\jsonapi\Normalizer\Value\CacheableNormalization;
use Drupal\jsonapi\ResourceType\ResourceTypeRepositoryInterface;
use Drupal\next\Entity\NextSiteInterface;
use Drupal\next\NextEntityTypeManagerInterface;
use Drupal\next\NextSettingsManagerInterface;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Sul Helper Serializer to build Next preview iframes.
 */
class SulSerializer {

  /**
   * Logger service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * Serializer constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager service.
   * @param \Drupal\next\NextEntityTypeManagerInterface $nextEntityTypeManager
   *   Next entity type manager service.
   * @param \Drupal\jsonapi\ResourceType\ResourceTypeRepositoryInterface $jsonApiResourceTypeRepo
   *   Json api resource repo service.
   * @param \Drupal\jsonapi\IncludeResolver $includeResolver
   *   Json api include resolver service.
   * @param \Symfony\Component\Serializer\SerializerInterface $serializer
   *   Json api serializer.
   * @param \Drupal\next\NextSettingsManagerInterface $nextSettingsManager
   *   Next settings manager service.
   */
  public function __construct(
    protected EntityTypeManagerInterface      $entityTypeManager,
    protected NextEntityTypeManagerInterface  $nextEntityTypeManager,
    protected ResourceTypeRepositoryInterface $jsonApiResourceTypeRepo,
    protected IncludeResolver                 $includeResolver,
    protected SerializerInterface             $serializer,
    protected NextSettingsManagerInterface    $nextSettingsManager,
    LoggerChannelFactoryInterface             $logger_factory
  ) {
    $this->logger = $logger_factory->get('sul_helper');
  }

  /**
   * Alter the entity build to replace the contents with an iframe element.
   *
   * @param array $build
   *   Build render array.
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   Entity object being built.
   * @param \Drupal\Core\Entity\Display\EntityViewDisplayInterface $display
   *   Display configuration object.
   */
  public function alterEntityBuild(array &$build, ContentEntityInterface $entity, EntityViewDisplayInterface $display): void {
    // Clone the entity so that if we modify it, we won't affect the original.
    $cloned_entity = clone $entity;
    if (!$cloned_entity->id()) {
      // To allow json api to normalize the entity, we have to set an entity id.
      $cloned_entity->set('id', rand(9999, 99999999));
    }

    $sites = $this->getNextSites($cloned_entity);
    if (!$sites) {
      return;
    }

    $new_build = [];
    foreach ($sites as $site) {
      try {
        $new_build[$site->id()] = $this->getIframeForSite($site, $cloned_entity);
      }
      catch (\Exception $e) {
        $this->logger->error('Unable to generate entity preview. %e', ['%e' => $e->getMessage()]);
      }
    }
    // If something went wrong building the iframes, don't modify the build or
    // the display settings.
    $build = array_filter($new_build) ?: $new_build;

    // Remove any third party settings for the Display Suite module since it
    // does some altering of its own after this.
    $display->unsetThirdPartySetting('ds', 'layout');
    $display->unsetThirdPartySetting('ds', 'regions');
    $display->unsetThirdPartySetting('ds', 'layout');

    unset($cloned_entity);
  }

  /**
   * Get the iframe render array for the given site and entity.
   *
   * @param \Drupal\next\Entity\NextSiteInterface $site
   *   Next site entity.
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   Content entity object.
   *
   * @return array
   *   Iframe render array.
   */
  protected function getIframeForSite(NextSiteInterface $site, ContentEntityInterface $entity): array {
    // Get the normalized data of the entity.
    $json_data = $this->getJsonApiNormalized($entity);

    $site_preview_url = $this->nextSettingsManager->getPreviewUrlGenerator()
      ->generate($site, $entity);

    return [
      '#type' => 'html_tag',
      '#tag' => 'iframe',
      '#attributes' => [
        'id' => Html::getUniqueId($site->id() . '-' . $entity->bundle() . '-' . $entity->id()),
        'class' => ['next-site-preview'],
        'src' => $site_preview_url->toString(),
        'width' => '100%',
        'height' => 100,
        'edit-data' => $this->serializer->serialize($json_data->getNormalization(), 'api_json'),
      ],
    ];
  }

  /**
   * Get the json api data for the given entity object.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   Content entity object.
   *
   * @return \Drupal\jsonapi\Normalizer\Value\CacheableNormalization
   *   Normalized data.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getJsonApiNormalized(ContentEntityInterface $entity): CacheableNormalization {
    $resource_type = $this->jsonApiResourceTypeRepo->get('paragraph', $entity->bundle());
    $resource_object = ResourceObject::createFromEntity($resource_type, $entity);

    /** @var \Drupal\jsonapi_extras\Entity\JsonapiResourceConfig[] $json_resource */
    $json_resource = $this->entityTypeManager->getStorage('jsonapi_resource_config')
      ->loadByProperties(['resourceType' => $entity->getEntityTypeId() . '--' . $entity->bundle()]);

    $entity_definition = $this->entityTypeManager->getDefinition($entity->getEntityTypeId());
    $include_fields = [$entity->getEntityTypeId() . '_' . $entity_definition->getKey('bundle')];

    // Look at the json api extras settings, and include the 'default included'
    // fields as configured.
    if ($json_resource) {
      $include = $json_resource[$entity->getEntityTypeId() . '--' . $entity->bundle()]->getThirdPartySetting('jsonapi_defaults', 'default_include') ?: [];
      $include_fields = [...$include_fields, ...$include];
    }
    $includes = $this->includeResolver->resolve($resource_object, implode(',', array_unique($include_fields)));

    $data = new ResourceObjectData([$resource_object], 1);
    $api_document = new JsonApiDocumentTopLevel($data, $includes, new LinkCollection([]));

    return $this->serializer->normalize($api_document, 'api_json', [
      'resource_type' => $resource_object,
      'account' => \Drupal::currentUser(),
    ]);
  }

  /**
   * Get the Next site entities that match the given content entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   Content entity object.
   *
   * @return \Drupal\next\Entity\NextSiteInterface[]
   *   Array of Next site entities.
   */
  protected function getNextSites(ContentEntityInterface $entity): array {
    $config = $this->nextEntityTypeManager->getConfigForEntityType($entity->getEntityTypeId(), $entity->bundle());
    if (!$config) {
      return [];
    }
    return $config->getSiteResolver()->getSitesForEntity($entity);
  }

}
