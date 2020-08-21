<?php

/**
 * @file
 * Provide views data for the tweet_link_compile module.
 */

/**
 * Implements hook_views_data().
 */
function tweet_link_compile_views_data() {
  $data = [];
  $data['tweet_link_compile'] = [];
  $data['tweet_link_compile']['table'] = [];

  // Base table.
  $data['tweet_link_compile']['table']['group'] = t('Tweet links');
  $data['tweet_link_compile']['table']['base'] = [
    'field' => 'lkid',
    'title' => t('Embedded links'),
    'help' => t('Links embedded in tweets.'),
  ];

  $data['tweet_link_compile']['lkid'] = [
    'title' => t('Link ID'),
    'help' => t('The link ID of the extracted link.'),
    'field' => ['id' => 'numeric'],
    'filter' => ['id' => 'numeric'],
    'sort' => ['id' => 'standard'],
    'argument' => ['id' => 'numeric'],
  ];

  $data['tweet_link_compile']['twitterhandle'] = [
    'title' => t('Tweet author handle'),
    'help' => t('The handle of the user who made the tweet.'),
    'field' => ['id' => 'standard'],
    'filter' => ['id' => 'standard'],
    'sort' => ['id' => 'standard'],
  ];

  $data['tweet_link_compile']['linkdestination'] = [
    'title' => t('Link destination'),
    'help' => t('External resource URL.'),
    'field' => ['id' => 'standard'],
    'filter' => ['id' => 'standard'],
    'sort' => ['id' => 'standard'],
  ];

  $data['tweet_link_compile']['linktitle'] = [
    'title' => t('Link title'),
    'help' => t('The title of the linked document.'),
    'field' => ['id' => 'standard'],
    'filter' => ['id' => 'standard'],
    'sort' => ['id' => 'standard'],
  ];

  $data['tweet_link_compile']['tweetid'] = [
    'title' => t('Parent tweet ID'),
    'help' => t('The parent tweet for the link.'),
    'field' => ['id' => 'standard'],
    'filter' => ['id' => 'standard'],
    'sort' => ['id' => 'standard'],
    'argument' => ['id' => 'string'],
  ];

  $data['tweet_link_compile']['timetweeted'] = [
    'title' => t('Time tweeted'),
    'help' => t('Time stamp for the tweet.'),
    'field' => ['id' => 'date'],
    'filter' => ['id' => 'date'],
    'sort' => ['id' => 'date'],
  ];

  $data['tweet_link_compile_following'] = [];

  $data['tweet_link_compile_following']['table'] = [];

  // Base table.
  $data['tweet_link_compile_following']['table']['group'] = t('Followed Twitter users');
  $data['tweet_link_compile_following']['table']['base'] = [
    'field' => 'lkuid',
    'title' => t('Twitter users'),
    'help' => t('People we are following.'),
  ];

  $data['tweet_link_compile_following']['twitterhandle'] = [
    'title' => t('Tweet author handle'),
    'help' => t('Twitter name of the user who made the tweet.'),
    'field' => ['id' => 'standard'],
    'filter' => ['id' => 'standard'],
    'sort' => ['id' => 'standard'],
  ];

  $data['tweet_link_compile_following']['lastcollectedtime'] = [
    'title' => t('Profile image'),
    'help' => t('Last collection time for this user.'),
    'field' => ['id' => 'standard'],
    'filter' => ['id' => 'standard'],
    'sort' => ['id' => 'standard'],
  ];

  $data['tweet_link_compile_following']['lasttweetid'] = [
    'title' => t('Most recent tweet'),
    'help' => t('Most recent tweet from this user.'),
    'field' => ['id' => 'standard'],
    'filter' => ['id' => 'standard'],
    'sort' => ['id' => 'standard'],
  ];

  return $data;

}
