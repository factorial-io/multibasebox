<?php

namespace Drupal\Tests\webform\Unit\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Cache\Context\CacheContextsManager;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\webform\Plugin\Block\WebformBlock;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformTokenManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Tests webform submission bulk form actions.
 *
 * @coversDefaultClass \Drupal\webform\Plugin\Block\WebformBlock
 *
 * @group webform
 */
class WebformBlockTest extends UnitTestCase {

  /**
   * Tests the dependencies of a webform block.
   */
  public function testCalculateDependencies() {
    $webform = $this->getMockBuilder(WebformInterface::class)
      ->disableOriginalConstructor()
      ->getMock();
    $webform->method('id')
      ->willReturn($this->randomMachineName());
    $webform->method('getConfigDependencyKey')
      ->willReturn('config');
    $webform->method('getConfigDependencyName')
      ->willReturn('config.webform.' . $webform->id());

    $entity_type_manager = $this->getMockBuilder(EntityTypeManagerInterface::class)
      ->disableOriginalConstructor()
      ->getMock();

    $storage = $this->getMockBuilder(EntityStorageInterface::class)
      ->disableOriginalConstructor()
      ->getMock();

    $entity_type_manager->method('getStorage')
      ->willReturnMap([
        ['webform', $storage],
      ]);

    $storage->method('load')
      ->willReturnMap([
        [$webform->id(), $webform],
      ]);

    $token_manager = $this->getMockBuilder(WebformTokenManagerInterface::class)
      ->disableOriginalConstructor()
      ->getMock();

    $block = new WebformBlock([
      'webform_id' => $webform->id(),
      'default_data' => [],
    ], 'webform_block', [
      'provider' => 'unit_test',
    ], $entity_type_manager, $token_manager);

    $dependencies = $block->calculateDependencies();
    $expected = [
      $webform->getConfigDependencyKey() => [$webform->getConfigDependencyName()],
    ];
    $this->assertEquals($expected, $dependencies, 'WebformBlock reports proper dependencies.');
  }

  /**
   * Tests the access of a webform block.
   */
  public function testBlockAccess() {
    $account = $this->getMockBuilder(AccountInterface::class)
      ->disableOriginalConstructor()
      ->getMock();

    $cache_contexts = ['dummy_cache_context'];

    $cache_contexts_manager = $this->getMockBuilder(CacheContextsManager::class)
      ->disableOriginalConstructor()
      ->getMock();
    $cache_contexts_manager->method('assertValidTokens')
      ->willReturnMap([
        [$cache_contexts, TRUE],
      ]);

    $container = $this->getMockBuilder(ContainerInterface::class)
      ->disableOriginalConstructor()
      ->getMock();
    $container->method('get')
      ->willReturnMap([
        ['cache_contexts_manager', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $cache_contexts_manager],
      ]);

    \Drupal::setContainer($container);

    $access_result = AccessResult::allowed();
    $access_result->setCacheMaxAge(1);
    $access_result->addCacheTags(['dummy_cache_tag']);
    $access_result->addCacheContexts($cache_contexts);

    $webform = $this->getMockBuilder(WebformInterface::class)
      ->disableOriginalConstructor()
      ->getMock();
    $webform->method('id')
      ->willReturn($this->randomMachineName());
    $webform->method('access')
      ->willReturnMap([
        ['submission_create', $account, TRUE, $access_result],
      ]);

    $entity_type_manager = $this->getMockBuilder(EntityTypeManagerInterface::class)
      ->disableOriginalConstructor()
      ->getMock();

    $storage = $this->getMockBuilder(EntityStorageInterface::class)
      ->disableOriginalConstructor()
      ->getMock();

    $entity_type_manager->method('getStorage')
      ->willReturnMap([
        ['webform', $storage],
      ]);

    $storage->method('load')
      ->willReturnMap([
        [$webform->id(), $webform],
      ]);

    $token_manager = $this->getMockBuilder(WebformTokenManagerInterface::class)
      ->disableOriginalConstructor()
      ->getMock();

    $block = new WebformBlock([
      'webform_id' => $webform->id(),
      'default_data' => [],
    ], 'webform_block', [
      'provider' => 'unit_test',
    ], $entity_type_manager, $token_manager);

    $result = $block->access($account, TRUE);

    // Make sure the block transparently follows the webform access logic.
    $this->assertSame($access_result->isAllowed(), $result->isAllowed(), 'Block access yields the same result as the access of the webform.');
    $this->assertEquals($access_result->getCacheContexts(), $result->getCacheContexts(), 'Block access has the same cache contexts as the access of the webform.');
    $this->assertEquals($access_result->getCacheTags(), $result->getCacheTags(), 'Block access has the same cache tags as the access of the webform.');
    $this->assertEquals($access_result->getCacheMaxAge(), $result->getCacheMaxAge(), 'Block access has the same cache max age as the access of the webform.');
  }

}
