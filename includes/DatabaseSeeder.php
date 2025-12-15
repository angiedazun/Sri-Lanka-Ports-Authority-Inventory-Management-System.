<?php
/**
 * Database Seeder
 * System for seeding database with test and demo data
 * 
 * @package SLPA\Database
 * @version 1.0.0
 */

abstract class Seeder {
    protected $db;
    protected $logger;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->logger = new Logger();
    }
    
    /**
     * Run the seeder
     */
    abstract public function run();
    
    /**
     * Truncate table
     */
    protected function truncate($table) {
        $this->db->query("SET FOREIGN_KEY_CHECKS=0");
        $this->db->query("TRUNCATE TABLE `$table`");
        $this->db->query("SET FOREIGN_KEY_CHECKS=1");
        
        $this->logger->info("Truncated table: $table");
    }
    
    /**
     * Insert single record
     */
    protected function insert($table, array $data) {
        $columns = array_keys($data);
        $values = array_values($data);
        
        $columnList = '`' . implode('`, `', $columns) . '`';
        $placeholders = implode(', ', array_fill(0, count($values), '?'));
        
        $sql = "INSERT INTO `$table` ($columnList) VALUES ($placeholders)";
        
        $stmt = $this->db->prepare($sql);
        
        // Bind parameters dynamically
        $types = $this->getBindTypes($values);
        $stmt->bind_param($types, ...$values);
        
        $stmt->execute();
        
        return $this->db->insert_id;
    }
    
    /**
     * Insert multiple records
     */
    protected function insertMultiple($table, array $records) {
        if (empty($records)) {
            return 0;
        }
        
        $columns = array_keys($records[0]);
        $columnList = '`' . implode('`, `', $columns) . '`';
        
        $placeholders = '(' . implode(', ', array_fill(0, count($columns), '?')) . ')';
        $valueSets = implode(', ', array_fill(0, count($records), $placeholders));
        
        $sql = "INSERT INTO `$table` ($columnList) VALUES $valueSets";
        
        $stmt = $this->db->prepare($sql);
        
        // Flatten values
        $values = [];
        foreach ($records as $record) {
            $values = array_merge($values, array_values($record));
        }
        
        // Bind parameters
        $types = $this->getBindTypes($values);
        $stmt->bind_param($types, ...$values);
        
        $stmt->execute();
        
        return $stmt->affected_rows;
    }
    
    /**
     * Get bind types for values
     */
    private function getBindTypes(array $values) {
        $types = '';
        
        foreach ($values as $value) {
            if (is_int($value)) {
                $types .= 'i';
            } elseif (is_float($value)) {
                $types .= 'd';
            } else {
                $types .= 's';
            }
        }
        
        return $types;
    }
}

/**
 * Database Seeder Manager
 */
class DatabaseSeeder {
    private $db;
    private $logger;
    private $seeders = [];
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->logger = new Logger();
    }
    
    /**
     * Register a seeder
     */
    public function register($seederClass) {
        $this->seeders[] = $seederClass;
        return $this;
    }
    
    /**
     * Run all registered seeders
     */
    public function run() {
        $this->logger->info("Starting database seeding...");
        
        foreach ($this->seeders as $seederClass) {
            $seeder = new $seederClass();
            
            $this->logger->info("Running seeder: " . get_class($seeder));
            
            try {
                $seeder->run();
                $this->logger->info("Seeder completed: " . get_class($seeder));
            } catch (Exception $e) {
                $this->logger->error("Seeder failed: " . get_class($seeder) . " - " . $e->getMessage());
                throw $e;
            }
        }
        
        $this->logger->info("Database seeding completed");
    }
    
    /**
     * Run specific seeder
     */
    public function seed($seederClass) {
        $seeder = new $seederClass();
        
        $this->logger->info("Running seeder: " . get_class($seeder));
        
        try {
            $seeder->run();
            $this->logger->info("Seeder completed: " . get_class($seeder));
        } catch (Exception $e) {
            $this->logger->error("Seeder failed: " . get_class($seeder) . " - " . $e->getMessage());
            throw $e;
        }
    }
}

/**
 * Faker - Simple data generator for seeders
 */
class Faker {
    private static $firstNames = [
        'John', 'Jane', 'Michael', 'Sarah', 'David', 'Emma', 'James', 'Emily',
        'Robert', 'Jessica', 'William', 'Ashley', 'Richard', 'Sophia', 'Joseph', 'Olivia'
    ];
    
    private static $lastNames = [
        'Smith', 'Johnson', 'Williams', 'Brown', 'Jones', 'Garcia', 'Miller', 'Davis',
        'Rodriguez', 'Martinez', 'Hernandez', 'Lopez', 'Gonzalez', 'Wilson', 'Anderson', 'Thomas'
    ];
    
    private static $departments = [
        'Administration', 'Finance', 'IT', 'Human Resources', 'Operations',
        'Marketing', 'Sales', 'Customer Service', 'Legal', 'Procurement'
    ];
    
    private static $paperTypes = [
        'A4 White', 'A4 Colored', 'Letter Size', 'Legal Size', 'A3',
        'Cardstock', 'Photo Paper', 'Bond Paper', 'Craft Paper'
    ];
    
    private static $ribbonTypes = [
        'Black', 'Blue', 'Red', 'Green', 'Purple',
        'YMCKO', 'Monochrome', 'White', 'Silver', 'Gold'
    ];
    
    private static $tonerTypes = [
        'Black Toner', 'Cyan Toner', 'Magenta Toner', 'Yellow Toner',
        'Multicolor Toner', 'High Capacity Black', 'Standard Capacity'
    ];
    
    /**
     * Generate random name
     */
    public static function name() {
        return self::randomElement(self::$firstNames) . ' ' . self::randomElement(self::$lastNames);
    }
    
    /**
     * Generate first name
     */
    public static function firstName() {
        return self::randomElement(self::$firstNames);
    }
    
    /**
     * Generate last name
     */
    public static function lastName() {
        return self::randomElement(self::$lastNames);
    }
    
    /**
     * Generate email
     */
    public static function email($name = null) {
        if (!$name) {
            $name = strtolower(self::firstName() . '.' . self::lastName());
        } else {
            $name = strtolower(str_replace(' ', '.', $name));
        }
        
        $domains = ['example.com', 'test.com', 'demo.com', 'sample.org'];
        return $name . '@' . self::randomElement($domains);
    }
    
    /**
     * Generate username
     */
    public static function username($name = null) {
        if (!$name) {
            $name = strtolower(self::firstName() . self::lastName());
        } else {
            $name = strtolower(str_replace(' ', '', $name));
        }
        
        return $name . rand(1, 999);
    }
    
    /**
     * Generate phone number
     */
    public static function phoneNumber() {
        return sprintf('%03d-%03d-%04d', rand(100, 999), rand(100, 999), rand(1000, 9999));
    }
    
    /**
     * Generate random department
     */
    public static function department() {
        return self::randomElement(self::$departments);
    }
    
    /**
     * Generate random paper type
     */
    public static function paperType() {
        return self::randomElement(self::$paperTypes);
    }
    
    /**
     * Generate random ribbon type
     */
    public static function ribbonType() {
        return self::randomElement(self::$ribbonTypes);
    }
    
    /**
     * Generate random toner type
     */
    public static function tonerType() {
        return self::randomElement(self::$tonerTypes);
    }
    
    /**
     * Generate random integer
     */
    public static function numberBetween($min = 0, $max = 100) {
        return rand($min, $max);
    }
    
    /**
     * Generate random float
     */
    public static function randomFloat($decimals = 2, $min = 0, $max = 100) {
        return round($min + mt_rand() / mt_getrandmax() * ($max - $min), $decimals);
    }
    
    /**
     * Generate random date
     */
    public static function date($format = 'Y-m-d', $max = 'now') {
        $timestamp = strtotime('-' . rand(1, 365) . ' days', strtotime($max));
        return date($format, $timestamp);
    }
    
    /**
     * Generate random datetime
     */
    public static function dateTime($format = 'Y-m-d H:i:s', $max = 'now') {
        $timestamp = strtotime('-' . rand(1, 365) . ' days', strtotime($max));
        return date($format, $timestamp);
    }
    
    /**
     * Generate random text
     */
    public static function text($maxLength = 200) {
        $words = [
            'lorem', 'ipsum', 'dolor', 'sit', 'amet', 'consectetur', 'adipiscing', 'elit',
            'sed', 'do', 'eiusmod', 'tempor', 'incididunt', 'ut', 'labore', 'et', 'dolore',
            'magna', 'aliqua', 'enim', 'ad', 'minim', 'veniam', 'quis', 'nostrud'
        ];
        
        $text = '';
        while (strlen($text) < $maxLength) {
            $text .= self::randomElement($words) . ' ';
        }
        
        return trim(substr($text, 0, $maxLength));
    }
    
    /**
     * Generate random sentence
     */
    public static function sentence($wordCount = 6) {
        $words = [
            'lorem', 'ipsum', 'dolor', 'sit', 'amet', 'consectetur', 'adipiscing', 'elit',
            'sed', 'do', 'eiusmod', 'tempor', 'incididunt', 'ut', 'labore', 'et', 'dolore'
        ];
        
        $sentence = [];
        for ($i = 0; $i < $wordCount; $i++) {
            $sentence[] = self::randomElement($words);
        }
        
        return ucfirst(implode(' ', $sentence)) . '.';
    }
    
    /**
     * Generate random boolean
     */
    public static function boolean($chanceOfGettingTrue = 50) {
        return rand(1, 100) <= $chanceOfGettingTrue;
    }
    
    /**
     * Get random element from array
     */
    public static function randomElement(array $array) {
        return $array[array_rand($array)];
    }
    
    /**
     * Get random elements from array
     */
    public static function randomElements(array $array, $count = 1) {
        shuffle($array);
        return array_slice($array, 0, $count);
    }
}

/**
 * Example User Seeder
 */
class UsersSeeder extends Seeder {
    public function run() {
        // Truncate table first
        $this->truncate('users');
        
        // Create admin user
        $this->insert('users', [
            'username' => 'admin',
            'password' => password_hash('admin123', PASSWORD_DEFAULT),
            'full_name' => 'System Administrator',
            'email' => 'admin@slpa.lk',
            'role' => 'admin',
            'status' => 'active',
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        // Create test users
        $users = [];
        for ($i = 1; $i <= 10; $i++) {
            $name = Faker::name();
            $users[] = [
                'username' => Faker::username($name),
                'password' => password_hash('password123', PASSWORD_DEFAULT),
                'full_name' => $name,
                'email' => Faker::email($name),
                'role' => Faker::randomElement(['user', 'manager']),
                'status' => 'active',
                'created_at' => Faker::dateTime()
            ];
        }
        
        $this->insertMultiple('users', $users);
        
        $this->logger->info("Seeded " . (count($users) + 1) . " users");
    }
}

/**
 * Example Papers Seeder
 */
class PapersSeeder extends Seeder {
    public function run() {
        $this->truncate('papers_master');
        
        $papers = [];
        for ($i = 1; $i <= 20; $i++) {
            $papers[] = [
                'paper_type' => Faker::paperType(),
                'quantity' => Faker::numberBetween(100, 1000),
                'unit_price' => Faker::randomFloat(2, 5, 50),
                'supplier' => 'Supplier ' . Faker::numberBetween(1, 5),
                'received_date' => Faker::date(),
                'status' => 'available',
                'created_at' => Faker::dateTime()
            ];
        }
        
        $this->insertMultiple('papers_master', $papers);
        
        $this->logger->info("Seeded " . count($papers) . " paper records");
    }
}

/**
 * Example Ribbons Seeder
 */
class RibbonsSeeder extends Seeder {
    public function run() {
        $this->truncate('ribbons_master');
        
        $ribbons = [];
        for ($i = 1; $i <= 15; $i++) {
            $ribbons[] = [
                'ribbon_type' => Faker::ribbonType(),
                'quantity' => Faker::numberBetween(50, 500),
                'unit_price' => Faker::randomFloat(2, 10, 100),
                'supplier' => 'Supplier ' . Faker::numberBetween(1, 5),
                'received_date' => Faker::date(),
                'status' => 'available',
                'created_at' => Faker::dateTime()
            ];
        }
        
        $this->insertMultiple('ribbons_master', $ribbons);
        
        $this->logger->info("Seeded " . count($ribbons) . " ribbon records");
    }
}

/**
 * Example Toner Seeder
 */
class TonerSeeder extends Seeder {
    public function run() {
        $this->truncate('toner_master');
        
        $toners = [];
        for ($i = 1; $i <= 15; $i++) {
            $toners[] = [
                'toner_type' => Faker::tonerType(),
                'quantity' => Faker::numberBetween(10, 100),
                'unit_price' => Faker::randomFloat(2, 50, 500),
                'supplier' => 'Supplier ' . Faker::numberBetween(1, 5),
                'received_date' => Faker::date(),
                'status' => 'available',
                'created_at' => Faker::dateTime()
            ];
        }
        
        $this->insertMultiple('toner_master', $toners);
        
        $this->logger->info("Seeded " . count($toners) . " toner records");
    }
}

/**
 * Run all seeders
 */
function runAllSeeders() {
    $seeder = new DatabaseSeeder();
    
    $seeder->register(UsersSeeder::class)
           ->register(PapersSeeder::class)
           ->register(RibbonsSeeder::class)
           ->register(TonerSeeder::class)
           ->run();
}
