<?php

namespace Drupal\stanford_fields\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\Url as UrlElement;
use Drupal\Core\Url;
use Drupal\link\Plugin\Field\FieldWidget\LinkWidget;
use GuzzleHttp\ClientInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

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

    // It is not yet possible to set "Featured" or "Sponsored"
    // events in the Localist UI.
    // I'm going to comment out these fields for now, but
    // if the Localist UI is updated in the future, we can
    // re-enable them easily here.
    /*
    $element['filters']['picks'] = [
    '#type' => 'checkbox',
    '#title' => $this->t('Only Show Featured'),
    '#default_value' => $query_parameters['picks'] ?? FALSE,
    ];
    $element['filters']['sponsored'] = [
    '#type' => 'checkbox',
    '#title' => $this->t('Only Show Sponsored'),
    '#default_value' => $query_parameters['sponsored'] ?? FALSE,
    ];
     */
    // End of unused form elements.
    $element['filters']['match'] = [
      '#type' => 'select',
      '#title' => $this->t('Content Must Match'),
      '#default_value' => $query_parameters['match'] ?? NULL,
      '#empty_option' => $this->t('At least one place or group, and one filter item'),
      '#options' => [
        'any' => $this->t('Any venue, group, or filter item'),
        'all' => $this->t('At least one venue and group, and all filter items'),
        'or' => $this->t('Any venue or group, and one filter item'),
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
    $filters = $this->fetchLocalistData('events/filters');
    $labels = $this->fetchLocalistData('events/labels');
    $element = [];
    foreach ($filters as $filter_key => $options) {
      $filter_options = [];

      foreach ($options as $option) {
        $filter_options[$option['id']] = $option['name'];
      }
      sort($filter_options);
      $element[$filter_key] = [
        '#type' => 'select',
        '#title' => $labels['filters'][$filter_key],
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
  protected function getGroups($default_value = NULL): array {
    $groups = $this->fetchLocalistData('groups');
    $departments = $this->fetchLocalistData('departments');
    $element = [
      '#type' => 'select',
      '#title' => $this->t('Departments/Groups'),
      '#multiple' => FALSE,
      '#options' => [],
      '#empty_option' => 'Select one:',
      '#default_value' => $default_value,
    ];
    foreach ($groups['groups'] as $group) {
      $element['#options'][$group['group']['id']] = $group['group']['name'];
    }
    foreach ($departments['departments'] as $department) {
      $element['#options'][$department['department']['id']] = $department['department']['name'];
    }
    sort($element['#options']);
    return $element;
  }

  /**
   * Get the form element for the venues selection.
   *
   * @param string $default_value
   *   Default value for the form elements.
   *
   * @return array
   *   Form element render array.
   */
  protected function getPlaces($default_value = null): array {
    $places = $this->fetchLocalistData('places');
    $element = [
      '#type' => 'select',
      '#title' => $this->t('Venues'),
      '#multiple' => FALSE,
      '#options' => [],
      '#empty_option' => '(Optional) Select one:',
      '#default_value' => $default_value,
    ];
    foreach ($places['places'] as $place) {
      $element['#options'][$place['place']['id']] = $place['place']['name'];
    }
    return $element;
  }

  /**
   * Call the localist API and return the data in array format.
   *
   * @param string $uri
   *   API endpoint.
   *
   * @return array
   *   API response data.
   *
   * @see https://developer.localist.com/doc/api
   */
  public function fetchLocalistData($uri): array {
    if ($cache = $this->cache->get("localist:$uri")) {
      return $cache->data;
    }
    try {
      $data = $this->fetchLocalistAggregatedData($uri);
    }
    catch (\Throwable $e) {
      return [];
    }
    $this->cache->set("localist:$uri", $data, time() + 60 * 60 * 24);
    return $data;
  }

  /**
   * Localist pages their responses, so fetch all pages to get all the data.
   *
   * @param string $uri
   *   API endpoint.
   * @param int $count
   *   The number of items per page to return.
   * @param int $page
   *   The page of data to return.
   *
   * @return array
   *   API response data.
   */
  protected function fetchLocalistPage(string $uri, int $count = 100, int $page = 1): array {
    try {
      $response = $this->client->request('GET', "/api/2/$uri?pp=$count&page=$page", ['base_uri' => $this->getSetting('base_url')]);
      return json_decode((string) $response->getBody(), TRUE);
    }
    catch (\Throwable $e) {
      return [];
    }
  }

  /**
   * Make API call, if multiple pages, coalate all remaining pages.
   *
   * @param string $uri
   *   API endpoint.
   *
   * @return array
   *   API response data.
   */
  protected function fetchLocalistAggregatedData(string $uri): array {
    $response = $this->fetchLocalistPage($uri, 100, 1);
    if (!array_key_exists('page', $response)) {
      // Only one page, go ahead and return it and we're done.
      return $response;
    }
    // We have more than one page of data to parse.
    $current_page = 1;
    $total_pages = (int) $response['page']['total'];
    // We have our first page of data already.
    unset($response['page']);
    $aggregated_data = $response;

    while ($current_page <= $total_pages) {
      $current_page++;
      $response = $this->fetchLocalistPage($uri, 100, $current_page);
      // Get rid of the page element.
      unset($response['page']);
      // Merge the remaining elements into our $aggregated_data.
      foreach (array_keys($aggregated_data) as $key) {
        $aggregated_data[$key] = array_merge($aggregated_data[$key], $response[$key]);
      }
    }
    // Return all the pages combined.
    return $aggregated_data;
  }

}
