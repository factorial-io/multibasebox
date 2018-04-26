<?php

namespace Drupal\Tests\webform\Unit\Plugin\WebformSourceEntity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\Language;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\webform\Plugin\WebformSourceEntity\QueryStringWebformSourceEntity;
use Drupal\Tests\UnitTestCase;
use Drupal\webform\WebformEntityReferenceManagerInterface;
use Drupal\webform\WebformInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Tests the "query_string" webform source entity plugin.
 *
 * @group webform
 *
 * @coversDefaultClass \Drupal\webform\Plugin\WebformSourceEntity\QueryStringWebformSourceEntity
 */
class QueryStringWebformSourceEntityTest extends UnitTestCase {

  /**
   * Tests detection of source entity via query string.
   *
   * @param bool $webform_in_route
   *   Whether webform should be included in route object.
   * @param string $source_entity_type_in_query
   *   Source entity type to include into query string.
   * @param bool $source_entity_view_access
   *   Whether 'view' access should be allowed in the source entity.
   * @param bool $webform_prepopulate_source_entity
   *   Value for the setting 'form_prepopulate_source_entity' of the webform.
   * @param bool $source_entity_references_webform
   *   Whether the source entity should reference webform.
   * @param bool $source_entity_has_translation
   *   Whether the source entity should have a translation and (whenever
   *   $source_entity_references_webform is TRUE) refer the webform from that
   *   translation.
   * @param string[] $ignored_types
   *   Array of entity types that may not be source.
   * @param bool $expect_source_entity
   *   Whether we expect the tested method to return the source entity.
   * @param string $assert_message
   *   Assert message to use.
   *
   * @see QueryStringWebformSourceEntity::getSourceEntity()
   *
   * @dataProvider providerGetCurrentSourceEntity
   */
  public function testGetCurrentSourceEntity($webform_in_route, $source_entity_type_in_query, $source_entity_view_access, $webform_prepopulate_source_entity, $source_entity_references_webform, $source_entity_has_translation, array $ignored_types, $expect_source_entity, $assert_message = '') {
    $source_entity_type = 'node';
    $source_entity_id = 1;

    $entity_type_manager = $this->getMockBuilder(EntityTypeManagerInterface::class)
      ->disableOriginalConstructor()
      ->getMock();

    $route_match = $this->getMockBuilder(RouteMatchInterface::class)
      ->disableOriginalConstructor()
      ->getMock();

    $request_stack = $this->getMockBuilder(RequestStack::class)
      ->disableOriginalConstructor()
      ->getMock();

    $webform_entity_reference_manager = $this->getMockBuilder(WebformEntityReferenceManagerInterface::class)
      ->disableOriginalConstructor()
      ->getMock();

    $language_manager = $this->getMockBuilder(LanguageManagerInterface::class)
      ->disableOriginalConstructor()
      ->getMock();

    $webform = $this->getMockBuilder(WebformInterface::class)
      ->disableOriginalConstructor()
      ->getMock();

    $request = new Request([
      'source_entity_type' => $source_entity_type_in_query,
      'source_entity_id' => $source_entity_id,
    ]);

    $source_entity_storage = $this->getMockBuilder(EntityStorageInterface::class)
      ->getMock();

    $source_entity = $this->getMockBuilder(ContentEntityInterface::class)
      ->getMock();

    $source_entity_translation = $this->getMockBuilder(ContentEntityInterface::class)
      ->getMock();

    $route_match->method('getParameter')
      ->will($this->returnValueMap([
        ['webform', $webform_in_route ? $webform : NULL],
      ]));

    $request_stack->method('getCurrentRequest')
      ->will($this->returnValue($request));

    $entity_type_manager->method('hasDefinition')
      ->willReturnMap([
        [$source_entity_type, TRUE],
      ]);

    $entity_type_manager->method('getStorage')
      ->willReturnMap([
        [$source_entity_type, $source_entity_storage],
      ]);

    $source_entity_storage->method('load')
      ->willReturnMap([
        [$source_entity_id, $source_entity],
      ]);

    $source_entity_storage->method('load')
      ->willReturnMap([
        [$source_entity_id, $source_entity],
      ]);

    $source_entity->method('access')
      ->willReturnMap([
        ['view', NULL, FALSE, $source_entity_view_access],
      ]);

    $source_entity_translation->method('access')
      ->willReturnMap([
        ['view', NULL, FALSE, $source_entity_view_access],
      ]);

    $webform->method('getSetting')
      ->willReturnMap([
        ['form_prepopulate_source_entity', FALSE, $webform_prepopulate_source_entity],
      ]);

    $webform->method('id')
      ->willReturn('webform_id');

    $webform_entity_reference_manager->method('getFieldName')
      ->willReturnMap([
        [$source_entity, 'webform_field_name'],
        [$source_entity_translation, 'webform_field_name'],
      ]);

    $language_manager->method('getCurrentLanguage')
      ->willReturn(new Language([
        'id' => 'ua',
        'name' => 'Ukrainian',
        'direction' => LanguageInterface::DIRECTION_LTR,
        'weight' => 0,
        'locked' => FALSE,
      ]));

    $source_entity->webform_field_name = (object) [
      'target_id' => $source_entity_references_webform && !$source_entity_has_translation ? $webform->id() : 'other_webform',
    ];

    $source_entity_translation->webform_field_name = (object) [
      'target_id' => $source_entity_references_webform && $source_entity_has_translation ? $webform->id() : 'other_webform',
    ];

    $source_entity->method('hasTranslation')
      ->willReturnMap([
        [$language_manager->getCurrentLanguage()->getId(), $source_entity_has_translation],
      ]);

    $source_entity->method('getTranslation')
      ->willReturnMap([
        [$language_manager->getCurrentLanguage()->getId(), $source_entity_translation],
      ]);

    $plugin = new QueryStringWebformSourceEntity([], 'query_string', [], $entity_type_manager, $route_match, $request_stack, $language_manager, $webform_entity_reference_manager);
    $output = $plugin->getSourceEntity($ignored_types);

    if ($expect_source_entity) {
      $this->assertSame($source_entity_has_translation ? $source_entity_translation : $source_entity, $output, $assert_message);
    }
    else {
      $this->assertNull($output, $assert_message);
    }
  }

  /**
   * Data provider for testGetCurrentSourceEntity().
   *
   * @see testGetCurrentSourceEntity()
   */
  public function providerGetCurrentSourceEntity() {
    $tests[] = [FALSE, 'node', TRUE, TRUE, TRUE, FALSE, [], FALSE, 'No webform in route'];
    $tests[] = [TRUE, 'user', TRUE, TRUE, TRUE, FALSE, [], FALSE, 'Inexisting entity type in query string'];
    $tests[] = [TRUE, 'node', FALSE, TRUE, TRUE, FALSE, [], FALSE, 'Source entity without "view" access'];
    $tests[] = [TRUE, 'node', FALSE, TRUE, TRUE, TRUE, [], FALSE, 'Source entity translated without "view" access'];
    $tests[] = [TRUE, 'node', TRUE, TRUE, TRUE, FALSE, [], TRUE, 'Prepopulating of webform source entity is allowed'];
    $tests[] = [TRUE, 'node', TRUE, TRUE, TRUE, FALSE, ['node'], TRUE, '$ignored_types is not considered'];
    $tests[] = [TRUE, 'node', TRUE, FALSE, TRUE, FALSE, [], TRUE, 'Source entity references webform'];
    $tests[] = [TRUE, 'node', TRUE, FALSE, TRUE, TRUE, [], TRUE, 'Translation of source entity references webform'];
    $tests[] = [TRUE, 'node', TRUE, FALSE, FALSE, FALSE, [], FALSE, 'Source entity does not reference webform'];
    $tests[] = [TRUE, 'node', TRUE, FALSE, FALSE, TRUE, [], FALSE, 'Translation of source entity does not reference webform'];
    return $tests;
  }

}
