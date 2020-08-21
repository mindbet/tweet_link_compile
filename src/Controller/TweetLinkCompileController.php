<?php

namespace Drupal\tweet_link_compile\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\tweet_link_compile\TweetLinkCompileRepository;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for tweet link list.
 *
 * @ingroup tweet_link_compile
 */
class TweetLinkCompileController extends ControllerBase {

  /**
   * The repository for our specialized queries.
   *
   * @var \Drupal\tweet_link_compile\TweetLinkCompileRepository
   */

  protected $repository;

  /**
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *
   * @todo explain this
   *
   * @return controller
   */
  public static function create(ContainerInterface $container) {
    $controller = new static($container->get('tweet_link_compile.repository'));
    $controller->setStringTranslation($container->get('string_translation'));
    return $controller;

  }//end create()

  /**
   * Construct a new controller.
   *
   * @param \Drupal\tweet_link_compile\TweetLinkCompileRepository $repository
   *   The repository service.
   */
  public function __construct(TweetLinkCompileRepository $repository) {
    $this->repository = $repository;

  }//end __construct()

  /**
   * Render a list of entries in the database.
   *
   * @return array
   *   Render array (table) of database entries
   */
  public function entryList() {
    $content            = [];
    $content['message'] = [
      '#markup' => $this->t('Generate a list of all entries in the database. There is no filter in the query.'),
    ];

    $rows    = [];
    $headers = [
      $this->t('Link ID'),
      $this->t('Twitter UserID'),
      $this->t('Twitter Handle'),
      $this->t('Last Collected Time'),
      $this->t('Last Tweet ID'),
    ];

    foreach ($entries = $this->repository->load() as $entry) {
      // Sanitize each entry.
      $rows[] = array_map('Drupal\Component\Utility\Html::escape', (array) $entry);
    }

    $content['table'] = [
      '#type'   => 'table',
      '#header' => $headers,
      '#rows'   => $rows,
      '#empty'  => $this->t('No entries available.'),
    ];
    // Don't cache this page.
    $content['#cache']['max-age'] = 0;

    return $content;

  }//end entryList()

  /**
   * Render a filtered list of entries in the database.
   *
   * @return array
   *   render array
   */
  public function entryAdvancedList() {
    $content = [];

    $content['message'] = [
      '#markup' => $this->t('Recently collected links.'),
    ];

    $headers = [
      $this->t('Link ID'),
      $this->t('Twitter Handle'),
      $this->t('Link Title'),
      $this->t('Link Destination'),
      $this->t('Tweet ID'),
      $this->t('Time Tweeted'),
    // $this->t('Twitter Profile Image'),
    ];

    $rows = [];
    foreach ($entries = $this->repository->advancedLoad() as $entry) {
      // Sanitize each entry.
      $rows[] = array_map('Drupal\Component\Utility\Html::escape', $entry);
    }

    $content['table'] = [
      '#type'       => 'table',
      '#header'     => $headers,
      '#rows'       => $rows,
      '#attributes' => ['id' => 'tweet_link_compile-example-advanced-list'],
      '#empty'      => $this->t('No entries available.'),
    ];
    // Don't cache this page.
    $content['#cache']['max-age'] = 0;
    return $content;

  }//end entryAdvancedList()

}//end class
