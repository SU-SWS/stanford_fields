<?php

namespace Drupal\stanford_fields\Plugin\Field\FieldFormatter;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Url;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;
use Drupal\media\Entity\MediaType;
use Drupal\media\IFrameUrlHelper;
use Drupal\media\OEmbed\Resource;
use Drupal\media\OEmbed\ResourceException;
use Drupal\media\OEmbed\ResourceFetcherInterface;
use Drupal\media\OEmbed\UrlResolverInterface;
use Drupal\media\Plugin\media\Source\OEmbedInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
class MultiMedia extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The oEmbed resource fetcher.
   *
   * @var \Drupal\media\OEmbed\ResourceFetcherInterface
   */
  protected $resourceFetcher;

  /**
   * The oEmbed URL resolver service.
   *
   * @var \Drupal\media\OEmbed\UrlResolverInterface
   */
  protected $urlResolver;

  /**
   * The logger service.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The media settings config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * The iFrame URL helper service.
   *
   * @var \Drupal\media\IFrameUrlHelper
   */
  protected $iFrameUrlHelper;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs an OEmbedFormatter instance.
   *
   * @param string $plugin_id
   *   The plugin ID for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \Drupal\media\OEmbed\ResourceFetcherInterface $resource_fetcher
   *   The oEmbed resource fetcher service.
   * @param \Drupal\media\OEmbed\UrlResolverInterface $url_resolver
   *   The oEmbed URL resolver service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\media\IFrameUrlHelper $iframe_url_helper
   *   The iFrame URL helper service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, MessengerInterface $messenger, ResourceFetcherInterface $resource_fetcher, UrlResolverInterface $url_resolver, LoggerChannelFactoryInterface $logger_factory, ConfigFactoryInterface $config_factory, IFrameUrlHelper $iframe_url_helper, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->messenger = $messenger;
    $this->resourceFetcher = $resource_fetcher;
    $this->urlResolver = $url_resolver;
    $this->logger = $logger_factory->get('media');
    $this->config = $config_factory->get('media.settings');
    $this->iFrameUrlHelper = $iframe_url_helper;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('messenger'),
      $container->get('media.oembed.resource_fetcher'),
      $container->get('media.oembed.url_resolver'),
      $container->get('logger.factory'),
      $container->get('config.factory'),
      $container->get('media.oembed.iframe_url_helper'),
      $container->get('entity_type.manager')
    );
  }

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
      'image_formatter' => 'responsive',
      'image_formatter_option' => 'large',
      'video_formatter' => 'oembed',
      'video_formatter_option' => 'default',
      'video_formatter_height' => 0,
      'video_formatter_width' => 0,
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
        'oembed' => $this->t('oEmbed Content'),
        'rendered' => $this->t('Rendered entity'),
      ],
      '#default_value' => $this->getSetting('video_formatter'),
    ];

    $form['video_formatter_option'] = [
      '#title' => $this->t('Video Formatter Setting'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('video_formatter_option'),
      '#description' => $this->t('The machine name key for the display mode, style, etc.'),
    ];

    $form['video_formatter_height'] = [
      '#title' => $this->t('Video Max Height (px)'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('video_formatter_height'),
      '#description' => $this->t('Integer value for max height.'),
    ];

    $form['video_formatter_width'] = [
      '#title' => $this->t('Video Max Width (px)'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('video_formatter_width'),
      '#description' => $this->t('Integer value for max width.'),
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
      $type = $media_item->bundle();

      // Depending on the type of media referenced
      switch ($type) {
        case 'image':
          $elements[$delta] = $this->viewImageElement($item, $media_item);
          break;

        case 'video':
          $elements[$delta] = $this->viewVideoElement($item, $media_item);
          break;

        default:
          $elements[$delta] = $this->viewDefaultElement($item, $media_item);
      }
    }

    return $elements;
  }

  /**
   * Generates render arrays for the different image media type options.
   *
   * @param object $item
   *   A single item from the FieldItemList object.
   * @param \Drupal\media\Entity\Media $media
   *   An instantiated Media Object.
   *
   * @return array
   *   A render array.
   */
  private function viewImageElement($item, $media) {
    $fid = $media->get('thumbnail')[0]->get('target_id')->getValue();
    $height = $media->get('thumbnail')[0]->get('height')->getValue();
    $width = $media->get('thumbnail')[0]->get('width')->getValue();
    $file = File::load($fid);
    $formatter = $this->getSetting('image_formatter');
    $opt = $this->getSetting('image_formatter_option');
    $element = [];

    // Create a render array based on the setting selected by the site builder.
    switch ($formatter) {
      case 'responsive':
        $element = [
          '#theme' => 'responsive_image',
          '#width' => $height,
          '#height' => $width,
          '#responsive_image_style_id' => $opt,
          '#uri' => $file->getFileUri(),
        ];
        break;

      case 'static':
        $element = [
          '#theme' => 'image_style',
          '#width' => $height,
          '#height' => $width,
          '#style_name' => $opt,
          '#uri' => $file->getFileUri(),
        ];
        break;

      default: // render
        $element = $this->viewDefaultElement($item, $media, $this->getSetting('image_formatter_option'));
    }

    return $element;
  }

  /**
   * Generate a render array for a Video media element.
   *
   * @param object $item
   *   A single item from the FieldItemList object.
   * @param \Drupal\media\Entity\Media $media
   *   An instantiated Media Object.
   *
   * @return array
   *   A render array.
   */
  private function viewVideoElement($item, $media) {
    $element = [];
    $max_width = $this->getSetting('video_formatter_width');
    $max_height= $this->getSetting('video_formatter_height');

    // As there are only two options right now if they user doesn't select
    // oembed default to the render view mode option.
    if ($this->getSetting("video_formatter") !== "oembed") {
      return $this->viewDefaultElement($item, $media, $this->getSetting('video_formatter_option'));
    }

    // Oembed render.
    // Most of the below code has been taken from
    // Drupal\media\Plugin\Field\FieldFormatter\OEmbedFormatter
    // It has been slightly modified to suit our needs.
    $value = $media->get("field_media_oembed_video")->getValue()[0]['value'];

    try {
      $resource_url = $this->urlResolver->getResourceUrl($value, $max_width, $max_height);
      $resource = $this->resourceFetcher->fetchResource($resource_url);
    }
    catch (ResourceException $exception) {
      $this->logger->error("Could not retrieve the remote URL (@url).", ['@url' => $value]);
      return;
    }

    if ($resource->getType() === Resource::TYPE_LINK) {
      $element = [
        '#title' => $resource->getTitle(),
        '#type' => 'link',
        '#url' => Url::fromUri($value),
      ];
    }
    elseif ($resource->getType() === Resource::TYPE_PHOTO) {
      $element = [
        '#theme' => 'image',
        '#uri' => $resource->getUrl()->toString(),
        '#width' => $max_width ?: $resource->getWidth(),
        '#height' => $max_height ?: $resource->getHeight(),
      ];
    }
    else {
      $url = Url::fromRoute('media.oembed_iframe', [], [
        'query' => [
          'url' => $value,
          'max_width' => $max_width,
          'max_height' => $max_height,
          'hash' => $this->iFrameUrlHelper->getHash($value, $max_width, $max_height),
        ],
      ]);

      $domain = $this->config->get('iframe_domain');
      if ($domain) {
        $url->setOption('base_url', $domain);
      }

      // Render videos and rich content in an iframe for security reasons.
      // @see: https://oembed.com/#section3
      $element = [
        '#type' => 'html_tag',
        '#tag' => 'iframe',
        '#attributes' => [
          'src' => $url->toString(),
          'frameborder' => 0,
          'scrolling' => FALSE,
          'allowtransparency' => TRUE,
          'width' => $max_width ?: $resource->getWidth(),
          'height' => $max_height ?: $resource->getHeight(),
          'class' => ['media-oembed-content'],
        ],
        '#attached' => [
          'library' => [
            'media/oembed.formatter',
          ],
        ],
      ];

      // An empty title attribute will disable title inheritance, so only
      // add it if the resource has a title.
      $title = $resource->getTitle();
      if ($title) {
        $element['#attributes']['title'] = $title;
      }

      CacheableMetadata::createFromObject($resource)
        ->addCacheTags($this->config->getCacheTags())
        ->applyTo($element);
    }

    return $element;
  }

  /**
   * Use view modes to generate a render array.
   *
   * @param object $item
   *   A single item from the FieldItemList object.
   * @param \Drupal\media\Entity\Media $media
   *   An instantiated Media Object.
   * @param string $view_mode
   *   The view mode machine name.
   *
   * @return array
   *   A render array for a view mode.
   */
  private function viewDefaultElement($item, $media, $view_mode = "default") {
    $build = $this->entityTypeManager->getViewBuilder('media')->view($media, $view_mode);
    return $build;
  }

}
