<?php

namespace Drupal\limited_download\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\State\StateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure Limited Download settings using FormBase.
 */
class LimitedDownloadSettingsForm extends FormBase {

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Constructs a SimpleForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, StateInterface $state) {
    $this->configFactory = $config_factory;
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('state')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'limited_download_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->configFactory->get('limited_download.settings');
    $current_count = $this->state->get('limited_download_count', 0);

    // Attach JavaScript library
    $form['#attached']['library'][] = 'limited_download/admin';

    $form['secret_key_wrapper'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Secret Key Configuration'),
    ];

    $form['secret_key_wrapper']['secret_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Secret Key'),
      '#description' => $this->t('The secret key that users need to access the download. This will be part of the URL: /special-download/SECRET_KEY'),
      '#default_value' => $config->get('secret_key') ?: 'your_forum_secret_123',
      '#required' => TRUE,
      '#maxlength' => 255,
    ];

    $form['secret_key_wrapper']['generate_key'] = [
      '#type' => 'button',
      '#value' => $this->t('Generate Random Key'),
      '#ajax' => [
        'callback' => '::generateRandomKeyCallback',
        'wrapper' => 'ajax-url-wrapper',
        'effect' => 'fade',
      ],
    ];

    $form['secret_key_wrapper']['current_url'] = [
      '#type' => 'item',
      '#title' => $this->t('Current Download URL'),
      '#prefix' => '<div id="ajax-url-wrapper">',
      '#suffix' => '</div>',
      '#markup' => '<code>' . 
        \Drupal::request()->getSchemeAndHttpHost() . '/special-download/' . 
        ($config->get('secret_key') ?: 'your_forum_secret_123') . '</code>',
    ];

    $form['download_limit'] = [
      '#type' => 'number',
      '#title' => $this->t('Download Limit'),
      '#description' => $this->t('Maximum number of downloads allowed.'),
      '#default_value' => $config->get('download_limit') ?: 100,
      '#required' => TRUE,
      '#min' => 1,
    ];

    $form['file_path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('File Path'),
      '#description' => $this->t('Absolute path to the file to be downloaded. Example: /var/www/html/sites/default/files/myfile.pdf'),
      '#default_value' => $config->get('file_path'),
      '#required' => TRUE,
      '#maxlength' => 500,
    ];

    $form['status'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Current Status'),
    ];

    $form['status']['info'] = [
      '#markup' => '<p><strong>Downloads used:</strong> ' . $current_count . ' / ' . ($config->get('download_limit') ?: 100) . '</p>',
    ];

    if ($current_count > 0) {
      $form['reset'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Reset Counter'),
      ];

      $form['reset']['reset_counter'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Reset download counter to 0'),
        '#description' => $this->t('Check this box to reset the download counter. This will allow downloads to resume.'),
      ];
    }

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save configuration'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * AJAX callback for generating random key.
   */
  public function generateRandomKeyCallback(array &$form, FormStateInterface $form_state) {
    // Generate a random 32-character hash
    $random_key = bin2hex(random_bytes(16));
    
    // Set the new value in the form state
    $form_state->setValue('secret_key', $random_key);
    
    // Update the form element value
    $form['secret_key_wrapper']['secret_key']['#value'] = $random_key;
    
    // Update just the URL display element
    $base_url = \Drupal::request()->getSchemeAndHttpHost();
    $form['secret_key_wrapper']['current_url']['#markup'] = '<code>' . 
      $base_url . '/special-download/' . $random_key . '</code>';
    
    // Add a status message
    $this->messenger()->addStatus($this->t('New random key generated: @key', ['@key' => $random_key]));
    
    // Return only the URL wrapper element to prevent nesting
    return $form['secret_key_wrapper']['current_url'];
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $file_path = $form_state->getValue('file_path');
    if (!empty($file_path) && !file_exists($file_path)) {
      $form_state->setErrorByName('file_path', $this->t('The specified file does not exist.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->configFactory->getEditable('limited_download.settings');
    
    $config->set('secret_key', $form_state->getValue('secret_key'));
    $config->set('download_limit', $form_state->getValue('download_limit'));
    $config->set('file_path', $form_state->getValue('file_path'));
    $config->save();

    if ($form_state->getValue('reset_counter')) {
      $this->state->set('limited_download_count', 0);
      $this->messenger()->addMessage($this->t('Download counter has been reset to 0.'));
    }

    $this->messenger()->addStatus($this->t('The configuration options have been saved.'));
  }

}