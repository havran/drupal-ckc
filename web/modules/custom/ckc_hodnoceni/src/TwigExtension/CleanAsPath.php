<?php

namespace Drupal\ckc_hodnoceni\TwigExtension;

class CleanAsPath extends \Twig_Extension {

  /**
   * Generates a list of all Twig filters that this extension defines.
   */
  public function getFilters() {
    return [
      new \Twig_SimpleFilter('cleanAsPath', array($this, 'cleanAsPath')),
    ];
  }

  /**
   * Gets a unique identifier for this Twig extension.
   */
  public function getName() {
    return 'ckc_hodnoceni.twig_extension';
  }

  /**
   * Replaces all numbers from the string.
   */
  public static function cleanAsPath($string) {
    return \Drupal::service('pathauto.alias_cleaner')->cleanString($string);
  }

}
