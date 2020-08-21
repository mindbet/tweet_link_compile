<?php

namespace Drupal\tweet_link_compile\Services;

use Drupal\tweet_link_compile\RenderTweet;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Creates a drupal node from a tweets.
 */
class CreateNodeFromTweet {

  /**
   * The node storage.
   *
   * @var \Drupal\node\NodeStorage
   */
  protected $nodeStorage;

  /**
   * Constructs a new node.
   */
  public function __construct(EntityTypeManagerInterface $entity_manager) {
    $this->nodeStorage = $entity_manager->getStorage('node');
  }

  /**
   * Creating node.
   *
   * @param object $tweet
   *   Tweet for import.
   * @param string $tweet_type
   *   Tweet type.
   * @param string $query_name
   *   Query name.
   */
  public function createNode(\stdClass $tweet, $tweet_type = 'statuses/user_timeline', $query_name = '') {

    $render_tweet = new RenderTweet($tweet);

    $tweetid = $tweet->id;

    $tweetid = str_pad($tweetid, 19, "0", STR_PAD_LEFT);

    /*
     * @var \Drupal\node\NodeInterface $node
     */
    $node = $this->nodeStorage->create(
      [
        'type' => 'tweet',
        // 'field_tweet_id' => $tweet->id,
        'field_tweet_id' => $tweetid,
        'field_tweet_author' => [
          'uri' => $tweet_type == 'statuses/user_timeline' ? 'https://twitter.com/' . $tweet->user->screen_name : 'https://twitter.com/search?q=' . str_replace('#', '%23', $query_name),
          'title' => $tweet_type == 'statuses/user_timeline' ? $tweet->user->screen_name : $query_name,
        ],
        'title' => 'Tweet #' . $tweetid,
        'field_tweet_content' => [
          'value' => $render_tweet->build(),
          'format' => 'full_html',
        ],
        'created' => strtotime($tweet->created_at),
        'uid' => '1',
        'status' => 1,
      ]
    );

    if (isset($tweet->retweeted_status) === FALSE) {
      if (isset($tweet->entities->user_mentions)) {
        foreach ($tweet->entities->user_mentions as $user_mention) {
          $node->field_tweet_mentions->appendItem($user_mention->screen_name);
        }
      }

      if (isset($tweet->entities->hashtags)) {
        foreach ($tweet->entities->hashtags as $hashtag) {
          $node->field_tweet_hashtags->appendItem($hashtag->text);
        }
      }

      if (isset($tweet->entities->media)) {
        foreach ($tweet->entities->media as $media) {
          if ($media->type === 'photo') {
            $node->set('field_tweet_external_image', $media->media_url);
            $path_info = pathinfo($media->media_url_https);
            $data = file_get_contents($media->media_url_https);
            $dir = 'public://tweets/';
            if ($data && file_prepare_directory($dir, FILE_CREATE_DIRECTORY)) {
              $file = file_save_data($data, $dir . $path_info['basename'], FILE_EXISTS_RENAME);
              $node->set('field_tweet_local_image', $file);
            }
          }
        }
      }
    }//end if

    if (isset($tweet->retweeted_status)) {
      if (isset($tweet->retweeted_status->entities->user_mentions)) {
        foreach ($tweet->retweeted_status->entities->user_mentions as $user_mention) {
          if (self::checkDuplicateUsers($node->field_tweet_mentions, $user_mention->screen_name) === FALSE) {
            $node->field_tweet_mentions->appendItem($user_mention->screen_name);
          }
        }
      }

      if (isset($tweet->retweeted_status->entities->hashtags)) {
        foreach ($tweet->retweeted_status->entities->hashtags as $hashtag) {
          if (self::checkDuplicateHashtags($node->field_tweet_hashtags, $hashtag->text) === TRUE) {
            $node->field_tweet_hashtags->appendItem($hashtag->text);
          }
        }
      }

      if (isset($tweet->retweeted_status->entities->media)) {
        foreach ($tweet->retweeted_status->entities->media as $media) {
          if ($media->type === 'photo') {
            $node->set('field_tweet_external_image', $media->media_url);
            $path_info = pathinfo($media->media_url_https);
            $data = file_get_contents($media->media_url_https);
            $dir = 'public://tweets/';
            if ($data && file_prepare_directory($dir, FILE_CREATE_DIRECTORY)) {
              $file = file_save_data($data, $dir . $path_info['basename'], FILE_EXISTS_RENAME);
              $node->set('field_tweet_local_image', $file);
            }
          }
        }
      }
    }//end if

    $node->save();

  }//end createNode()

  /**
   * Check if user already exists.
   *
   * @param array $users
   *   List of new users to check.
   * @param string $tweetuser
   *   List of existing users.
   */
  public function checkDuplicateUsers(array $users, $tweetuser) {
    foreach ($users as $user) {
      if ($user === $tweetuser) {
        return TRUE;
      }
    }

    return FALSE;

  }//end checkDuplicateUsers()

  /**
   * Remove duplicate hashtags.
   */
  public function checkDuplicateHashtags($hashtags, $tweethash) {
    foreach ($hashtags as $hashtag) {
      if ($hashtag === $tweethash) {
        return TRUE;
      }
    }

    return FALSE;

  }//end checkDuplicateHashtags()

}
