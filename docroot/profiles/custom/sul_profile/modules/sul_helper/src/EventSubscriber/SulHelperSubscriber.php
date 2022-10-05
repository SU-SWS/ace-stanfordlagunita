<?php

namespace Drupal\sul_helper\EventSubscriber;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\core_event_dispatcher\EntityHookEvents;
use Drupal\core_event_dispatcher\Event\Entity\AbstractEntityEvent;
use Drupal\layout_paragraphs\Event\LayoutParagraphsAllowedTypesEvent;
use Drupal\node\NodeInterface;
use GuzzleHttp\ClientInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * SUL Helper event subscriber.
 */
class SulHelperSubscriber implements EventSubscriberInterface {

  /**
   * Logger service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      LayoutParagraphsAllowedTypesEvent::EVENT_NAME => 'layoutParagraphsAllowedTypes',
      EntityHookEvents::ENTITY_PRE_SAVE => 'onEntityCrud',
      EntityHookEvents::ENTITY_INSERT => 'onEntityCrud',
      EntityHookEvents::ENTITY_UPDATE => 'onEntityCrud',
      EntityHookEvents::ENTITY_PRE_DELETE => 'onEntityCrud',
    ];
  }

  /**
   * @param \GuzzleHttp\ClientInterface $guzzle
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerFactory
   */
  public function __construct(protected ClientInterface $guzzle, protected EntityTypeManagerInterface $entityTypeManager, LoggerChannelFactoryInterface $loggerFactory) {
    $this->logger = $loggerFactory->get('sul_helper');
  }

  /**
   * Adjust the paragraphs allowed in the layout paragraphs widget.
   *
   * @param \Drupal\layout_paragraphs\Event\LayoutParagraphsAllowedTypesEvent $event
   *   Layout paragraphs event.
   */
  public function layoutParagraphsAllowedTypes(LayoutParagraphsAllowedTypesEvent $event): void {
    $parent_component = $event->getLayout()
      ->getComponentByUuid($event->getParentUuid());

    // If adding a new layout, it won't have a parent.
    if ($parent_component) {

      $layout_settings = $parent_component->getSettings();
      if ($layout_settings['layout'] != 'sul_helper_1_column') {
        $types = $event->getTypes();
        unset($types['stanford_banner']);
        $event->setTypes($types);
      }
    }
  }

  /**
   * Act on entities to revalidate the Next site.
   *
   * @param \Drupal\core_event_dispatcher\Event\Entity\AbstractEntityEvent $event
   *   Hook Event Dispatcher Event.
   */
  public function onEntityCrud(AbstractEntityEvent $event): void {
    $entity = $event->getEntity();

    if ($entity instanceof NodeInterface) {
      try {
        $this->triggerRevalidation($entity->toUrl()->toString());
      } catch (\Exception $e) {
        // When the node is new, the pre-save hook will fail because the node
        // doesn't have a path at this time. But we want to keep the pre-save
        // hook because it allows us to revalidate paths if they change when
        // saving a node.
      }
    }

    if ($entity->getEntityTypeId() == 'redirect') {
      $this->triggerRevalidation($entity->getSourceUrl());
    }
  }

  /**
   * @params tring $path
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function triggerRevalidation(string $path): void {
    /** @var \Drupal\next\Entity\NextSiteInterface[] $next_sites */
    $next_sites = $this->entityTypeManager->getStorage('next_site')->loadMultiple();
    foreach ($next_sites as $next_site) {
      $request_options = [
        'query' => [
          'token' => $next_site->getPreviewSecret(),
          'path' => $path,
        ],
        'headers' => [
          'Accept' => '*/*',
          'User-Agent' => $_SERVER['HTTP_USER_AGENT'],
        ],
        'timeout' => 3,
        'base_uri' => $next_site->getBaseUrl(),
      ];

      try {
        $this->guzzle->request('GET', '/api/revalidate', $request_options);
      } catch (\Throwable $e) {
        dpm($e);
        $this->logger->error($e->getMessage());
      }
    }
  }

}
