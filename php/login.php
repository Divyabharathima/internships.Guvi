<?php
require_once "../_config/db.php";
require_once "../_config/redis_connection.php"; // Include Redis configuration file
require_once "../_config/mongodb.php"; // Include MongoDB configuration file

// Check if 'gmail' and 'gpassword' are set in the $_POST array
if (isset($_POST['gmail'], $_POST['gpassword']) && !empty($_POST['gmail']) && !empty($_POST['gpassword'])) {
    $email = $_POST['gmail'];
    $password = $_POST['gpassword'];

   
    // Check user in MySQL
    $sql = "SELECT * FROM user_details WHERE email=? AND password = ?";
    $stmt = $con->prepare($sql);
    $stmt->bind_param("ss", $email, $password);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    // Check user in MongoDB if not found in MySQL
    if (!$user) {
        $filter = ['email' => $email, 'password' => $password];
        $options = [];

        $query = new MongoDB\Driver\Query($filter, $options);
        $cursor = $mongoClient->executeQuery("$mongoDatabase.$mongoCollection", $query);
        $user = current($cursor->toArray());
    }

    if ($user) {
        // Store user data in Redis
        $userDataKey = "user:$email";
        $redisClient->hmset($userDataKey, [
            'name' => $user['name'],
            'email' => $user['email'],
            'age' => $user['age'],
            'dob' => $user['dob'],
            'contact' => $user['contact'],
            'address' => $user['address'],
            'password' => $user['password'],
        ]);



        exit(json_encode([
            'status' => 'success',
            'status_code' => 200,
            'message' => 'Logged in successfully.',
        ]));
    } else {
        exit(json_encode([
            'status' => 'error',
            'status_code' => 400,
            'message' => 'Invalid email or password.',
        ]));
    }
} else {
    // Handle the case when 'gmail' or 'gpassword' is not set or empty in the $_POST array
    exit(json_encode([
        'status' => 'error',
        'status_code' => 400,
        'message' => 'Email or password not provided.',
    ]));
}
?>
