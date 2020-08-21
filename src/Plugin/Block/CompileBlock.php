<?php

namespace Drupal\tweet_link_compile\Plugin\Block;

use Drupal\tweet_link_compile\Controller\CompileMostPopular;
use Drupal\Core\Block\BlockBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * Provides a Compiled Links Block.
 *
 * @Block(
 *   id = "compile_block",
 *   admin_label = @Translation("Most popular links"),
 *   category = @Translation("Social"),
 * )
 */
class CompileBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Constructs a Drupal\Component\Plugin\PluginBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Connection $connection) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->connection = $connection;
  }

  /**
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   * @param array $configuration
   * @param string $plugin_id
   * @param mixed $plugin_definition
   *
   * @return static
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {

    $compileddata = new CompileMostPopular();

    $results = $compileddata->report();

    return [
      '#theme'      => 'tweet_link_compile_page',
      '#text'       => 'Most popular links',
      '#myvariable' => $results['#myvariable'],
      '#cache'      => [
        'keys'     => ['my_page'],
        'contexts' => ['route'],
        'tags'     => ['rendered'],
        'max-age'  => 300,
      ],
    ];

  }//end build()

}//end class
