<?php

namespace Drupal\lightning_media\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\ckeditor\Ajax\AddStyleSheetCommand;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\WidgetInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\PluralTranslatableMarkup as Plural;
use Drupal\file\FileInterface;
use Drupal\image\Entity\ImageStyle;
use Drupal\image\Plugin\Field\FieldType\ImageItem;
use Drupal\image\Plugin\Field\FieldWidget\ImageWidget;
use Drupal\image\Plugin\ImageEffect\ResizeImageEffect;
use Drupal\lightning_core\BundleEntityStorage;
use Drupal\lightning_core\OverrideHelper as Override;
use Drupal\lightning_media\Exception\IndeterminateBundleException;
use Drupal\lightning_media\Form\MediaForm;
use Drupal\lightning_media\ImageWidgetHelper;
use Drupal\lightning_media\MediaHelper;
use Drupal\media\Entity\MediaType;
use Drupal\media\MediaInterface;
use Drupal\media\MediaTypeInterface;
use Drupal\views\ViewExecutable;

/**
 * Hook implementations for lightning_media.
 */
final class LightningMediaHooks {

  /**
   * Implements hook_form_media_type_form_alter().
   */
  #[Hook('form_media_type_form_alter')]
  public function formMediaTypeFormAlter(array &$form, FormStateInterface $form_state) {
    $form['source_dependent'] += [
      'source_configuration' => [],
    ];
  }

  /**
   * Implements hook_field_widget_third_party_settings_form().
   */
  #[Hook('field_widget_third_party_settings_form')]
  public function fieldWidgetThirdPartySettingsForm(WidgetInterface $widget) {
    $element = [];

    if ($widget instanceof ImageWidget) {
      $element = ImageWidgetHelper::getSettingsForm($widget);
    }
    return $element;
  }

  /**
   * Implements hook_field_widget_settings_summary_alter().
   */
  #[Hook('field_widget_settings_summary_alter')]
  public function fieldWidgetSettingsSummaryAlter(array &$summary, array $context) {
    if ($context['widget'] instanceof ImageWidget) {
      ImageWidgetHelper::summarize($context['widget'], $summary);
    }
  }

  /**
   * Implements hook_field_widget_form_alter().
   */
  #[Hook('field_widget_form_alter')]
  public function fieldWidgetFormAlter(array &$element, FormStateInterface $form_state, array $context) {
    if ($context['widget'] instanceof ImageWidget) {
      ImageWidgetHelper::alter($element, $context['widget']);
    }
  }

  /**
   * Implements hook_field_widget_entity_browser_entity_reference_form_alter().
   */
  #[Hook('field_widget_entity_browser_entity_reference_form_alter')]
  public function fieldWidgetEntityBrowserEntityReferenceFormAlter(array &$element, FormStateInterface $form_state, array $context) {
    // Move the remaining number of selections to the details summary.
    if (isset($element['current']['#prefix'])) {
      $element['#description'] .= $element['current']['#prefix'];
      unset($element['current']['#prefix']);
    }

    if (!empty($element['current']['items'])) {
      // Wrap the current selections in a nice <details> element.
      $cardinality = (int) $context['items']
        ->getFieldDefinition()
        ->getFieldStorageDefinition()
        ->getCardinality();

      $element['current']['#theme_wrappers'] = [
        'details' => [
          '#attributes' => [
            'open' => TRUE,
          ],
          '#title' => new Plural($cardinality, 'Current selection', 'Current selections'),
          '#summary_attributes' => [],
        ],
      ];
    }
  }

  /**
   * Implements hook_modules_installed().
   */
  #[Hook('modules_installed')]
  public function modulesInstalled(array $modules) {
    // Don't do anything during config sync.
    if (\Drupal::isConfigSyncing()) {
      return;
    }

    if (in_array('lightning_roles', $modules, TRUE)) {
      \Drupal::service('lightning.content_roles')
        ->grantPermissions('creator', ['use text format rich_text']);
    }
  }

  /**
   * Implements hook_element_info_alter().
   */
  #[Hook('element_info_alter')]
  public function elementInfoAlter(array &$info) {
    $info['entity_browser']['#after_build'][] = 'lightning_media_inject_entity_browser_count';
  }

  /**
   * Implements hook_entity_extra_field_info().
   */
  #[Hook('entity_extra_field_info')]
  public function entityExtraFieldInfo() {
    $extra_fields = [];

    if (\Drupal::moduleHandler()->moduleExists('media')) {
      /** @var \Drupal\media\MediaTypeInterface $media_type */
      foreach (MediaType::loadMultiple() as $id => $media_type) {
        $plugin_definition = $media_type->getSource()->getPluginDefinition();

        if (isset($plugin_definition['preview'])) {
          $extra_fields['media'][$id]['form']['preview'] = [
            'label' => t('Preview'),
            'description' => t('A live preview of the @media_type.', [
              '@media_type' => $media_type->label(),
            ]),
            'weight' => 0,
          ];
        }
      }
    }
    return $extra_fields;
  }

  /**
   * Implements hook_inline_entity_form_entity_form_alter().
   */
  #[Hook('inline_entity_form_entity_form_alter')]
  public function inlineEntityFormEntityFormAlter(array &$entity_form) {
    $entity = $entity_form['#entity'];

    if ($entity instanceof MediaInterface && MediaHelper::isPreviewable($entity)) {
      $entity_form['preview'] = MediaHelper::getSourceField($entity)->view('default');
    }
  }

  /**
   * Implements hook_form_entity_browser_media_browser_form_alter().
   */
  #[Hook('form_entity_browser_media_browser_form_alter')]
  public function formEntityBrowserMediaBrowserFormAlter(array &$form) {
    $form['#attached']['library'][] = 'lightning_media/browser.styling';
  }

  /**
   * Implements hook_form_entity_browser_ckeditor_media_browser_form_alter().
   */
  #[Hook('form_entity_browser_ckeditor_media_browser_form_alter')]
  public function formEntityBrowserCkeditorMediaBrowserFormAlter(array &$form) {
    lightning_media_form_entity_browser_media_browser_form_alter($form);
  }

  /**
   * Implements hook_entity_base_field_info_alter().
   */
  #[Hook('entity_base_field_info_alter')]
  public function entityBaseFieldInfoAlter(array &$fields, EntityTypeInterface $entity_type) {
    if ($entity_type->id() === 'media' && array_key_exists('revision_log_message', $fields)) {
      $fields['revision_log_message']->setDisplayConfigurable('form', TRUE);
    }
  }

  /**
   * Implements hook_entity_type_alter().
   */
  #[Hook('entity_type_alter')]
  public function entityTypeAlter(array &$entity_types) {
    // Use a configuration flag to determine whether or not to show the revision
    // UI.
    $entity_types['media']->set('show_revision_ui', (bool) \Drupal::config('lightning_media.settings')->get('revision_ui'));

    // Use our specialized entity form for adding and editing media assets in
    // order to support dynamic preview generation.
    Override::entityForm($entity_types['media'], MediaForm::class);
    Override::entityForm($entity_types['media'], MediaForm::class, 'edit');

    // Use our extended storage handler for media bundles.
    Override::entityHandler($entity_types['media_type'], 'storage', BundleEntityStorage::class);
  }

  /**
   * Implements hook_media_type_insert().
   */
  #[Hook('media_type_insert')]
  public function mediaTypeInsert(MediaTypeInterface $media_type) {
    // Don't do anything during config sync.
    if (\Drupal::isConfigSyncing()) {
      return;
    }

    \Drupal::entityTypeManager()
      ->getStorage('field_config')
      ->create([
        'field_name' => 'field_media_in_library',
        'entity_type' => 'media',
        'bundle' => $media_type->id(),
        'label' => t('Show in media library'),
        'settings' => [
          'on_label' => t('Shown in media library'),
          'off_label' => t('Hidden in media library'),
        ],
        'default_value' => [
          ['value' => TRUE],
        ],
      ])
      ->save();

    \Drupal::service('entity_display.repository')
      ->getFormDisplay('media', $media_type->id())
      ->setComponent('field_media_in_library', [
        'type' => 'boolean_checkbox',
        'settings' => [
          'display_label' => TRUE,
        ],
      ])
      ->save();
  }

  /**
   * Implements hook_form_entity_embed_dialog_alter().
   */
  #[Hook('form_entity_embed_dialog_alter')]
  public function formEntityEmbedDialogAlter(array &$form, FormStateInterface $form_state) {
    list ($editor, $embed_button) = $form_state->getBuildInfo()['args'];

    /** @var \Drupal\embed\EmbedButtonInterface $embed_button */
    if ($embed_button->id() == 'media_browser') {
      $element = &$form['attributes']['data-entity-embed-settings']['view_mode'];
      if (isset($element['#options']['embedded'])) {
        $element['#default_value'] = 'embedded';
      }
    }
  }

  /**
   * Implements hook_js_settings_alter().
   */
  #[Hook('js_settings_alter')]
  public function jsSettingsAlter(array &$settings) {
    if (empty($settings['ajax'])) {
      $settings['ajax'] = [];
    }

    $route_name = \Drupal::routeMatch()->getRouteName();
    if (strpos($route_name, 'entity_browser') === 0 && isset($settings['ajaxPageState']['libraries'])) {
      $libraries = explode(',', $settings['ajaxPageState']['libraries']);
      // If we pretend EB's iframe library has not been previously loaded, it will
      // ALWAYS be fetched from the server, preventing (in a crappy, kludgey way)
      // the bug in #2768849.
      $libraries = array_diff($libraries, ['entity_browser/iframe']);
      $settings['ajaxPageState']['libraries'] = implode(',', $libraries);
    }
  }

  /**
   * Implements hook_ajax_render_alter().
   */
  #[Hook('ajax_render_alter')]
  public function ajaxRenderAlter(array &$data) {
    $route = \Drupal::routeMatch()->getRouteName();
    $query = \Drupal::request()->query;

    if ($route == 'entity_embed.dialog') {
      foreach ($data as &$command) {
        if ($command['command'] == 'settings' && isset($command['settings']['ajaxPageState']['libraries'])) {
          $libraries = explode(',', $command['settings']['ajaxPageState']['libraries']);
          $libraries = array_diff($libraries, ['entity_browser/iframe']);
          $command['settings']['ajaxPageState']['libraries'] = implode(',', $libraries);
        }
      }
    }
    elseif ($route == 'embed.preview' && $query->has('editor')) {
      $style_sheets = [];

      foreach ($data as $command) {
        // Any CSS being added should be replicated in the editor.
        if ($command['command'] == 'add_css') {
          $matched = [];
          // Assume (perhaps naively) that all the style sheets are embedded as
          // <link /> tags.
          preg_match_all('/href="([^"]+)"/', $command['data'], $matched);
          $style_sheets = array_merge($style_sheets, $matched[1]);
        }
      }

      if ($style_sheets) {
        $command = new AddStyleSheetCommand($query->get('editor'), $style_sheets);
        $data[] = $command->render();
      }
    }
  }

  /**
   * Implements hook_library_info_alter().
   */
  #[Hook('library_info_alter')]
  public function libraryInfoAlter(array &$libraries, $extension) {
    if ($extension === 'entity_browser') {
      // Clicking the "Remove" or "Edit" buttons in the media browser doesn't
      // trigger the event that makes Quick Edit display the "Save" button.
      // Loading the drupal.file library before entity browser attaches the
      // file button event handlers to the media browser buttons.
      // @see Drupal.behaviors.fileButtons
      $libraries['entity_reference']['dependencies'][] = 'file/drupal.file';
    }
  }

  /**
   * Implements hook_views_pre_view().
   */
  #[Hook('views_pre_view')]
  public function viewsPreView(ViewExecutable $view, $display_id) {
    // If the field_media_in_library field doesn't exist, there is no point in
    // continuing here.
    $field_map = \Drupal::service('entity_field.manager')->getFieldMap();
    if (empty($field_map['media']['field_media_in_library'])) {
      return;
    }

    if ($view->id() === 'media_library' && strpos($display_id, 'widget') === 0) {
      $filters = $view->display_handler->getOption('filters');
      // Filter out media items where field_media_in_library is FALSE. If the view
      // already has stored configuration for this field, it will be respected.
      $filters += [
        'field_media_in_library_value' => [
          'id' => 'field_media_in_library_value',
          'table' => 'media__field_media_in_library',
          'field' => 'field_media_in_library_value',
          'relationship' => 'none',
          'group_type' => 'group',
          'admin_label' => '',
          'operator' => '=',
          'value' => '1',
          'group' => 1,
          'exposed' => FALSE,
          'expose' => [
            'operator_id' => '',
            'label' => '',
            'description' => '',
            'use_operator' => FALSE,
            'operator' => '',
            'operator_limit_selection' => FALSE,
            'operator_list' => [],
            'identifier' => '',
            'required' => FALSE,
            'remember' => FALSE,
            'multiple' => FALSE,
            'remember_roles' => [
              'authenticated' => 'authenticated',
            ],
          ],
          'is_grouped' => FALSE,
          'group_info' => [
            'label' => '',
            'description' => '',
            'identifier' => '',
            'optional' => TRUE,
            'widget' => 'select',
            'multiple' => FALSE,
            'remember' => FALSE,
            'default_group' => 'All',
            'default_group_multiple' => [],
            'group_items' => [],
          ],
          'plugin_id' => 'boolean',
        ],
      ];
      $view->display_handler->setOption('filters', $filters);
    }
  }

}
