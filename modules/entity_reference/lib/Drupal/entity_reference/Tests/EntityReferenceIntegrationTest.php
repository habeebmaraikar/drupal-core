<?php

/**
 * @file
 * Contains \Drupal\entity_reference\Tests\EntityReferenceIntegrationTest.
 */

namespace Drupal\entity_reference\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests various Entity reference UI components.
 */
class EntityReferenceIntegrationTest extends WebTestBase {

  /**
   * The entity type used in this test.
   *
   * @var string
   */
  protected $entityType = 'entity_test';

  /**
   * The bundle used in this test.
   *
   * @var string
   */
  protected $bundle = 'entity_test';

  /**
   * The name of the field used in this test.
   *
   * @var string
   */
  protected $fieldName;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('config_test', 'entity_test', 'entity_reference');

  public static function getInfo() {
    return array(
      'name' => 'Entity reference components (widgets, formatters, etc.)',
      'description' => 'Tests for various Entity reference components.',
      'group' => 'Entity Reference',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Create a test user.
    $web_user = $this->drupalCreateUser(array('administer entity_test content'));
    $this->drupalLogin($web_user);
  }

  /**
   * Tests the entity reference field with all its widgets.
   */
  public function testSupportedEntityTypesAndWidgets() {
    foreach ($this->getTestEntities() as $referenced_entities) {
      $this->fieldName = 'field_test_' . $referenced_entities[0]->getEntityTypeId();

      // Create an Entity reference field.
      entity_reference_create_instance($this->entityType, $this->bundle, $this->fieldName, $this->fieldName, $referenced_entities[0]->getEntityTypeId(), 'default', array(), 2);

      // Test the default 'entity_reference_autocomplete' widget.
      entity_get_form_display($this->entityType, $this->bundle, 'default')->setComponent($this->fieldName)->save();

      $entity_name = $this->randomName();
      $edit = array(
        'name' => $entity_name,
        'user_id' => mt_rand(0, 128),
        $this->fieldName . '[0][target_id]' => $referenced_entities[0]->label() . ' (' . $referenced_entities[0]->id() . ')',
        $this->fieldName . '[1][target_id]' => $referenced_entities[1]->label() . ' (' . $referenced_entities[1]->id() . ')',
      );
      $this->drupalPostForm($this->entityType . '/add', $edit, t('Save'));
      $this->assertFieldValues($entity_name, $referenced_entities);

      // Try to post the form again with no modification and check if the field
      // values remain the same.
      $entity = current(entity_load_multiple_by_properties($this->entityType, array('name' => $entity_name)));
      $this->drupalPostForm($this->entityType . '/manage/' . $entity->id(), array(), t('Save'));
      $this->assertFieldValues($entity_name, $referenced_entities);

      // Test the 'entity_reference_autocomplete_tags' widget.
      entity_get_form_display($this->entityType, $this->bundle, 'default')->setComponent($this->fieldName, array(
        'type' => 'entity_reference_autocomplete_tags',
      ))->save();

      $entity_name = $this->randomName();
      $target_id = $referenced_entities[0]->label() . ' (' . $referenced_entities[0]->id() . ')';
      $target_id .= ', ' . $referenced_entities[1]->label() . ' (' . $referenced_entities[1]->id() . ')';
      $edit = array(
        'name' => $entity_name,
        'user_id' => mt_rand(0, 128),
        $this->fieldName . '[target_id]' => $target_id,
      );
      $this->drupalPostForm($this->entityType . '/add', $edit, t('Save'));
      $this->assertFieldValues($entity_name, $referenced_entities);

      // Try to post the form again with no modification and check if the field
      // values remain the same.
      $entity = current(entity_load_multiple_by_properties($this->entityType, array('name' => $entity_name)));
      $this->drupalPostForm($this->entityType . '/manage/' . $entity->id(), array(), t('Save'));
      $this->assertFieldValues($entity_name, $referenced_entities);
    }
  }

  /**
   * Asserts that the reference field values are correct.
   *
   * @param string $entity_name
   *   The name of the test entity.
   * @param \Drupal\Core\Entity\EntityInterface[] $referenced_entities
   *   An array of referenced entities.
   */
  protected function assertFieldValues($entity_name, $referenced_entities) {
    $entity = current(entity_load_multiple_by_properties($this->entityType, array('name' => $entity_name)));

    $this->assertTrue($entity, format_string('%entity_type: Entity found in the database.', array('%entity_type' => $this->entityType)));

    $this->assertEqual($entity->{$this->fieldName}->target_id, $referenced_entities[0]->id());
    $this->assertEqual($entity->{$this->fieldName}->entity->id(), $referenced_entities[0]->id());
    $this->assertEqual($entity->{$this->fieldName}->entity->label(), $referenced_entities[0]->label());

    $this->assertEqual($entity->{$this->fieldName}[1]->target_id, $referenced_entities[1]->id());
    $this->assertEqual($entity->{$this->fieldName}[1]->entity->id(), $referenced_entities[1]->id());
    $this->assertEqual($entity->{$this->fieldName}[1]->entity->label(), $referenced_entities[1]->label());
  }

  /**
   * Creates two content and two config test entities.
   *
   * @return array
   *   An array of entity objects.
   */
  protected function getTestEntities() {
    $config_entity_1 = entity_create('config_test', array('id' => $this->randomName(), 'label' => $this->randomName()));
    $config_entity_1->save();
    $config_entity_2 = entity_create('config_test', array('id' => $this->randomName(), 'label' => $this->randomName()));
    $config_entity_2->save();

    $content_entity_1 = entity_create('entity_test', array('name' => $this->randomName()));
    $content_entity_1->save();
    $content_entity_2 = entity_create('entity_test', array('name' => $this->randomName()));
    $content_entity_2->save();

    return array(
      'config' => array(
        $config_entity_1,
        $config_entity_2,
      ),
      'content' => array(
        $content_entity_1,
        $content_entity_2,
      ),
    );
  }

}
