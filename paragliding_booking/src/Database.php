<?php
// Ensure this file is not accessed directly
if (basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME'])) {
    die('Access denied.');
}

require_once dirname(__DIR__) . '/config/config.php'; // For DB constants

class Database {
    private $host = DB_HOST;
    private $user = DB_USER;
    private $pass = DB_PASS;
    private $dbname = DB_NAME;
    private $charset = 'utf8mb4'; // Recommended charset

    private $dbh; // Database Handler
    private $stmt; // Statement
    private $error;

    public function __construct() {
        $dsn = "mysql:host={$this->host};dbname={$this->dbname};charset={$this->charset}";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Highly recommended
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Default to associative arrays
            PDO::ATTR_EMULATE_PREPARES   => false,                  // For true prepared statements
            PDO::ATTR_PERSISTENT         => true,                   // Optional: Persistent connections
        ];

        try {
            $this->dbh = new PDO($dsn, $this->user, $this->pass, $options);
        } catch (PDOException $e) {
            $this->error = "Database Connection Error: " . $e->getMessage();
            // In a real app, log this error and show a user-friendly message
            // For development, you might want to echo/die here.
            error_log($this->error); // Log to PHP error log
            die("A database error occurred. Please try again later."); // User-friendly message
        }
    }

    // Prepare statement with query
    public function query($sql) {
        if (!$this->dbh) {
            // Handle case where DB connection failed in constructor
             $this->error = "No database connection available.";
             error_log($this->error);
             die("A database error occurred. Please try again later.");
        }
        $this->stmt = $this->dbh->prepare($sql);
    }

    // Bind values, similar to http://www.php.net/manual/en/pdostatement.bindvalue.php
    public function bind($param, $value, $type = null) {
        if (is_null($type)) {
            switch (true) {
                case is_int($value):
                    $type = PDO::PARAM_INT;
                    break;
                case is_bool($value):
                    $type = PDO::PARAM_BOOL;
                    break;
                case is_null($value):
                    $type = PDO::PARAM_NULL;
                    break;
                default:
                    $type = PDO::PARAM_STR;
            }
        }
        $this->stmt->bindValue($param, $value, $type);
    }

    // Execute the prepared statement
    public function execute() {
        try {
            return $this->stmt->execute();
        } catch (PDOException $e) {
            $this->error = "Execute Error: " . $e->getMessage();
            error_log($this->error . " | SQL: " . $this->stmt->queryString);
            // Depending on app needs, you might throw the exception or return false
            return false;
        }
    }

    // Get result set as array of associative arrays
    public function resultSet() {
        $this->execute();
        return $this->stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get single record as associative array
    public function single() {
        $this->execute();
        return $this->stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Get row count
    public function rowCount() {
        return $this->stmt->rowCount();
    }

    // Returns the ID of the last inserted row or sequence value
    public function lastInsertId() {
        return $this->dbh->lastInsertId();
    }

    // Transactions methods
    public function beginTransaction() {
        return $this->dbh->beginTransaction();
    }

    public function endTransaction() { // Alias for commit
        return $this->dbh->commit();
    }

    public function commit() {
        return $this->dbh->commit();
    }

    public function cancelTransaction() { // Alias for rollback
        return $this->dbh->rollBack();
    }

    public function rollBack() {
        return $this->dbh->rollBack();
    }

    // Debugging method to get query string (use with caution)
    public function debugQuery() {
        // This is a simplified version. For complex queries, you might need a more robust solution
        // to replace placeholders with actual bound values for debugging.
        return $this->stmt->queryString;
    }

    public function getError() {
        return $this->error;
    }
}
?>
