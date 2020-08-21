<?php

namespace Drupal\tweet_link_compile;

use Drupal\Core\Database\Connection;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;

/**
 * Repository for database-related helper methods for our example.
 *
 * This repository is a service named 'tweet_link_compile.repository'.
 * You can see how the service is defined in
 * tweet_link_compile/tweet_link_compile.services.yml.
 *
 * For projects where there are many specialized queries, it can be useful to
 * group them into 'repositories' of queries. We can also architect this
 * repository to be a service, so that it gathers the database connections it
 * needs. This way other classes which use the repository don't need to concern
 * themselves with database connections, only with business logic.
 *
 * This repository demonstrates basic CRUD behaviors, and also has an advanced
 * query which performs a join with the user table.
 *
 * @ingroup tweet_link_compile
 */
class TweetLinkCompileRepository {

  use MessengerTrait;
  use StringTranslationTrait;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Construct a repository object.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $translation
   *   The translation service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   */
  public function __construct(Connection $connection, TranslationInterface $translation, MessengerInterface $messenger) {
    $this->connection = $connection;
    $this->setStringTranslation($translation);
    $this->setMessenger($messenger);

  }//end __construct()

  /**
   * Save an entry in the database.
   *
   * Exception handling is shown in this example. It could be simplified
   * without the try/catch blocks, but since an insert will throw an exception
   * and terminate your application if the exception is not handled, it is best
   * to employ try/catch.
   *
   * @param array $entry
   *   An array containing all the fields of the database record.
   *
   * @return int
   *   The number of updated rows.
   *
   * @throws \Exception
   *   When the database insert fails.
   */
  public function insert(array $entry) {
    try {
      $return_value = $this->connection->insert('tweet_link_compile')
        ->fields($entry)
        ->execute();
    }
    catch (\Exception $e) {
      $this->messenger()->addMessage(
            $this->t(
                'Insert failed. Message = %message',
                [
                  '%message' => $e->getMessage(),
                ]
            ),
            'error'
            );
    }

    return $return_value ?? NULL;

  }//end insert()

  /**
   * Update an entry in the database.
   *
   * @param array $entry
   *   An array containing all the fields of the item to be updated.
   *
   * @return int
   *   The number of updated rows.
   */
  public function update(array $entry) {
    try {
      // Connection->update()...->execute() returns the number of rows updated.
      $count = $this->connection->update('tweet_link_compile_following')
        ->fields($entry)
        ->condition('lkuid', $entry['lkuid'])
        ->execute();
    }
    catch (\Exception $e) {
      $this->messenger()->addMessage(
            $this->t(
                'Update failed. Message = %message, query= %query',
                [
                  '%message' => $e->getMessage(),
                  '%query'   => $e->query_string,
                ]
            ),
            'error'
            );
    }

    return ($count ?? 0);

  }//end update()

  /**
   * Delete an entry from the database.
   *
   * @param array $entry
   *   An array containing at least the person identifier 'lkuid' element of the
   *   entry to delete.
   *
   * @see Drupal\Core\Database\Connection::delete()
   */
  public function delete(array $entry) {
    $this->connection->delete('tweet_link_compile')
      ->condition('lkuid', $entry['lkuid'])
      ->execute();

  }//end delete()

  public function load(array $entry = []) {
    // Read all the fields from the tweet_link_compile_following table.
    $select = $this->connection
      ->select('tweet_link_compile_following')
      // Add all the fields into our select query.
      ->fields('tweet_link_compile_following')
      ->orderBy('twitterhandle');

    // Add each field and value as a condition to this query.
    foreach ($entry as $field => $value) {
      $select->condition($field, $value);
    }

    // Return the result in object format.
    return $select->execute()->fetchAll();

  }//end load()

  /**
   * Load recently collected links.
   */
  public function advancedLoad() {
    // Get a select query for our tweet_link_compile table. We supply an alias
    // of e (for 'example').
    $select = $this->connection->select('tweet_link_compile', 'e');
    // Select these specific fields for the output.
    $select->addField('e', 'lkid');
    $select->addField('e', 'twitterhandle');
    $select->addField('e', 'linktitle');
    $select->addField('e', 'linkfinaldestination');
    $select->addField('e', 'tweetid');
    $select->addField('e', 'timetweeted');

    $config = \Drupal::config('tweet_link_compile.settings');
    // Default value is 48 hours if not in config.
    $decayhours = $config->get('decay') ?? 48;
    $linkexpire = (time() - ($decayhours * 60 * 60));

    $select->condition('e.timetweeted', $linkexpire, '>');
    // Make sure we only get items 0-49, for scalability reasons.
    $select->range(0, 50);
    $select->orderBy('lkid', 'DESC');

    $entries = $select->execute()->fetchAll(\PDO::FETCH_ASSOC);

    return $entries;

  }//end advancedLoad()

}//end class
