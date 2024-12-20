<?php

namespace Drupal\summer_helper\Plugin\Scheduler;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\scheduler\SchedulerPluginBase;

/**
 * Plugin for Taxonomy Term entity type.
 *
 * @package Drupal\Scheduler\Plugin\Scheduler
 *
 * @SchedulerPlugin(
 *  id = "summer_entity_scheduler",
 *  label = @Translation("Summer Entity Scheduler Plugin"),
 *  description = @Translation("Provides support for scheduling Summer entities"),
 *  entityType = "summer_entity",
 *  dependency = "summer_helper",
 *  collectionRoute = "entity.summer_entity.collection",
 *  schedulerEventClass = "\Drupal\summer_helper\Event\SchedulerSummerEntityEvents",
 * )
 */
class SummerEntityScheduler extends SchedulerPluginBase implements ContainerFactoryPluginInterface {}
