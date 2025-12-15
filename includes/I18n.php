<?php
/**
 * Internationalization (i18n) System
 * Multi-language support with translation management
 * 
 * @package SLPA\I18n
 * @version 1.0.0
 */

class I18n {
    private static $instance = null;
    private $currentLanguage = 'en';
    private $fallbackLanguage = 'en';
    private $translations = [];
    private $loadedFiles = [];
    
    private function __construct() {
        $this->currentLanguage = $_SESSION['language'] ?? 'en';
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Set current language
     */
    public function setLanguage($language) {
        $this->currentLanguage = $language;
        $_SESSION['language'] = $language;
        $this->translations = []; // Clear cache
        $this->loadedFiles = [];
    }
    
    /**
     * Get current language
     */
    public function getLanguage() {
        return $this->currentLanguage;
    }
    
    /**
     * Load language file
     */
    public function loadLanguageFile($filename) {
        $fileKey = $this->currentLanguage . '_' . $filename;
        
        if (isset($this->loadedFiles[$fileKey])) {
            return; // Already loaded
        }
        
        $filepath = BASE_PATH . "/languages/{$this->currentLanguage}/$filename.php";
        
        if (file_exists($filepath)) {
            $translations = require $filepath;
            $this->translations = array_merge($this->translations, $translations);
            $this->loadedFiles[$fileKey] = true;
        } else {
            // Try fallback language
            $fallbackPath = BASE_PATH . "/languages/{$this->fallbackLanguage}/$filename.php";
            if (file_exists($fallbackPath)) {
                $translations = require $fallbackPath;
                $this->translations = array_merge($this->translations, $translations);
            }
        }
    }
    
    /**
     * Translate text
     */
    public function translate($key, $params = []) {
        $translation = $this->translations[$key] ?? $key;
        
        // Replace parameters
        if (!empty($params)) {
            foreach ($params as $param => $value) {
                $translation = str_replace("{{$param}}", $value, $translation);
            }
        }
        
        return $translation;
    }
    
    /**
     * Translate with plural support
     */
    public function translatePlural($key, $count, $params = []) {
        $pluralKey = $count === 1 ? $key : $key . '_plural';
        $translation = $this->translations[$pluralKey] ?? $this->translations[$key] ?? $key;
        
        $params['count'] = $count;
        
        foreach ($params as $param => $value) {
            $translation = str_replace("{{$param}}", $value, $translation);
        }
        
        return $translation;
    }
    
    /**
     * Get available languages
     */
    public static function getAvailableLanguages() {
        $languagesDir = BASE_PATH . '/languages';
        $languages = [];
        
        if (is_dir($languagesDir)) {
            $dirs = scandir($languagesDir);
            foreach ($dirs as $dir) {
                if ($dir !== '.' && $dir !== '..' && is_dir($languagesDir . '/' . $dir)) {
                    $infoFile = $languagesDir . '/' . $dir . '/info.php';
                    if (file_exists($infoFile)) {
                        $info = require $infoFile;
                        $languages[$dir] = $info;
                    } else {
                        $languages[$dir] = ['name' => $dir, 'native_name' => $dir];
                    }
                }
            }
        }
        
        return $languages;
    }
    
    /**
     * Format date according to locale
     */
    public function formatDate($date, $format = 'medium') {
        $timestamp = is_numeric($date) ? $date : strtotime($date);
        
        $formats = [
            'short' => 'd/m/Y',
            'medium' => 'd M Y',
            'long' => 'd F Y',
            'full' => 'l, d F Y'
        ];
        
        $formatString = $formats[$format] ?? $formats['medium'];
        
        // Load month/day names from language file
        $this->loadLanguageFile('datetime');
        
        return date($formatString, $timestamp);
    }
    
    /**
     * Format time according to locale
     */
    public function formatTime($time, $format = '24h') {
        $timestamp = is_numeric($time) ? $time : strtotime($time);
        
        $formatString = $format === '12h' ? 'g:i A' : 'H:i';
        
        return date($formatString, $timestamp);
    }
    
    /**
     * Format number according to locale
     */
    public function formatNumber($number, $decimals = 0) {
        $decimalPoint = $this->translations['decimal_point'] ?? '.';
        $thousandsSep = $this->translations['thousands_separator'] ?? ',';
        
        return number_format($number, $decimals, $decimalPoint, $thousandsSep);
    }
    
    /**
     * Format currency
     */
    public function formatCurrency($amount, $currency = 'LKR') {
        $formatted = $this->formatNumber($amount, 2);
        $symbol = $this->translations['currency_' . $currency] ?? $currency;
        $position = $this->translations['currency_position'] ?? 'before';
        
        if ($position === 'before') {
            return $symbol . ' ' . $formatted;
        }
        
        return $formatted . ' ' . $symbol;
    }
    
    /**
     * Check if text direction is RTL
     */
    public function isRTL() {
        $rtlLanguages = ['ar', 'he', 'fa', 'ur'];
        return in_array($this->currentLanguage, $rtlLanguages);
    }
}

// Helper functions
function __($key, $params = []) {
    return I18n::getInstance()->translate($key, $params);
}

function __n($key, $count, $params = []) {
    return I18n::getInstance()->translatePlural($key, $count, $params);
}

function formatDate($date, $format = 'medium') {
    return I18n::getInstance()->formatDate($date, $format);
}

function formatTime($time, $format = '24h') {
    return I18n::getInstance()->formatTime($time, $format);
}

function formatNumber($number, $decimals = 0) {
    return I18n::getInstance()->formatNumber($number, $decimals);
}

function formatCurrency($amount, $currency = 'LKR') {
    return I18n::getInstance()->formatCurrency($amount, $currency);
}

/**
 * Translation Manager
 */
class TranslationManager {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Add translation
     */
    public function addTranslation($language, $key, $value) {
        $conn = $this->db->getConnection();
        
        $sql = "INSERT INTO translations (language, translation_key, translation_value, created_at) 
                VALUES (?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE translation_value = VALUES(translation_value), updated_at = NOW()";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('sss', $language, $key, $value);
        $stmt->execute();
    }
    
    /**
     * Get translation
     */
    public function getTranslation($language, $key) {
        $conn = $this->db->getConnection();
        
        $sql = "SELECT translation_value FROM translations 
                WHERE language = ? AND translation_key = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ss', $language, $key);
        $stmt->execute();
        
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        return $row ? $row['translation_value'] : null;
    }
    
    /**
     * Get all translations for language
     */
    public function getAllTranslations($language) {
        $conn = $this->db->getConnection();
        
        $sql = "SELECT translation_key, translation_value 
                FROM translations 
                WHERE language = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('s', $language);
        $stmt->execute();
        
        $result = $stmt->get_result();
        $translations = [];
        
        while ($row = $result->fetch_assoc()) {
            $translations[$row['translation_key']] = $row['translation_value'];
        }
        
        return $translations;
    }
    
    /**
     * Import translations from file
     */
    public function importFromFile($language, $filepath) {
        if (!file_exists($filepath)) {
            throw new Exception("Translation file not found: $filepath");
        }
        
        $translations = require $filepath;
        
        foreach ($translations as $key => $value) {
            $this->addTranslation($language, $key, $value);
        }
        
        return count($translations);
    }
    
    /**
     * Export translations to file
     */
    public function exportToFile($language, $filepath) {
        $translations = $this->getAllTranslations($language);
        
        $content = "<?php\nreturn " . var_export($translations, true) . ";\n";
        
        file_put_contents($filepath, $content);
        
        return count($translations);
    }
    
    /**
     * Find missing translations
     */
    public function findMissingTranslations($sourceLanguage, $targetLanguage) {
        $conn = $this->db->getConnection();
        
        $sql = "SELECT t1.translation_key 
                FROM translations t1
                LEFT JOIN translations t2 ON t1.translation_key = t2.translation_key AND t2.language = ?
                WHERE t1.language = ? AND t2.translation_key IS NULL";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ss', $targetLanguage, $sourceLanguage);
        $stmt->execute();
        
        $result = $stmt->get_result();
        $missing = [];
        
        while ($row = $result->fetch_assoc()) {
            $missing[] = $row['translation_key'];
        }
        
        return $missing;
    }
}

/**
 * Language Detector
 */
class LanguageDetector {
    /**
     * Detect browser language
     */
    public static function detectBrowserLanguage() {
        $languages = [];
        
        if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            preg_match_all('/([a-z]{1,8}(-[a-z]{1,8})?)\s*(;\s*q\s*=\s*(1|0\.[0-9]+))?/i', 
                           $_SERVER['HTTP_ACCEPT_LANGUAGE'], $matches);
            
            if (count($matches[1])) {
                $languages = array_combine($matches[1], $matches[4]);
                
                foreach ($languages as $lang => $val) {
                    if ($val === '') $languages[$lang] = 1;
                }
                
                arsort($languages, SORT_NUMERIC);
            }
        }
        
        $availableLanguages = array_keys(I18n::getAvailableLanguages());
        
        foreach ($languages as $lang => $q) {
            $lang = substr($lang, 0, 2); // Get primary language
            if (in_array($lang, $availableLanguages)) {
                return $lang;
            }
        }
        
        return 'en'; // Default
    }
    
    /**
     * Detect language from user location
     */
    public static function detectFromLocation($countryCode) {
        $mapping = [
            'LK' => 'si',  // Sri Lanka -> Sinhala
            'US' => 'en',
            'GB' => 'en',
            'FR' => 'fr',
            'DE' => 'de',
            'ES' => 'es',
            'IT' => 'it',
            'JP' => 'ja',
            'CN' => 'zh',
            'IN' => 'hi'
        ];
        
        return $mapping[strtoupper($countryCode)] ?? 'en';
    }
}
