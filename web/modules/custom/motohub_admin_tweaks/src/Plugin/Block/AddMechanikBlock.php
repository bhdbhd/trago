<?php

declare(strict_types=1);

namespace Drupal\motohub_admin_tweaks\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an 'Add Mechanik' block.
 *
 * @Block(
 *   id = "add_mechanik_block",
 *   admin_label = @Translation("Add Mechanik Button"),
 *   category = @Translation("Motohub"),
 * )
 */
class AddMechanikBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The route match service.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Constructs a new AddMechanikBlock.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match service.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    RouteMatchInterface $route_match,
    AccountProxyInterface $current_user
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->routeMatch = $route_match;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_route_match'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    // Get the current node from the route.
    $node = $this->routeMatch->getParameter('node');

    // Only show on garage nodes.
    if (!$node || $node->bundle() !== 'garage') {
      return [];
    }

    // Check permission.
    if (!$this->currentUser->hasPermission('create mechanik content')) {
      return [];
    }

    return [
      '#type' => 'link',
      '#title' => $this->t('Add Mechanik'),
      '#url' => Url::fromRoute('motohub_admin_tweaks.add_mechanik', [
        'garage' => $node->id(),
      ]),
      '#attributes' => [
        'class' => ['button', 'button--primary', 'add-mechanik-button'],
      ],
      '#cache' => [
        'contexts' => [
          'route',
          'user.permissions',
        ],
      ],
    ];
  }

}