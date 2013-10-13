<?php

/**
 * @file
 * Definition of Drupal\comment\Plugin\views\field\LinkApprove.
 */

namespace Drupal\comment\Plugin\views\field;

use Drupal\views\ResultRow;
use Drupal\Component\Annotation\PluginID;

/**
 * Provides a comment approve link.
 *
 * @ingroup views_field_handlers
 *
 * @PluginID("comment_link_approve")
 */
class LinkApprove extends Link {

  public function access() {
    //needs permission to administer comments in general
    return user_access('administer comments');
  }

  /**
   * Prepares the link pointing for approving the comment.
   *
   * @param \Drupal\Core\Entity\EntityInterface $data
   *   The comment entity.
   * @param \Drupal\views\ResultRow $values
   *   The values retrieved from a single row of a view's query result.
   *
   * @return string
   *   Returns a string for the link text.
   */
  protected function renderLink($data, ResultRow $values) {
    $status = $this->getValue($values, 'status');

    // Don't show an approve link on published nodes.
    if ($status == COMMENT_PUBLISHED) {
      return;
    }

    $text = !empty($this->options['text']) ? $this->options['text'] : t('approve');
    $comment = $this->get_entity($values);

    $this->options['alter']['make_link'] = TRUE;
    $this->options['alter']['path'] = "comment/" . $comment->id() . "/approve";
    $this->options['alter']['query'] = drupal_get_destination() + array('token' => drupal_get_token($this->options['alter']['path']));

    return $text;
  }

}
