<?php

namespace Drupal\Tests\lightning_media\FunctionalJavascript;

use Drupal\entity_browser\Element\EntityBrowserElement;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\node\Entity\Node;
use Drupal\Tests\media\Traits\MediaTypeCreationTrait;

/**
 * @group lightning_media
 */
class UploadBundleTest extends WebDriverTestBase {

  use MediaTypeCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'lightning_media_image',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->createMediaType('image', [
      'id' => 'picture',
      'label' => 'Picture',
    ]);
    $this->createContentType([
      'type' => 'article',
      'name' => 'Article',
    ]);

    $field_storage = FieldStorageConfig::create([
      'field_name' => 'field_media',
      'entity_type' => 'node',
      'type' => 'entity_reference',
      'settings' => [
        'target_type' => 'media',
      ],
    ]);
    $field_storage->save();

    FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => 'article',
      'label' => 'Media',
      'settings' => [
        'handler_settings' => [
          'target_bundles' => [
            'image' => 'image',
            'picture' => 'picture',
          ],
        ],
      ],
    ])->save();

    $this->container->get('entity_type.manager')
      ->getStorage('entity_form_display')
      ->load('node.article.default')
      ->setComponent('field_media', [
        'type' => 'entity_browser_entity_reference',
        'settings' => [
          'entity_browser' => 'media_browser',
          'field_widget_display' => 'rendered_entity',
          'field_widget_edit' => TRUE,
          'field_widget_remove' => TRUE,
          'selection_mode' => EntityBrowserElement::SELECTION_MODE_APPEND,
          'field_widget_display_settings' => [
            'view_mode' => 'thumbnail',
          ],
          'open' => TRUE,
        ],
        'region' => 'content',
      ])
      ->save();

    $user = $this->createUser([], NULL, TRUE);
    $this->drupalLogin($user);
  }

  /**
   * Tests that select is shown when media bundle is ambiguous.
   */
  public function testUpload() {
    $session = $this->getSession();
    $page = $session->getPage();
    $assert_session = $this->assertSession();

    // Create an article with a media item added via the upload widget.
    $this->drupalGet('node/add/article');
    $page->fillField('Title', 'Foo');
    $page->pressButton('Add media');
    $assert_session->assertWaitOnAjaxRequest();

    $session->switchToIFrame('entity_browser_iframe_media_browser');
    // Assert that we are actually in the frame. If we are still in the
    // top-level window, window.frameElement will be null.
    // @see https://developer.mozilla.org/en-US/docs/Web/API/Window/frameElement
    $this->assertTrue($session->evaluateScript('window.frameElement !== null'));

    // To increase reliability, ensure auto-upload is disabled.
    $session->executeScript('Drupal.behaviors.fileAutoUpload.detach(document, drupalSettings, "unload");');
    // Make the "Upload" button visible so that we can press it.
    $session->executeScript('document.getElementById("edit-input-upload").classList.remove("js-hide");');

    $page->attachFileToField('input_file', __DIR__ . '/../../files/test.jpg');
    $assert_session->elementExists('css', '.js-form-managed-file')->pressButton('Upload');

    $this->assertNotEmpty($assert_session->waitForField('Bundle'));
    $page->selectFieldOption('Bundle', 'Picture');
    $this->assertNotEmpty($assert_session->waitForField('Name'));
    $page->fillField('Name', 'Bar');
    $page->fillField('Alternative text', 'Baz');
    $page->pressButton('Place');
    $assert_session->assertWaitOnAjaxRequest();
    sleep(1);

    $session->switchToIFrame();
    $this->assertNotEmpty($assert_session->waitForButton('Remove'));
    $page->pressButton('Save');

    // Assert the correct entities are created.
    $node = Node::load(1);
    $this->assertInstanceOf(Node::class, $node);
    /** @var \Drupal\node\NodeInterface $node */
    $this->assertSame('Foo', $node->getTitle());
    $this->assertFalse($node->get('field_media')->isEmpty());
    $this->assertSame('picture', $node->field_media->entity->bundle());
    $this->assertSame('Bar', $node->field_media->entity->getName());
    $this->assertSame('Baz', $node->field_media->entity->field_media_image->alt);
    $this->assertSame('test.jpg', $node->field_media->entity->field_media_image->entity->getFilename());
  }

}
