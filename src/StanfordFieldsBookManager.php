<?php

namespace Drupal\stanford_fields;

use Drupal\book\BookManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\State\StateInterface;
use Drupal\node\NodeInterface;
use Drupal\shs\StringTranslationTrait;

class StanfordFieldsBookManager implements BookManagerInterface {

  use StringTranslationTrait;

  public function __construct(protected BookManagerInterface $bookManager, protected StateInterface $state) {
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
   */
  public function getActiveTrailIds($bid, $link) {
    return $this->bookManager->getActiveTrailIds($bid, $link);
  }

  /**
   * {@inheritdoc}
   */
  public function loadBookLink($nid, $translate = TRUE) {
    return $this->bookManager->loadBookLink($nid, $translate);
  }

  /**
   * {@inheritdoc}
   */
  public function loadBookLinks($nids, $translate = TRUE) {
    return $this->bookManager->loadBookLinks($nids, $translate);
  }

  /**
   * {@inheritdoc}
   */
  public function getTableOfContents($bid, $depth_limit, array $exclude = []) {
    return $this->bookManager->getTableOfContents($bid, $depth_limit, $exclude);
  }

  /**
   * {@inheritdoc}
   */
  public function getParentDepthLimit(array $book_link) {
    return $this->bookManager->getParentDepthLimit($book_link);
  }

  /**
   * {@inheritdoc}
   */
  public function bookTreeCollectNodeLinks(&$tree, &$node_links) {
    return $this->bookManager->bookTreeCollectNodeLinks($tree, $node_links);
  }

  /**
   * {@inheritdoc}
   */
  public function bookLinkTranslate(&$link) {
    return $this->bookManager->bookLinkTranslate($link);
  }

  /**
   * {@inheritdoc}
   */
  public function bookTreeGetFlat(array $book_link) {
    return $this->bookManager->bookTreeGetFlat($book_link);
  }

  /**
   * {@inheritdoc}
   */
  public function getAllBooks() {
    return $this->bookManager->getAllBooks();
  }

  /**
   * {@inheritdoc}
   */
  public function updateOutline(NodeInterface $node) {
    return $this->bookManager->updateOutline($node);
  }

  /**
   * {@inheritdoc}
   */
  public function saveBookLink(array $link, $new) {
    return $this->bookManager->saveBookLink($link, $new);
  }

  /**
   * {@inheritdoc}
   */
  public function getLinkDefaults($nid) {
    return $this->bookManager->getLinkDefaults($nid);
  }

  /**
   * {@inheritdoc}
   */
  public function getBookParents(array $item, array $parent = []) {
    return $this->bookManager->getBookParents($item, $parent);
  }

  /**
   * {@inheritdoc}
   */
  public function addFormElements(array $form, FormStateInterface $form_state, NodeInterface $node, AccountInterface $account, $collapsed = TRUE) {
    return $this->bookManager->addFormElements($form, $form_state, $node, $account, $collapsed);
  }


  /**
   * {@inheritdoc}
   */
  public function deleteFromBook($nid) {
    return $this->bookManager->deleteFromBook($nid);
  }

  /**
   * {@inheritdoc}
   */
  public function bookTreeOutput(array $tree) {
    return $this->bookManager->bookTreeOutput($tree);
  }

  /**
   * {@inheritdoc}
   */
  public function bookTreeCheckAccess(&$tree, $node_links = []) {
    return $this->bookManager->bookTreeCheckAccess($tree, $node_links);
  }

  /**
   * {@inheritdoc}
   */
  public function bookSubtreeData($link) {
    return $this->bookManager->bookSubtreeData($link);
  }

  /**
   * {@inheritdoc}
   */
  public function checkNodeIsRemovable(NodeInterface $node) {
    return $this->bookManager->checkNodeIsRemovable($node);
  }

}