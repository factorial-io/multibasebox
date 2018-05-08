<?php

namespace Drupal\Tests\webform\Unit;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\Context\CacheContextsManager;
use Drupal\Core\Config\Entity\ConfigEntityType;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\webform\Plugin\WebformSourceEntityManagerInterface;
use Drupal\webform\WebformEntityAccessControlHandler;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\webform\WebformSubmissionStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Tests webform access handler.
 *
 * @coversDefaultClass \Drupal\webform\WebformEntityAccessControlHandler
 *
 * @group webform
 */
class WebformEntityAccessControlHandlerTest extends UnitTestCase {

  /**
   * Tests the access logic.
   *
   * @param string $operation
   *   Operation to request from ::checkAccess() method.
   * @param array $permissions
   *   Array of permissions to assign to a mocked account.
   * @param array $check_access_rules
   *   Array of access rules that should yield 'allowed' when the mocked webform
   *   is requested ::checkAccessRules().
   * @param array $options
   *   Array of extra options. Allowed key-value pairs are:
   *   - is_owner: (bool) Whether the mocked user should be owner of the
   *     webform. Defaults to FALSE.
   *   - is_template: (bool) Whether the mocked webform should be a template.
   *     Defaults  to FALSE.
   *   - is_open: (bool) Whether the mocked webform should be open.
   *     Defaults to TRUE.
   *   - has_token: (bool) Whether the mocked webform submission should
   *     successfully load through token in query string. Defaults to FALSE.
   * @param array $expected
   *   Expected data from the tested class. It should have the following
   *   structure:
   *   - is_allowed: (bool) Whether ::isAllowed() on the return should yield
   *     TRUE.
   *   - cache_tags: (array) Cache tags of the return. Defaults to [].
   *   - cache_contexts: (array) Cache contexts of the return. Defaults to [].
   * @param string $assert_message
   *   Assertion message to use for this test case.
   *
   * @see WebformEntityAccessControlHandler::checkAccess()
   *
   * @dataProvider providerCheckAccess
   */
  public function testCheckAccess($operation, array $permissions, array $check_access_rules, array $options, array $expected, $assert_message = '') {
    $options += [
      'is_owner' => FALSE,
      'is_template' => FALSE,
      'is_open' => TRUE,
      'has_token' => FALSE,
    ];
    $expected += [
      'cache_tags' => [],
      'cache_contexts' => [],
    ];

    $cache_contexts_manager = $this->getMockBuilder(CacheContextsManager::class)
      ->disableOriginalConstructor()
      ->getMock();
    $cache_contexts_manager->method('assertValidTokens')
      ->willReturn(TRUE);

    $container = $this->getMockBuilder(ContainerInterface::class)
      ->disableOriginalConstructor()
      ->getMock();
    $container->method('get')
      ->willReturnMap([
        ['cache_contexts_manager', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $cache_contexts_manager],
      ]);

    \Drupal::setContainer($container);

    $entity_type = new ConfigEntityType([
      'id' => 'webform',
    ]);

    $request_stack = $this->getMockBuilder(RequestStack::class)
      ->disableOriginalConstructor()
      ->getMock();
    $request_stack->method('getCurrentRequest')
      ->willReturn(new Request([
        'token' => $this->randomMachineName(),
      ]));

    $webform_submission_storage = $this->getMockBuilder(WebformSubmissionStorageInterface::class)
      ->getMock();

    $entity_type_manager = $this->getMockBuilder(EntityTypeManagerInterface::class)
      ->getMock();
    $entity_type_manager->method('getStorage')
      ->willReturnMap([
        ['webform_submission', $webform_submission_storage],
      ]);

    $webform_source_entity_manager = $this->getMockBuilder(WebformSourceEntityManagerInterface::class)
      ->getMock();
    $webform_source_entity_manager->method('getSourceEntity')
      ->willReturn(NULL);

    $access_handler = new WebformEntityAccessControlHandler($entity_type, $request_stack, $entity_type_manager, $webform_source_entity_manager);

    $account = $this->getMockBuilder(AccountInterface::class)
      ->getMock();
    $account->method('hasPermission')
      ->willReturnCallback(function ($permission) use ($permissions) {
        return in_array($permission, $permissions);
      });

    $webform = $this->getMockBuilder(WebformInterface::class)
      ->getMock();
    $webform->method('getOwnerId')
      ->willReturn(2);
    $webform->method('isTemplate')
      ->willReturn($options['is_template']);
    $webform->method('isOpen')
      ->willReturn($options['is_open']);
    $webform->method('access')
      ->willReturnMap([
        ['create', $account, TRUE, AccessResult::forbidden()],
      ]);

    $webform_submission = $this->getMockBuilder(WebformSubmissionInterface::class)
      ->getMock();
    $webform_submission->method('getCacheContexts')
      ->willReturn(['webform_submission_cache_context']);
    $webform_submission->method('getCacheTags')
      ->willReturn(['webform_submission_cache_tag']);
    $webform_submission->method('getCacheMaxAge')
      ->willReturn(Cache::PERMANENT);
    $webform_submission_storage->method('loadFromToken')
      ->willReturnMap([
        [$request_stack->getCurrentRequest()->query->get('token'), $webform, $webform_source_entity_manager->getSourceEntity(['webform']), NULL, ($options['has_token'] ? $webform_submission : NULL)],
      ]);

    $check_access_rules_map = [];
    foreach (['administer', 'page', 'view_any', 'view_own'] as $v) {
      $check_access_rules_map[] = [$v, $account, NULL, AccessResult::allowedIf(in_array($v, $check_access_rules))->addCacheContexts(['check_access_rules_cache_context'])->addCacheTags(['check_access_rules_cache_tag'])];
    }
    $webform->method('checkAccessRules')
      ->willReturnMap($check_access_rules_map);
    $webform->method('getCacheMaxAge')
      ->willReturn(Cache::PERMANENT);
    $webform->method('getCacheContexts')
      ->willReturn(['webform_cache_context']);
    $webform->method('getCacheTags')
      ->willReturn(['webform_cache_tag']);

    $account->method('id')
      ->willReturn($options['is_owner'] ? $webform->getOwnerId() : $webform->getOwnerId() + 1);
    $account->method('isAuthenticated')
      ->willReturn($account->id() > 0);

    $access_result = $access_handler->checkAccess($webform, $operation, $account);

    $this->assertEquals($expected['is_allowed'], $access_result->isAllowed(), $assert_message);
    $this->assertEquals(Cache::PERMANENT, $access_result->getCacheMaxAge(), $assert_message . ': cache max age');
    $this->assertArrayEquals($expected['cache_contexts'], $access_result->getCacheContexts(), $assert_message . ': cache contexts');
    $this->assertArrayEquals($expected['cache_tags'], $access_result->getCacheTags(), $assert_message . ': cache tags');
  }

  /**
   * Data provider for testCheckAccess().
   *
   * @see testCheckAccess()
   */
  public function providerCheckAccess() {
    $tests[] = ['view', [], [], [], ['is_allowed' => TRUE], 'View operation'];

    // The "update" operation.
    $tests[] = ['update', [], [], [], [
      'is_allowed' => FALSE,
      'cache_tags' => ['webform_cache_tag'],
      'cache_contexts' => ['webform_cache_context'],
    ], 'Update when nobody'];

    $tests[] = ['update', [], ['administer'], [], [
      'is_allowed' => TRUE,
      'cache_tags' => ['check_access_rules_cache_tag', 'webform_cache_tag'],
      'cache_contexts' => ['check_access_rules_cache_context', 'user', 'user.permissions', 'webform_cache_context'],
    ], 'Update when admin of the webform'];

    $tests[] = ['update', ['edit any webform'], [], [], [
      'is_allowed' => TRUE,
      'cache_tags' => ['check_access_rules_cache_tag', 'webform_cache_tag'],
      'cache_contexts' => ['check_access_rules_cache_context', 'user', 'user.permissions', 'webform_cache_context'],
    ], 'Update when has "edit any webform" permission'];

    $tests[] = ['update', ['edit own webform'], [], [], [
      'is_allowed' => FALSE,
      'cache_tags' => ['webform_cache_tag'],
      'cache_contexts' => ['webform_cache_context'],
    ], 'Update when has "edit own webform" permission but is not owner'];

    $tests[] = ['update', ['edit own webform'], [], ['is_owner' => TRUE], [
      'is_allowed' => TRUE,
      'cache_tags' => ['check_access_rules_cache_tag', 'webform_cache_tag'],
      'cache_contexts' => ['check_access_rules_cache_context', 'user', 'user.permissions', 'webform_cache_context'],
    ], 'Update when has "edit own webform" permission and is owner'];

    // The "duplicate" operation.
    $tests[] = ['duplicate', [], [], [], [
      'is_allowed' => FALSE,
      'cache_tags' => ['webform_cache_tag'],
      'cache_contexts' => ['webform_cache_context'],
    ], 'Duplicate when nobody'];

    $tests[] = ['duplicate', [], ['administer'], [], [
      'is_allowed' => TRUE,
      'cache_tags' => ['check_access_rules_cache_tag', 'webform_cache_tag'],
      'cache_contexts' => ['check_access_rules_cache_context', 'user', 'user.permissions', 'webform_cache_context'],
    ], 'Duplicate when admin of the webform'];

    $tests[] = ['duplicate', ['create webform'], [], ['is_template' => TRUE], [
      'is_allowed' => TRUE,
      'cache_tags' => ['check_access_rules_cache_tag', 'webform_cache_tag'],
      'cache_contexts' => ['check_access_rules_cache_context', 'user', 'user.permissions', 'webform_cache_context'],
    ], 'Duplicate when has "create webform" and the webform is a template'];

    $tests[] = ['duplicate', ['create webform', 'edit any webform'], [], [], [
      'is_allowed' => TRUE,
      'cache_tags' => ['check_access_rules_cache_tag', 'webform_cache_tag'],
      'cache_contexts' => ['check_access_rules_cache_context', 'user', 'user.permissions', 'webform_cache_context'],
    ], 'Duplicate when has "create webform" and "edit any webform"'];

    $tests[] = ['duplicate', ['create webform', 'edit own webform'], [], [], [
      'is_allowed' => FALSE,
      'cache_tags' => ['webform_cache_tag'],
      'cache_contexts' => ['webform_cache_context'],
    ], 'Duplicate when has "create webform" and "edit own webform" but is not owner'];

    $tests[] = ['duplicate', ['create webform', 'edit own webform'], [], ['is_owner' => TRUE], [
      'is_allowed' => TRUE,
      'cache_tags' => ['check_access_rules_cache_tag', 'webform_cache_tag'],
      'cache_contexts' => ['check_access_rules_cache_context', 'user', 'user.permissions', 'webform_cache_context'],
    ], 'Duplicate when has "create webform" and "edit own webform" and is owner'];

    // The "delete" operation.
    $tests[] = ['delete', [], [], [], [
      'is_allowed' => FALSE,
      'cache_tags' => ['webform_cache_tag'],
      'cache_contexts' => ['webform_cache_context'],
    ], 'Delete when nobody'];

    $tests[] = ['delete', [], ['administer'], [], [
      'is_allowed' => TRUE,
      'cache_tags' => ['check_access_rules_cache_tag', 'webform_cache_tag'],
      'cache_contexts' => ['check_access_rules_cache_context', 'user', 'user.permissions', 'webform_cache_context'],
    ], 'Delete when admin of the webform'];
    $tests[] = ['delete', ['delete any webform'], [], [], [
      'is_allowed' => TRUE,
      'cache_tags' => ['check_access_rules_cache_tag', 'webform_cache_tag'],
      'cache_contexts' => ['check_access_rules_cache_context', 'user', 'user.permissions', 'webform_cache_context'],
    ], 'Delete when has "delete any webform"'];

    $tests[] = ['delete', ['delete own webform'], [], [], [
      'is_allowed' => FALSE,
      'cache_tags' => ['webform_cache_tag'],
      'cache_contexts' => ['webform_cache_context'],
    ], 'Delete when has "delete own webform" but is not owner'];

    $tests[] = ['delete', ['delete own webform'], [], ['is_owner' => TRUE], [
      'is_allowed' => TRUE,
      'cache_tags' => ['check_access_rules_cache_tag', 'webform_cache_tag'],
      'cache_contexts' => ['check_access_rules_cache_context', 'user', 'user.permissions', 'webform_cache_context'],
    ], 'Delete when has "delete own webform" and is owner'];

    $tests[] = ['submission_view_any', [], [], [], [
      'is_allowed' => FALSE,
      'cache_tags' => ['webform_cache_tag'],
      'cache_contexts' => ['webform_cache_context'],
    ], 'Submission view any when nobody'];

    $tests[] = ['submission_view_any', ['view any webform submission'], [], [], [
      'is_allowed' => TRUE,
      'cache_contexts' => ['user.permissions'],
    ], 'Submission view any when has "view any webform submission" permission'];

    $tests[] = ['submission_view_any', ['view own webform submission'], [], [], [
      'is_allowed' => FALSE,
      'cache_tags' => ['webform_cache_tag'],
      'cache_contexts' => ['webform_cache_context'],
    ], 'Submission view any when has "view own webform submission" permission but is not owner'];

    $tests[] = ['submission_view_any', ['view own webform submission'], [], ['is_owner' => TRUE], [
      'is_allowed' => TRUE,
      'cache_tags' => ['webform_cache_tag'],
      'cache_contexts' => ['user', 'webform_cache_context'],
    ], 'Submission view any when has "view own webform submission" permission and is owner'];

    $tests[] = ['submission_view_own', [], [], [], [
      'is_allowed' => FALSE,
      'cache_tags' => ['webform_cache_tag'],
      'cache_contexts' => ['webform_cache_context'],
    ], 'Submission view own when nobody'];

    $tests[] = ['submission_view_own', ['view own webform submission'], [], [], [
      'is_allowed' => TRUE,
      'cache_contexts' => ['user.permissions'],
    ], 'Submission view own when has "view own webform submission" permission'];

    // The "submission_page" operation.
    $tests[] = ['submission_page', [], [], ['is_open' => FALSE], [
      'is_allowed' => FALSE,
      'cache_tags' => ['webform_cache_tag'],
      'cache_contexts' => ['webform_cache_context'],
    ], 'Submission page when nobody'];

    $tests[] = ['submission_page', [], [], ['has_token' => TRUE], [
      'is_allowed' => TRUE,
      'cache_tags' => ['webform_cache_tag', 'webform_submission_cache_tag'],
      'cache_contexts' => ['url', 'webform_cache_context', 'webform_submission_cache_context'],
    ], 'Submission page when accessible through token'];

    $tests[] = ['submission_page', [], [], ['is_template' => TRUE, 'is_open' => FALSE], [
      'is_allowed' => FALSE,
      'cache_tags' => ['webform_cache_tag'],
      'cache_contexts' => ['webform_cache_context'],
    ], 'Submission page when the webform is template without create access'];

    $tests[] = ['submission_page', [], ['page'], ['is_open' => FALSE], [
      'is_allowed' => TRUE,
      'cache_tags' => ['check_access_rules_cache_tag', 'webform_cache_tag'],
      'cache_contexts' => ['check_access_rules_cache_context', 'webform_cache_context'],
    ], 'Submission page when the webform allows "page"'];

    return $tests;
  }

}
