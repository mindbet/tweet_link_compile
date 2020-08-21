<?php

namespace Drupal\tweet_link_compile\Controller;

use Abraham\TwitterOAuth\TwitterOAuth;

/**
 * Class SyncFollowingList.
 */
class TwitterAuthCallback {

  /**
   * Get Twitter authorization.
   */
  public function getAuthCallback() {

    $config = \Drupal::config('tweet_link_compile.settings');
    define('OAUTH_CALLBACK', 'http://th2020.lndo.site/authcallback');

    $tempstore = \Drupal::service('user.shared_tempstore')->get('tweet_link_compile');

    $valuetoken = $tempstore->get('oauth_token');
    $valuetokensecret = $tempstore->get('oauth_token_secret');

    $request_token = [];

    $request_token['oauth_token'] = $tempstore->get('oauth_token');
    $request_token['oauth_token_secret'] = $tempstore->get('oauth_token_secret');

    if (isset($_REQUEST['oauth_token']) && $request_token['oauth_token'] !== $_REQUEST['oauth_token']) {
      // Abort! Something is wrong.
    }

    $connection = new TwitterOAuth($config->get('consumer_key'), $config->get('consumer_secret'), $request_token['oauth_token'], $request_token['oauth_token_secret']);

    $access_token = $connection->oauth("oauth/access_token", ["oauth_verifier" => $_REQUEST['oauth_verifier']]);

    $config = \Drupal::service('config.factory')->getEditable('tweet_link_compile.settings');

    $config->set('th_oauth_token', $access_token["oauth_token"])->save();

    $config->set('th_oauth_token_secret', $access_token["oauth_token_secret"])->save();

    return [
      '#type'   => 'markup',
      // '#markup' => $this->t('New users'),
      '#markup' => 'Credentials saved.',
    ];

  }//end getAuthCallback()

}
