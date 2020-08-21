<?php

namespace Drupal\tweet_link_compile\Controller;

use Drupal\Core\Controller\ControllerBase;
use Abraham\TwitterOAuth\TwitterOAuth;

/**
 * Compiles the most popular links.
 */
class CompileMostPopular extends ControllerBase {

  /**
   * {@inheritdoc}
   *
   * @return array
   *   Results of compilation.
   */
  public function report() {

    $config = \Drupal::config('tweet_link_compile.settings');

    $database = \Drupal::database();
    $decayhours = $config->get('decay') ?? 48;
    $linkexpire = (time() - ($decayhours * 60 * 60));

    $query = $database->query("SELECT count(distinct twitterhandle) as CT, max(linktitle) as mlt, max(linkfinaldestination) as mld, max(timetweeted) as mtt FROM {tweet_link_compile} where (timetweeted > $linkexpire) group by linkfinaldestination order by ct desc, mtt desc limit 10");

    $results = $query->fetchAll();

    foreach ($results as $result) {
      $targeturl = $result->mld;
      $title = $result->mlt;

      if (!$title) {

      $title = '';

      if (strstr($targeturl, 'https://twitter.com/')) {
        preg_match('/https:\/\/twitter.com\/(.*)\/status\/(.*)/', $targeturl, $matches);

        $connection = new TwitterOAuth($config->get('consumer_key'), $config->get('consumer_secret'), $config->get('oauth_token'), $config->get('oauth_token_secret'));

        $parameters = [
          "id"      => $matches[2],
          "tweet_mode"       => 'extended',
          "include_entities" => TRUE,
        ];

        $endpoint = 'statuses/show';
        $tweet = $connection->get($endpoint, $parameters);

        $title = $tweet->full_text;

        // Convert entities.
        $title = html_entity_decode($title, ENT_QUOTES, 'UTF-8');

        $title = $tweet->user->name . ' on Twitter: ' . $title;
      }

      else {

        try {
          $fp = @file_get_contents($targeturl);

          if ($fp) {
            $res = preg_match("/<title(.*)>(.*)<\/title>/siU", $fp, $title_matches);

            // If title is found.
            if ($res) {

              $title = preg_replace('/\s+/', ' ', $title_matches[2]);

              // Convert entities.
              $title = html_entity_decode($title, ENT_QUOTES, 'UTF-8');

              if (strstr($title, 'DEFINE_ME')) {
                $title = $targeturl;
              }

              if (strstr($title, 'Bloomberg - Are you a robot?')) {
                $title = $targeturl;
              }

              if (strlen(trim($title)) == 0) {
                $title = $targeturl;
              }

              $fields = array(
                'linktitle' => $title,
              );
              \Drupal::database()
                ->update('tweet_link_compile')
                ->fields($fields)
                ->condition('tweet_link_compile.linkfinaldestination', $targeturl)
                ->execute();
            }//end if
            else {
              $title = $targeturl;
            }
          }
          else {
            // Set the title to the URL.
            $title = $targeturl;

            // Log the error.
            $urlnotloaded = 'Document not retrieved: ' . $targeturl;
            \Drupal::logger('tweet_link_compile')->notice($urlnotloaded);
            // Handle the error.
            // throw new Exception("File_get_contents not successful.");.
          }//end if
        }
        catch (Exception $e) {
          // Handle exception.
          \Drupal::logger('tweet_link_compile')->error($e->getMessage());
        }//end try

      }
      }


      $result->mlt = $title;

      $detailquery = $database->query("SELECT twitterhandle, max(twitterprofileimagelink) as mtpl, max(linkfinaldestination) as mldd, max(tweetid) as mtwid FROM {tweet_link_compile} WHERE linkfinaldestination = '" . $result->mld . "' group by twitterhandle");







      $detailresults = $detailquery->fetchAll();

      $recommender = [];

      foreach ($detailresults as $key => $value) {
        $recommender[$key]['iduser'] = $value->twitterhandle;
        $recommender[$key]['iduserpic'] = $value->mtpl;
        $recommender[$key]['idtweetlink'] = $value->mtwid;
      }

      $result->recommenders = $recommender;
    }

    return [
      '#theme'      => 'tweet_link_compile_page',
      '#text'       => 'Most popular links',
      '#myvariable' => $results,
      '#cache'      => [
        'keys'     => ['my_page'],
        'contexts' => ['route'],
        'tags'     => ['rendered'],
        'max-age'  => 900,
      ],
    ];

  }//end report()

}//end class
