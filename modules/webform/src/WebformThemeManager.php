<?php

namespace Drupal\webform;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Theme\ThemeManagerInterface;
use Drupal\Core\Theme\ThemeInitializationInterface;

/**
 * Defines a class to manage webform themeing.
 */
class WebformThemeManager implements WebformThemeManagerInterface {

  /**
   * The configuration object factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The theme manager.
   *
   * @var \Drupal\Core\Theme\ThemeManagerInterface
   */
  protected $themeManager;

  /**
   * The theme initialization.
   *
   * @var \Drupal\Core\Theme\ThemeInitializationInterface
   */
  protected $themeInitialization;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Contains the current active theme.
   *
   * @var \Drupal\Core\Theme\ActiveTheme
   */
  protected $activeTheme;

  /**
   * Constructs a WebformTokenManager object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration object factory.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   * @param \Drupal\Core\Theme\ThemeManagerInterface $theme_manager
   *   The theme manager.
   * @param \Drupal\Core\Theme\ThemeInitializationInterface $theme_initialization
   *   The theme initialization.
   */
  public function __construct(ConfigFactoryInterface $config_factory, RendererInterface $renderer, ThemeManagerInterface $theme_manager, ThemeInitializationInterface $theme_initialization) {
    $this->configFactory = $config_factory;
    $this->renderer = $renderer;
    $this->themeManager = $theme_manager;
    $this->themeInitialization = $theme_initialization;
  }

  /**
   * {@inheritdoc}
   */
  public function getActiveThemeNames() {
    $active_theme = $this->themeManager->getActiveTheme();
    // Note: Reversing the order so that base themes are first.
    return array_reverse(array_merge([$active_theme->getName()], array_keys($active_theme->getBaseThemes())));
  }

  /**
   * {@inheritdoc}
   */
  public function isActiveTheme($theme_name) {
    return in_array($theme_name, $this->getActiveThemeNames());
  }

  /**
   * {@inheritdoc}
   */
  public function setDefaultTheme() {
    if (!isset($this->activeTheme)) {
      $this->activeTheme = $this->themeManager->getActiveTheme();
    }
    $default_theme_name = $this->configFactory->get('system.theme')->get('default');
    $default_theme = $this->themeInitialization->getActiveThemeByName($default_theme_name);
    $this->themeManager->setActiveTheme($default_theme);
  }

  /**
   * {@inheritdoc}
   */
  public function setActiveTheme() {
    if ($this->activeTheme) {
      $this->themeManager->setActiveTheme($this->activeTheme);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function render(array &$elements, $default_theme = TRUE) {
    if ($default_theme) {
      $this->setDefaultTheme();
    }
    $markup = $this->renderer->render($elements);
    if ($default_theme) {
      $this->setActiveTheme();
    }
    return $markup;
  }

  /**
   * {@inheritdoc}
   */
  public function renderPlain(array &$elements, $default_theme = TRUE) {
    if ($default_theme) {
      $this->setDefaultTheme();
    }
    $markup = $this->renderer->renderPlain($elements);
    if ($default_theme) {
      $this->setActiveTheme();
    }
    return $markup;
  }

}
