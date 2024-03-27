<?php

declare(strict_types=1);

namespace Drupal\Tests\supress_helper\Unit\Plugin\migrate_plus\authentication;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\supress_helper\Plugin\migrate_plus\authentication\FileMakerAuth;
use Drupal\Tests\UnitTestCase;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Stream;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 *
 */
class FileMakerAuthTest extends UnitTestCase {

  public function testEmptyConfigFailure() {
    $client = $this->createMock(ClientInterface::class);
    $request = $this->createMock(RequestInterface::class);
    $response = $this->createMock(ResponseInterface::class);
    $client->method('request')->willThrowException(new ClientException('failed', $request, $response));
    $container = new ContainerBuilder();
    $container->set('http_client', $client);
    $auth_plugin = FileMakerAuth::create($container, [], '', []);

    $this->expectException(\Exception::class);
    $auth_plugin->getAuthenticationOptions();
  }

  public function testFailedRequestFailure() {
    $client = $this->createMock(ClientInterface::class);
    $request = $this->createMock(RequestInterface::class);
    $response = $this->createMock(ResponseInterface::class);
    $client->method('request')->willThrowException(new ClientException('failed', $request, $response));
    $container = new ContainerBuilder();
    $container->set('http_client', $client);
    $auth_plugin = FileMakerAuth::create($container, ['token_url' => 'https://localhost', 'client_id' => 'foo', 'client_secret' => 'bar'], '', []);

    $this->expectException(ClientException::class);
    $auth_plugin->getAuthenticationOptions();
  }

  public function testBadJsonFailure() {
    $resource = fopen('php://memory','r+');
    fwrite($resource, 'foobar');
    rewind($resource);
    $body = new Stream($resource);

    $response = $this->createMock(ResponseInterface::class);
    $response->method('getBody')->willReturn($body);

    $client = $this->createMock(ClientInterface::class);
    $client->method('request')->willReturn($response);


    $container = new ContainerBuilder();
    $container->set('http_client', $client);
    $auth_plugin = FileMakerAuth::create($container, ['token_url' => 'https://localhost', 'client_id' => 'foo', 'client_secret' => 'bar'], '', []);

    $this->expectException(\Exception::class);
    $auth_plugin->getAuthenticationOptions();
  }

  public function testSuccess() {
    $resource = fopen('php://memory', 'r+');
    fwrite($resource, json_encode(['response' => ['token' => 'foobarbaz']]));
    rewind($resource);
    $body = new Stream($resource);

    $response = $this->createMock(ResponseInterface::class);
    $response->method('getBody')->willReturn($body);

    $client = $this->createMock(ClientInterface::class);
    $client->method('request')->willReturn($response);

    $container = new ContainerBuilder();
    $container->set('http_client', $client);
    $auth_plugin = FileMakerAuth::create($container, [
      'token_url' => 'https://localhost',
      'client_id' => 'foo',
      'client_secret' => 'bar',
    ], '', []);

    $options = $auth_plugin->getAuthenticationOptions();
    $this->assertEquals(['Authorization' => 'Bearer foobarbaz'], $options['headers']);
  }

}
