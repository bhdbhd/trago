<?php

declare(strict_types=1);

namespace Drupal\motohub_admin_tweaks\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Entity\EntityInterface;

/**
 * Entity hooks for setting garage reference.
 */
class MotohubAdminTweaksEntityHooks {

  /**
   * Implements hook_entity_presave().
   */
  #[Hook('entity_presave')]
  public function entityPresave(EntityInterface $entity): void {
    // Only check mechanik nodes.
    if ($entity->getEntityTypeId() === 'node' && $entity->bundle() === 'mechanik') {
      
      // Check if this is a new mechanik being created from the "Add Mechanik" button.
      if ($entity->isNew()) {
        $tempstore = \Drupal::service('tempstore.private')->get('motohub_admin_tweaks');
        $garage_id = $tempstore->get('new_mechanik_garage_id');
        
        if ($garage_id) {
          // Set the garage reference field.
          $entity->set('field_garage', ['target_id' => $garage_id]);
          
          // Clear the tempstore value.
          $tempstore->delete('new_mechanik_garage_id');
        }
      }
    }
  }

}