<?php

namespace Drupal\keyed_download\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\State\StateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Controller for handling keyed downloads.
 */
class DownloadController extends ControllerBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Constructs a DownloadController object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, StateInterface $state) {
    $this->entityTypeManager = $entity_type_manager;
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('state')
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
    // Find the node with this secret key.
    $node_storage = $this->entityTypeManager->getStorage('node');
    $query = $node_storage->getQuery()
      ->condition('type', 'keyed_download')
      ->condition('status', 1) // Published nodes only
      ->condition('field_secret_key', $secret_key)
      ->condition('field_status', 1) // Active downloads only
      ->accessCheck(FALSE)
      ->range(0, 1);
    
    $nids = $query->execute();
    
    if (empty($nids)) {
      throw new NotFoundHttpException('Invalid download key.');
    }

    $node = $node_storage->load(reset($nids));
    
    if (!$node) {
      throw new NotFoundHttpException('Download not found.');
    }

    // Get download settings from the node.
    $download_limit = $node->get('field_download_limit')->value ?: 1;
    $current_count = $node->get('field_download_count')->value ?: 0;
    $media_entity = $node->get('field_file')->entity;

    // Check if limit has been reached.
    if ($current_count >= $download_limit) {
      return [
        '#markup' => '<div class="messages messages--error">
          <h2>Download Limit Reached</h2>
          <p>Sorry, the download limit of ' . $download_limit . ' for "' . $node->getTitle() . '" has been reached.</p>
        </div>',
      ];
    }

    // Get client IP address.
    $client_ip = \Drupal::request()->getClientIp();
    
    // Check if this IP has already downloaded from this specific node.
    $ip_key = 'keyed_download_ips_' . $node->id();
    $downloaded_ips = $this->state->get($ip_key, []);
    
    if (in_array($client_ip, $downloaded_ips)) {
      return [
        '#markup' => '<div class="messages messages--error">
          <h2>Already Downloaded</h2>
          <p>Your IP address (' . $client_ip . ') has already downloaded "' . $node->getTitle() . '". Each IP address can only download once.</p>
        </div>',
      ];
    }

    // Check if media entity and file exist.
    if (!$media_entity) {
      return [
        '#markup' => '<div class="messages messages--error">
          <h2>File Not Found</h2>
          <p>No file is associated with this download.</p>
        </div>',
      ];
    }

    // Get the actual file from the media entity.
    $file_field = $media_entity->get('field_media_document');
    if ($file_field->isEmpty()) {
      return [
        '#markup' => '<div class="messages messages--error">
          <h2>File Not Found</h2>
          <p>The file is not available for download.</p>
        </div>',
      ];
    }

    $file_entity = $file_field->entity;
    if (!$file_entity) {
      return [
        '#markup' => '<div class="messages messages--error">
          <h2>File Not Found</h2>
          <p>The download file is not available.</p>
        </div>',
      ];
    }

    $file_uri = $file_entity->getFileUri();
    $file_path = \Drupal::service('file_system')->realpath($file_uri);

    if (!file_exists($file_path)) {
      return [
        '#markup' => '<div class="messages messages--error">
          <h2>File Not Found</h2>
          <p>The physical file does not exist on the server.</p>
        </div>',
      ];
    }

    // Add IP to downloaded list for this node.
    $downloaded_ips[] = $client_ip;
    $this->state->set($ip_key, $downloaded_ips);

    // Increment the download counter on the node.
    $node->set('field_download_count', $current_count + 1);
    $node->save();

    // Log the download.
    \Drupal::logger('keyed_download')->info('Download "@title" (#@count/@limit) served to IP @ip', [
      '@title' => $node->getTitle(),
      '@count' => $current_count + 1,
      '@limit' => $download_limit,
      '@ip' => $client_ip,
    ]);

    // Serve the file.
    $response = new BinaryFileResponse($file_path);
    $response->setContentDisposition(
      ResponseHeaderBag::DISPOSITION_ATTACHMENT,
      $file_entity->getFilename()
    );

    return $response;
  }

}