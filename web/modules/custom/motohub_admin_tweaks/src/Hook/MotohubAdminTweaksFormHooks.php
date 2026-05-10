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
    // Get the node being edited.
    $node = $form_state->getFormObject()->getEntity();

    // Only pre-populate for NEW nodes (not existing ones being edited).
    if ($node->isNew()) {
      // Get the garage ID from query parameters.
      $request = \Drupal::request();
      $garage_id = $request->query->get('field_garage');

      if ($garage_id && isset($form['field_garage'])) {
        $garage_node = \Drupal::entityTypeManager()
          ->getStorage('node')
          ->load($garage_id);
        
        // Pre-populate the garage reference field.
        $form['field_garage']['widget'][0]['target_id']['#default_value'] = $garage_node;
        
        // Disable the field visually.
        $form['field_garage']['#disabled'] = TRUE;
        
        $tempstore = \Drupal::service('tempstore.private')->get('motohub_admin_tweaks');
        $tempstore->set('new_mechanik_garage_id', $garage_id);
        
         // Hide unnecessary sections for inline modal form.
        $form['menu']['#access'] = FALSE;
        $form['revision_information']['#access'] = FALSE;
        $form['path']['#access'] = FALSE;
        $form['author']['#access'] = FALSE;
        $form['options']['#access'] = FALSE;

         // Hide the meta/advanced sidebar.
        if (isset($form['advanced'])) {
          $form['advanced']['#access'] = FALSE;
        }
        
        // Hide the footer/meta information.
        if (isset($form['meta'])) {
          $form['meta']['#access'] = FALSE;
        }
        
        // Also hide the revision log message field if it's standalone.
        if (isset($form['revision_log'])) {
          $form['revision_log']['#access'] = FALSE;
        }
        
        // Hide status field if visible.
        if (isset($form['status'])) {
          $form['status']['#access'] = FALSE;
        }
        
        // Add AJAX handler to close modal and redirect after save.
        $form['actions']['submit']['#ajax'] = [
          'callback' => [static::class, 'ajaxSubmitCallback'],
        ];
        
        // Store garage ID in form state for the AJAX callback.
        $form_state->set('garage_id_for_redirect', $garage_id);
      }
    }
  }

  /**
   * Implements hook_form_FORM_ID_alter() for node_mechanik_edit_form.
   */
  #[Hook('form_node_mechanik_edit_form_alter')]
  public function formNodeMechanikEditFormAlter(&$form, FormStateInterface $form_state, $form_id): void {
    // For edit forms, we don't want to override anything.
    // The existing value will be loaded automatically.
  }

}