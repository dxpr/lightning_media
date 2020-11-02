<?php

namespace Drupal\Tests\lightning_media\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the add and edit forms for all our shipped media types.
 *
 * @group lightning
 * @group lightning_media
 */
class AddEditFormTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'lightning_media_audio',
    'lightning_media_document',
    'lightning_media_image',
    'lightning_media_instagram',
    'lightning_media_twitter',
    'lightning_media_video',
  ];

  /**
   * Tests the add and edit forms for all our shipped media types.
   */
  public function testEditForms() {
    $media_types = $this->container->get('entity_type.manager')
      ->getStorage('media_type')
      ->getQuery()
      ->execute();

    $permissions = [
      'create url aliases',
    ];
    foreach ($media_types as $media_type) {
      $permissions[] = "create $media_type media";
    }
    $account = $this->drupalCreateUser($permissions);
    $this->drupalLogin($account);

    $assert_session = $this->assertSession();

    foreach ($media_types as $media_type) {
      $this->drupalGet("/media/add/$media_type");
      $assert_session->statusCodeEquals(200);
      $assert_session->fieldNotExists('URL alias');
    }
  }

}
