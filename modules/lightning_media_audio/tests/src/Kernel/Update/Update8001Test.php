<?php

namespace Drupal\Tests\lightning_media_audio\Kernel\Update;

use Drupal\Core\Entity\Entity\EntityFormMode;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\media\Traits\MediaTypeCreationTrait;

/**
 * Tests Lightning Media Audio's update path.
 *
 * @group lightning_media_audio
 * @group lightning_media
 *
 * @covers lightning_media_audio_update_8001()
 */
class Update8001Test extends KernelTestBase {

  use MediaTypeCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'field',
    'lightning_media_audio',
    'media',
    'media_test_source',
  ];

  /**
   * Tests the update function.
   */
  public function testUpdate() {
    $this->createMediaType('test', ['id' => 'audio']);

    EntityFormMode::create([
      'targetEntityType' => 'media',
      'id' => 'media.media_library',
    ])->save();

    module_load_install('lightning_media_audio');
    lightning_media_audio_update_8001();
  }

}
