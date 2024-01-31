<?php
require_once "../_config/db.php";
require_once "../_config/mongodb.php"; // Include MongoDB configuration file
require_once "../_config/redis_connection.php"; // Include Redis configuration file

// Check if the 'gmail' key exists in the $_POST array
if (isset($_POST['gmail'])) {
    $email = $_POST['gmail'];

    // Try fetching user details from MySQL
    $sql = "SELECT name, age, dob, contact, address FROM user_details WHERE email=?";
    $stmt = $con->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user) {
        // Store user data in Redis for future use
        $userDataKey = "user:$email";
        $redisClient->hmset($userDataKey, [
            'name' => $user['name'],
            'age' => $user['age'],
            'dob' => $user['dob'],
            'contact' => $user['contact'],
            'address' => $user['address'],
        ]);

        $response = array(
            'status' => 'success',
            'status_code' => 200,
            'name' => $user['name'],
            'age' => $user['age'],
            'dob' => $user['dob'],
            'contact' => $user['contact'],
            'address' => $user['address']
        );
    } else {
        // User details not found in MySQL, try fetching from MongoDB
        $collection = $mongoDB->selectCollection('users');
        $userDocument = $collection->findOne(['email' => $email]);

        if ($userDocument) {
            // Store user data in Redis for future use
            $userDataKey = "user:$email";
            $redisClient->hmset($userDataKey, [
                'name' => $userDocument['name'],
                'age' => $userDocument['age'],
                'dob' => $userDocument['dob'],
                'contact' => $userDocument['contact'],
                'address' => $userDocument['address'],
            ]);

            $response = array(
                'status' => 'success',
                'status_code' => 200,
                'name' => $userDocument['name'],
                'age' => $userDocument['age'],
                'dob' => $userDocument['dob'],
                'contact' => $userDocument['contact'],
                'address' => $userDocument['address']
            );
        } else {
            $response = array(
                'status' => 'error',
                'status_code' => 400,
                'message' => 'User details not found in MySQL or MongoDB.'
            );
        }
    }
} else {
    $response = array(
        'status' => 'error',
        'status_code' => 400,
        'message' => 'Email not provided.'
    );
}

header('Content-Type: application/json');
echo json_encode($response);
?>
