<?php

namespace Drupal\Tests\stanford_fields\Kernel\Plugin\Field\FieldWidget;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Form\FormState;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\Entity\Node;
use Drupal\stanford_fields\Plugin\Field\FieldWidget\LocalistUrlWidget;
use Drupal\Tests\stanford_fields\Kernel\StanfordFieldKernelTestBase;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Class LocalistUrlWidgetTest.
 *
 * @group
 * @coversDefaultClass \Drupal\stanford_fields\Plugin\Field\FieldWidget\LocalistUrlWidget
 */
class LocalistUrlWidgetTest extends StanfordFieldKernelTestBase {

  /**
   * Tell the guzzle mock service to throw an error.
   *
   * @var bool
   */
  protected $throwGuzzleError = FALSE;

  /**
   * {@inheritDoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $field_storage = FieldStorageConfig::create([
      'field_name' => 'su_localist_url',
      'entity_type' => 'node',
      'type' => 'link',
      'cardinality' => -1,
    ]);
    $field_storage->save();

    $field = FieldConfig::create([
      'field_name' => 'su_localist_url',
      'entity_type' => 'node',
      'bundle' => 'page',
    ]);
    $field->save();

    /** @var \Drupal\Core\Entity\Display\EntityFormDisplayInterface $entity_form_display */
    $entity_form_display = EntityFormDisplay::create([
      'targetEntityType' => 'node',
      'bundle' => 'page',
      'mode' => 'default',
      'status' => TRUE,
    ]);
    $entity_form_display->setComponent('su_localist_url', [
      'type' => 'localist_url',
      'settings' => [
        'base_url' => 'https://events.stanford.edu',
      ],
    ])->removeComponent('created')->save();

    $guzzle_client = $this->createMock(ClientInterface::class);
    $guzzle_client->method('requestAsync')
      ->will($this->returnCallback([$this, 'requestAsyncCallback']));

    \Drupal::getContainer()->set('http_client', $guzzle_client);
  }

  /**
   * Test the entity form is displayed correctly.
   */
  public function testWidgetForm() {
    $node = Node::create([
      'type' => 'page',
    ]);


    $node->set('su_localist_url', [
      [
        'uri' => 'https://events.stanford.edu/api/2/events?group_id=37955145294460&days=365',
        'title' => '',
        'options' => '',
      ],
    ]);

    $form = [];
    $form_state = new FormState();
    $entity_form_display = EntityFormDisplay::load('node.page.default');
    $entity_form_display->buildForm($node, $form, $form_state);

    // Build the form twice to set up cache and check the cache.
    $entity_form_display = EntityFormDisplay::load('node.page.default');
    $entity_form_display->buildForm($node, $form, $form_state);

    $widget_value = $form['su_localist_url']['widget'];

    $this->assertIsArray($widget_value[0]);
    $this->assertEquals('https://events.stanford.edu/api/2/events?group_id=37955145294460&days=365', $widget_value[0]['uri']['#default_value']);
    $this->assertEquals('details', $widget_value[0]['filters']['#type']);
    $this->assertIsArray($widget_value[0]['filters']['type']['event_audience']);
    $this->assertIsArray($widget_value[0]['filters']['type']['event_audience']['#options']);
    $this->assertContains('Students', $widget_value[0]['filters']['type']['event_audience']['#options']);
    $this->assertContains('Everyone', $widget_value[0]['filters']['type']['event_audience']['#options']);
    $this->assertNotEmpty($widget_value[0]['filters']['type']['event_subject']['#options']);
    $this->assertNotEmpty($widget_value[0]['filters']['type']['event_types']['#options']);
    $this->assertNotEmpty($widget_value[0]['filters']['group_id']);
    $this->assertFalse($widget_value[0]['filters']['group_id']['#multiple']);
    $this->assertIsArray($widget_value[0]['filters']['venue_id']);
    $this->assertFalse($widget_value[0]['filters']['venue_id']['#multiple']);


    $this->throwGuzzleError = true;

    \Drupal::cache()->delete('localist_api:https://events.stanford.edu');
    $form = [];
    $form_state = new FormState();
    $entity_form_display = EntityFormDisplay::load('node.page.default');
    $entity_form_display->buildForm($node, $form, $form_state);
    $widget_value = $form['su_localist_url']['widget'];

    $this->assertIsArray($widget_value[0]['filters']['venue_id']);
    $this->assertEmpty($widget_value[0]['filters']['venue_id']['#options']);

    \Drupal::cache()->set('localist_api:https://events.stanford.edu', [
      'data' => ['places' => [['place' => ['id' => 12345, 'name' => 'foobar']]]],
      'expires' => time() - 500,
    ]);
    $form = [];
    $form_state = new FormState();
    $entity_form_display = EntityFormDisplay::load('node.page.default');
    $entity_form_display->buildForm($node, $form, $form_state);
    $widget_value = $form['su_localist_url']['widget'];

    $this->assertArrayHasKey(12345, $widget_value[0]['filters']['venue_id']['#options']);
  }

  /**
   * Test the settings form and the summary.
   */
  public function testSettingsForm() {

    $field_def = $this->createMock(FieldDefinitionInterface::class);
    $config = [
      'field_definition' => $field_def,
      'settings' => [],
      'third_party_settings' => [],
    ];
    $definition = [];
    $widget = LocalistUrlWidget::create(\Drupal::getContainer(), $config, '', $definition);

    $summary = $widget->settingsSummary();
    $this->assertCount(1, $summary);
    $this->assertEquals('No Base URL Provided', (string) $summary[0]);

    $config = [
      'field_definition' => $field_def,
      'settings' => [
        'base_url' => 'https://events.stanford.edu',
      ],
      'third_party_settings' => [],
    ];

    $widget = LocalistUrlWidget::create(\Drupal::getContainer(), $config, '', $definition);
    $summary = $widget->settingsSummary();
    $this->assertCount(1, $summary);
    $this->assertEquals('Base URL: https://events.stanford.edu', (string) $summary[0]);

    $form = [];
    $form_state = new FormState();
    $element = $widget->settingsForm($form, $form_state);
    $this->assertCount(4, $element);
    $this->assertEquals("https://events.stanford.edu", $element['base_url']['#default_value']);
    $this->assertArrayHasKey('select_distinct', $element);
    $element['#parents'] = [];

    $widget->validateUrl($element, $form_state, $form);
    $this->assertCount(1, $form_state->getErrors());

    $values['0']['filters'] = [];
    $this->assertEmpty($widget->massageFormValues($values, $form, $form_state));

    $values = $this->getValidValue();
    $massaged_values = $widget->massageFormValues($values, $form, $form_state);
    $this->assertCount(1, $massaged_values);
  }

  /**
   * Returns valid form submission values.
   */
  protected function getValidValue() {
    return [
      [
        'uri' => 'https://events.stanford.edu/api/2/events?group_id=37955145294460&days=365',
        'title' => '',
        'attributes' => [],
        'filters' => [
          'type' => [
            'event_audience' => [],
            'event_subject' => [],
            'event_types' => [37952570025304 => "37952570025304"],
          ],
          'group_id' => '37955145294460',
          'venue_id' => '',
          'match' => '',
        ],
        '_weight' => '0',
        '_original_delta' => 0,
      ],
    ];
  }

  public function requestAsyncCallback($method, $uri, $options) {
    if ($this->throwGuzzleError) {
      throw new ClientException('Failed', $this->createMock(RequestInterface::class));
    }

    $data = [];
    switch ($uri) {
      case '/api/2/groups':
        $data = [
          'groups' => [['group' => ['id' => 123456, 'name' => 'Foo']]],
          'page' => ['total' => 1],
        ];
        break;
      case '/api/2/departments':
        $data = [
          'departments' => [
            [
              'department' => [
                'id' => 654321,
                'name' => 'Bar',
              ],
            ],
          ],
          'page' => ['total' => 1],
        ];
        break;
      case '/api/2/places':
        $data = [
          'places' => [['place' => ['id' => 555555, 'name' => 'Baz']]],
          'page' => ['total' => 1],
        ];
        break;
      case'/api/2/events/filters':
        $data = [
          'event_audience' => [
            ['id' => 999, 'name' => 'Everyone'],
            ['id' => 111, 'name' => 'Students'],
          ],
          'event_subject' => [
            ['id' => 159, 'name' => 'Everyone'],
          ],
          'event_types' => [
            ['id' => 753, 'name' => 'Everyone'],
          ],
        ];
        break;
      case'/api/2/events/labels':
        $data = [
          'filters' => [
            'event_audience' => 'Audience',
            'event_types' => 'Types',
            'event_subject' => 'Subject',
          ],
        ];
        break;
    }

    $response = $this->createMock(ResponseInterface::class);
    $response->method('getBody')->willReturn(json_encode($data));

    $promise = $this->createMock(PromiseInterface::class);
    $promise->method('wait')->willReturn($response);

    return $promise;
  }

}
