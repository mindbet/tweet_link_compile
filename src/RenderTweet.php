<?php

namespace Drupal\tweet_link_compile;

use Drupal\Core\Url;
use Drupal\Core\Link;

/**
 * Class RenderTweet.
 */
class RenderTweet {

  /**
   * The tweet.
   *
   * @var object
   */
  protected $tweet;

  /**
   * Content of tweet.
   *
   * @var string
   */
  protected $content;

  /**
   * RenderTweet constructor.
   *
   * @param object $tweet
   *   Tweet.
   */
  public function __construct(\stdClass $tweet) {
    $this->tweet   = $tweet;
    $this->content = $tweet->full_text;
    if (isset($tweet->retweeted_status) === TRUE) {
      $this->content = 'RT @' . $tweet->retweeted_status->user->screen_name . ': ' . $tweet->retweeted_status->full_text;
    }

  }//end __construct()

  /**
   * Return rendered tweet.
   *
   * @return string
   *   Rendered tweet.
   */
  public function build() {

    // Does not take into account media, hashtags, user_mentions
    // or URLs of retweets.
    if (!isset($this->tweet->retweeted_status)) {
      !isset($this->tweet->entities->media) ?: $this->replaceMedia();
      !isset($this->tweet->entities->hashtags) ?: $this->replaceTags();
      !isset($this->tweet->entities->user_mentions) ?: $this->replaceUsers();
      !isset($this->tweet->entities->urls) ?: $this->replaceUrls();
    }

    if (isset($this->tweet->retweeted_status)) {
      !isset($this->tweet->retweeted_status->entities->media) ?: $this->replaceRetweetMedia();
      !isset($this->tweet->retweeted_status->entities->hashtags) ?: $this->replaceRetweetTags();
      !isset($this->tweet->retweeted_status->entities->user_mentions) ?: $this->replaceRetweetUsers();
      !isset($this->tweet->retweeted_status->entities->urls) ?: $this->replaceRetweetUrls();
    }

    return $this->content;

  }//end build()

  /**
   * Creating link.
   *
   * @param string $text
   *   Text for replace.
   * @param string $uri
   *   Link for replace.
   *
   * @return \Drupal\Core\GeneratedLink
   *   Link object.
   */
  private function createLink($text, $uri) {
    $url = Url::fromUri($uri);
    $url->setOption(
          'attributes',
          ['target' => '_blank']
      );

    return Link::fromTextAndUrl($text, $url)->toString();

  }//end createLink()

  /**
   * Replace entities in tweet.
   *
   * @param string $text
   *   Text for replace.
   * @param string $uri
   *   Link for replace.
   */
  private function entityReplace($text, $uri) {
    $link          = $this->createLink($text, $uri);
    $this->content = str_replace($text, $link, $this->content);

  }//end entityReplace()

  /**
   * Replace hashtags.
   */
  private function replaceTags() {
    foreach ($this->tweet->entities->hashtags as $hashtag) {
      $this->entityReplace(
            "#" . $hashtag->text,
            "https://twitter.com/hashtag/" . $hashtag->text
        );
    }

  }//end replaceTags()

  /**
   * Replace retweet hashtags.
   */
  private function replaceRetweetTags() {
    foreach ($this->tweet->retweeted_status->entities->hashtags as $hashtag) {
      $this->entityReplace(
            "#" . $hashtag->text,
            "https://twitter.com/hashtag/" . $hashtag->text
        );
    }

  }//end replaceRetweetTags()

  /**
   * Replace users.
   */
  private function replaceUsers() {
    foreach ($this->tweet->entities->user_mentions as $user) {
      $this->entityReplace(
            "@" . $user->screen_name,
            "https://twitter.com/" . $user->screen_name
        );
    }

  }//end replaceUsers()

  /**
   * Replace retweeted users.
   */
  private function replaceRetweetUsers() {
    foreach ($this->tweet->retweeted_status->entities->user_mentions as $user) {
      $this->entityReplace(
            "@" . $user->screen_name,
            "https://twitter.com/" . $user->screen_name
        );
    }

  }//end replaceRetweetUsers()

  /**
   * Replace urls.
   */
  private function replaceUrls() {
    foreach ($this->tweet->entities->urls as $url_value) {
      $this->entityReplace(
            $url_value->url,
            $url_value->url
        );
    }

  }//end replaceUrls()

  /**
   * Replace retweeted urls.
   */
  private function replaceRetweetUrls() {

    // Load the URLs already processed into an array
    // $lookup = array_column($this->tweet->entities->urls, 'url');.
    foreach ($this->tweet->retweeted_status->entities->urls as $url_value) {
      // Check if any of the URLs included with retweet have already been made
      // into links.
      // If already made into link, then skip.
      // if (!in_array($url_value->url, $lookup)) {.
      $this->entityReplace(
            $url_value->url,
            $url_value->url
        );
      // }
    }

  }//end replaceRetweetUrls()

  /**
   * Replace media.
   */
  private function replaceMedia() {
    foreach ($this->tweet->entities->media as $url_value) {
      $this->entityReplace(
            $url_value->url,
            $url_value->url
        );
    }

  }//end replaceMedia()

  /**
   * Replace retweeted media.
   */
  private function replaceRetweetMedia() {
    foreach ($this->tweet->retweeted_status->entities->media as $url_value) {
      $this->entityReplace(
            $url_value->url,
            $url_value->url
        );
    }

  }//end replaceRetweetMedia()

}//end class
