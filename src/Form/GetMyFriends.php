<?php

namespace Drupal\tweet_link_compile\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Abraham\TwitterOAuth\TwitterOAuth;


/**
 * Class GetMyFriends.
 */
class GetMyFriends extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'get_myfriends_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['base_user'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Base user'),
      '#description' => $this->t('Base user to process'),
      '#maxlength' => 32,
      '#size' => 32,
      '#weight' => '0',
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    foreach ($form_state->getValues() as $key => $value) {
      // @TODO: Validate fields.
    }
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Display result.
    $parameters = [
      "screen_name"      => $form_state->getValue('base_user'),
      "count"            => 200,
      "cursor"           => -1,
      "skip_status"      => 1,
    ];



    $config = \Drupal::config('tweet_link_compile.settings');


    $connection = new TwitterOAuth($config->get('consumer_key'), $config->get('consumer_secret'), $config->get('oauth_token'), $config->get('oauth_token_secret'));
    $endpoint = "friends/list";
    $database = \Drupal::database();

    do {

    $body = $connection->get($endpoint, $parameters);

    if ($body->next_cursor) {

      $next_cursor = $body->next_cursor;

      $sourceuser = $form_state->getValue('base_user');

      // Loop through the downloaded followings and add to database.

      foreach ($body->users as $key => $user) {


        $checkfor = trim($user->screen_name);
        $result = $database->insert('tweet_link_compile_extend')
          ->fields([
            'friendtwitterhandle' => $checkfor,
            'basefriendtwitterhandle' => $sourceuser,
            'relationship' => 'friend',
          ])
          ->execute();
        }


        $parameters = [
          "screen_name"      => $form_state->getValue('base_user'),
          "count"            => 200,
          "cursor"           => $next_cursor,
          "skip_status"      => 1,
        ];

    } else {

        return [
          '#type'   => 'markup',
          '#markup' => 'Unable to collect data.',
        ];

      }


    } while ($next_cursor != 0);



//    foreach ($form_state->getValues() as $key => $value) {
//      \Drupal::messenger()->addMessage($key . ': ' . ($key === 'text_format'?$value['value']:$value));
//    }
  }

}
