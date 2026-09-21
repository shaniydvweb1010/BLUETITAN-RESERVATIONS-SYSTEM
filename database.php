<?php
class Database {
    
    // Specify your local database credentials
    private $host = "localhost";
    private $db_name = "boat_booking_system";
    private $username = "root"; // Default XAMPP/WAMP username
    private $password = "";     // Default XAMPP/WAMP password (leave blank)
    
    public $conn;

    // Get the database connection
    public function getConnection() {
        $this->conn = null;

        try {
            // Create a new PDO instance
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name, 
                $this->username, 
                $this->password
            );
            
            // Set PDO error mode to exception for strict security and better debugging
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Set default fetch mode to associative array (saves memory and is cleaner to code with)
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            
            // Set character encoding to utf8mb4 (Modern standard, supports special characters/emojis)
            $this->conn->exec("set names utf8mb4");

        } catch(PDOException $exception) {
            // If connection fails, halt the script safely and display the error
            // (In a real live server, you would write this to an error.log file instead of dying)
            die("<div style='text-align: center; margin-top: 50px; font-family: sans-serif;'>
                    <h2 style='color: #e74c3c;'>Database Connection Failed</h2>
                    <p>Please ensure your MySQL server (XAMPP/WAMP) is running and the credentials are correct.</p>
                    <p style='color: #7f8c8d; font-size: 0.9em;'>Error Details: " . $exception->getMessage() . "</p>
                 </div>");
        }

        return $this->conn;
    }
}
?>