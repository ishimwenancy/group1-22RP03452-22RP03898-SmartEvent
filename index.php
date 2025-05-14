    <?php
    include 'EventsManagement.php';
    $sessionId   = $_POST['sessionId'] ?? '';
    $phoneNumber = $_POST['phoneNumber'] ?? '';
    $serviceCode = $_POST['serviceCode'] ?? '';
    $text        = $_POST['text'] ?? '';



    $events=new Events($text, $sessionId, $phoneNumber);
    $isRegistered=$events->isRegistered($phoneNumber);
    if($text=="" && !$isRegistered){
        $events->unmenuRegister();
    }

    elseif(!$isRegistered){
        $textArray=explode("*",$text);
        $level=count($textArray);
        switch($textArray[0]){
            case 1:
                $events->menuRegister($textArray);
                break;
            default:
                echo "END Invalid input try Again";    }
    }
    elseif($text=="" && $isRegistered){
        $events->MenuEvents();
    }
    // elseif($isRegistered) {
    //     $textArray = explode("*", $text);
    //     $level=count($textArray);
    //     // If user enters 98, go to main menu
    //     if (in_array('98', $textArray)) {
    //         $events->MenuEvents();
    //          }
    //     elseif(in_array('0',$textArray)){
    //       $events->MenuEvents();
    
    //     }
    //     else{
    //     switch($textArray[0]) {
    //         case 1:
    //             // Call viewEvents without parameters since it shows all events
    //             $events->viewEvents();
    //             break;
    //         default:
    //             echo "END Invalid input. Please select 1 to view events.";
    //     }
    // }
    // }

    elseif($isRegistered) {
        $textArray = explode("*", $text);
        $level = count($textArray);
        
        // Handle back navigation (0 or 98)
        if (in_array('98', $textArray) || (end($textArray) == "0" && $level > 1)) {
            $events->MenuEvents();
            return;
        }
        
        switch($textArray[0]) {
            case 1: // Event Management
                if ($level == 1) {
                    // Show all events
                    $events->viewEvents($textArray, $level);
                } elseif ($level == 2) {
                    // Handle event selection/booking
                    $events->bookEvent($textArray, $level);
                }
                break;
                
            case 2: // My Events
                $events->myEvents();
                break;
                
            default:
                echo "CON Invalid option\n";
                echo "0. Back to Main Menu";
        }
    }   


    ?>