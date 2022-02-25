<?php

namespace Drupal\fico_taxonomy_condition\Plugin\Field\FieldFormatter\Condition;

use Drupal\fico\Plugin\FieldFormatterConditionBase;

/**
 * The plugin for check empty fields.
 *
 * @FieldFormatterCondition(
 *   id = "hide_no_taxonomy_value",
 *   label = @Translation("Hide when taxonomy value does not match a string"),
 *   dsFields = TRUE,
 *   types = {
 *     "all"
 *   }
 * )
 */
class HideNoTaxonomyValue extends FieldFormatterConditionBase {

  /**
   * {@inheritdoc}
   */
  public function alterForm(&$form, $settings) {
    $options = [];
    $entityFieldManager = \Drupal::service('entity_field.manager');
    $fields = $entityFieldManager->getFieldDefinitions($settings['entity_type'], $settings['bundle']);
    $allowed_field_types = ['entity_reference'];
    $not_field_types = [
      'moderation_state',
      'revision_log',
    ];
    foreach ($fields as $field_name => $field) {
      if ($field_name != $settings['field_name'] && in_array($field->getType(), $allowed_field_types) && !in_array($field_name, $not_field_types)) {
        $options[$field_name] = $field->getLabel();
      }
    }
    $default_target = isset($settings['settings']['target_field']) ? $settings['settings']['target_field'] : NULL;
    $default_string = isset($settings['settings']['string']) ? $settings['settings']['string'] : NULL;
    $default_single = isset($settings['settings']['single']) ? $settings['settings']['single'] : NULL;
    $default_case_sensitive = isset($settings['settings']['case_sensitive']) ? $settings['settings']['case_sensitive'] : NULL;
    $form['target_field'] = [
      '#type' => 'select',
      '#title' => t('Select target field'),
      '#options' => $options,
      '#default_value' => $default_target,
    ];
    $form['string'] = [
      '#type' => 'textfield',
      '#title' => t('Enter target string'),
      '#default_value' => $default_string,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function access(&$build, $field, $settings) {
    # If we cannot load the entity, don't show!
    if (!($entity = $this->getEntity($build))) {
      $build[$field]['#access'] = FALSE;
      return;
    }

    $items = $entity->get($settings['settings']['target_field']);

    # If we find any term
    $build[$field]['#access'] = FALSE;
    foreach ($items as $key => $item) {
      $target_id = $item->getValue()["target_id"];
      $term = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($target_id);
      if ($term) {
        $applied_media_use = $term->getName();
        $selected_value = $settings['settings']['string'];
        if (strtolower($applied_media_use) == strtolower($selected_value)) {
          $build[$field]['#access'] = TRUE;
          break;
        }
      }
    }

  }

  /**
   * {@inheritdoc}
   */
  public function summary($settings) {
    $options = [];
    $entityFieldManager = \Drupal::service('entity_field.manager');
    $fields = $entityFieldManager->getFieldDefinitions($settings['entity_type'], $settings['bundle']);
    #$allowed_field_types = fico_text_types();
    $allowed_field_types = ['entity_reference'];
    $not_field_types = [
      'moderation_state',
      'revision_log',
    ];
    foreach ($fields as $field_name => $field) {
      if ($field_name != $settings['field_name'] && in_array($field->getType(), $allowed_field_types) && !in_array($field_name, $not_field_types)) {
        $options[$field_name] = $field->getLabel();
      }
    }

    return t('Condition: %condition (%field = "%string")', [
      "%condition" => t('Hide when taxonomy value does not match a string'),
      '%field' => $options[$settings['settings']['target_field']],
      '%string' => $settings['settings']['string'],
    ]);
  }

}
