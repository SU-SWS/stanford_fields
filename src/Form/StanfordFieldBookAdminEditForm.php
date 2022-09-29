<?php

namespace Drupal\stanford_fields\Form;

use Drupal\book\Form\BookAdminEditForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\node\NodeInterface;

/**
 * Override and modify the core book admin edit form.
 */
class StanfordFieldBookAdminEditForm extends BookAdminEditForm {

  /**
   * {@inheritDoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, NodeInterface $node = NULL) {
    $form = parent::buildForm($form, $form_state, $node);
    foreach (Element::children($form['table']) as $key) {
      if (isset($form['table'][$key]['#nid'])) {
        $page_title = $form['table'][$key]['title']['#default_value'];
        $form['table'][$key]['title']['#type'] = 'markup';
        $form['table'][$key]['title']['#markup'] = $page_title;
        $form_state->setValue(['table', $key, 'title'], $page_title);
      }
    }
    return $form;
  }

  /**
   * {@inheritDoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    foreach (Element::children($form['table']) as $key) {
      if (isset($form['table'][$key]['#nid'])) {
        $page_title = $form['table'][$key]['title']['#markup'];
        $form_state->setValue(['table', $key, 'title'], $page_title);
      }
    }
    parent::submitForm($form, $form_state);
  }

}
