<?php
include 'conn.php';
include 'sms.php';

class Events {
    protected $text;
    protected $sessionId;
    protected $pdo;
    protected $phoneNumber;   

    function __construct($text, $sessionId, $phoneNumber = null) {

        global $pdo;
        $this->text = $text;
        $this->sessionId = $sessionId;
        $this->pdo = $pdo;
        $this->phoneNumber = $phoneNumber;
    }
    public function unmenuRegister(){
        $response = "CON Welcome !!! \n";
        $response .= "1. Register \n";
        $response .= "2. Contact Us";
        echo $response;
    }
    public function isRegistered($phoneNumber) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE phoneNumber = ?");
        $stmt->execute([$phoneNumber]);
        return $stmt->rowCount() > 0;
    }

    public function menuRegister($textArray) {
        $level = count($textArray);

        if($level == 1) {
            echo "CON Enter your fullname \n";
        }
        else if($level == 2) {
            echo "CON Enter your Email \n";
        }
        else if($level == 3) {
            echo "CON -enter your Password \n";
        }
        else if($level == 4) {
            echo "CON Re-enter your Password \n";
        }
        else if($level == 5) {
            $name = $textArray[1];
            $email=$textArray[2];
            $pin = $textArray[3];
            $confirm_pin = $textArray[4];
            
            if($pin != $confirm_pin) {
                echo "END PINs do not match, Retry";
            } else {
                try {
                    $hashed_pass = password_hash($pin, PASSWORD_DEFAULT);
                    $stmt = $this->pdo->prepare("INSERT INTO users (phoneNumber, fullname,email, password) VALUES (?, ?, ?,?)");
                    $stmt->execute([$this->phoneNumber, $name,$email, $hashed_pass]);
                    
                    // Send welcome SMS
                    $sms = new SMS();
                $sms->sendWelcomeSMS($this->phoneNumber, $name);
                
                echo "END Dear $name, you have successfully registered \n SMS will come shortly";
                } catch(PDOException $e) {
                    echo "END Registration failed. Please try again.".$e->getmessage();
                }
            }
        }

        
    }

    function MenuEvents(){
        $response = "CON Welcome to Smart Event\n";
        $response .= "1. View Events\n";
        $response .= "2. My Events\n";
        $response .= "4. Help\n";
        echo $response;
    }
    public function viewEvents($textArray=[],$level=1) {
        if($level>1 && end($textArray)=="0" ){
            $this->MenuEvents();
            return;
        }
        try {
            $stmt = $this->pdo->prepare("SELECT id, EventName, description, Location, price FROM events");
            $stmt->execute();
    
            if ($stmt->rowCount() == 0) {
                echo "END No Events Available";
                return;
            }
    
            $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $response = "CON Available Events:\n\n";
            
            foreach($events as $event) {
                $response .= $event['id'] . ". " . $event['EventName'] . "\n";
                $response .= "Location: " . $event['Location'] . "\n";  // Removed number_format for location
                $response .= "Price: KSh " . number_format($event['price'], 2) . "\n";
                $shortDesc = strlen($event['description']) > 30 ? 
                             substr($event['description'], 0, 30) . "..." : 
                             $event['description'];
                $response .= $shortDesc . "\n";
                $response .= "------------------------\n";
            }
    
            $response .= "Reply with the Event number to Book\n";
            $response .= "0. Back to Main Menu";
            echo $response;
    
        } catch(PDOException $e) {
            error_log("Database error: " . $e->getMessage()); // Log the error
            echo "END An error occurred while fetching events. Please try again later.";
        }
    }

    protected function getUserId() {
        try {
            $stmt = $this->pdo->prepare("SELECT userid FROM users WHERE phoneNumber = ?");
            $stmt->execute([$this->phoneNumber]);
            
            if ($stmt->rowCount() > 0) {
                $user = $stmt->fetch();
                return $user['userid'];
            }
            return false;
        } catch (PDOException $e) {
            error_log("Error getting user ID: " . $e->getMessage());
            return false;
        }
    }
    
    public function bookEvent($textArray, $level) {
        if ($level == 2) {
            $eventId = $textArray[1];
            
            // Validate event ID
            if (!is_numeric($eventId)) {
                echo "END Invalid event selection. Please try again.";
                return;
            }
    
            try {
                // First, get the user ID and verify it
                $userId = $this->getUserId();
                if (!$userId) {
                    error_log("Failed to get user ID for phone: " . $this->phoneNumber);
                    echo "END User account not found. Please register first.";
                    return;
                }
    
                // Check if event exists
                $stmt = $this->pdo->prepare("SELECT id, EventName FROM events WHERE id = ?");
                $stmt->execute([$eventId]);
                
                if ($stmt->rowCount() == 0) {
                    echo "END Event not found. Please try again.";
                    return;
                }
                
                $event = $stmt->fetch();
                $eventName = $event['EventName'];
    
                // Check if user already booked this event
                $stmt = $this->pdo->prepare("SELECT id FROM bookedEvents WHERE userId = ? AND eventId = ?");
                $stmt->execute([$userId, $eventId]);
                
                if ($stmt->rowCount() > 0) {
                    echo "END You have already booked: " . $eventName;
                    return;
                }
    
                // Book the event
                $stmt = $this->pdo->prepare("INSERT INTO bookedEvents (userId, eventId, bookingDate) VALUES (?, ?, NOW())");
                $success = $stmt->execute([$userId, $eventId]);
                
                if ($success) {
                    // Send confirmation SMS (if implemented)
                    echo "END You have successfully booked: " . $eventName;
                } else {
                    error_log("Booking failed for user $userId, event $eventId");
                    echo "END Booking failed. Please try again.";
                }
    
            } catch (PDOException $e) {
                error_log("Booking Error: " . $e->getMessage() . "\nTrace: " . $e->getTraceAsString());
                echo "END An error occurred while booking. Please try again later.";
            }
        }
    }

    public function myEvents(){
        try {
            $smsContent = "Your Ikaze Shop Cart:\n";
            // Use phoneNumber to get the user_id
            $stmtUser = $this->pdo->prepare("SELECT userid, fullname FROM users WHERE phoneNumber = ?");
            $stmtUser->execute([$this->phoneNumber]);
            $user = $stmtUser->fetch(PDO::FETCH_ASSOC);
    
            if (!$user) {
                echo "END User not found.";
                return;
            }
    
            $userId = $user['userid'];
    
            // Fetch cart with product and user info
            $stmt = $this->pdo->prepare("
            SELECT 
                e.EventName AS EventName, 
                e.description, 
                e.Location, 
                e.price, 
                u.fullname AS fullname 
            FROM bookedevents b
            INNER JOIN events e ON b.eventId = e.id
            INNER JOIN users u ON b.userId = u.userid
            WHERE b.userId = ?
        ");
        
            $stmt->execute([$userId]);
    
            if ($stmt->rowCount() == 0) {
                echo "END Your cart is empty.";
                return;
            }
    
            $cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
            // Build response
            $response = "CON Dear " . $user['fullname'] . ", here is your cart:\n\n";
            foreach( $cartItems  as $event) {
                // $response .= $event['id'] . ". " . $event['EventName'] . "\n";
                $response .= "Location: " . $event['Location'] . "\n";  // Removed number_format for location
                $response .= "Price: KSh " . number_format($event['price'], 2) . "\n";
                $shortDesc = strlen($event['description']) > 30 ? 
                             substr($event['description'], 0, 30) . "..." : 
                             $event['description'];
                $response .= $shortDesc . "\n";
                $response .= "------------------------\n";
            }
            $response.="Check your sms to review you Event\n";
            $response .= "0. Back to Main Menu";
            echo $response;
      
            $sms = new SMS();
            $sms->sendCartSummarySMS($this->phoneNumber, $smsContent);
        } catch (PDOException $e) {
            echo "END Error loading cart: " . $e->getMessage();
        }
    
    }
    
    
}