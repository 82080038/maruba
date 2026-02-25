<?php
/**
 * Language Helper - Internationalization Support
 * Supports Indonesian and English language toggle
 */

class LanguageHelper
{
    private static $languages = [
        'id' => 'Indonesian',
        'en' => 'English'
    ];

    private static $currentLanguage = 'id'; // Default to Indonesian

    /**
     * Initialize language from session or default
     */
    public static function init()
    {
        if (isset($_SESSION['language']) && array_key_exists($_SESSION['language'], self::$languages)) {
            self::$currentLanguage = $_SESSION['language'];
        }

        // Set HTML lang attribute
        if (!isset($GLOBALS['html_lang'])) {
            $GLOBALS['html_lang'] = self::$currentLanguage;
        }
    }

    /**
     * Set current language
     */
    public static function setLanguage($lang)
    {
        if (array_key_exists($lang, self::$languages)) {
            self::$currentLanguage = $lang;
            $_SESSION['language'] = $lang;
            return true;
        }
        return false;
    }

    /**
     * Get current language
     */
    public static function getLanguage()
    {
        return self::$currentLanguage;
    }

    /**
     * Get available languages
     */
    public static function getAvailableLanguages()
    {
        return self::$languages;
    }

    /**
     * Get translated text
     */
    public static function get($key, $default = null)
    {
        static $translations = null;

        // Load translations if not loaded
        if ($translations === null) {
            $translations = self::loadTranslations();
        }

        // Return translation or default
        if (isset($translations[self::$currentLanguage][$key])) {
            return $translations[self::$currentLanguage][$key];
        }

        // Fallback to English if current language doesn't have the key
        if (self::$currentLanguage !== 'en' && isset($translations['en'][$key])) {
            return $translations['en'][$key];
        }

        // Return default or key itself
        return $default ?: $key;
    }

    /**
     * Load translation files
     */
    private static function loadTranslations()
    {
        $translations = [];

        // Load Indonesian translations
        $idFile = __DIR__ . '/../Languages/id.php';
        if (file_exists($idFile)) {
            $translations['id'] = require $idFile;
        }

        // Load English translations
        $enFile = __DIR__ . '/../Languages/en.php';
        if (file_exists($enFile)) {
            $translations['en'] = require $enFile;
        }

        return $translations;
    }

    /**
     * Get language toggle HTML
     */
    public static function getLanguageToggle()
    {
        $current = self::getLanguage();
        $html = '<div class="dropdown">';
        $html .= '<button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">';
        $html .= '<i class="fas fa-globe"></i> ' . strtoupper($current);
        $html .= '</button>';
        $html .= '<ul class="dropdown-menu">';

        foreach (self::$languages as $code => $name) {
            $active = ($code === $current) ? ' active' : '';
            $html .= '<li><a class="dropdown-item' . $active . '" href="#" onclick="changeLanguage(\'' . $code . '\')">';
            $html .= '<i class="fas fa-language"></i> ' . $name;
            $html .= '</a></li>';
        }

        $html .= '</ul>';
        $html .= '</div>';

        return $html;
    }
}

/**
 * Global function for easy translation
 */
function __($key, $default = null)
{
    return LanguageHelper::get($key, $default);
}

/**
 * Initialize language on every request
 */
LanguageHelper::init();
?>
