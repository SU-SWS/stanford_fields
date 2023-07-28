<?php

namespace Drupal\stanford_fields\Plugin\Condition;

use Drupal\Core\Condition\ConditionPluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/**
 * Provides a 'Response code' condition.
 *
 * @Condition(
 *   id = "response_code",
 *   label = @Translation("Response code"),
 * )
 */
class ResponseCodeCondition extends ConditionPluginBase implements ContainerFactoryPluginInterface {

  /**
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  private $requestStack;

  /**
   * Creates a new Response code instance.
   *
   * @param array $configuration
   *   The plugin configuration, i.e. an array with configuration values keyed
   *   by configuration option name. The special key 'context' may be used to
   *   initialize the defined contexts by setting it to an array of context
   *   values keyed by context names.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RequestStack $requestStack) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->requestStack = $requestStack;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('request_stack')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {

    $form['response_codes'] = [
      '#title' => $this->t('Response codes'),
      '#default_value' => $this->configuration['response_codes'],
      '#description' => $this->t('Specify response codes. Enter one per line. This only works for 4xx response codes.'),
      '#type' => 'textarea',
    ];

    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['response_codes'] = $form_state->getValue('response_codes');
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {
    $response_codes = array_map('trim', explode("\n", $this->configuration['response_codes']));
    $response_codes = implode(', ', $response_codes);
    if (!empty($this->configuration['negate'])) {
      return $this->t('Do not return true on the following response codes: @response_codes', ['@response_codes' => $response_codes]);
    }
    return $this->t('Return true on the following response codes: @response_codes', ['@response_codes' => $response_codes]);
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate() {
    $response_code_list = $this->configuration['response_codes'];
    if (!$response_code_list) {
      return TRUE;
    }

    if (
      !$this->requestStack->getCurrentRequest()->attributes->has('exception') ||
      !($this->requestStack->getCurrentRequest()->attributes->get('exception') instanceof HttpExceptionInterface)
    ) {
      return FALSE;
    }

    $response_code = $this->requestStack->getCurrentRequest()->attributes->get('exception')->getStatusCode();
    $config_response_codes = explode("\n", $response_code_list);

    foreach ($config_response_codes as $config_response_code) {
      if ($response_code == trim($config_response_code)) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return ['response_codes' => ''] + parent::defaultConfiguration();
  }

}
