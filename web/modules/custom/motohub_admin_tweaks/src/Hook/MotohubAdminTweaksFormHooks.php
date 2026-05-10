<?php

declare(strict_types=1);

namespace Drupal\motohub_admin_tweaks\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form hooks for pre-populating Mechanik with Garage reference.
 */
class MotohubAdminTweaksFormHooks {

  /**
   * Implements hook_form_FORM_ID_alter() for node_mechanik_form.
   */
  #[Hook('form_node_mechanik_form_alter')]
  public function formNodeMechanikFormAlter(&$form, FormStateInterface $form_state, $form_id): void {
    // Get the garage ID from query parameters.
    $request = \Drupal::request();
    $garage_id = $request->query->get('field_garage');

    if ($garage_id && isset($form['field_garage'])) {
      // Pre-populate the garage reference field.
      $form['field_garage']['widget'][0]['target_id']['#default_value'] = \Drupal::entityTypeManager()
        ->getStorage('node')
        ->load($garage_id);
      
      // Optionally make it read-only so users can't change it.
      $form['field_garage']['#disabled'] = TRUE;
    }
  }

  /**
   * Implements hook_form_FORM_ID_alter() for node_mechanik_edit_form.
   */
  #[Hook('form_node_mechanik_edit_form_alter')]
  public function formNodeMechanikEditFormAlter(&$form, FormStateInterface $form_state, $form_id): void {
    // Reuse the same logic for edit form.
    $this->formNodeMechanikFormAlter($form, $form_state, $form_id);
  }

}