<?php

namespace Drupal\lightning_media_audio\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\lightning_core\OverrideHelper as Override;
use Drupal\lightning_media_audio\Plugin\media\Source\AudioFile;

/**
 * Hook implementations for lightning_media_audio.
 */
final class LightningMediaAudioHooks {

  /**
   * Implements hook_media_source_info_alter().
   */
  #[Hook('media_source_info_alter')]
  public function mediaSourceInfoAlter(array &$sources) {
    Override::pluginClass($sources['audio_file'], AudioFile::class);
  }

}
