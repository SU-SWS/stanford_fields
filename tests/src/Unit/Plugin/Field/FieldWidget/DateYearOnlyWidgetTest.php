<?php

namespace Drupal\Tests\stanford_fields\Unit\Plugin\Field\FieldWidget;

use Drupal\stanford_fields\Plugin\Field\FieldWidget\DateYearOnlyWidget;
use Drupal\Tests\UnitTestCase;

/**
 * Class DateYearOnlyWidgetTest
 *
 * @group
 * @coversDefaultClass \Drupal\stanford_fields\Plugin\Field\FieldWidget\DateYearOnlyWidget
 */
class DateYearOnlyWidgetTest extends UnitTestCase {

  public function testDefaultSettings() {
    $default_settings = DateYearOnlyWidget::defaultSettings();
    $ten_years = (60 * 60 * 24 * 365 * 10);
    $expected = [
      'date_order' => 'YMD',
      'increment' => '15',
      'time_type' => '24',
      'start' => date('Y', time() - $ten_years),
      'end' => date('Y', time() + $ten_years),
    ];
    $this->assertArrayEquals($expected, $default_settings);
  }

}
