<?php

namespace Drupal\tweet_link_compile\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

use Abraham\TwitterOAuth\TwitterOAuth;


/**
 * Class GetFriendsForm.
 */
class GetFriendsForm extends FormBase {

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'get_friends_form';
  }//end getFormId()



  /**
   * Form constructor.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The form structure.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form = [
      '#prefix' => '<div id="updateform">',
      '#suffix' => '</div>',
    ];

    $form['description'] = [
      '#type' => 'item',
      '#markup' => $this->t('User to find friends.'),
    ];



    $form['baseuser'] = [
      '#type'          => 'textfield',
      '#header'        => [
        $this->t('Query'),
        $this->t('Operations'),
      ],
      '#title'         => $this->t('Base friend'),
      '#description'   => $this->t('Input your search queries here.'),
    ];


    $form['actions'] = [
      '#type' => 'actions',
    ];

    // Add a submit button that handles the submission of the form.
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;


  }//end buildForm()


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

    $parameters = [
      "screen_name"      => $form_state->getValue('baseuser'),
      "count"            => 200,
      "cursor"           => -1,
      "skip_status"      => 1,
    ];

    $config = \Drupal::config('tweet_link_compile.settings');


    $connection = new TwitterOAuth($config->get('consumer_key'), $config->get('consumer_secret'), $config->get('oauth_token'), $config->get('oauth_token_secret'));
    $endpoint = "friends/list";


    do {

      // Set type of api request.
      // $endpoint = "followers/list";
      // Connect to Twitter and get the followings.
      $body = $connection->get($endpoint, $parameters);


      // If error in retrieving from Twitter, log the error.
      if (isset($connection->getLastBody()->errors)) {
//        $this->logger->error($connection->getLastBody()->errors[0]->message);
        return [
          '#type'   => 'markup',
          '#markup' => 'Connection error on friends import',
        ];
      }


      $next_cursor = $body->next_cursor;
      $parameters = [
        "cursor"           => $body->next_cursor,
      ];


      // Loop through the downloaded followings and add to database.
      foreach ($body->users as $key => $user) {

        $checkfor = trim($user->screen_name);
        $checkid = trim($user->id_str);

        $sourceuser = $form_state->getValue('baseuser');

        $database = \Drupal::database();

        $result = $database->insert('tweet_link_compile_extend')
          ->fields([
            'friendtwitteruserid' => $checkid,
            'friendtwitterhandle' => $checkfor,
            'basefriendtwitteruserid' => 'Example',
            'basefriendtwitterhandle	' => $sourceuser,
            'relationship' => 'friend',
          ])
          ->execute();



      }

    } while ($next_cursor != 0);





    return [
      '#type'   => 'markup',
      '#markup' => 'End of friends import',
    ];







  }//end submitForm()






}
