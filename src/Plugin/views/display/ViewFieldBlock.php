<?php

namespace Drupal\stanford_fields\Plugin\views\display;

use Drupal\views\Plugin\views\display\Block;

/**
 * The plugin that handles a block.
 *
 * @ingroup views_display_plugins
 *
 * @ViewsDisplay(
 *   id = "viewfield_block",
 *   title = @Translation("View Field Block"),
 *   help = @Translation("Identical to a block, but allows for granular viewfield settings."),
 *   theme = "views_view",
 *   register_theme = FALSE,
 *   uses_hook_block = TRUE,
 *   contextual_links_locations = {"block"},
 *   admin = @Translation("Block")
 * )
 *
 * @see \Drupal\views\Plugin\Block\ViewsBlock
 * @see \Drupal\views\Plugin\Derivative\ViewsBlock
 */
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
