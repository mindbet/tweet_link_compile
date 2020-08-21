<?php

namespace Drupal\tweet_link_compile\Controller;

use Abraham\TwitterOAuth\TwitterOAuth;

/**
 * Class SyncFollowingList.
 */
class SyncFollowingList {

  /**
   * Import users.
   *
   * @return array
   *   Render array (table) of database entries
   */
  public function importUsers() {

    $config = \Drupal::config('tweet_link_compile.settings');




    $parameters = [
      "screen_name"      => $config->get('collectingaccount'),
      "count"            => 200,
      "cursor"           => -1,
      "skip_status"      => 1,
    ];

    do {

      $connection = new TwitterOAuth($config->get('consumer_key'), $config->get('consumer_secret'), $config->get('oauth_token'), $config->get('oauth_token_secret'));

      // Set type of api request.
      $endpoint = "friends/list";

      // Connect to Twitter and get the followings.
      $body = $connection->get($endpoint, $parameters);

      $next_cursor = $body->next_cursor;

      $parameters = [
        "cursor"           => $next_cursor,
      ];

      // If error in retrieving from Twitter, log the error.
      if (isset($connection->getLastBody()->errors)) {
        $this->logger->error($connection->getLastBody()->errors[0]->message);
        return [
          '#type'   => 'markup',
          '#markup' => 'Error importing',
        ];
      }

      // Loop through the downloaded followings and check if present.
      foreach ($body->users as $key => $user) {

        $checkfor = trim($user->screen_name);
        $checkid = trim($user->id_str);

        $database = \Drupal::database();
        $query = $database->query("SELECT twitterhandle FROM {tweet_link_compile_following} where twitterhandle like '" . $checkfor . "%'");
        $results = $query->fetchAll();

        if (!$results) {

          $insquery = $database->query("insert into {tweet_link_compile_following} (twitterhandle, twitteruserid) values ('" . $checkfor . "','" . $checkid . "');");

        }

        foreach ($results as $result) {

        }

      }

    } while ($next_cursor != 0);

    return [
      '#type'   => 'markup',
      '#markup' => 'End of followings import',
    ];

  }//end importUsers()

}
