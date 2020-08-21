<?php

namespace Drupal\tweet_link_compile;

use Abraham\TwitterOAuth\TwitterOAuth;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\RedirectMiddleware;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

/**
 * Class GetTweetsImport.
 */
class GetTweetsBase {

  /**
   * Drupal logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */

  protected $logger;

  /**
   * The node storage.
   *
   * @var \Drupal\node\NodeStorage
   */
  protected $nodeStorage;

  /**
   * The GetTweets settings.
   *
   * @var \Drupal\Core\Config\ConfigInterface
   */
  protected $tweetLinkCompileSettings;

  /**
   * Twitter connection object.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Constructs a GetTweetsBase object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger
   *   The logger.
   */
  public function __construct(EntityTypeManagerInterface $entity_manager, ConfigFactoryInterface $config_factory, LoggerChannelFactoryInterface $logger) {
    $this->nodeStorage = $entity_manager->getStorage('node');
    $this->tweetLinkCompileSettings = $config_factory->get('tweet_link_compile.settings');
    $this->logger = $logger->get('tweet_link_compile');

  }//end __construct()

  /**
   * Returns TwitterOAuth object or null.
   *
   * @param string $consumer_key
   *   The Application Consumer Key.
   * @param string $consumer_secret
   *   The Application Consumer Secret.
   * @param string|null $oauth_token
   *   The Client Token (optional).
   * @param string|null $oauth_token_secret
   *   The Client Token Secret (optional).
   *
   * @return \Abraham\TwitterOAuth\TwitterOAuth|null
   *   Returns TwitterOAuth object or null.
   */
  public function getConnection($consumer_key, $consumer_secret, $oauth_token = NULL, $oauth_token_secret = NULL) {
    $connection = new TwitterOAuth($consumer_key, $consumer_secret, $oauth_token, $oauth_token_secret);

    if ($connection) {
      return $connection;
    }

    return NULL;

  }//end getConnection()

  /**
   * Import tweets.
   */
  public function import() {
    $config     = $this->tweetLinkCompileSettings;
    $connection = $this->getConnection($config->get('consumer_key'), $config->get('consumer_secret'), $config->get('oauth_token'), $config->get('oauth_token_secret'));

    if (!$config->get('import') && !$connection) {
      return;
    }

    // Max number of tweets to retrieve for each twitterhandle.
    $count = $config->get('count');
    $loadcount = $config->get('loadcount');

    // Load all twitterhandles from database.
    $database       = \Drupal::database();
    $loadusersquery = $database->query("SELECT lkuid, twitterhandle, lasttweetid FROM {tweet_link_compile_following} order by lastcollectedtime asc limit $loadcount");
    $queries        = $loadusersquery->fetchAll();

    // Loop through twitterhandles.
    foreach ($queries as $query) {
      // Parameters for twitter api request.
      $parameters = [
        "screen_name"      => $query->twitterhandle,
        "count"            => $count,
        "tweet_mode"       => 'extended',
        "include_entities" => TRUE,
      ];

      $currentuserid = $query->lkuid;
      $currentuser = $query->twitterhandle;

      // Set type of api request.
      $endpoint = 'statuses/user_timeline';

      // Remove @ from twitterhandle.
      $querystring = trim($query->twitterhandle, '@');
      $querystring = trim($querystring);

      if (strlen($query->lasttweetid) > 0) {
        $parameters['since_id'] = $query->lasttweetid;
      }

      // Get the tweets.
      $tweets = $connection->get($endpoint, $parameters);

      if (isset($connection->getLastBody()->errors)) {
        $this->logger->error($connection->getLastBody()->errors[0]->message);
        return;
      }

      // Log the last collection time to the following table.
      $lastcollectedtime = time();
      $query = \Drupal::database()->update('tweet_link_compile_following');
      $query->fields(['lastcollectedtime' => $lastcollectedtime]);
      $query->condition('lkuid', $currentuserid, '=');
      $query->execute();

      $savenodes = $config->get('savenodes');

      // If ($endpoint == 'search/tweets') {
      // $tweets = $tweets->statuses;
      // }.
      if ($tweets && empty($tweets->errors)) {
        foreach ($tweets as $tweet) {

          if ($savenodes == 1) {
            // Make tweets into nodes.
            $service = \Drupal::service('tweet_link_compile.createnode');
            $service->createNode($tweet, $endpoint, $currentuser);
          }

          $this->logUrl($tweet);

          // Log the last tweet collected to the following table.
          $query = \Drupal::database()->update('tweet_link_compile_following');
          $query->fields(['lasttweetid' => $tweet->id]);
          $query->condition('lkuid', $currentuserid, '=');
          $query->condition('lasttweetid', $tweet->id, '<');
          $query->execute();

        }
      }
    }//end foreach

  }//end import()

  /**
   * Logging external URL.
   *
   * @param object $tweet
   *   Tweet for parsing.
   */
  private function logUrl(\stdClass $tweet) {
    // $tweet = new RenderTweet($tweet);
    $this->tweet = $tweet;

    // Pull the URLs from the tweet.
    if (!isset($this->tweet->retweeted_status)) {

      foreach ($this->tweet->entities->urls as $url_value) {
        $targeturl    = $url_value->url;
        $targetexpurl = $url_value->expanded_url;

        // Set values to save to database.
        $twitterhandle           = $this->tweet->user->screen_name;
        $twitterprofileimagelink = $this->tweet->user->profile_image_url_https;
        $tweettimestring         = $this->tweet->created_at;

        $this->followRedirects($targetexpurl);

        if (
          (strstr($targetexpurl, 'youtube.com'))
          || (strstr($targetexpurl, 'facebook.com/photo.php'))
        ) {
          $targetexpurlstrip = $targetexpurl;
        }
        else {
          $targetexpurlstrip = strtok($targetexpurl, '?');
        }

        $tweetdate = strtotime($tweettimestring);
        $tweetid   = $this->tweet->id;

        // Pad tweetid to 19 characters if necessary.
        $tweetid = str_pad($tweetid, 19, "0", STR_PAD_LEFT);

        // Save to tweetlinkcompile table.
        // Build new record.
        $entry = [
          'twitterhandle'           => $twitterhandle,
          'twitterprofileimagelink' => $twitterprofileimagelink,
          'linkdestination'         => $targeturl,
          'linkfinaldestination'    => $targetexpurlstrip,
          'timetweeted'             => $tweetdate,
          'tweetid'                 => $tweetid,
        ];

        // Insert data into database.
        $connection = \Drupal::database();
        $connection->insert('tweet_link_compile')->fields($entry)->execute();

      }//end foreach
    }//end if

    if (isset($this->tweet->retweeted_status)) {

      foreach ($this->tweet->retweeted_status->entities->urls as $url_value) {
        // Loop through URLs embedded in tweet.
        $targeturl = $url_value->url;
        $targetexpurl = $url_value->expanded_url;

        // Set values to be stored in database.
        $twitterhandle           = $this->tweet->retweeted_status->user->screen_name;
        $twitterprofileimagelink = $this->tweet->retweeted_status->user->profile_image_url_https;
        $tweettimestring         = $this->tweet->retweeted_status->created_at;
        $tweetdate               = strtotime($tweettimestring);

        $this->followRedirects($targetexpurl);

        if (
             (strstr($targetexpurl, 'youtube.com'))
          || (strstr($targetexpurl, 'facebook.com/photo.php'))
          ) {
          $targetexpurlstrip = $targetexpurl;
        }
        else {
          $targetexpurlstrip = strtok($targetexpurl, '?');
        }

        $tweetid = $this->tweet->retweeted_status->id;
        // Pad tweetid to 19 characters if necessary.
        $tweetid = str_pad($tweetid, 19, "0", STR_PAD_LEFT);

        // Save to tweetlinkcompile table
        // Build new record.
        $entry = [
          'twitterhandle'           => $twitterhandle,
          'twitterprofileimagelink' => $twitterprofileimagelink,
          'linkdestination'         => $targeturl,
          'linkfinaldestination'    => $targetexpurlstrip,
          'timetweeted'             => $tweetdate,
          'tweetid'                 => $tweetid,
        ];

        // Insert data into database.
        $connection = \Drupal::database();
        $connection->insert('tweet_link_compile')->fields($entry)->execute();

      }//end foreach
    }//end if

  }//end logUrl()

  /**
   * Follow redirects.
   */
  private function followRedirects($url) {

    if ((strstr($url, 'https://wapo.st/'))
      || (strstr($url, 'https://nyti.ms/'))
      || (strstr($url, 'https://bit.ly/'))
      || (strstr($url, 'https://trib.al/'))
      || (strstr($url, 'http://apne.ws/'))
      || (strstr($url, 'http://dlvr.it/'))
      || (strstr($url, 'https://propub.li/'))
      || (strstr($url, 'https://politi.co/'))
      || (strstr($url, 'https://buff.ly/'))
      || (strstr($url, 'https://abcn.ws/'))
      || (strstr($url, 'https://ift.tt/'))
    ) {
      try {
        $this->client    = new Client(['allow_redirects' => ['track_redirects' => TRUE]]);
        $response        = $this->client->get($url);
        $headersRedirect = $response->getHeader(RedirectMiddleware::HISTORY_HEADER);
        if (array_count_values($headersRedirect)) {
          $highkey        = max(array_keys($headersRedirect));
          $urldestination = $headersRedirect[$highkey];
          $urldestination = parse_url($urldestination, PHP_URL_HOST) . parse_url($urldestination, PHP_URL_PATH);
          $url            = 'https://' . $urldestination;
        }
      }
      catch (ConnectException $e) {
        // This is will catch all connection timeouts
        // Handle accordinly.
        \Drupal::logger('tweet_link_comple')->error($e->getMessage());
      }
      catch (ClientException $e) {
        // This will catch all 400 level errors.
        // return $e->getResponse()->getStatusCode();
        \Drupal::logger('tweet_link_comple')->error($e->getMessage());
      }
      catch (RequestException $e) {
        \Drupal::logger('tweet_link_comple')->error($e->getMessage());
      }//end try

      return($url);
    }

  }

  /**
   * Run all tasks.
   */
  public function runAll() {
    $this->import();
    $this->cleanup();

  }//end runAll()

  /**
   * Delete old tweets.
   */
  public function cleanup() {
    $config = $this->tweetLinkCompileSettings;
    $expire = $config->get('expire');

    if ($expire == FALSE) {
      return;
    }

    $storage = $this->nodeStorage;
    $query   = $storage->getQuery();
    $query->condition('created', (time() - $expire), '<');
    $query->condition('type', 'tweet');
    $result = $query->execute();
    $nodes  = $storage->loadMultiple($result);

    foreach ($nodes as $node) {
      $node->delete();
    }

  }//end cleanup()

}//end class
