<?php

namespace Drupal\tweet_link_compile\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Build Tweet link compile settings form.
 */
class TweetLinkCompileSettings extends ConfigFormBase {

  /**
   * Date formatter.
   *
   * @var \Drupal\core\datetime\DateFormatter
   */
  protected $dateFormatter;

  /**
   * Class constructor.
   */
  public function __construct(ConfigFactoryInterface $config_factory, $date_formatter) {
    parent::__construct($config_factory);
    $this->dateFormatter = $date_formatter;

  }//end __construct()

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
          $container->get('config.factory'),
          $container->get('date.formatter')
      );

  }//end create()

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'tweet_link_compile_settings_form';

  }//end getFormId()

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['tweet_link_compile.settings'];

  }//end getEditableConfigNames()

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config  = $this->config('tweet_link_compile.settings');
    $queries = $config->get('queries');

    // Load queries from Database.
    $database = \Drupal::database();
    $query    = $database->query("SELECT lkuid, twitterhandle FROM {tweet_link_compile_following}");
    $results  = $query->fetchAll();

    if ($queries === NULL || $form_state->get('queries')) {
      $queries = $form_state->get('queries') ? $form_state->get('queries') : [['query' => '']];
    }

    $form_state->set('queries', $results);

    $form['import'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Import tweets'),
      '#default_value' => $config->get('import'),
    ];

    $lookup = array_column($results, 'twitterhandle');

    $rendered = implode("\n", $lookup);

    $form['queries'] = [
      '#type'          => 'textarea',
      '#tree'          => TRUE,
      '#header'        => [
        $this->t('Query'),
        $this->t('Operations'),
      ],
      '#title'         => $this->t('Search Queries'),
      '#description'   => $this->t('Input your search queries here.'),
      '#prefix'        => '<div id="queries-table-wrapper">',
      '#suffix'        => '</div>',
      '#rows'          => '20',
      '#cols'          => '40',
      '#default_value' => $rendered,
    ];

    $form['count'] = [
      '#type'          => 'number',
      '#title'         => $this->t('Tweets count'),
      '#default_value' => $config->get('count'),
      '#min'           => 1,
      '#max'           => 200,
    ];

    $form['loadcount'] = [
      '#type'          => 'number',
      '#title'         => $this->t('Users to process at once'),
      '#default_value' => $config->get('loadcount'),
      '#min'           => 1,
      '#max'           => 200,
    ];

    $form['decay'] = [
      '#type'          => 'number',
      '#title'         => $this->t('Decay period (h)'),
      '#default_value' => $config->get('decay'),
      '#min'           => 1,
      '#max'           => 168,
    ];



    $form['collectingaccount'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Twitter account used to collect tweets'),
      '#default_value' => $config->get('collectingaccount'),
    ];




    $form['oauth'] = [
      '#type'        => 'fieldset',
      '#title'       => $this->t('OAuth Settings'),
      '#description' => $this->t('To enable OAuth based access for twitter, you must <a href="@url">register your application</a> with Twitter and add the provided keys here.', ['@url' => 'https://apps.twitter.com/apps/new']),
    ];

    $form['oauth']['consumer_key'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('OAuth Consumer key'),
      '#default_value' => $config->get('consumer_key'),
      '#required'      => TRUE,
    ];

    $form['oauth']['consumer_secret'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('OAuth Consumer secret'),
      '#default_value' => $config->get('consumer_secret'),
      '#required'      => TRUE,
    ];

    $form['oauth']['oauth_token'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Access Token'),
      '#default_value' => $config->get('oauth_token'),
    ];

    $form['oauth']['oauth_token_secret'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Access Token Secret'),
      '#default_value' => $config->get('oauth_token_secret'),
    ];


    $form['savenodes'] = [
      '#type'          => 'select',
      '#title'         => $this->t('Save statuses as nodes'),
      '#default_value' => $config->get('savenodes'),
      '#options'       => [0 => $this->t('No'), 1 => $this->t('Yes')],
    ];

    $intervals = [
      604800,
      2592000,
      7776000,
      31536000,
    ];

    $form['expire'] = [
      '#type'          => 'select',
      '#title'         => $this->t('Delete old statuses'),
      '#default_value' => $config->get('expire'),
      '#options'       => ([0 => $this->t('Never')] + array_map(
          [
            $this->dateFormatter,
            'formatInterval',
          ],
          array_combine($intervals, $intervals)
        )),
    ];





    $form_state->setCached(FALSE);
    return parent::buildForm($form, $form_state);

  }//end buildForm()

  /**
   * Callback for adding more queries.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function addMore(array &$form, FormStateInterface $form_state) {
    $form_state->setRebuild(TRUE);
    $queries = $form_state->get('queries');
    array_push($queries, ['query' => '']);
    $form_state->set('queries', $queries);

  }//end addMore()

  /**
   * Callback for remove query.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function removeCallback(array &$form, FormStateInterface $form_state) {
    $form_state->setRebuild(TRUE);
    $queries           = $form_state->get('queries');
    $triggered_element = $form_state->getTriggeringElement();
    unset($queries[$triggered_element['#parents'][1]]);
    $form_state->set('queries', $queries);

  }//end removeCallback()

  /**
   * Callback for queries.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   Render array for queries.
   */
  public function queries(array &$form, FormStateInterface $form_state) {
    return $form['queries'];

  }//end queries()

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }//end validateForm()

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->cleanValues()->getValues();

    $searchlist = explode(PHP_EOL, $values['queries']);

    $searchlist = array_filter($searchlist);

    $searchlist = array_unique($searchlist);

    $database = \Drupal::database();

    $querylookup = $database->query("SELECT twitterhandle FROM {tweet_link_compile_following}")
      ->fetchCol();

    $querylookup = array_filter($querylookup);

    $querylookup = array_unique($querylookup);

    $adds = array_diff($searchlist, $querylookup);

    $drops = array_diff($querylookup, $searchlist);

    // Remove no longer included followings.
    foreach ($drops as $todrop) {
      $queryclean = $database->delete('tweet_link_compile_following')
        ->condition('twitterhandle', $todrop, '=')
        ->execute();
    }

    // Add new followings.
    foreach ($adds as $toadd) {
      $queryadd = $database->insert('tweet_link_compile_following')
        ->fields(['twitterhandle'])
        ->values(
          ['twitterhandle' => $toadd]
          )
        ->execute();
    }

    $this->config('tweet_link_compile.settings')
      ->setData($values)
      ->save();

    $this->messenger()->addMessage($this->t('Changes saved.'));

  }//end submitForm()

}//end class
