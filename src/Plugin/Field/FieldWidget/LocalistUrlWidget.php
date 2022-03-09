<?php

namespace Drupal\stanford_fields\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\Url as UrlElement;
use Drupal\Core\Url;
use Drupal\link\Plugin\Field\FieldWidget\LinkWidget;
use GuzzleHttp\ClientInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use GuzzleHttp\Promise\Utils;

/**
 * Plugin implementation of the 'localist_url' widget.
 *
 * @FieldWidget(
 *   id = "localist_url",
 *   label = @Translation("Localist"),
 *   field_types = {
 *     "link"
 *   }
 * )
 */
class LocalistUrlWidget extends LinkWidget {

  /**
   * Http Client Service.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $client;

  /**
   * Caching service.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * API data from Localist.
   *
   * @var array
   */
  protected $apiData = [];

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('http_client'),
      $container->get('cache.default')
    );
  }

  /**
   * {@inheritDoc}
   */
  public static function defaultSettings() {
    $settings = [
      'base_url' => '',
      'select_distinct' => FALSE,
    ];
    return $settings + parent::defaultSettings();
  }

  /**
   * {@inheritDoc}
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, ClientInterface $client, CacheBackendInterface $cache) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->client = $client;
    $this->cache = $cache;
  }

  /**
   * {@inheritDoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);
    $elements['placeholder_url']['#access'] = FALSE;
    $elements['placeholder_title']['#access'] = FALSE;
    $elements['select_distinct'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Select Distinct'),
      '#default_value' => $this->getSetting('select_distinct'),
    ];
    $elements['base_url'] = [
      '#type' => 'url',
      '#title' => $this->t('Base localist domain'),
      '#required' => TRUE,
      '#default_value' => $this->getSetting('base_url'),
      '#element_validate' => [
        [UrlElement::class, 'validateUrl'],
        [$this, 'validateUrl'],
      ],
    ];
    return $elements;
  }

  /**
   * Validate the given domain has a localist API response.
   *
   * @param array $element
   *   Url form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Current form state object.
   * @param array $complete_form
   *   Complete form.
   */
  public function validateUrl(array &$element, FormStateInterface $form_state, array &$complete_form) {
    $input = NestedArray::getValue($form_state->getValues(), $element['#parents']);
    if ($form_state::hasAnyErrors()) {
      return;
    }
    try {
      $response = $this->client->request('GET', '/api/2/events', ['base_uri' => $input]);
      $response = json_decode((string) $response->getBody(), TRUE);

      if (!is_array($response)) {
        throw new \Exception('Invalid response');
      }
    }
    catch (\Throwable $e) {
      $form_state->setError($element, $this->t('URL is not a Localist domain.'));
    }
  }

  /**
   * {@inheritDoc}
   */
  public function settingsSummary() {
    $summary = [];
    if (empty($this->getSetting('base_url'))) {
      $summary[] = $this->t('No Base URL Provided');
    }
    else {
      $summary[] = $this->t('Base URL: @url', ['@url' => $this->getSetting('base_url')]);
    }
    return $summary;
  }

  /**
   * {@inheritDoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    $element['uri']['#access'] = FALSE;
    $element['title']['#access'] = FALSE;
    $element['attributes']['#access'] = FALSE;
    $item = $items[$delta];

    $element['filters'] = [
      '#type' => 'details',
      '#title' => $this->t('Filters'),
      '#open' => TRUE,
      '#collapsible' => FALSE,
    ];
    $query_parameters = [];
    if ($item->uri) {
      parse_str(parse_url(urldecode($item->uri), PHP_URL_QUERY), $query_parameters);
    }

    $element['filters']['group_id'] = $this->getGroups($query_parameters['group_id'] ?? NULL);
    $element['filters']['venue_id'] = $this->getPlaces($query_parameters['venue_id'] ?? NULL);
    $element['filters']['type'] = $this->getFilters($query_parameters['type'] ?? []);

    $element['filters']['match'] = [
      '#type' => 'select',
      '#title' => $this->t('Content Must Match'),
      '#default_value' => $query_parameters['match'] ?? NULL,
      '#empty_option' => $this->t('At least one selected group or venue, and one selected filter item'),
      '#options' => [
        'any' => $this->t('Any selected group, venue, or filter item'),
        'all' => $this->t('At least one selected group or venue, and all selected filter items'),
        'or' => $this->t('Any selected group or venue, and one selected filter item'),
      ],
    ];

    return $element;
  }

  /**
   * {@inheritDoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {

    foreach ($values as $delta => &$value) {

      foreach ($value['filters'] as &$filter_values) {
        if (is_array($filter_values)) {
          $filter_values = self::flattenValues($filter_values);
        }
      }

      $value['filters'] = array_filter($value['filters']);

      if (empty($value['filters'])) {
        unset($values[$delta]);
        continue;
      }

      $value['filters']['days'] = '365';
      $value['filters']['pp'] = '100';

      // We may in the future have a configuration value
      // to include the "distinct"key to our API call.
      // This tries to find such a value,
      // and applies the key if it finds it.
      if ($this->getSetting('select_distinct')) {
        $value['filters']['distinct'] = TRUE;
      }

      $value['uri'] = Url::fromUri(rtrim($this->getSetting('base_url'), '/') . '/api/2/events', ['query' => $value['filters']])
        ->toString();

    }
    return parent::massageFormValues($values, $form, $form_state);
  }

  /**
   * Flatten a multidimensional array.
   *
   * @param array $array
   *   The array to flatten.
   *
   * @return array
   *   Flattened array.
   */
  protected static function flattenValues(array $array): array {
    $return = [];
    array_walk_recursive($array, function ($a) use (&$return) {
      $return[] = $a;
    });
    return $return;
  }

  /**
   * Get the form element with the filters from localist.
   *
   * @param array $default_value
   *   Default value for the form elements.
   *
   * @return array
   *   Form element render array.
   */
  protected function getFilters(array $default_value = []): array {
    $this->getApiData();

    $element = [];
    foreach ($this->apiData['events/filters'] as $filter_key => $options) {
      $filter_options = [];

      foreach ($options as $option) {
        $filter_options[$option['id']] = $option['name'];
      }
      asort($filter_options);
      $element[$filter_key] = [
        '#type' => 'select',
        '#title' => $this->apiData['events/labels']['filters'][$filter_key],
        '#multiple' => TRUE,
        '#options' => $filter_options,
        '#default_value' => array_intersect($default_value, array_keys($filter_options)),
        '#chosen' => TRUE,
      ];
    }
    return $element;
  }

  /**
   * Gets groups and departments.
   *
   * @param string|null $default_value
   *   Default value for the form elements.
   *
   * @return array
   *   Form element render array.
   */
  protected function getGroups(?string $default_value = NULL): array {
    $this->getApiData();
    $element = [
      '#type' => 'select',
      '#title' => $this->t('Departments/Groups'),
      '#multiple' => FALSE,
      '#options' => [],
      '#empty_option' => 'Select one:',
      '#default_value' => $default_value,
    ];
    foreach ($this->apiData['groups'] as $group) {
      $element['#options'][$group['group']['id']] = $group['group']['name'];
    }
    foreach ($this->apiData['departments'] as $department) {
      $element['#options'][$department['department']['id']] = $department['department']['name'];
    }
    asort($element['#options']);
    return $element;
  }

  /**
   * Get the form element for the venues selection.
   *
   * @param string|null $default_value
   *   Default value for the form element.
   *
   * @return array
   *   Form element render array.
   */
  protected function getPlaces(?string $default_value = NULL): array {
    $this->getApiData();
    $element = [
      '#type' => 'select',
      '#title' => $this->t('Venues'),
      '#multiple' => FALSE,
      '#options' => [],
      '#empty_option' => 'Select one:',
      '#default_value' => $default_value,
    ];
    foreach ($this->apiData['places'] as $place) {
      $element['#options'][$place['place']['id']] = $place['place']['name'];
    }
    return $element;
  }

  /**
   * Get the data from the localist API.
   *
   * @return array
   *   API Data.
   */
  protected function getApiData(): array {
    // Data was already fetched.
    if ($this->apiData) {
      return $this->apiData;
    }

    $base_url = $this->getSetting('base_url');

    // Check for some cached data before we fetch it all again.
    if ($cache = $this->cache->get("localist_api:$base_url")) {
      $this->apiData = $cache->data;
      return $this->apiData;
    }

    return $this->fetchApiData();
  }

  /**
   * Call the Localist API with various endpoints to gather all the data needed.
   *
   * @return array
   *   Keyed array of api data.
   */
  protected function fetchApiData(): array {
    $base_url = $this->getSetting('base_url');
    $options = ['base_uri' => $base_url, 'query' => ['pp' => 1]];
    $promises = [
      'groups' => $this->client->requestAsync('GET', '/api/2/groups', $options),
      'departments' => $this->client->requestAsync('GET', '/api/2/departments', $options),
      'places' => $this->client->requestAsync('GET', '/api/2/places', $options),
      'events/filters' => $this->client->requestAsync('GET', '/api/2/events/filters', $options),
      'events/labels' => $this->client->requestAsync('GET', '/api/2/events/labels', $options),
    ];
    $results = self::unwrapAsyncRequests($promises);

    foreach ($results as $key => $response) {
      if (empty($response['page']['total'])) {
        $this->apiData[$key] = $response;
        continue;
      }

      $this->apiData[$key] = $this->fetchPagedApiData($key, $response['page']['total']);
    }
    $this->cache->set("localist_api:$base_url", $this->apiData, Cache::PERMANENT, ['localist_api']);
    return $this->apiData;
  }

  /**
   * Given the endpoint and count, async fetch from the API all pages.
   *
   * @param string $endpoint
   *   Localist API Endpoint.
   * @param int $total_count
   *   Total number of items to chunk up.
   *
   * @return array
   *   Indexed array of api data.
   */
  protected function fetchPagedApiData($endpoint, $total_count): array {
    $base_url = $this->getSetting('base_url');
    $options = ['base_uri' => $base_url, 'query' => ['pp' => 100]];

    $number_of_pages = ceil($total_count / 100);
    for ($i = 1; $i <= $number_of_pages; $i++) {
      $options['query']['page'] = $i;
      $paged_data[$i] = $this->client->requestAsync('GET', '/api/2/' . $endpoint, $options);
    }
    $paged_data = self::unwrapAsyncRequests($paged_data);

    $data = [];
    foreach ($paged_data as $page) {
      unset($page['page']);
      $key = key($page);
      $data = array_merge($data, $page[$key]);
    }
    return $data;
  }

  /**
   * Unwrap async promises and decode their body data.
   *
   * @param \GuzzleHttp\Promise\PromiseInterface[] $promises
   *   Associative array of Guzzle promises.
   *
   * @return array
   *   Associative array of json decoded data.
   */
  protected static function unwrapAsyncRequests(array $promises): array {
    $promises = Utils::unwrap($promises);
    /** @var \GuzzleHttp\Psr7\Response $response */
    foreach ($promises as &$response) {
      $response = json_decode((string) $response->getBody(), TRUE);
    }

    return $promises;
  }

}
