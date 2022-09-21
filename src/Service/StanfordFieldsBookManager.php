<?php

namespace Drupal\stanford_fields\Service;

use Drupal\book\BookManagerInterface;
use Drupal\Component\Utility\NestedArray;
use Drupal\Component\Utility\SortArray;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\node\NodeInterface;

/**
 * Book manager service decorator.
 */
class StanfordFieldsBookManager implements BookManagerInterface {

  use StringTranslationTrait;

  /**
   * Decorated service constructor.
   *
   * @param \Drupal\book\BookManagerInterface $bookManager
   *   Original book manager service.
   * @param \Drupal\Core\State\StateInterface $state
   *   State service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Config factory service.
   */
  public function __construct(protected BookManagerInterface $bookManager, protected StateInterface $state, protected ConfigFactoryInterface $configFactory) {
  }

  /**
   * {@inheritdoc}
   */
  public function bookTreeAllData($bid, $link = NULL, $max_depth = NULL) {
    $tree_data = $this->bookManager->bookTreeAllData($bid, $link, $max_depth);
    $this->prependTreeLinks($tree_data);
    return $tree_data;
  }

  /**
   * {@inheritdoc}
   */
  protected function prependTreeLinks(array &$links, $prefix = '', $depth = 0) {
    $step = 1;
    foreach ($links as &$link) {
      $link_prefix = [];

      if ($depth) {
        $link_prefix = array_filter([
          $prefix,
          $this->getLinkPrefix($depth, $step),
        ]);

        $link['link']['title'] = implode('.', $link_prefix) . '. ' . $link['link']['title'];
        $step++;
      }

      if (isset($link['below'])) {
        $this->prependTreeLinks($link['below'], implode('.', $link_prefix), $depth + 1);
      }
    }
  }

  /**
   * Use state to allow customizing which characters are used for the prefix.
   *
   * @param int $depth
   *   Depth level 1-9.
   * @param int $position
   *   Position in the given depth level.
   *
   * @return string
   *   Character(s) prefix to use.
   */
  protected function getLinkPrefix(int $depth, int $position): string {
    $prefix_set = $this->state->get("book.prefix.$depth");
    $letters = range('A', 'Z');

    switch ($prefix_set) {
      case 'alpha_uppercase':
        return $letters[$position - 1];

      case 'alpha_lowercase':
        return strtolower($letters[$position - 1]);

      case 'roman_numerals_uppercase':
        return $this->getRomanNumeral($position);

      case 'roman_numersal_lowercase':
        return strtolower($this->getRomanNumeral($position));
    }

    return $position;
  }

  /**
   * Get the roman numeral representation of a number.
   *
   * @param int $num
   *   Number to convert to roman numeral.
   *
   * @return string
   *   Roman numeral.
   *
   * @link https://stackoverflow.com/questions/14994941/numbers-to-roman-numbers-with-php
   */
  protected function getRomanNumeral(int $num): string {
    $map = [
      'M' => 1000,
      'CM' => 900,
      'D' => 500,
      'CD' => 400,
      'C' => 100,
      'XC' => 90,
      'L' => 50,
      'XL' => 40,
      'X' => 10,
      'IX' => 9,
      'V' => 5,
      'IV' => 4,
      'I' => 1,
    ];
    $returnValue = '';
    while ($num > 0) {
      foreach ($map as $roman => $int) {
        if ($num >= $int) {
          $num -= $int;
          $returnValue .= $roman;
          break;
        }
      }
    }
    return $returnValue;
  }

  /**
   * {@inheritdoc}
   *
   * @codeCoverageIgnore
   */
  public function getActiveTrailIds($bid, $link) {
    return $this->bookManager->getActiveTrailIds($bid, $link);
  }

  /**
   * {@inheritdoc}
   *
   * @codeCoverageIgnore
   */
  public function loadBookLink($nid, $translate = TRUE) {
    return $this->bookManager->loadBookLink($nid, $translate);
  }

  /**
   * {@inheritdoc}
   *
   * @codeCoverageIgnore
   */
  public function loadBookLinks($nids, $translate = TRUE) {
    return $this->bookManager->loadBookLinks($nids, $translate);
  }

  /**
   * {@inheritdoc}
   *
   * @codeCoverageIgnore
   */
  public function getTableOfContents($bid, $depth_limit, array $exclude = []) {
    return $this->bookManager->getTableOfContents($bid, $depth_limit, $exclude);
  }

  /**
   * {@inheritdoc}
   *
   * @codeCoverageIgnore
   */
  public function getParentDepthLimit(array $book_link) {
    return $this->bookManager->getParentDepthLimit($book_link);
  }

  /**
   * {@inheritdoc}
   *
   * @codeCoverageIgnore
   */
  public function bookTreeCollectNodeLinks(&$tree, &$node_links) {
    return $this->bookManager->bookTreeCollectNodeLinks($tree, $node_links);
  }

  /**
   * {@inheritdoc}
   *
   * @codeCoverageIgnore
   */
  public function bookLinkTranslate(&$link) {
    return $this->bookManager->bookLinkTranslate($link);
  }

  /**
   * {@inheritdoc}
   *
   * @codeCoverageIgnore
   */
  public function bookTreeGetFlat(array $book_link) {
    return $this->bookManager->bookTreeGetFlat($book_link);
  }

  /**
   * {@inheritdoc}
   *
   * @codeCoverageIgnore
   */
  public function getAllBooks() {
    return $this->bookManager->getAllBooks();
  }

  /**
   * {@inheritdoc}
   */
  public function updateOutline(NodeInterface $node) {
    if (isset($node->book['weight'])) {

      // Before saving the node, look at the book weight data . The weight has
      // to be an integer, but we also have to adjust the weights of the sibling
      // book items so that they all stay in proper order.
      if (is_array($node->book['weight'])) {
        // New nodes use the key 'new'. At this point trying to use
        // $node->isNew() doesn't work because the database transactions have
        // been scheduled and the node has an id value.
        $key = array_key_exists('new', $node->book['weight']) ? 'new' : $node->id();

        // Loop through the sibling book links and adjust their weights.
        foreach ($node->book['weight'] as $nid => $weight) {
          if ($nid == $key) {
            continue;
          }
          $book_link = $this->loadBookLink($nid);
          $book_link['weight'] = $weight['weight'];
          $this->saveBookLink($book_link, FALSE);
        }

        // Finally set the weight of the current node to it's submitted value.
        $node->book['weight'] = $node->book['weight'][$key]['weight'];
      }
      $node->book['weight'] = $node->book['weight'] ?: 0;
    }
    return $this->bookManager->updateOutline($node);
  }

  /**
   * {@inheritdoc}
   *
   * @codeCoverageIgnore
   */
  public function saveBookLink(array $link, $new) {
    return $this->bookManager->saveBookLink($link, $new);
  }

  /**
   * {@inheritdoc}
   *
   * @codeCoverageIgnore
   */
  public function getLinkDefaults($nid) {
    return $this->bookManager->getLinkDefaults($nid);
  }

  /**
   * {@inheritdoc}
   *
   * @codeCoverageIgnore
   */
  public function getBookParents(array $item, array $parent = []) {
    return $this->bookManager->getBookParents($item, $parent);
  }

  /**
   * Is the given node allowed in books based on config settings.
   *
   * @param \Drupal\node\NodeInterface $node
   *   Node entity.
   *
   * @return bool
   *   If the given node can be added to books.
   */
  protected function nodeAllowedInBook(NodeInterface $node): bool {
    $allowed_types = $this->configFactory->get('book.settings')
      ->get('allowed_types');
    return in_array($node->getType(), $allowed_types);
  }

  /**
   * {@inheritdoc}
   */
  public function addFormElements(array $form, FormStateInterface $form_state, NodeInterface $node, AccountInterface $account, $collapsed = TRUE) {
    // The book module will add the book settings to all node types for admins,
    // which makes it annoying. This checks the node against the settings
    // instead of only the 'administer book outlines' permission.
    // @see book_form_node_form_alter()
    if (!$this->nodeAllowedInBook($node)) {
      return $form;
    }

    // Prepare the form state before passing to the original service to add form
    // elements.
    if ($form_state->hasValue(['book', 'weight'])) {

      // During the AJAX call, the weight value is keyed array of other book
      // links. Extract the weight of the current node on this form so that the
      // original service can still use it normally.
      $weight_value = $form_state->getValue(['book', 'weight']);
      if (is_array($weight_value)) {
        $key = array_key_exists('new', $weight_value) ? 'new' : $node->id();
        $this_node_weight = NestedArray::getValue($weight_value, [
          $key,
          'weight',
        ]);
        $form_state->setValue(['book', 'weight'], $this_node_weight);
      }
      else {
        $form_state->setValue(['book', 'weight'], (int) $weight_value);
      }
    }

    // Call the original service to add the form parts.
    $form = $this->bookManager->addFormElements($form, $form_state, $node, $account, $collapsed);

    // Force the book details to be open, because after the ajax returns, the
    // field set closes.
    $form['book']['#open'] = TRUE;
    $form['book']['#prefix'] = '<div id="book-widget-wrapper">';
    $form['book']['#suffix'] = '</div>';
    // Override the book selection ajax callback so that we can return the whole
    // book portion, not just the parent selector.
    // @see book_form_update().
    $form['book']['bid']['#ajax']['callback'] = [self::class, 'bookSelected'];
    $form['book']['bid']['#ajax']['wrapper'] = 'book-widget-wrapper';

    // Add the ajax to the parent selector.
    $form['book']['pid']['#ajax'] = [
      'callback' => [self::class, 'parentChosen'],
      'wrapper' => 'book-item-reorder-wrapper',
    ];

    $form['book']['weight'] = [
      '#type' => 'table',
      '#header' => [
        'name' => t('Name'),
        'weight' => t('Weight'),
      ],
      '#prefix' => '<div id="book-item-reorder-wrapper">',
      '#suffix' => '</div>',
      '#id' => 'book-item-reorder',
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'book-item-weight',
        ],
      ],
      '#access' => FALSE,
    ];

    $parent_id = $this->getParentIdFromForm($form, $form_state);

    if (!$parent_id) {
      return $form;
    }
    $form['book']['weight']['#access'] = TRUE;

    foreach ($this->getSiblingBookItems($parent_id, $form['book']['nid']['#value']) as $nid => $link_data) {
      $form['book']['weight'][$nid] = [
        '#attributes' => [
          'class' => [
            'draggable',
          ],
        ],
        '#weight' => $link_data['weight'],
        'name' => ['#markup' => $link_data['title']],
        'weight' => [
          '#type' => 'weight',
          '#title' => t('Weight'),
          '#default_value' => $link_data['weight'],
          '#delta' => 50,
          '#title_display' => 'invisible',
          '#attributes' => ['class' => ['book-item-weight']],
        ],
      ];
    }

    return $form;
  }

  /**
   * Get book links that would be children of the parent id.
   *
   * @param int $parent_id
   *   Node ID.
   * @param int|string $current_nid
   *   Another Node ID to identify different links.
   *
   * @return array
   *   Keyed array of book link data.
   */
  protected function getSiblingBookItems(int $parent_id, int|string $current_nid): array {
    $parent_link = $this->loadBookLink($parent_id);
    if (!$parent_link) {
      return [];
    }

    $parent_subtree = $this->bookSubtreeData($parent_link);

    $parent_key = key($parent_subtree);
    $sibling_links = $parent_subtree[$parent_key]['below'] ?? [];

    $items = [];
    foreach ($sibling_links as $sibling) {
      if ($sibling['link']['nid'] == $current_nid) {
        $sibling['link']['title'] .= ' (' . $this->t('This Content') . ')';
      }
      $items[$sibling['link']['nid']] = $sibling['link'];
    }

    if ($current_nid == 'new') {
      $items['new'] = [
        'weight' => 50,
        'title' => $this->t('(This Content)'),
        'nid' => 'new',
      ];
    }

    uasort($items, [SortArray::class, 'sortByWeightElement']);
    return $items;
  }

  /**
   * Get the parent book item from the current form state.
   *
   * @param array $form
   *   Complete form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Current state of the form.
   *
   * @return int|null
   *   Parent ID or null if none was found.
   */
  protected function getParentIdFromForm(array $form, FormStateInterface $form_state): ?int {
    // The book module uses -1 as it's indication that nothing was chosen.
    $parent_id = $form['book']['pid']['#default_value'] ?? -1;

    // If the form was submitted via ajax, grab the book id from the user input.
    $user_input = $form_state->getUserInput() ?: [];
    $parent_id = $parent_id != -1 ? $parent_id : NestedArray::getValue($user_input, [
      'book',
      'bid',
    ]);

    // As an extra check, if the parent item still hasn't been found, try to
    // fetch the parent id from the form state.
    if ($parent_id == -1 && $form_state->hasValue(['book', 'pid'])) {
      $parent_id = $form_state->getValue(['book', 'pid']);
    }

    return $parent_id >= 1 ? $parent_id : NULL;
  }

  /**
   * Ajax callback when a book is selected.
   *
   * @param array $form
   *   Complete Form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Ajaxed form state.
   *
   * @return array
   *   Modified book element.
   */
  public static function bookSelected(array &$form, FormStateInterface $form_state): array {
    return $form['book'];
  }

  /**
   * Ajax callback when a parent page is selected for the book.
   *
   * @param array $form
   *   Complete Form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Ajaxed form state.
   *
   * @return array
   *   Modified weight element.
   */
  public static function parentChosen(array &$form, FormStateInterface $form_state): array {
    return $form['book']['weight'];
  }

  /**
   * {@inheritdoc}
   *
   * @codeCoverageIgnore
   */
  public function deleteFromBook($nid) {
    return $this->bookManager->deleteFromBook($nid);
  }

  /**
   * {@inheritdoc}
   *
   * @codeCoverageIgnore
   */
  public function bookTreeOutput(array $tree) {
    return $this->bookManager->bookTreeOutput($tree);
  }

  /**
   * {@inheritdoc}
   *
   * @codeCoverageIgnore
   */
  public function bookTreeCheckAccess(&$tree, $node_links = []) {
    return $this->bookManager->bookTreeCheckAccess($tree, $node_links);
  }

  /**
   * {@inheritdoc}
   *
   * @codeCoverageIgnore
   */
  public function bookSubtreeData($link) {
    return $this->bookManager->bookSubtreeData($link);
  }

  /**
   * {@inheritdoc}
   *
   * @codeCoverageIgnore
   */
  public function checkNodeIsRemovable(NodeInterface $node) {
    return $this->bookManager->checkNodeIsRemovable($node);
  }

}
