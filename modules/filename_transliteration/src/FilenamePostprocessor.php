<?php
namespace Drupal\filename_transliteration;

use Drupal\Component\Transliteration\TransliterationInterface;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Config\ConfigFactoryInterface;

class FilenamePostprocessor {

protected $configFactory;
protected $transliteration;

  public function __construct(ConfigFactoryInterface $config_factory, TransliterationInterface $transliteration) {
    $this->configFactory = $config_factory;
    $this->transliteration = $transliteration;
 	}

 	public function process($filename) {
 		$filename = Unicode::strtolower($filename);
    $filename = str_replace(' ', '_', $filename);
 		$filename = $this->transliteration->transliterate($filename);

   	return $filename;
 	}
}