<?php
/**
 * Code Analyzer
 * Analyzes PHP code for metrics, complexity, and quality indicators
 * 
 * @package SLPA\CodeQuality
 * @version 1.0.0
 */

class CodeAnalyzer {
    private $logger;
    private $metrics = [];
    
    public function __construct() {
        $this->logger = new Logger();
    }
    
    /**
     * Analyze a PHP file
     * 
     * @param string $filePath Path to PHP file
     * @return array Analysis results
     */
    public function analyzeFile($filePath) {
        try {
            if (!file_exists($filePath)) {
                throw new FileException("File not found: $filePath");
            }
            
            $content = file_get_contents($filePath);
            $tokens = token_get_all($content);
            
            return [
                'file' => $filePath,
                'metrics' => $this->calculateMetrics($content, $tokens),
                'complexity' => $this->analyzeComplexity($content),
                'quality' => $this->calculateQualityScore($content, $tokens),
                'dependencies' => $this->analyzeDependencies($tokens),
                'documentation' => $this->analyzeDocumentation($content),
                'structure' => $this->analyzeStructure($tokens)
            ];
            
        } catch (Exception $e) {
            $this->logger->error("Code analysis failed", [
                'file' => $filePath,
                'error' => $e->getMessage()
            ]);
            
            return [
                'file' => $filePath,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Calculate code metrics
     */
    private function calculateMetrics($content, $tokens) {
        $lines = explode("\n", $content);
        
        $metrics = [
            'lines_of_code' => count($lines),
            'blank_lines' => 0,
            'comment_lines' => 0,
            'code_lines' => 0,
            'classes' => 0,
            'interfaces' => 0,
            'traits' => 0,
            'methods' => 0,
            'functions' => 0,
            'constants' => 0,
            'properties' => 0
        ];
        
        // Count blank and comment lines
        foreach ($lines as $line) {
            $trimmed = trim($line);
            if (empty($trimmed)) {
                $metrics['blank_lines']++;
            } elseif (preg_match('/^(\/\/|#|\*)/', $trimmed)) {
                $metrics['comment_lines']++;
            } else {
                $metrics['code_lines']++;
            }
        }
        
        // Count structural elements
        foreach ($tokens as $token) {
            if (is_array($token)) {
                switch ($token[0]) {
                    case T_CLASS:
                        $metrics['classes']++;
                        break;
                    case T_INTERFACE:
                        $metrics['interfaces']++;
                        break;
                    case T_TRAIT:
                        $metrics['traits']++;
                        break;
                    case T_FUNCTION:
                        $metrics['methods']++;
                        break;
                    case T_CONST:
                        $metrics['constants']++;
                        break;
                    case T_VARIABLE:
                        // Count class properties (simplified)
                        break;
                }
            }
        }
        
        // Calculate ratios
        $metrics['comment_ratio'] = $metrics['lines_of_code'] > 0 
            ? round(($metrics['comment_lines'] / $metrics['lines_of_code']) * 100, 2) 
            : 0;
        
        $metrics['code_ratio'] = $metrics['lines_of_code'] > 0 
            ? round(($metrics['code_lines'] / $metrics['lines_of_code']) * 100, 2) 
            : 0;
        
        return $metrics;
    }
    
    /**
     * Analyze code complexity
     */
    private function analyzeComplexity($content) {
        $complexity = [
            'cyclomatic' => 1,
            'cognitive' => 0,
            'nesting_level' => 0,
            'max_nesting' => 0,
            'functions' => []
        ];
        
        // Extract functions
        preg_match_all('/function\s+([a-zA-Z_][A-Za-z0-9_]*)\s*\([^)]*\)\s*\{(.*?)\n\s*\}/s', $content, $matches, PREG_SET_ORDER);
        
        foreach ($matches as $match) {
            $functionName = $match[1];
            $functionBody = $match[2];
            
            $funcComplexity = $this->calculateCyclomaticComplexity($functionBody);
            $funcCognitive = $this->calculateCognitiveComplexity($functionBody);
            
            $complexity['functions'][$functionName] = [
                'cyclomatic' => $funcComplexity,
                'cognitive' => $funcCognitive,
                'lines' => substr_count($functionBody, "\n")
            ];
            
            $complexity['cyclomatic'] = max($complexity['cyclomatic'], $funcComplexity);
            $complexity['cognitive'] = max($complexity['cognitive'], $funcCognitive);
        }
        
        // Calculate nesting level
        $lines = explode("\n", $content);
        $currentNesting = 0;
        
        foreach ($lines as $line) {
            $currentNesting += substr_count($line, '{') - substr_count($line, '}');
            $complexity['max_nesting'] = max($complexity['max_nesting'], $currentNesting);
        }
        
        return $complexity;
    }
    
    /**
     * Calculate cyclomatic complexity
     */
    private function calculateCyclomaticComplexity($code) {
        $complexity = 1;
        
        $patterns = [
            '/\bif\b/', '/\belse\b/', '/\belseif\b/',
            '/\bfor\b/', '/\bforeach\b/', '/\bwhile\b/',
            '/\bcase\b/', '/\bcatch\b/',
            '/\?\s*:/', '/&&/', '/\|\|/'
        ];
        
        foreach ($patterns as $pattern) {
            $complexity += preg_match_all($pattern, $code);
        }
        
        return $complexity;
    }
    
    /**
     * Calculate cognitive complexity
     */
    private function calculateCognitiveComplexity($code) {
        $cognitive = 0;
        $nestingLevel = 0;
        
        $lines = explode("\n", $code);
        
        foreach ($lines as $line) {
            $trimmed = trim($line);
            
            // Increment for control structures
            if (preg_match('/\b(if|for|foreach|while|catch)\b/', $trimmed)) {
                $cognitive += (1 + $nestingLevel);
            }
            
            // Increment for else/elseif
            if (preg_match('/\b(else|elseif)\b/', $trimmed)) {
                $cognitive++;
            }
            
            // Track nesting
            $nestingLevel += substr_count($line, '{') - substr_count($line, '}');
        }
        
        return $cognitive;
    }
    
    /**
     * Calculate quality score
     */
    private function calculateQualityScore($content, $tokens) {
        $score = 100;
        $issues = [];
        
        // Deduct for long file
        $lines = count(explode("\n", $content));
        if ($lines > 500) {
            $score -= 10;
            $issues[] = "File too long ($lines lines)";
        }
        
        // Deduct for lack of documentation
        $hasClassDoc = preg_match('/\/\*\*.*?@package.*?\*\//s', $content);
        if (!$hasClassDoc) {
            $score -= 15;
            $issues[] = "Missing class documentation";
        }
        
        // Deduct for complexity
        $complexity = $this->analyzeComplexity($content);
        if ($complexity['cyclomatic'] > 10) {
            $score -= 20;
            $issues[] = "High cyclomatic complexity ({$complexity['cyclomatic']})";
        }
        
        if ($complexity['max_nesting'] > 4) {
            $score -= 15;
            $issues[] = "Deep nesting level ({$complexity['max_nesting']})";
        }
        
        // Check for best practices
        $hasTypeHints = preg_match('/function\s+\w+\s*\([^)]*\w+\s+\$/', $content);
        if (!$hasTypeHints) {
            $score -= 10;
            $issues[] = "Missing type hints";
        }
        
        $hasReturnTypes = preg_match('/function\s+\w+\s*\([^)]*\)\s*:\s*\w+/', $content);
        if (!$hasReturnTypes) {
            $score -= 10;
            $issues[] = "Missing return type declarations";
        }
        
        // Security checks
        if (preg_match('/\$_(GET|POST|REQUEST)\[/', $content)) {
            $score -= 25;
            $issues[] = "Direct superglobal access (security risk)";
        }
        
        if (preg_match('/eval\s*\(/', $content)) {
            $score -= 30;
            $issues[] = "eval() usage (security risk)";
        }
        
        return [
            'score' => max(0, $score),
            'grade' => $this->getGrade($score),
            'issues' => $issues
        ];
    }
    
    /**
     * Get letter grade from score
     */
    private function getGrade($score) {
        if ($score >= 90) return 'A';
        if ($score >= 80) return 'B';
        if ($score >= 70) return 'C';
        if ($score >= 60) return 'D';
        return 'F';
    }
    
    /**
     * Analyze dependencies
     */
    private function analyzeDependencies($tokens) {
        $dependencies = [
            'classes' => [],
            'traits' => [],
            'interfaces' => []
        ];
        
        $expectingName = false;
        $type = null;
        
        foreach ($tokens as $token) {
            if (is_array($token)) {
                if ($token[0] === T_EXTENDS || $token[0] === T_IMPLEMENTS) {
                    $expectingName = true;
                    $type = $token[0] === T_EXTENDS ? 'classes' : 'interfaces';
                } elseif ($token[0] === T_USE && $expectingName) {
                    $type = 'traits';
                } elseif ($token[0] === T_STRING && $expectingName && $type) {
                    $dependencies[$type][] = $token[1];
                } elseif ($token[0] === T_WHITESPACE) {
                    continue;
                } else {
                    $expectingName = false;
                }
            }
        }
        
        return $dependencies;
    }
    
    /**
     * Analyze documentation coverage
     */
    private function analyzeDocumentation($content) {
        $doc = [
            'has_file_doc' => false,
            'has_class_doc' => false,
            'method_docs' => 0,
            'total_methods' => 0,
            'coverage' => 0
        ];
        
        // Check file documentation
        $doc['has_file_doc'] = preg_match('/^<\?php\s*\/\*\*/', $content) === 1;
        
        // Check class documentation
        $doc['has_class_doc'] = preg_match('/\/\*\*.*?@package.*?\*\/\s*class/s', $content) === 1;
        
        // Count documented methods
        preg_match_all('/\/\*\*.*?\*\/\s*(?:public|protected|private)\s+function/s', $content, $documentedMethods);
        $doc['method_docs'] = count($documentedMethods[0]);
        
        // Count total methods
        preg_match_all('/(?:public|protected|private)\s+function/', $content, $totalMethods);
        $doc['total_methods'] = count($totalMethods[0]);
        
        // Calculate coverage
        if ($doc['total_methods'] > 0) {
            $doc['coverage'] = round(($doc['method_docs'] / $doc['total_methods']) * 100, 2);
        }
        
        return $doc;
    }
    
    /**
     * Analyze code structure
     */
    private function analyzeStructure($tokens) {
        $structure = [
            'namespace' => null,
            'uses' => [],
            'classes' => [],
            'interfaces' => [],
            'traits' => []
        ];
        
        $expectingName = false;
        $type = null;
        
        foreach ($tokens as $i => $token) {
            if (is_array($token)) {
                switch ($token[0]) {
                    case T_NAMESPACE:
                        $expectingName = true;
                        $type = 'namespace';
                        break;
                    case T_USE:
                        $expectingName = true;
                        $type = 'use';
                        break;
                    case T_CLASS:
                        $expectingName = true;
                        $type = 'class';
                        break;
                    case T_INTERFACE:
                        $expectingName = true;
                        $type = 'interface';
                        break;
                    case T_TRAIT:
                        $expectingName = true;
                        $type = 'trait';
                        break;
                    case T_STRING:
                        if ($expectingName && $type) {
                            $name = $token[1];
                            
                            switch ($type) {
                                case 'namespace':
                                    $structure['namespace'] = $name;
                                    break;
                                case 'use':
                                    $structure['uses'][] = $name;
                                    break;
                                case 'class':
                                    $structure['classes'][] = $name;
                                    break;
                                case 'interface':
                                    $structure['interfaces'][] = $name;
                                    break;
                                case 'trait':
                                    $structure['traits'][] = $name;
                                    break;
                            }
                            
                            $expectingName = false;
                        }
                        break;
                }
            }
        }
        
        return $structure;
    }
    
    /**
     * Analyze entire directory
     */
    public function analyzeDirectory($dirPath, $extensions = ['php']) {
        $results = [];
        
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dirPath)
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $ext = pathinfo($file->getFilename(), PATHINFO_EXTENSION);
                
                if (in_array($ext, $extensions)) {
                    $results[] = $this->analyzeFile($file->getPathname());
                }
            }
        }
        
        return [
            'directory' => $dirPath,
            'files_analyzed' => count($results),
            'files' => $results,
            'summary' => $this->generateDirectorySummary($results)
        ];
    }
    
    /**
     * Generate directory summary
     */
    private function generateDirectorySummary($results) {
        $summary = [
            'total_files' => count($results),
            'total_lines' => 0,
            'total_classes' => 0,
            'avg_quality_score' => 0,
            'avg_complexity' => 0
        ];
        
        $qualitySum = 0;
        $complexitySum = 0;
        
        foreach ($results as $result) {
            if (isset($result['metrics'])) {
                $summary['total_lines'] += $result['metrics']['lines_of_code'];
                $summary['total_classes'] += $result['metrics']['classes'];
            }
            
            if (isset($result['quality'])) {
                $qualitySum += $result['quality']['score'];
            }
            
            if (isset($result['complexity'])) {
                $complexitySum += $result['complexity']['cyclomatic'];
            }
        }
        
        if (count($results) > 0) {
            $summary['avg_quality_score'] = round($qualitySum / count($results), 2);
            $summary['avg_complexity'] = round($complexitySum / count($results), 2);
        }
        
        return $summary;
    }
}
