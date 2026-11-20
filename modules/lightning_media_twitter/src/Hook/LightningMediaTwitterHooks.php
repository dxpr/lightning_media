<?php

namespace Drupal\lightning_media_twitter\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\lightning_core\OverrideHelper as Override;
use Drupal\lightning_media\Form\AddByUrlForm;
use Drupal\lightning_media_twitter\Plugin\media\Source\Twitter;

/**
 * Hook implementations for lightning_media_twitter.
 */
final class LightningMediaTwitterHooks {

  /**
   * Implements hook_media_source_info_alter().
   */
  #[Hook('media_source_info_alter')]
  public function mediaSourceInfoAlter(array &$sources) {
    $sources['twitter']['input_match'] = [
      'constraint' => 'TweetEmbedCode',
      'field_types' => [
        'string',
        'string_long',
      ],
    ];
    $sources['twitter']['preview'] = TRUE;
    $sources['twitter']['forms']['media_library_add'] = AddByUrlForm::class;

    Override::pluginClass($sources['twitter'], Twitter::class);
  }

  /**
   * Implements hook_theme_registry_alter().
   */
  #[Hook('theme_registry_alter')]
  public function themeRegistryAlter(array &$theme_registry) {
    /** @var \Drupal\Core\Extension\ModuleExtensionList $module_list */
    $module_list = \Drupal::service('extension.list.module');

    $hook = &$theme_registry['media_entity_twitter_tweet'];
    if ($hook['path'] === $module_list->getPath('media_entity_twitter') . '/templates') {
      $hook['path'] = $module_list->getPath('lightning_media_twitter') . '/templates';
    }
  }

  /**
   * Implements hook_preprocess_media_entity_twitter_tweet().
   */
  #[Hook('preprocess_media_entity_twitter_tweet')]
  public function preprocessMediaEntityTwitterTweet(array &$variables) {
    if (isset($variables['placeholder'])) {
      return;
    }

    // Add a placeholder image so that's easy to see where tweets are embedded in
    // CKEditor.
    $placeholder = [
      '#theme' => 'image',
      '#uri' => \Drupal::service('extension.list.module')->getPath('media_entity_twitter') . '/images/icons/twitter.png',
      '#width' => 180,
      '#height' => 180,
      // This is a placeholder, so no need for an alt text.
      '#alt' => NULL,
    ];
    $variables['placeholder'] = method_exists(\Drupal::service('renderer'), 'renderInIsolation') ? \Drupal::service('renderer')->renderInIsolation($placeholder) : \Drupal::service('renderer')->renderPlain($placeholder);
  }

}
