<?php

namespace Drupal\ckc_hodnoceni\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'WorksBlock' Block.
 *
 * @Block(
 *   id = "works_block",
 *   admin_label = @Translation("Works block"),
 *   category = @Translation("Works World"),
 * )
 */
class WorksBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [
      '#markup' => $this->t('Hello, World!'),
    ];
  }

}
