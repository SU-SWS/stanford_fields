<?php

namespace Drupal\stanford_fields\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\media\Entity\Media;
use Drupal\file\Entity\File;
use Drupal\Core\Url;

/**
 * Plugin implementation of the 'multimedia' formatter.
 *
 * @FieldFormatter(
 *   id = "multimedia",
 *   label = @Translation("Multiple Media Formatter"),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class MultiMedia extends FormatterBase {
  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $summary[] = $this->t('Too many things to show you. Open up the settings to see.');
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      // Declare a setting named 'text_length', with
      // a default value of 'short'
      'text_length' => 'short',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {

    $form['image_formatter'] = [
      '#title' => $this->t('Image Formatter'),
      '#type' => 'select',
      '#options' => [
        'responsive' => $this->t('Media Responsive Image Style '),
        'static' => $this->t('Media Image Style'),
        'rendered' => $this->t('Rendered entity'),
      ],
      '#default_value' => $this->getSetting('image_formatter'),
    ];

    $form['image_formatter_option'] = [
      '#title' => $this->t('Image Formatter Setting'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('image_formatter_option'),
      '#description' => $this->t('The machine name key for the display mode, style, etc.'),
    ];

    $form['video_formatter'] = [
      '#title' => $this->t('Video Formatter'),
      '#type' => 'select',
      '#options' => [
        'oembed' => $this->t('Oembed'),
        'rendered' => $this->t('Rendered entity'),
      ],
      '#default_value' => $this->getSetting('video_formatter'),
    ];

    $form['video_formatter_option'] = [
      '#title' => $this->t('Video Formatter Setting'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('image_formatter_option'),
      '#description' => $this->t('The machine name key for the display mode, style, etc.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    foreach ($items as $delta => $item) {
      // Get the media item.
      $media_id = $item->getValue()['target_id'];
      $media_item = Media::load($media_id);
      // Get the file.
      $file_id = $media_item->field_media_file->getValue()[0]['target_id'];
      $file = File::load($file_id);
      // Get the URL.
      $uri = $file->getFileUri();
      $url = Url::fromUri(file_create_url($uri))->toString();
      // Output.
      $elements[$delta] = [
        '#type'     => 'inline_template',
        '#template' => '<a href="{{ url }}" target="_blank">Download Slides</a>',
        '#context'  => [
          'url' => $url,
        ],
      ];
    }
    return $elements;
  }
}
