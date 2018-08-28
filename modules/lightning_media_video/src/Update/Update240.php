<?php

namespace Drupal\lightning_media_video\Update;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Drupal\lightning_core\ConfigHelper;

/**
 * @Update("2.4.0")
 */
final class Update240 implements ContainerInjectionInterface {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  private $configFactory;

  /**
   * Update240 constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory')
    );
  }

  /**
   * Installs Lightning's Video file media type.
   *
   * @update
   *
   * @ask Do you want to install "Video file" media type?
   */
  public function installVideoFileMedia() {
    $video_config_helper = ConfigHelper::forModule('lightning_media_video');
    // Configs which could be imported simply.
    $configs = [
      ['media_type', 'video_file'],
      ['field_storage_config', 'media.field_media_video_file'],
      ['field_config', 'media.video_file.field_media_in_library'],
      ['field_config', 'media.video_file.field_media_video_file'],
      ['entity_form_display', 'media.video_file.media_browser'],
      ['entity_view_display', 'media.video_file.embedded'],
      ['entity_view_display', 'media.video_file.thumbnail'],
    ];
    // Default entity displays should be handled separately since these are
    // created automatically during media type import.
    $default_display_configs = [
      'core.entity_form_display.media.video_file.default',
      'core.entity_view_display.media.video_file.default',
    ];

    // Importing simple configs.
    foreach ($configs as $config_params) {
      list($entity_type, $id) = $config_params;
      $video_config_helper->getEntity($entity_type, $id)->save();
    }

    // Dealing with default displays.
    foreach ($default_display_configs as $config_id) {
      $new_display_data = $video_config_helper->read($config_id);

      $this->configFactory->getEditable($config_id)
        ->setData($new_display_data)
        ->save(TRUE);
    }
  }

}
