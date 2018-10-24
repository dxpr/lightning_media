<?php

namespace Acquia\LightningExtension\Context;

use Drupal\DrupalExtension\Context\DrupalSubContextBase;

/**
 * Contains step definitions for interacting with Lightning's image browsers.
 */
final class ImageBrowserContext extends DrupalSubContextBase {

  use AwaitTrait;

  /**
   * Opens an image browser for a particular field.
   *
   * @param string $field
   *   The field label.
   *
   * @When I open the :field image browser
   */
  public function open($field) {
    /** @var ElementContext $context */
    $context = $this->getContext(ElementContext::class);

    $context->assertDetails($field)->pressButton('Select Image(s)');
    $this->awaitAjax();
    $context->enterFrame('entity_browser_iframe_image_browser');

    // This might be vestigial.
    sleep(10);
  }

  /**
   * Asserts that cropping is enabled for an uploaded image.
   *
   * @Then I should be able to crop the image
   */
  public function assertCrop() {
    /** @var \Acquia\LightningExtension\Context\ElementContext $context */
    $context = $this->getContext(ElementContext::class);

    $context->assertOpenDetails('Crop image');
    $context->assertDetails('Freeform');
  }

}
