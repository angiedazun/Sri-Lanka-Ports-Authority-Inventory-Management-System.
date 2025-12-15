<?php
/**
 * Code Validator
 * Validates PHP code quality, standards compliance, and best practices
 * 
 * @package SLPA\CodeQuality
 * @version 1.0.0
 * @author SLPA Development Team
 */

class CodeValidator {
    private $logger;
    private $violations = [];
    
    // PSR Standards
    const PSR_1 = 'PSR-1';
    const PSR_12 = 'PSR-12';
    const PSR_4 = 'PSR-4';
    
    // Severity levels
    const SEVERITY_ERROR = 'error';
    const SEVERITY_WARNING = 'warning';
    const SEVERITY_INFO = 'info';
    
    public function __construct() {
        $this->logger = new Logger();
    }
    
    /**
     * Validate a PHP file for quality and standards
     * 
     * @param string $filePath Path to PHP file
     * @param array $options Validation options
     * @return array Validation results
     */
    public function validateFile($filePath, $options = []) {
        try {
            if (!file_exists($filePath)) {
                throw new InvalidArgumentException("File not found: $filePath");
            }
            
            $this->violations = [];
            $content = file_get_contents($filePath);
            
            // Run validation checks
            $this->checkSyntax($filePath);
            $this->checkPSR1($content, $filePath);
            $this->checkPSR12($content, $filePath);
            $this->checkComplexity($content, $filePath);
            $this->checkDocumentation($content, $filePath);
            $this->checkSecurityIssues($content, $filePath);
            $this->checkBestPractices($content, $filePath);
            
            return [
                'file' => $filePath,
                'valid' => empty($this->violations),
                'violations' => $this->violations,
                'summary' => $this->generateSummary()
            ];
            
        } catch (Exception $e) {
            $this->logger->error("Code validation failed", [
                'file' => $filePath,
                'error' => $e->getMessage()
            ]);
            
            return [
                'file' => $filePath,
                'valid' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Check PHP syntax errors
     */
    private function checkSyntax($filePath) {
        $output = [];
        $return = 0;
        
        exec("php -l " . escapeshellarg($filePath) . " 2>&1", $output, $return);
        
        if ($return !== 0) {
            $this->addViolation(
                self::SEVERITY_ERROR,
                'Syntax Error',
                implode("\n", $output),
                0
            );
        }
    }
    
    /**
     * Check PSR-1 Basic Coding Standard
     */
    private function checkPSR1($content, $filePath) {
        // PHP tags
        if (!preg_match('/^<\?php/', $content)) {
            $this->addViolation(
                self::SEVERITY_ERROR,
                'PSR-1: Invalid PHP opening tag',
                'Files MUST use only <?php tags',
                1
            );
        }
        
        // Short tags
        if (preg_match('/<\?(?!php|=)/', $content)) {
            $this->addViolation(
                self::SEVERITY_ERROR,
                'PSR-1: Short PHP tags used',
                'Files MUST NOT use short PHP tags',
                1
            );
        }
        
        // BOM
        if (substr($content, 0, 3) === "\xEF\xBB\xBF") {
            $this->addViolation(
                self::SEVERITY_WARNING,
                'PSR-1: UTF-8 BOM detected',
                'Files SHOULD NOT have UTF-8 BOM',
                1
            );
        }
        
        // Class naming (PascalCase)
        if (preg_match_all('/class\s+([a-z][A-Za-z0-9_]*)/i', $content, $matches)) {
            foreach ($matches[1] as $className) {
                if (!preg_match('/^[A-Z][A-Za-z0-9]*$/', $className)) {
                    $this->addViolation(
                        self::SEVERITY_WARNING,
                        'PSR-1: Class name not in PascalCase',
                        "Class '$className' should be in PascalCase",
                        1
                    );
                }
            }
        }
        
        // Method naming (camelCase)
        if (preg_match_all('/function\s+([a-zA-Z_][A-Za-z0-9_]*)/i', $content, $matches)) {
            foreach ($matches[1] as $methodName) {
                if (strpos($methodName, '__') !== 0 && !preg_match('/^[a-z][a-zA-Z0-9]*$/', $methodName)) {
                    $this->addViolation(
                        self::SEVERITY_INFO,
                        'PSR-1: Method name not in camelCase',
                        "Method '$methodName' should be in camelCase",
                        1
                    );
                }
            }
        }
    }
    
    /**
     * Check PSR-12 Extended Coding Style
     */
    private function checkPSR12($content, $filePath) {
        $lines = explode("\n", $content);
        
        foreach ($lines as $lineNum => $line) {
            $lineNumber = $lineNum + 1;
            
            // Trailing whitespace
            if (preg_match('/\s+$/', $line)) {
                $this->addViolation(
                    self::SEVERITY_INFO,
                    'PSR-12: Trailing whitespace',
                    'Lines MUST NOT have trailing whitespace',
                    $lineNumber
                );
            }
            
            // Line length
            if (strlen($line) > 120) {
                $this->addViolation(
                    self::SEVERITY_WARNING,
                    'PSR-12: Line too long',
                    'Lines SHOULD be 80 characters or less, MUST NOT exceed 120',
                    $lineNumber
                );
            }
            
            // Tabs
            if (strpos($line, "\t") !== false) {
                $this->addViolation(
                    self::SEVERITY_WARNING,
                    'PSR-12: Tabs used for indentation',
                    'Code MUST use 4 spaces for indentation, not tabs',
                    $lineNumber
                );
            }
        }
        
        // Blank line after namespace
        if (preg_match('/namespace\s+[^;]+;(?!\n\n)/', $content)) {
            $this->addViolation(
                self::SEVERITY_INFO,
                'PSR-12: Missing blank line after namespace',
                'There MUST be one blank line after namespace declaration',
                1
            );
        }
        
        // Control structure spacing
        $controlStructures = ['if', 'else', 'elseif', 'for', 'foreach', 'while', 'switch'];
        foreach ($controlStructures as $structure) {
            if (preg_match('/' . $structure . '\s*\(/', $content)) {
                // Correct format found
            } elseif (preg_match('/' . $structure . '\(/', $content)) {
                $this->addViolation(
                    self::SEVERITY_WARNING,
                    'PSR-12: Missing space after control structure',
                    "Control structure '$structure' MUST have one space after keyword",
                    1
                );
            }
        }
    }
    
    /**
     * Check code complexity (Cyclomatic Complexity)
     */
    private function checkComplexity($content, $filePath) {
        // Extract functions
        preg_match_all('/function\s+([a-zA-Z_][A-Za-z0-9_]*)\s*\([^)]*\)\s*\{(.*?)\n\s*\}/s', $content, $matches, PREG_SET_ORDER);
        
        foreach ($matches as $match) {
            $functionName = $match[1];
            $functionBody = $match[2];
            
            // Calculate cyclomatic complexity
            $complexity = $this->calculateComplexity($functionBody);
            
            if ($complexity > 10) {
                $this->addViolation(
                    self::SEVERITY_WARNING,
                    'High Complexity',
                    "Function '$functionName' has complexity of $complexity (should be <= 10)",
                    1
                );
            } elseif ($complexity > 15) {
                $this->addViolation(
                    self::SEVERITY_ERROR,
                    'Very High Complexity',
                    "Function '$functionName' has complexity of $complexity (critical, should be refactored)",
                    1
                );
            }
            
            // Check function length
            $lines = substr_count($functionBody, "\n");
            if ($lines > 50) {
                $this->addViolation(
                    self::SEVERITY_WARNING,
                    'Long Function',
                    "Function '$functionName' has $lines lines (should be <= 50)",
                    1
                );
            }
        }
    }
    
    /**
     * Calculate cyclomatic complexity
     */
    private function calculateComplexity($code) {
        $complexity = 1; // Base complexity
        
        // Count decision points
        $patterns = [
            '/\bif\b/',
            '/\belse\b/',
            '/\belseif\b/',
            '/\bfor\b/',
            '/\bforeach\b/',
            '/\bwhile\b/',
            '/\bcase\b/',
            '/\bcatch\b/',
            '/\?\s*:/', // Ternary
            '/&&/',     // Logical AND
            '/\|\|/'    // Logical OR
        ];
        
        foreach ($patterns as $pattern) {
            $complexity += preg_match_all($pattern, $code);
        }
        
        return $complexity;
    }
    
    /**
     * Check documentation (PHPDoc)
     */
    private function checkDocumentation($content, $filePath) {
        // Check class documentation
        if (preg_match('/class\s+([A-Z][A-Za-z0-9]*)/i', $content, $match)) {
            $className = $match[1];
            $beforeClass = substr($content, 0, strpos($content, $match[0]));
            
            if (!preg_match('/\/\*\*.*?\*\//s', $beforeClass)) {
                $this->addViolation(
                    self::SEVERITY_WARNING,
                    'Missing Documentation',
                    "Class '$className' lacks PHPDoc documentation",
                    1
                );
            }
        }
        
        // Check public method documentation
        preg_match_all('/public\s+function\s+([a-zA-Z_][A-Za-z0-9_]*)/i', $content, $matches, PREG_OFFSET_CAPTURE);
        
        foreach ($matches[1] as $match) {
            $methodName = $match[0];
            $position = $match[1];
            
            // Check for PHPDoc before method
            $beforeMethod = substr($content, max(0, $position - 500), 500);
            
            if (!preg_match('/\/\*\*.*?@param.*?\*\//s', $beforeMethod) && !preg_match('/\/\*\*.*?@return.*?\*\//s', $beforeMethod)) {
                $this->addViolation(
                    self::SEVERITY_INFO,
                    'Missing Documentation',
                    "Public method '$methodName' lacks complete PHPDoc (@param, @return)",
                    1
                );
            }
        }
    }
    
    /**
     * Check for security issues
     */
    private function checkSecurityIssues($content, $filePath) {
        // SQL Injection risks
        if (preg_match('/\$[a-zA-Z_]+->query\s*\(\s*["\'].*?\$/', $content)) {
            $this->addViolation(
                self::SEVERITY_ERROR,
                'Security: SQL Injection Risk',
                'Direct variable interpolation in SQL query detected. Use prepared statements.',
                1
            );
        }
        
        // XSS risks
        if (preg_match('/echo\s+\$_(GET|POST|REQUEST)\[/', $content)) {
            $this->addViolation(
                self::SEVERITY_ERROR,
                'Security: XSS Risk',
                'Direct output of user input without sanitization detected',
                1
            );
        }
        
        // eval() usage
        if (preg_match('/\beval\s*\(/', $content)) {
            $this->addViolation(
                self::SEVERITY_ERROR,
                'Security: eval() Usage',
                'eval() is dangerous and should be avoided',
                1
            );
        }
        
        // Hardcoded credentials
        if (preg_match('/(password|passwd|pwd)\s*=\s*["\'][^"\']+["\']/i', $content)) {
            $this->addViolation(
                self::SEVERITY_ERROR,
                'Security: Hardcoded Credentials',
                'Hardcoded password detected. Use environment variables.',
                1
            );
        }
    }
    
    /**
     * Check best practices
     */
    private function checkBestPractices($content, $filePath) {
        // Type hints
        if (preg_match_all('/function\s+[a-zA-Z_][A-Za-z0-9_]*\s*\(([^)]+)\)/', $content, $matches)) {
            foreach ($matches[1] as $params) {
                if (!empty($params) && !preg_match('/\w+\s+\$/', $params)) {
                    $this->addViolation(
                        self::SEVERITY_INFO,
                        'Best Practice: Missing Type Hints',
                        'Function parameters should have type hints',
                        1
                    );
                    break;
                }
            }
        }
        
        // Return type declarations
        if (preg_match_all('/function\s+[a-zA-Z_][A-Za-z0-9_]*\s*\([^)]*\)(?!\s*:\s*\w+)/', $content, $matches)) {
            if (count($matches[0]) > 0) {
                $this->addViolation(
                    self::SEVERITY_INFO,
                    'Best Practice: Missing Return Type',
                    'Functions should declare return types',
                    1
                );
            }
        }
        
        // Magic numbers
        if (preg_match_all('/[^a-zA-Z_]\d{3,}[^a-zA-Z_]/', $content, $matches)) {
            $this->addViolation(
                self::SEVERITY_INFO,
                'Best Practice: Magic Numbers',
                'Large numbers should be defined as named constants',
                1
            );
        }
    }
    
    /**
     * Add violation to list
     */
    private function addViolation($severity, $rule, $message, $line) {
        $this->violations[] = [
            'severity' => $severity,
            'rule' => $rule,
            'message' => $message,
            'line' => $line
        ];
    }
    
    /**
     * Generate summary of violations
     */
    private function generateSummary() {
        $summary = [
            'total' => count($this->violations),
            'errors' => 0,
            'warnings' => 0,
            'info' => 0
        ];
        
        foreach ($this->violations as $violation) {
            switch ($violation['severity']) {
                case self::SEVERITY_ERROR:
                    $summary['errors']++;
                    break;
                case self::SEVERITY_WARNING:
                    $summary['warnings']++;
                    break;
                case self::SEVERITY_INFO:
                    $summary['info']++;
                    break;
            }
        }
        
        return $summary;
    }
    
    /**
     * Validate entire directory
     */
    public function validateDirectory($dirPath, $extensions = ['php']) {
        $results = [];
        
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dirPath)
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $ext = pathinfo($file->getFilename(), PATHINFO_EXTENSION);
                
                if (in_array($ext, $extensions)) {
                    $results[] = $this->validateFile($file->getPathname());
                }
            }
        }
        
        return [
            'directory' => $dirPath,
            'files_checked' => count($results),
            'files' => $results,
            'summary' => $this->generateDirectorySummary($results)
        ];
    }
    
    /**
     * Generate summary for directory validation
     */
    private function generateDirectorySummary($results) {
        $summary = [
            'total_files' => count($results),
            'valid_files' => 0,
            'total_violations' => 0,
            'errors' => 0,
            'warnings' => 0,
            'info' => 0
        ];
        
        foreach ($results as $result) {
            if ($result['valid']) {
                $summary['valid_files']++;
            }
            
            if (isset($result['summary'])) {
                $summary['total_violations'] += $result['summary']['total'];
                $summary['errors'] += $result['summary']['errors'];
                $summary['warnings'] += $result['summary']['warnings'];
                $summary['info'] += $result['summary']['info'];
            }
        }
        
        return $summary;
    }
}
