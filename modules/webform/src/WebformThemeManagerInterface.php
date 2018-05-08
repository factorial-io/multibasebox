<?php

namespace Drupal\webform;

/**
 * Defines an interface for theme manager classes.
 */
interface WebformThemeManagerInterface {

  /**
   * Get all active theme names.
   *
   * @return array
   *   An array containing the active theme and base theme names.
   */
  public function getActiveThemeNames();

  /**
   * Determine if a theme name is being used the active or base theme.
   *
   * @param string $theme_name
   *   A theme name.
   *
   * @return bool
   *   TRUE if a theme name is being used the active or base theme.
   */
  public function isActiveTheme($theme_name);

  /**
   * Sets the current theme the default theme.
   */
  public function setDefaultTheme();

  /**
   * Sets the current theme the active theme.
   */
  public function setActiveTheme();

  /**
   * Renders HTML given a structured array tree.
   *
   * @param array $elements
   *   The structured array describing the data to be rendered.
   * @param bool $default_theme
   *   Render using the default theme. Defaults to TRUE.
   *
   * @return \Drupal\Component\Render\MarkupInterface
   *   The rendered HTML.
   */
  public function render(array &$elements, $default_theme = TRUE);

  /**
   * Renders using the default theme final HTML in situations where no assets are needed.
   *
   * @param array $elements
   *   The structured array describing the data to be rendered.
   * @param bool $default_theme
   *   Render using the default theme. Defaults to TRUE.
   *
   * @return \Drupal\Component\Render\MarkupInterface
   *   The rendered HTML.
   */
  public function renderPlain(array &$elements, $default_theme = TRUE);

}
