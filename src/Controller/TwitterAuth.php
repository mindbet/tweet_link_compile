<?php

namespace Drupal\tweet_link_compile\Controller;

use Abraham\TwitterOAuth\TwitterOAuth;

/**
 * Class SyncFollowingList.
 */
class TwitterAuth {

  /**
   * Get Twitter authorization.
   */
  public function getAuth() {

    $config = \Drupal::config('tweet_link_compile.settings');

    $connection = new TwitterOAuth($config->get('consumer_key'), $config->get('consumer_secret'));

    $request_token = $connection->oauth('oauth/request_token', ['oauth_callback' => 'http://th2020.lndo.site/authcallback']);

    $tempstore = \Drupal::service('user.shared_tempstore')->get('tweet_link_compile');
    $tempstore->set('oauth_token', $request_token['oauth_token']);
    $tempstore->set('oauth_token_secret', $request_token['oauth_token_secret']);

    $url = $connection->url('oauth/authorize', ['oauth_token' => $request_token['oauth_token']]);

    $setupstring = "<a href=" . $url . ">Click here to verify with Twitter.</a>";

    // If error in retrieving from Twitter, log the error.
    if (isset($connection->getLastBody()->errors)) {
      // $this->logger->error($connection->getLastBody()->errors[0]->message);
      \Drupal::logger('tweet_link_compile')->error($connection->getLastBody()->errors[0]->message);
      return [
        '#type'   => 'markup',
        // '#markup' => $this->t('New users'),
        '#markup' => 'Error posting',
      ];
    }

    return [
      '#type'   => 'markup',
      // '#markup' => $this->t('New users'),
      '#markup' => $setupstring,
    ];

  }//end getAuth()

}
