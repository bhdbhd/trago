<?php

declare(strict_types=1);

namespace Drupal\motohub_admin_tweaks\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Url;

/**
 * Node hooks for adding Mechanik button.
 */
class MotohubAdminTweaksNodeHooks {

  /**
   * Implements hook_node_view().
   */
//   #[Hook('node_view')]
//   public function nodeView(array &$build, $entity, $display, $view_mode): void {
//     // Only add button to garage nodes in full view mode.
//     if ($entity->bundle() === 'garage' && $view_mode === 'full') {
//       // Check if current user has permission to create mechanik content.
//       if (\Drupal::currentUser()->hasPermission('create mechanik content')) {
//         $build['add_mechanik_button'] = [
//           '#type' => 'link',
//           '#title' => t('Add Mechanik'),
//           '#url' => Url::fromRoute('motohub_admin_tweaks.add_mechanik', [
//             'garage' => $entity->id(),
//           ]),
//           '#attributes' => [
//             'class' => ['button', 'button--primary', 'add-mechanik-button'],
//           ],
//           '#weight' => 100,
//         ];
//       }
//     }
//   }

}