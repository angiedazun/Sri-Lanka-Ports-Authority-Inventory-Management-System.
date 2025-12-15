<?php
/**
 * PHPSpreadsheet Stub File
 * This file exists only to prevent IDE warnings.
 * Install the actual library with: composer require phpoffice/phpspreadsheet
 */

namespace PhpOffice\PhpSpreadsheet {
    class Spreadsheet {
        public function getActiveSheet() {
            return new \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet();
        }
    }
    
    class IOFactory {
        public static function load($filename) {
            return new Spreadsheet();
        }
    }
    
    class Cell {
        public function getValue() {
            return '';
        }
    }
}

namespace PhpOffice\PhpSpreadsheet\Writer {
    class Xlsx {
        public function __construct($spreadsheet) {}
        public function save($filename) {}
    }
}

namespace PhpOffice\PhpSpreadsheet\Worksheet {
    class Worksheet {
        public function getHighestColumn() {
            return 'Z';
        }
        public function getCell($coordinate) {
            return new \PhpOffice\PhpSpreadsheet\Cell();
        }
        public function setCellValue($coordinate, $value) {
            return $this;
        }
        public function getRowIterator($startRow = 1) {
            return [];
        }
    }
}
