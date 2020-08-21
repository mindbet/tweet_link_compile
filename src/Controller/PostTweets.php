<?php

namespace Drupal\tweet_link_compile\Controller;

use Abraham\TwitterOAuth\TwitterOAuth;

/**
 * Class SyncFollowingList.
 */
class PostTweets {

  /**
   * Import users.
   *
   * @return array
   *   Render array (table) of database entries
   */
  public function postLink() {

    $toplinks = new CompileMostPopular();

    $toplinkslist = $toplinks->report();

    $config = \Drupal::config('tweet_link_compile.settings');

    foreach ($toplinkslist['#myvariable'] as $key => $storylink) {
      $statusbuild = '';
      $statusbuild = $statusbuild . $storylink->mlt;
      $statusbuild = $statusbuild . ' | ' . $storylink->mld;
      $recommenders = array_column($storylink->recommenders, 'iduser');
      $recommenders = array_map([$this, 'addat'], $recommenders);
      $recommenderlist = implode($recommenders, ' ');
      if (strlen($statusbuild) + strlen($recommenderlist) < 280) {
        $statusbuild = $statusbuild . ' ' . $recommenderlist;
      }
      if (strlen($statusbuild) < 252) {
        $statusbuild = $statusbuild . ' ' . ' | More links: https://trending.health';
      }

      $parameters = [
        "status" => $statusbuild,
      ];

      $connection = new TwitterOAuth($config->get('consumer_key'), $config->get('consumer_secret'), $config->get('th_oauth_token'), $config->get('th_oauth_token_secret'));

      // Set type of api request to post status.
      $endpoint = "statuses/update";

      // Connect to Twitter and make the post.



      // If error posting to Twitter, log the error to watchdog and screen.
      if (isset($connection->getLastBody()->errors)) {
        // $this->logger->error($connection->getLastBody()->errors[0]->message);
        // \Drupal::logger('tweet_link_compile')->error($connection->getLastBody()->errors[0]->message);
        return [
          '#type' => 'markup',
          '#markup' => 'Error posting',
        ];
      }

      // Print success message to screen.
    }

    return [
      '#type' => 'markup',
      // '#markup' => $this->t('New users'),
      '#markup' => 'Link posted',
    ];

  }//end postLink()

  /**
   * Adds @ to twitter handle for mentions in tweets
   */
  public function addat($value) {
    return '@' . $value;
  }

}
