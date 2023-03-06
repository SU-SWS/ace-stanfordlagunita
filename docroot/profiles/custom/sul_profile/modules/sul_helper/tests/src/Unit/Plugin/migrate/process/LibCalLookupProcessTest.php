<?php

namespace Drupal\Tests\sul_helper\Unit\Plugin\migrate\process;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Site\Settings;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Drupal\sul_helper\Plugin\migrate\process\LibCalLookupProcess;
use Drupal\Tests\UnitTestCase;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Class LibCalLookupProcessTest.
 */
class LibCalLookupProcessTest extends UnitTestCase {

  protected $throwGuzzleError = FALSE;

  /**
   * Test the process plugin correctly tranforms data.
   */
  public function testProcessPlugin() {
    new Settings([
      'library_libcal_client_id' => 'foo',
      'library_libcal_client_secret' => 'bar',
    ]);

    $guzzle = $this->createMock(ClientInterface::class);
    $guzzle->method('request')
      ->will($this->returnCallback([$this, 'guzzleRequest']));
    $cache = $this->createMock(CacheBackendInterface::class);

    $container = new ContainerBuilder();
    $container->set('http_client', $guzzle);
    $container->set('cache.default', $cache);

    $plugin = LibCalLookupProcess::create($container, [], '', []);

    $migrate = $this->createMock(MigrateExecutableInterface::class);
    $row = $this->createMock(Row::class);

    $this->assertNull($plugin->transform('bar@foobar.com', $migrate, $row, ''));
    $this->assertEquals(12345, $plugin->transform('foobar@foobar.com', $migrate, $row, ''));

    $this->throwGuzzleError = TRUE;
    $this->assertNull($plugin->transform('bar@foobar.com', $migrate, $row, ''));
  }

  /**
   * Guzzle request callback.
   */
  public function guzzleRequest($method, $url, $options) {
    if ($this->throwGuzzleError) {
      throw new ClientException('test', $this->createMock(RequestInterface::class));
    }

    $body = [
      [
        'id' => 12345,
        'email' => 'foobar@foobar.com',
      ],
    ];
    if (str_contains($url, 'oauth/token')) {
      $body['access_token'] = 'foobar';
    }

    $response = $this->createMock(ResponseInterface::class);
    $response->method('getBody')->willReturn(json_encode($body));

    return $response;
  }

}