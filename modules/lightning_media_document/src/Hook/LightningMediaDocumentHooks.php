<?php

namespace Drupal\lightning_media_document\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\lightning_core\OverrideHelper as Override;
use Drupal\lightning_media_document\Plugin\media\Source\File;

/**
 * Hook implementations for lightning_media_document.
 */
final class LightningMediaDocumentHooks {

  /**
   * Implements hook_media_source_info_alter().
   */
  #[Hook('media_source_info_alter')]
  public function mediaSourceInfoAlter(array &$sources) {
    $sources['file']['input_match']['field_types'] = ['file'];
    Override::pluginClass($sources['file'], File::class);
  }

}
