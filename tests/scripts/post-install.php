<?php

/**
 * @file
 * Executed after installation, to prepare for development.
 */

use Drupal\lightning_core\ConfigHelper as Config;

Drupal::service('module_installer')->install([
  'image_widget_crop',
  'lightning_page',
  'pathauto',
]);

module_load_install('lightning_media_image');
lightning_media_image_install();

$config = Config::forModule('lightning_media')->optional();
$config->getEntity('user_role', 'media_creator')->save();
$config->getEntity('user_role', 'media_manager')->save();

user_role_grant_permissions('media_creator', [
  'access content',
  'access content overview',
  'access image_browser entity browser pages',
  'create page content',
  'edit own page content',
  'use text format rich_text',
  'view own unpublished content',
]);

Drupal::service('theme_installer')->install(['bartik', 'seven']);

$config_factory = Drupal::configFactory();

$config_factory->getEditable('system.theme')
  ->set('default', 'bartik')
  ->set('admin', 'seven')
  ->save();

$config_factory->getEditable('node.settings')
  ->set('use_admin_theme', TRUE)
  ->save();

// Sentence-case the label of the media browser's embed code widget. Yes,
// not doing this can cause tests to fail.
$config_factory->getEditable('entity_browser.browser.media_browser')
  ->set('widgets.8b142f33-59d1-47b1-9e3a-4ae85d8376fa.label', 'Create embed')
  ->save();

$GLOBALS['install_state'] = [];
$view = entity_load('view', 'media');
lightning_media_view_insert($view);
unset($GLOBALS['install_state']);
