<?php

declare(strict_types=1);

namespace Drupal\motohub_admin_tweaks\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\NodeInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Controller for Mechanik operations.
 */
class MechanikController extends ControllerBase {

  /**
   * Redirect to node add form with garage reference pre-filled.
   */
  public function addMechanik(NodeInterface $garage): RedirectResponse {
    // Verify it's actually a garage node.
    if ($garage->bundle() !== 'garage') {
      $this->messenger()->addError($this->t('Invalid garage.'));
      return $this->redirect('<front>');
    }

    // Redirect to the mechanik node add form with the garage ID and destination.
    $url = \Drupal\Core\Url::fromRoute('node.add', [
      'node_type' => 'mechanik',
    ], [
      'query' => [
        'field_garage' => $garage->id(),
        'destination' => '/node/' . $garage->id(),
      ],
    ]);

    return new RedirectResponse($url->toString());
  }

}