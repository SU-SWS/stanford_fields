<?php

declare(strict_types=1);

namespace Drupal\stanford_fields\Plugin\GraphQLCompose\FieldType;

use Drupal\Core\Field\FieldItemInterface;
use Drupal\file\FileInterface;
use Drupal\graphql\GraphQL\Execution\FieldContext;
use Drupal\graphql_compose\Plugin\GraphQLCompose\FieldType\ImageItem as OrigImageItem;

/**
 * Overrides the original image item to provide focal point data.
 *
 * @codeCoverageIgnore
 */
class ImageItem extends OrigImageItem {

  /**
   * {@inheritDoc}
   */
  public function resolveFieldItem(FieldItemInterface $item, FieldContext $context) {
    $fields = parent::resolveFieldItem($item, $context);
    $focal_point = self::getFocalPoint($item->entity, (int) $fields['width'], (int) $fields['height']);
    return [...$fields, ...$focal_point];
  }

  /**
   * @param \Drupal\file\FileInterface $file
   * @param int $width
   * @param int $height
   *
   * @return array
   */
  protected static function getFocalPoint(FileInterface $file, int $width, int $height) {
    /** @var \Drupal\focal_point\FocalPointManagerInterface $focal_point_manager */
    $focal_point_manager = \Drupal::service('focal_point.manager');
    $crop_type = \Drupal::config('focal_point.settings')->get('crop_type');

    $crop = $focal_point_manager->getCropEntity($file, $crop_type);
    $x = (int) $crop->get('x')->getString();
    $y = (int) $crop->get('y')->getString();

    if ($x && $y) {
      $focal_point = $focal_point_manager->absoluteToRelative($x, $y, $width, $height);
      return array_combine(['focalX', 'focalY'], $focal_point);
    }
    return ['focalX' => 50, 'focalY' => 50];
  }

}
