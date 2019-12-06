<?php

namespace Drupal\Tests\lightning_media_slideshow\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\media\Entity\Media;
use Drupal\Tests\lightning_media\FunctionalJavascript\WebDriverWebAssert;

/**
 * Tests the basic functionality of Lightning Media's slideshow component.
 *
 * @group lightning_media_slideshow
 * @group lightning_media
 */
class SlideshowTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'block_content',
    'lightning_media_instagram',
    'lightning_media_slideshow',
    'lightning_media_twitter',
  ];

  /**
   * Tests creating a slideshow block with media items in it.
   */
  public function testSlideshow() {
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    $account = $this->drupalCreateUser([
      'access content',
      'access media overview',
      'view media',
      'create media',
      'update media',
      'administer blocks',
    ]);
    $this->drupalLogin($account);

    /** @var \Drupal\media\MediaInterface $media */
    Media::create(['bundle' => 'tweet'])
      ->setName("I'm a tweet")
      ->set('embed_code', 'https://twitter.com/50NerdsofGrey/status/757319527151636480')
      ->set('field_media_in_library', TRUE)
      ->setPublished()
      ->save();

    Media::create(['bundle' => 'instagram'])
      ->setName("I'm an instagram")
      ->set('embed_code', 'https://www.instagram.com/p/BaecNGYAYyP/')
      ->set('field_media_in_library', TRUE)
      ->setPublished()
      ->save();

    $this->drupalGet('/block/add/media_slideshow');
    $page->fillField('Block description', 'Test Block');

    // This is an amazingly sketchy way to use the media library, but it will
    // suffice for now until there is a trait in core that allows us to write
    // interact with it more cleanly.
    $page->pressButton('Add media');
    $assert_session->waitForText('Add or select media');
    $assert_session->waitForElement('css', '.js-media-library-item')->click();
    $page->clickLink('Instagram');
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->waitForElement('css', '.js-media-library-item')->click();
    $assert_session->elementExists('css', '.ui-dialog-buttonpane')->pressButton('Insert selected');

    // Wait for the selected items to actually appear on the page.
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->waitForElement('css', '.js-media-library-selection .js-media-library-item');

    $page->pressButton('Save');
    $page->selectFieldOption('Region', 'Content');
    $page->pressButton('Save block');
    $this->drupalGet('<front>');

    $this->assertNotEmpty($assert_session->waitForElement('css', 'button.slick-prev.slick-arrow'));
    $assert_session->elementExists('css', 'button.slick-next.slick-arrow');
  }

  /**
   * {@inheritdoc}
   */
  public function assertSession($name = NULL) {
    return new WebDriverWebAssert($this->getSession($name), $this->baseUrl);
  }

}
