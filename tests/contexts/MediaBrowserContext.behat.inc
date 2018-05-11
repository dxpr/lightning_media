<?php

namespace Acquia\LightningExtension\Context;

use Drupal\DrupalExtension\Context\DrupalSubContextBase;
use Drupal\DrupalExtension\Context\MinkContext;

/**
 * Contains step definitions for interacting with Lightning's media browser.
 *
 * @internal
 * This class is part of Lightning's internal testing code. It is not an API and
 * should not be extended. This class will be marked final, and all protected
 * members will be made private, in Lightning Media 3.x.
 */
class MediaBrowserContext extends DrupalSubContextBase {

  use AwaitTrait;

  /**
   * Sets the path to any uploadable files.
   *
   * @BeforeScenario
   */
  public function setFilePath() {
    /** @var MinkContext $mink_context */
    $mink_context = $this->getContext(MinkContext::class);

    $path = $mink_context->getMinkParameter('files_path');
    if (empty($path)) {
      $mink_context->setMinkParameter('files_path', __DIR__ . '/../files');
    }
  }

  /**
   * Opens the media browser, obviously.
   *
   * @param string $button
   *   (optional) The embed button ID.
   *
   * @When I open the media browser
   */
  public function open($button = 'media_browser') {
    $this
      ->getContext(CkEditorContext::class)
      ->execute('editdrupalentity', NULL, ['id' => $button]);

    $frame = $this->awaitElement("iframe[name='entity_browser_iframe_$button']", 30)
      ->getAttribute('name');

    $this->getSession()->switchToIFrame($frame);
  }

  /**
   * Selects an item from the media browser.
   *
   * @param int $n
   *   The one-based index of the item to select.
   *
   * @When I select item :n in the media browser
   */
  public function selectN($n) {
    $this->getContext(EntityBrowserContext::class)->select($n, 'media_browser');
  }

  /**
   * Completes the media browser selection.
   *
   * @When I complete the media browser selection
   */
  public function completeSelection() {
    $assert = $this->assertSession();
    $session = $this->getSession();

    $frame = $session->evaluateScript('window.name') ?: $session->evaluateScript('window.active_iframe.name');
    assert(! empty($frame));

    $button = $assert->elementExists('named', ['button', 'Place'])->getXpath();

    // Switch out of the iFrame, because it will be destroyed as soon as we
    // press the button.
    $session->switchToIFrame();
    $js = <<<END
document.evaluate('$button', window.$frame.document, null).iterateNext().click();
END;
    $session->executeScript($js);

    $this->awaitElement('form.entity-embed-dialog');
    $assert->elementExists('named', ['button', 'Embed'])->press();
    $this->awaitAjax();
  }

  /**
   * Enters an embed code in the media browser.
   *
   * @param string $code
   *   The embed code.
   *
   * @When I enter embed code :code
   */
  public function embed($code) {
    // Activate the 'Create embed' tab. We cannot use the link text because it
    // may change between versions of Lightning (as in commit 48fa57e), but the
    // UUID won't.
    $this->assertSession()
      ->elementExists('css', 'nav.eb-tabs ul li a[data-button-id="edit-tab-selector-8b142f33-59d1-47b1-9e3a-4ae85d8376fa"]')
      ->click();

    $this->getContext(MinkContext::class)->fillField('input', $code);

    // The change event, which triggers AJAX, is fired automatically after 600
    // milliseconds.
    sleep(1);
    $this->awaitAjax();
    $this->awaitExpression('jQuery("#entity").children().length');
  }

  /**
   * Uploads a file in the media browser.
   *
   * @param string $file
   *   The path to the file, relative to the test files directory.
   *
   * @When I upload :file
   */
  public function upload($file) {
    /** @var MinkContext $mink_context */
    $mink_context = $this->getContext(MinkContext::class);

    $mink_context->clickLink('Upload');
    $mink_context->attachFileToField('File', $file);
    $this->awaitExpression('jQuery("#entity").children().length');
  }

}
