<?php

declare(strict_types=1);

namespace Drupal\Tests\supress_helper\Unit\Plugin\migrate_plus\authentication;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\migrate\MigrateException;
use Drupal\supress_helper\Plugin\migrate_plus\authentication\FileMakerAuth;
use Drupal\Tests\UnitTestCase;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Stream;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Unit tests for the FileMaker authentication plugin.
 *
 * @coversDefaultClass \Drupal\supress_helper\Plugin\migrate_plus\authentication\FileMakerAuth
 * @group supress_helper
 */
class FileMakerAuthTest extends UnitTestCase {

  const CONFIG = [
    'token_url' => 'https://localhost/sessions',
    'client_id' => 'foo',
    'client_secret' => 'bar',
  ];

  /**
   * Build the plugin with the given http client.
   */
  protected function buildPlugin(ClientInterface $client, array $config = self::CONFIG): FileMakerAuth {
    $container = new ContainerBuilder();
    $container->set('http_client', $client);
    return FileMakerAuth::create($container, $config, 'press_filemaker', []);
  }

  /**
   * Build a response with the given body.
   */
  protected function buildResponse(string $body): ResponseInterface {
    $resource = fopen('php://memory', 'r+');
    fwrite($resource, $body);
    rewind($resource);

    $response = $this->createMock(ResponseInterface::class);
    $response->method('getBody')->willReturn(new Stream($resource));
    return $response;
  }

  /**
   * Missing or placeholder settings fail before any request is made.
   *
   * @covers ::getAuthenticationOptions
   * @covers ::getToken
   *
   * @dataProvider providerInvalidConfig
   */
  public function testInvalidConfigFailure(array $config, string $message): void {
    $client = $this->createMock(ClientInterface::class);
    $client->expects($this->never())->method('request');

    $this->expectException(MigrateException::class);
    $this->expectExceptionMessage($message);
    $this->buildPlugin($client, $config)->getAuthenticationOptions('');
  }

  /**
   * Data provider for invalid configurations.
   */
  public static function providerInvalidConfig(): array {
    return [
      'empty' => [[], 'token_url, client_id, client_secret'],
      'empty secret' => [['client_secret' => ''] + self::CONFIG, 'client_secret'],
      'placeholders' => [['client_id' => '[client_id]', 'client_secret' => '[client_secret]'] + self::CONFIG, 'client_id, client_secret'],
    ];
  }

  /**
   * @covers ::getToken
   */
  public function testFailedRequestFailure(): void {
    $client = $this->createMock(ClientInterface::class);
    $client->method('request')->willThrowException(new ClientException('failed', $this->createMock(RequestInterface::class), $this->createMock(ResponseInterface::class)));

    $this->expectException(ClientException::class);
    $this->buildPlugin($client)->getAuthenticationOptions('');
  }

  /**
   * @covers ::getToken
   */
  public function testBadJsonFailure(): void {
    $client = $this->createMock(ClientInterface::class);
    $client->method('request')->willReturn($this->buildResponse('foobar'));

    $this->expectException(\JsonException::class);
    $this->buildPlugin($client)->getAuthenticationOptions('');
  }

  /**
   * @covers ::getToken
   */
  public function testMissingTokenFailure(): void {
    $client = $this->createMock(ClientInterface::class);
    $client->method('request')->willReturn($this->buildResponse(json_encode(['messages' => [['code' => '212', 'message' => 'Invalid user account']]])));

    $this->expectException(MigrateException::class);
    $this->buildPlugin($client)->getAuthenticationOptions('');
  }

  /**
   * The session is only created once and reused for later requests.
   *
   * @covers ::getAuthenticationOptions
   * @covers ::getToken
   */
  public function testSuccessReusesToken(): void {
    $client = $this->createMock(ClientInterface::class);
    $client->expects($this->once())
      ->method('request')
      ->with('POST', self::CONFIG['token_url'], $this->callback(fn(array $options) => $options['auth'] === ['foo', 'bar']))
      ->willReturn($this->buildResponse(json_encode(['response' => ['token' => 'foobarbaz']])));

    $plugin = $this->buildPlugin($client);
    $this->assertEquals(['headers' => ['Authorization' => 'Bearer foobarbaz']], $plugin->getAuthenticationOptions('https://localhost/page1'));
    $this->assertEquals(['headers' => ['Authorization' => 'Bearer foobarbaz']], $plugin->getAuthenticationOptions('https://localhost/page2'));
  }

}
