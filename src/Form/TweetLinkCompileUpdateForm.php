<?php

namespace Drupal\tweet_link_compile\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\tweet_link_compile\TweetLinkCompileRepository;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Sample UI to update a record.
 *
 * @ingroup tweet_link_compile
 */
class TweetLinkCompileUpdateForm extends FormBase {

  /**
   * Our database repository service.
   *
   * @var \Drupal\tweet_link_compile\TweetLinkCompileRepository
   */
  protected $repository;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'tweetlinkcompile_update_form';

  }//end getFormId()

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $form = new static($container->get('tweet_link_compile.repository'));
    $form->setStringTranslation($container->get('string_translation'));
    $form->setMessenger($container->get('messenger'));
    return $form;

  }//end create()

  /**
   * Construct the new form object.
   */
  public function __construct(TweetLinkCompileRepository $repository) {
    $this->repository = $repository;

  }//end __construct()

  /**
   * Sample UI to update a record.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Wrap the form in a div.
    $form = [
      '#prefix' => '<div id="updateform">',
      '#suffix' => '</div>',
    ];
    // Add some explanatory text to the form.
    $form['message'] = [
      '#markup' => $this->t('Update a user record.'),
    ];
    // Query for items to display.
    $entries = $this->repository->load();
    // Tell the user if there is nothing to display.
    if (empty($entries)) {
      $form['no_values'] = [
        '#value' => $this->t('No entries exist in the table tweet_link_compile table.'),
      ];
      return $form;
    }

    $keyed_entries = [];
    $options       = [];
    foreach ($entries as $entry) {
      $options[$entry->lkuid]       = $this->t(
            '@lkuid: @twitterhandle',
            [
              '@lkuid'         => $entry->lkuid,
              '@twitterhandle' => $entry->twitterhandle,
            ]
        );
      $keyed_entries[$entry->lkuid] = $entry;
    }

    // Grab the lkuid.
    $lkuid = $form_state->getValue('lkuid');
    // Use the lkuid to set the default entry for updating.
    $default_entry = !empty($lkuid) ? $keyed_entries[$lkuid] : $entries[0];

    // Save the entries into the $form_state. We do this so the AJAX callback
    // doesn't need to repeat the query.
    $form_state->setValue('entries', $keyed_entries);

    $form['lkuid'] = [
      '#type'          => 'select',
      '#options'       => $options,
      '#title'         => $this->t('Choose entry to update'),
      '#default_value' => $default_entry->lkuid,
      '#ajax'          => [
        'wrapper'  => 'updateform',
        'callback' => [
          $this,
          'updateCallback',
        ],
      ],
    ];

    $form['twitterhandle'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Updated Twitter user handle'),
      '#size'          => 16,
      '#default_value' => $default_entry->twitterhandle,
    ];

    $form['lastcollectedtime'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Last collection time'),
      '#size'          => 32,
      '#default_value' => $default_entry->lastcollectedtime,
    ];

    $form['submit'] = [
      '#type'  => 'submit',
      '#value' => $this->t('Update'),
    ];
    return $form;

  }//end buildForm()

  /**
   * AJAX callback handler for the lkuid select.
   *
   * When the lkuid changes, loads the defaults from the database in the form.
   */
  public function updateCallback(array $form, FormStateInterface $form_state) {
    // Gather the DB results from $form_state.
    $entries = $form_state->getValue('entries');
    // Use the specific entry for this $form_state.
    $entry = $entries[$form_state->getValue('lkuid')];
    // Setting the #value of items is the only way I was able to figure out
    // to get replaced defaults on these items. #default_value will not do it
    // and shouldn't.
    foreach (['twitterhandle', 'lastcollectedtime'] as $item) {
      $form[$item]['#value'] = $entry->$item;
    }

    return $form;

  }//end updateCallback()

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }//end validateForm()

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Gather the current user so the new record has ownership.
    // Save the submitted entry.
    $entry = [
      'lkuid'                   => $form_state->getValue('lkuid'),
      'twitterhandle'           => $form_state->getValue('twitterhandle'),
      'lastcollectedtime' => $form_state->getValue('lastcollectedtime'),
    ];
    $count = $this->repository->update($entry);
    $this->messenger()->addMessage(
          $this->t(
              'Updated entry @entry (@count row updated)',
              [
                '@count' => $count,
                '@entry' => print_r($entry, TRUE),
              ]
          )
      );

  }//end submitForm()

}//end class
