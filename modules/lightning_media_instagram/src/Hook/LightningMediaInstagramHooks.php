<?php

namespace Drupal\lightning_media_instagram\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Update\UpdateKernel;
use Drupal\lightning_core\OverrideHelper as Override;
use Drupal\lightning_media\Form\AddByUrlForm;
use Drupal\lightning_media_instagram\Plugin\media\Source\Instagram;

/**
 * Hook implementations for lightning_media_instagram.
 */
final class LightningMediaInstagramHooks {

  /**
   * Implements hook_media_source_info_alter().
   */
  #[Hook('media_source_info_alter')]
  public function mediaSourceInfoAlter(array &$sources) {
    $source = &$sources['oembed:instagram'];

    $source['input_match'] = [
      'constraint' => 'InstagramEmbedCode',
      'field_types' => [
        'string',
        'string_long',
      ],
    ];
    $source['preview'] = TRUE;
    $source['forms']['media_library_add'] = AddByUrlForm::class;

    Override::pluginClass($source, Instagram::class);

    // There may be at least one media type still using the old 'instagram' source
    // plugin ID, which was removed in Media Entity Instagram 3.x. If that's the
    // case, the update process might throw errors about a missing plugin ID. To
    // smooth that over, we temporarily alias the source plugin ID so that the
    // update can be finished successfully.
    // @see media_entity_instagram_post_update_change_source_name()
    if (\Drupal::service('kernel') instanceof UpdateKernel) {
      $sources['instagram'] = $source;
    }
  }

}
