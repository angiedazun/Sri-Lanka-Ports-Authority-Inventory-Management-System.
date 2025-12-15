<?php
/**
 * Asset Optimizer
 * Optimizes static assets (CSS, JS, images)
 * 
 * @package SLPA\Assets
 * @version 1.0.0
 */

class AssetOptimizer {
    private static $instance = null;
    private $config = [];
    private $manifest = [];
    
    private function __construct() {
        $this->config = [
            'minify' => true,
            'combine' => true,
            'cache_bust' => true,
            'cdn_url' => '', // CDN base URL
            'cache_dir' => BASE_PATH . '/cache/assets',
            'public_path' => '/assets'
        ];
        
        $this->ensureCacheDir();
        $this->loadManifest();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Ensure cache directory exists
     */
    private function ensureCacheDir() {
        if (!is_dir($this->config['cache_dir'])) {
            mkdir($this->config['cache_dir'], 0755, true);
        }
    }
    
    /**
     * Load asset manifest
     */
    private function loadManifest() {
        $manifestFile = $this->config['cache_dir'] . '/manifest.json';
        
        if (file_exists($manifestFile)) {
            $this->manifest = json_decode(file_get_contents($manifestFile), true) ?? [];
        }
    }
    
    /**
     * Save asset manifest
     */
    private function saveManifest() {
        $manifestFile = $this->config['cache_dir'] . '/manifest.json';
        file_put_contents($manifestFile, json_encode($this->manifest, JSON_PRETTY_PRINT));
    }
    
    /**
     * Get asset URL with cache busting
     */
    public function asset($path) {
        $cdnUrl = $this->config['cdn_url'];
        $publicPath = $this->config['public_path'];
        
        // Check if asset is in manifest
        if (isset($this->manifest[$path])) {
            $versionedPath = $this->manifest[$path];
        } else {
            // Generate versioned path
            $filePath = BASE_PATH . $publicPath . '/' . $path;
            
            if (file_exists($filePath)) {
                $hash = substr(md5_file($filePath), 0, 8);
                $pathInfo = pathinfo($path);
                $versionedPath = $pathInfo['dirname'] . '/' . 
                                $pathInfo['filename'] . '.' . $hash . '.' . 
                                $pathInfo['extension'];
                
                // Update manifest
                $this->manifest[$path] = $versionedPath;
                $this->saveManifest();
            } else {
                $versionedPath = $path;
            }
        }
        
        // Return full URL
        if ($cdnUrl) {
            return $cdnUrl . '/' . ltrim($versionedPath, '/');
        }
        
        return $publicPath . '/' . $versionedPath;
    }
    
    /**
     * Minify CSS
     */
    public function minifyCSS($css) {
        if (!$this->config['minify']) {
            return $css;
        }
        
        // Remove comments
        $css = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css);
        
        // Remove whitespace
        $css = str_replace(["\r\n", "\r", "\n", "\t"], '', $css);
        $css = preg_replace('/\s+/', ' ', $css);
        
        // Remove spaces around symbols
        $css = preg_replace('/\s*([:;{}])\s*/', '$1', $css);
        
        return trim($css);
    }
    
    /**
     * Minify JavaScript
     */
    public function minifyJS($js) {
        if (!$this->config['minify']) {
            return $js;
        }
        
        // Remove single-line comments
        $js = preg_replace('~//[^\n]*~', '', $js);
        
        // Remove multi-line comments
        $js = preg_replace('~/\*.*?\*/~s', '', $js);
        
        // Remove whitespace
        $js = preg_replace('/\s+/', ' ', $js);
        
        // Remove spaces around operators
        $js = preg_replace('/\s*([{}();,:])\s*/', '$1', $js);
        
        return trim($js);
    }
    
    /**
     * Combine CSS files
     */
    public function combineCSS($files) {
        if (!$this->config['combine']) {
            return $files;
        }
        
        $combined = '';
        $hash = md5(implode('|', $files));
        $cacheFile = $this->config['cache_dir'] . '/css_' . $hash . '.css';
        
        // Check if cached version exists
        if (file_exists($cacheFile)) {
            return file_get_contents($cacheFile);
        }
        
        // Combine files
        foreach ($files as $file) {
            $filePath = BASE_PATH . $this->config['public_path'] . '/css/' . $file;
            
            if (file_exists($filePath)) {
                $css = file_get_contents($filePath);
                $combined .= $this->minifyCSS($css) . "\n";
            }
        }
        
        // Save combined file
        file_put_contents($cacheFile, $combined);
        
        return $combined;
    }
    
    /**
     * Combine JavaScript files
     */
    public function combineJS($files) {
        if (!$this->config['combine']) {
            return $files;
        }
        
        $combined = '';
        $hash = md5(implode('|', $files));
        $cacheFile = $this->config['cache_dir'] . '/js_' . $hash . '.js';
        
        // Check if cached version exists
        if (file_exists($cacheFile)) {
            return file_get_contents($cacheFile);
        }
        
        // Combine files
        foreach ($files as $file) {
            $filePath = BASE_PATH . $this->config['public_path'] . '/js/' . $file;
            
            if (file_exists($filePath)) {
                $js = file_get_contents($filePath);
                $combined .= $this->minifyJS($js) . ";\n";
            }
        }
        
        // Save combined file
        file_put_contents($cacheFile, $combined);
        
        return $combined;
    }
    
    /**
     * Optimize image
     */
    public function optimizeImage($imagePath) {
        if (!file_exists($imagePath)) {
            return false;
        }
        
        $imageInfo = getimagesize($imagePath);
        $mimeType = $imageInfo['mime'];
        
        switch ($mimeType) {
            case 'image/jpeg':
                return $this->optimizeJPEG($imagePath);
            case 'image/png':
                return $this->optimizePNG($imagePath);
            case 'image/gif':
                return $this->optimizeGIF($imagePath);
            default:
                return false;
        }
    }
    
    /**
     * Optimize JPEG
     */
    private function optimizeJPEG($path) {
        $image = imagecreatefromjpeg($path);
        
        if (!$image) {
            return false;
        }
        
        // Save with 85% quality
        $result = imagejpeg($image, $path, 85);
        /** @phpstan-ignore-next-line */
        @imagedestroy($image);
        
        return $result;
    }
    
    /**
     * Optimize PNG
     */
    private function optimizePNG($path) {
        $image = imagecreatefrompng($path);
        
        if (!$image) {
            return false;
        }
        
        // Enable compression
        imagesavealpha($image, true);
        
        // Save with compression level 9
        $result = imagepng($image, $path, 9);
        /** @phpstan-ignore-next-line */
        @imagedestroy($image);
        
        return $result;
    }
    
    /**
     * Optimize GIF
     */
    private function optimizeGIF($path) {
        $image = imagecreatefromgif($path);
        
        if (!$image) {
            return false;
        }
        
        $result = imagegif($image, $path);
        /** @phpstan-ignore-next-line */
        @imagedestroy($image);
        
        return $result;
    }
    
    /**
     * Generate responsive image sizes
     */
    public function generateResponsiveSizes($imagePath, $sizes = [320, 640, 1024, 1920]) {
        if (!file_exists($imagePath)) {
            return [];
        }
        
        $imageInfo = getimagesize($imagePath);
        $width = $imageInfo[0];
        $height = $imageInfo[1];
        
        $pathInfo = pathinfo($imagePath);
        $generated = [];
        
        foreach ($sizes as $targetWidth) {
            // Skip if target is larger than original
            if ($targetWidth >= $width) {
                continue;
            }
            
            // Calculate proportional height
            $targetHeight = (int)($height * ($targetWidth / $width));
            
            // Generate filename
            $resizedPath = $pathInfo['dirname'] . '/' . 
                          $pathInfo['filename'] . '_' . $targetWidth . 'w.' . 
                          $pathInfo['extension'];
            
            // Resize image
            if ($this->resizeImage($imagePath, $resizedPath, $targetWidth, $targetHeight)) {
                $generated[$targetWidth] = $resizedPath;
            }
        }
        
        return $generated;
    }
    
    /**
     * Resize image
     */
    private function resizeImage($source, $destination, $newWidth, $newHeight) {
        $imageInfo = getimagesize($source);
        $mimeType = $imageInfo['mime'];
        
        // Create source image
        switch ($mimeType) {
            case 'image/jpeg':
                $sourceImage = imagecreatefromjpeg($source);
                break;
            case 'image/png':
                $sourceImage = imagecreatefrompng($source);
                break;
            case 'image/gif':
                $sourceImage = imagecreatefromgif($source);
                break;
            default:
                return false;
        }
        
        if (!$sourceImage) {
            return false;
        }
        
        // Create resized image
        $resizedImage = imagecreatetruecolor($newWidth, $newHeight);
        
        // Preserve transparency for PNG
        if ($mimeType === 'image/png') {
            imagealphablending($resizedImage, false);
            imagesavealpha($resizedImage, true);
        }
        
        // Resize
        imagecopyresampled(
            $resizedImage, $sourceImage,
            0, 0, 0, 0,
            $newWidth, $newHeight,
            $imageInfo[0], $imageInfo[1]
        );
        
        // Save resized image
        $result = false;
        switch ($mimeType) {
            case 'image/jpeg':
                $result = imagejpeg($resizedImage, $destination, 85);
                break;
            case 'image/png':
                $result = imagepng($resizedImage, $destination, 9);
                break;
            case 'image/gif':
                $result = imagegif($resizedImage, $destination);
                break;
        }
        
        /** @phpstan-ignore-next-line */
        @imagedestroy($sourceImage);
        /** @phpstan-ignore-next-line */
        @imagedestroy($resizedImage);
        
        return $result;
    }
    
    /**
     * Clear asset cache
     */
    public function clearCache() {
        $files = glob($this->config['cache_dir'] . '/*');
        
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        
        $this->manifest = [];
        $this->saveManifest();
    }
    
    /**
     * Get cache statistics
     */
    public function getCacheStats() {
        $files = glob($this->config['cache_dir'] . '/*');
        $totalSize = 0;
        
        foreach ($files as $file) {
            if (is_file($file)) {
                $totalSize += filesize($file);
            }
        }
        
        return [
            'file_count' => count($files),
            'total_size' => $totalSize,
            'total_size_mb' => round($totalSize / 1024 / 1024, 2)
        ];
    }
}

/**
 * Browser Cache Helper
 * Sets appropriate cache headers
 */
class BrowserCacheHelper {
    /**
     * Set cache headers for static assets
     */
    public static function setStaticAssetHeaders($maxAge = 31536000) { // 1 year default
        header('Cache-Control: public, max-age=' . $maxAge);
        header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $maxAge) . ' GMT');
        header('Pragma: cache');
    }
    
    /**
     * Set cache headers for dynamic content
     */
    public static function setDynamicContentHeaders($maxAge = 3600) { // 1 hour default
        header('Cache-Control: private, max-age=' . $maxAge);
        header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $maxAge) . ' GMT');
    }
    
    /**
     * Set no-cache headers
     */
    public static function setNoCacheHeaders() {
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Expires: 0');
    }
    
    /**
     * Set ETag header
     */
    public static function setETag($content) {
        $etag = md5($content);
        header('ETag: "' . $etag . '"');
        
        // Check if client has cached version
        if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && 
            trim($_SERVER['HTTP_IF_NONE_MATCH'], '"') === $etag) {
            header('HTTP/1.1 304 Not Modified');
            exit;
        }
    }
}

/**
 * Lazy Loading Helper
 * Generates lazy loading markup
 */
class LazyLoadHelper {
    /**
     * Generate lazy loading image tag
     */
    public static function image($src, $alt = '', $class = '', $placeholder = null) {
        $placeholder = $placeholder ?? self::getPlaceholder();
        
        return sprintf(
            '<img src="%s" data-src="%s" alt="%s" class="lazy %s">',
            $placeholder,
            htmlspecialchars($src),
            htmlspecialchars($alt),
            htmlspecialchars($class)
        );
    }
    
    /**
     * Generate lazy loading background image
     */
    public static function background($src, $class = '', $placeholder = null) {
        $placeholder = $placeholder ?? self::getPlaceholder();
        
        return sprintf(
            '<div class="lazy-bg %s" data-bg="%s" style="background-image: url(%s)"></div>',
            htmlspecialchars($class),
            htmlspecialchars($src),
            $placeholder
        );
    }
    
    /**
     * Get placeholder image (1x1 transparent PNG)
     */
    private static function getPlaceholder() {
        return 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=';
    }
    
    /**
     * Generate lazy loading JavaScript
     */
    public static function script() {
        return <<<'JS'
<script>
document.addEventListener('DOMContentLoaded', function() {
    const lazyImages = document.querySelectorAll('img.lazy');
    const lazyBackgrounds = document.querySelectorAll('.lazy-bg');
    
    const imageObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                
                if (img.dataset.src) {
                    img.src = img.dataset.src;
                    img.classList.remove('lazy');
                }
                
                if (img.dataset.bg) {
                    img.style.backgroundImage = `url(${img.dataset.bg})`;
                    img.classList.remove('lazy-bg');
                }
                
                observer.unobserve(img);
            }
        });
    });
    
    lazyImages.forEach(img => imageObserver.observe(img));
    lazyBackgrounds.forEach(bg => imageObserver.observe(bg));
});
</script>
JS;
    }
}
