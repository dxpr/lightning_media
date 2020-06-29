<?php

namespace Drupal\Tests\lightning_media_video\Kernel;

use Drupal\Core\Entity\Entity\EntityFormMode;
use Drupal\Core\Entity\Entity\EntityViewMode;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\media\Traits\MediaTypeCreationTrait;

/**
 * Tests Lightning Media Video's update path.
 *
 * @group lightning_media_video
 * @group lightning_media
 *
 * @covers lightning_media_video_update_8003()
 * @covers lightning_media_video_update_8004()
 */
class Update80038004Test extends KernelTestBase {

  use MediaTypeCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'field',
    'image',
    'lightning_media_video',
    'media',
    'media_test_source',
  ];

  /**
   * Tests the update function.
   */
  public function testUpdate() {
    $this->createMediaType('test', ['id' => 'remote_video']);

    EntityViewMode::create([
      'targetEntityType' => 'media',
      'id' => 'media.thumbnail',
    ])->save();

    EntityFormMode::create([
      'targetEntityType' => 'media',
      'id' => 'media.media_library',
    ])->save();

    module_load_install('lightning_media_video');
    lightning_media_video_update_8003();
    lightning_media_video_update_8004();
  }

}
