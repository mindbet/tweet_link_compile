<?php

namespace Drupal\tweet_link_compile\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * This class runs the tweet retrieval process.
 */
class CallTweetsService extends ControllerBase {

  /**
   * Runs tweet collection.
   *
   * @return array
   *   Status message render array.
   * @todo report number of tweets received
   */
  public function content() {

    \Drupal::service('tweet_link_compile.base')->runAll();

    return [
      '#type'   => 'markup',
      '#markup' => $this->t('New tweets received'),
    ];

  }//end content()

}//end class
