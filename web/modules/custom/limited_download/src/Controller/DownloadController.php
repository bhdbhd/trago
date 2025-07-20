<?php

namespace Drupal\limited_download\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Controller for handling limited downloads.
 */
class DownloadController extends ControllerBase {

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a DownloadController object.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(StateInterface $state, ConfigFactoryInterface $config_factory) {
    $this->state = $state;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('state'),
      $container->get('config.factory')
    );
  }

  /**
   * Handles the download page.
   *
   * @param string $secret_key
   *   The secret key provided in the URL.
   *
   * @return array|\Symfony\Component\HttpFoundation\BinaryFileResponse
   *   The download response or error page.
   */
  public function downloadPage($secret_key) {
    $config = $this->configFactory->get('limited_download.settings');
    $valid_secret = $config->get('secret_key') ?: 'your_forum_secret_123';
    $download_limit = $config->get('download_limit') ?: 100;
    $file_path = $config->get('file_path');

    // Check if secret key is valid.
    if ($secret_key !== $valid_secret) {
      throw new AccessDeniedHttpException('Invalid secret key.');
    }

    // Get current download count.
    $current_count = $this->state->get('limited_download_count', 0);

    // Check if limit has been reached.
    if ($current_count >= $download_limit) {
      return [
        '#markup' => '<div class="messages messages--error">
          <h2>Download Limit Reached</h2>
          <p>Sorry, the download limit of ' . $download_limit . ' has been reached. No more downloads are available.</p>
        </div>',
      ];
    }

    // Check if file exists.
    if (!$file_path || !file_exists($file_path)) {
      return [
        '#markup' => '<div class="messages messages--error">
          <h2>File Not Found</h2>
          <p>The download file is not available. Please contact the administrator.</p>
        </div>',
      ];
    }

    // Increment the download counter.
    $this->state->set('limited_download_count', $current_count + 1);

    // Log the download.
    \Drupal::logger('limited_download')->info('Download #@count served to IP @ip', [
      '@count' => $current_count + 1,
      '@ip' => \Drupal::request()->getClientIp(),
    ]);

    // Serve the file.
    $response = new BinaryFileResponse($file_path);
    $response->setContentDisposition(
      ResponseHeaderBag::DISPOSITION_ATTACHMENT,
      basename($file_path)
    );

    return $response;
  }

  /**
   * Shows download status.
   *
   * @return array
   *   Render array for status page.
   */
  public function statusPage() {
    $config = $this->configFactory->get('limited_download.settings');
    $download_limit = $config->get('download_limit') ?: 100;
    $current_count = $this->state->get('limited_download_count', 0);
    $remaining = max(0, $download_limit - $current_count);

    return [
      '#markup' => '<div class="limited-download-status">
        <h2>Download Status</h2>
        <p><strong>Downloads used:</strong> ' . $current_count . ' / ' . $download_limit . '</p>
        <p><strong>Downloads remaining:</strong> ' . $remaining . '</p>
        <div class="progress">
          <div class="progress-bar" style="width: ' . ($current_count / $download_limit * 100) . '%; background-color: #0073aa; height: 20px;"></div>
        </div>
      </div>',
    ];
  }

}