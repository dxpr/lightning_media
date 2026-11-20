<?php

namespace Drupal\lightning_media_video\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\lightning_core\OverrideHelper as Override;
use Drupal\lightning_media_video\Plugin\media\Source\VideoFile;

/**
 * Hook implementations for lightning_media_video.
 */
final class LightningMediaVideoHooks {

  /**
   * Implements hook_media_source_info_alter().
   */
  #[Hook('media_source_info_alter')]
  public function mediaSourceInfoAlter(array &$sources) {
    Override::pluginClass($sources['video_file'], VideoFile::class);
  }

}
