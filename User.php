<?php
class User {
    private $conn;
    private $table_name = "users";

    // Object properties
    public $id;
    public $username;
    public $email;
    public $password;
    public $full_name;
    public $phone;
    public $role; // Added for Admin functionality
    public $created_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    // ----------------------------------------------------
    // REGISTRATION & LOGIN METHODS
    // ----------------------------------------------------

    // Register a new user
    public function register() {
        // By default, new registrations from the front-end are 'user' role
        $query = "INSERT INTO " . $this->table_name . " 
                SET username=:username, email=:email, password=:password, 
                full_name=:full_name, phone=:phone, role='user'";
        
        $stmt = $this->conn->prepare($query);
        
        // Sanitize inputs
        $this->username = htmlspecialchars(strip_tags($this->username));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->full_name = htmlspecialchars(strip_tags($this->full_name));
        $this->phone = htmlspecialchars(strip_tags($this->phone));
        
        // Bind values
        $stmt->bindParam(":username", $this->username);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":full_name", $this->full_name);
        $stmt->bindParam(":phone", $this->phone);
        
        // Hash the password securely
        $password_hash = password_hash($this->password, PASSWORD_BCRYPT);
        $stmt->bindParam(":password", $password_hash);
        
        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Used during Login to fetch user data (including Admin role)
    public function emailExists() {
        $query = "SELECT id, username, password, full_name, phone, role 
                FROM " . $this->table_name . " 
                WHERE email = ? 
                LIMIT 0,1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->email);
        $stmt->execute();
        
        if($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Assign values to object properties
            $this->id = $row['id'];
            $this->username = $row['username'];
            $this->password = $row['password'];
            $this->full_name = $row['full_name'];
            $this->phone = $row['phone'];
            $this->role = $row['role']; // Crucial for Admin check
            return true;
        }
        
        // Reset properties if no user found
        $this->id = null;
        $this->username = null;
        $this->password = null;
        $this->full_name = null;
        $this->role = null;
        return false;
    }

    // Used during Registration to prevent duplicate usernames
    public function usernameExists() {
        $query = "SELECT id FROM " . $this->table_name . " WHERE username = ? LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->username);
        $stmt->execute();
        
        return $stmt->rowCount() > 0;
    }


    // ----------------------------------------------------
    // PROFILE MANAGEMENT METHODS (Used in profile.php)
    // ----------------------------------------------------

    // Fetch all details for a specific user ID
    public function getUserById($target_id) {
        $query = "SELECT id, username, email, full_name, phone, role, created_at 
                  FROM " . $this->table_name . " 
                  WHERE id = ? LIMIT 0,1";
                  
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $target_id);
        $stmt->execute();
        
        if($stmt->rowCount() > 0) {
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
        return false;
    }

    // Update the user's name, email, and phone
    public function updateProfile() {
        $query = "UPDATE " . $this->table_name . " 
                  SET full_name = :full_name, email = :email, phone = :phone 
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        
        // Sanitize
        $this->full_name = htmlspecialchars(strip_tags($this->full_name));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->phone = htmlspecialchars(strip_tags($this->phone));
        $this->id = htmlspecialchars(strip_tags($this->id));
        
        // Bind parameters
        $stmt->bindParam(':full_name', $this->full_name);
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':phone', $this->phone);
        $stmt->bindParam(':id', $this->id);
        
        return $stmt->execute();
    }

    // Used before allowing a password change
    public function verifyPassword($target_id, $current_password) {
        $query = "SELECT password FROM " . $this->table_name . " WHERE id = ? LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $target_id);
        $stmt->execute();
        
        if($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return password_verify($current_password, $row['password']);
        }
        return false;
    }

    // Commit the new hashed password to the database
    public function updatePassword($target_id, $new_password) {
        $query = "UPDATE " . $this->table_name . " SET password = :password WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        
        // Hash new password
        $password_hash = password_hash($new_password, PASSWORD_BCRYPT);
        
        $stmt->bindParam(':password', $password_hash);
        $stmt->bindParam(':id', $target_id);
        
        return $stmt->execute();
    }
}
?>