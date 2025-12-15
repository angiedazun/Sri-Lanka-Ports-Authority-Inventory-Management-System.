<?php
/**
 * Code Formatter
 * Automatically formats PHP code according to PSR standards
 * 
 * @package SLPA\CodeQuality
 * @version 1.0.0
 */

class CodeFormatter {
    private $options = [];
    
    // Default formatting options
    const DEFAULT_OPTIONS = [
        'indent_size' => 4,
        'indent_with_tabs' => false,
        'line_length' => 120,
        'psr_standard' => 'PSR-12',
        'blank_line_after_namespace' => true,
        'blank_line_after_opening_tag' => false,
        'blank_line_before_return' => true,
        'space_after_cast' => true,
        'lowercase_keywords' => true,
        'visibility_required' => true
    ];
    
    public function __construct(array $options = []) {
        $this->options = array_merge(self::DEFAULT_OPTIONS, $options);
    }
    
    /**
     * Format a PHP file
     * 
     * @param string $filePath Path to PHP file
     * @return array Result with formatted code
     */
    public function formatFile($filePath) {
        try {
            if (!file_exists($filePath)) {
                throw new FileException("File not found: $filePath");
            }
            
            $originalContent = file_get_contents($filePath);
            $formattedContent = $this->format($originalContent);
            
            // Backup original
            $backupPath = $filePath . '.backup';
            file_put_contents($backupPath, $originalContent);
            
            // Write formatted code
            file_put_contents($filePath, $formattedContent);
            
            return [
                'success' => true,
                'file' => $filePath,
                'backup' => $backupPath,
                'changes' => $originalContent !== $formattedContent
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'file' => $filePath,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Format PHP code
     * 
     * @param string $code PHP code to format
     * @return string Formatted code
     */
    public function format($code) {
        // Basic formatting steps
        $code = $this->normalizeLineEndings($code);
        $code = $this->fixIndentation($code);
        $code = $this->fixSpacing($code);
        $code = $this->fixBraces($code);
        $code = $this->fixKeywords($code);
        $code = $this->fixBlankLines($code);
        $code = $this->removeTrailingWhitespace($code);
        
        return $code;
    }
    
    /**
     * Normalize line endings to LF
     */
    private function normalizeLineEndings($code) {
        return str_replace(["\r\n", "\r"], "\n", $code);
    }
    
    /**
     * Fix indentation
     */
    private function fixIndentation($code) {
        $lines = explode("\n", $code);
        $indent = $this->options['indent_with_tabs'] ? "\t" : str_repeat(' ', $this->options['indent_size']);
        $level = 0;
        $formatted = [];
        
        foreach ($lines as $line) {
            $trimmed = trim($line);
            
            // Decrease indent before closing braces
            if (preg_match('/^\}/', $trimmed)) {
                $level = max(0, $level - 1);
            }
            
            // Add indentation
            if (!empty($trimmed)) {
                $formatted[] = str_repeat($indent, $level) . $trimmed;
            } else {
                $formatted[] = '';
            }
            
            // Increase indent after opening braces
            if (preg_match('/\{\s*$/', $trimmed)) {
                $level++;
            }
        }
        
        return implode("\n", $formatted);
    }
    
    /**
     * Fix spacing around operators and keywords
     */
    private function fixSpacing($code) {
        // Space after control structures
        $code = preg_replace('/\b(if|elseif|for|foreach|while|switch|catch)\(/', '$1 (', $code);
        
        // Space around operators
        $code = preg_replace('/([^\s=!<>])([=!<>]=?)([^\s=])/', '$1 $2 $3', $code);
        $code = preg_replace('/([^\s])([\+\-\*\/\%])([^\s=])/', '$1 $2 $3', $code);
        
        // Space after commas
        $code = preg_replace('/,(?!\s)/', ', ', $code);
        
        // Space after cast
        if ($this->options['space_after_cast']) {
            $code = preg_replace('/\(([a-z]+)\)(?!\s)/', '($1) ', $code);
        }
        
        // No space before semicolon
        $code = preg_replace('/\s+;/', ';', $code);
        
        return $code;
    }
    
    /**
     * Fix brace placement
     */
    private function fixBraces($code) {
        // Opening brace on same line for control structures
        $code = preg_replace('/\)\s*\n\s*\{/', ') {', $code);
        
        // Opening brace on new line for classes and functions
        $code = preg_replace('/(class|function|interface|trait)\s+[^{]+\{/', "$0\n{", $code);
        
        return $code;
    }
    
    /**
     * Fix keyword casing
     */
    private function fixKeywords($code) {
        if ($this->options['lowercase_keywords']) {
            $keywords = ['true', 'false', 'null', 'array', 'callable', 'bool', 'int', 'float', 'string'];
            
            foreach ($keywords as $keyword) {
                $code = preg_replace('/\b' . $keyword . '\b/i', strtolower($keyword), $code);
            }
        }
        
        return $code;
    }
    
    /**
     * Fix blank lines
     */
    private function fixBlankLines($code) {
        // Blank line after namespace
        if ($this->options['blank_line_after_namespace']) {
            $code = preg_replace('/namespace\s+[^;]+;\n(?!\n)/', "$0\n", $code);
        }
        
        // Blank line before return
        if ($this->options['blank_line_before_return']) {
            $code = preg_replace('/([^\n])\n(\s*)return\s/', "$1\n\n$2return ", $code);
        }
        
        // Remove multiple blank lines
        $code = preg_replace('/\n{3,}/', "\n\n", $code);
        
        return $code;
    }
    
    /**
     * Remove trailing whitespace
     */
    private function removeTrailingWhitespace($code) {
        $lines = explode("\n", $code);
        $lines = array_map('rtrim', $lines);
        
        return implode("\n", $lines);
    }
    
    /**
     * Format entire directory
     */
    public function formatDirectory($dirPath, $extensions = ['php']) {
        $results = [];
        
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dirPath)
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $ext = pathinfo($file->getFilename(), PATHINFO_EXTENSION);
                
                if (in_array($ext, $extensions)) {
                    $results[] = $this->formatFile($file->getPathname());
                }
            }
        }
        
        return [
            'directory' => $dirPath,
            'files_processed' => count($results),
            'files' => $results
        ];
    }
    
    /**
     * Restore from backup
     */
    public function restoreBackup($filePath) {
        $backupPath = $filePath . '.backup';
        
        if (!file_exists($backupPath)) {
            throw new FileException("Backup not found: $backupPath");
        }
        
        copy($backupPath, $filePath);
        unlink($backupPath);
        
        return true;
    }
}
