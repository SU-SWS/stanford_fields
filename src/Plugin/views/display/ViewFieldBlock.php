<?php

namespace Drupal\stanford_fields\Plugin\views\display;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\views\Attribute\ViewsDisplay;
use Drupal\views\Plugin\views\display\Block;

/**
 * The plugin that handles a block.
 *
 * @ingroup views_display_plugins
 *
 * @see \Drupal\views\Plugin\Block\ViewsBlock
 * @see \Drupal\views\Plugin\Derivative\ViewsBlock
 */
#[ViewsDisplay(
  id: "viewfield_block",
  title: new TranslatableMarkup("View Field Block"),
  admin: new TranslatableMarkup("Block"),
  help: new TranslatableMarkup("Identical to a block, but allows for granular viewfield settings."),
  theme: "views_view",
  contextual_links_locations: ["block"],
  register_theme: FALSE,
  uses_hook_block: TRUE,
)]
class ViewFieldBlock extends Block {

  /**
   * {@inheritDoc}
   */
  public function optionsSummary(&$categories, &$options) {
    parent::optionsSummary($categories, $options);
    $categories['block']['title'] = $this->t('Field Block Settings');
    $options['block_description']['title'] = $this->t('Field Block Name');
    $options['block_category']['title'] = $this->t('Field Block Category');
  }

}
