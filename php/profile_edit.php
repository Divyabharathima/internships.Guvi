<?php
require_once "../_config/db.php";
require_once "../_config/mongodb.php"; // Include MongoDB configuration file
require_once "../_config/redis_connection.php"; // Include Redis configuration file

// Check if the 'gmail' key exists in the $_POST array
if (isset($_POST['gmail'])) {
    $email = $_POST['gmail'];

    // Retrieve updated data from the request
    $updatedName = $_POST['name'];
    $updatedAge = $_POST['age'];
    $updatedDob = $_POST['dob'];
    $updatedContact = $_POST['contact'];
    $updatedAddress = $_POST['address'];

    // Update MySQL data
    $sql = "UPDATE user_details SET name=?, age=?, dob=?, contact=?, address=? WHERE email=?";
    $stmt = $con->prepare($sql);
    $stmt->bind_param("ssssss", $updatedName, $updatedAge, $updatedDob, $updatedContact, $updatedAddress, $email);
    $stmt->execute();

    // Check for MySQL errors
    if ($stmt->errno) {
        $response = array(
            'status' => 'error',
            'status_code' => 400,
            'message' => 'MySQL Error: ' . $stmt->error,
        );
        header('Content-Type: application/json');
        echo json_encode($response);
        exit();
    }

    // Update MongoDB data
    $filter = ['email' => $email];
    $update = [
        '$set' => [
            'name' => $updatedName,
            'age' => $updatedAge,
            'dob' => $updatedDob,
            'contact' => $updatedContact,
            'address' => $updatedAddress,
        ],
    ];

    $bulk = new MongoDB\Driver\BulkWrite;
    $bulk->update($filter, $update);

    $database = 'mongodatas'; // Update with your MongoDB database name
    $collection = 'user_details'; // Update with your MongoDB collection name

    // Execute the update operation
    $result = $mongoClient->executeBulkWrite("$database.$collection", $bulk);

    // Check for MongoDB errors
    if ($result->getWriteErrors()) {
        $response = array(
            'status' => 'error',
            'status_code' => 400,
            'message' => 'MongoDB Error: ' . json_encode($result->getWriteErrors()),
        );
        header('Content-Type: application/json');
        echo json_encode($response);
        exit();
    }

    // Update Redis data (optional)
    $userDataKey = "user:$email";
    $redisClient->hmset($userDataKey, [
        'name' => $updatedName,
        'age' => $updatedAge,
        'dob' => $updatedDob,
        'contact' => $updatedContact,
        'address' => $updatedAddress,
        'last_update' => date('Y-m-d H:i:s')
    ]);

    $response = array(
        'status' => 'success',
        'status_code' => 200,
        'message' => 'Profile updated successfully.',
    );
} else {
    $response = array(
        'status' => 'error',
        'status_code' => 400,
        'message' => 'Email not provided.',
    );
}

header('Content-Type: application/json');
echo json_encode($response);
?>
