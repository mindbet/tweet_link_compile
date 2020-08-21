<?php

/**
 * @file
 * Provide views data for the tweet_link_compile module.
 */

/**
 * Implements hook_views_data().
 */
function tweet_link_compile_views_data() {
  $data                                = [];
  $data['tweet_link_compile']          = [];
  $data['tweet_link_compile']['table'] = [];

  // Base table.
  $data['tweet_link_compile']['table']['group'] = t('Embedded links');

  $data['tweet_link_compile']['table']['base'] = [
    'field' => 'lkid',
    'title' => t('Embedded links'),
    'help'  => t('Links embedded in tweets.'),
  ];

  // Join for files table.
  // $data['tweet_link_compile']['table']['join']['file_managed'] = [
  // 'left_field' => 'fid',
  // 'field' => 'fid',
  // ];.
  $data['tweet_link_compile']['lkid'] = [
    'title'    => t('ID'),
    'help'     => t('The link ID of the extracted link.'),
    'field'    => ['id' => 'numeric'],
    'filter'   => ['id' => 'numeric'],
    'sort'     => ['id' => 'standard'],
    'argument' => ['id' => 'numeric'],
  ];

  $data['tweet_link_compile']['twitterhandle'] = [
    'title'  => t('User'),
    'help'   => t('Twitter handle of the user who made the tweet.'),
    'field'  => ['id' => 'standard'],
    'filter' => ['id' => 'standard'],
    'sort'   => ['id' => 'standard'],
  ];

  $data['tweet_link_compile']['twitterprofileimagelink'] = [
    'title'  => t('User'),
    'help'   => t('Twitter user profile image.'),
    'field'  => ['id' => 'standard'],
    'filter' => ['id' => 'standard'],
    'sort'   => ['id' => 'standard'],
  ];

  $data['tweet_link_compile']['linkdestination'] = [
    'title'  => t('External resource'),
    'help'   => t('External resource URL.'),
    'field'  => ['id' => 'standard'],
    'filter' => ['id' => 'standard'],
    'sort'   => ['id' => 'standard'],
  ];

  $data['tweet_link_compile']['linktitle'] = [
    'title'  => t('Resource title'),
    'help'   => t('The title of the linked document.'),
    'field'  => ['id' => 'standard'],
    'filter' => ['id' => 'standard'],
    'sort'   => ['id' => 'standard'],
  ];

  $data['tweet_link_compile']['tweetid'] = [
    'title'    => t('Tweet'),
    'help'     => t('The parent tweet for the link.'),
    'field'    => ['id' => 'standard'],
    'filter'   => ['id' => 'standard'],
    'sort'     => ['id' => 'standard'],
    'argument' => ['id' => 'string'],
  ];

  $data['tweet_link_compile']['timetweeted'] = [
    'title'  => t('Time tweeted'),
    'help'   => t('Time stamp for the tweet.'),
    'field'  => ['id' => 'date'],
    'filter' => ['id' => 'date'],
    'sort'   => ['id' => 'date'],
  ];

  return $data;

}//end tweet_link_compile_views_data()

/*
 * Implements hook_views_data_alter().
 */
// Function tweet_link_compile_views_data_alter(&$data) {
// Join for files table.
// $data['file_managed']['table']['join']['tweet_link_compile'] = [
// 'left_field' => 'fid',
// 'field' => 'fid',
// ];
// }.
