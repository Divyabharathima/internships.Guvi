<?php
require_once "../_config/db.php";
require_once "../_config/mongodb.php";
require_once "../_config/redis_connection.php";

if (!isset($_POST['submit'])) {
    $name = $_POST['gname'];
    $email = $_POST['gmail'];
    $age = $_POST['gage'];
    $date_of_birth = $_POST['gdateofbirth'];
    $number = $_POST['gnumber'];
    $address = $_POST['gaddress'];
    $password = $_POST['gpassword'];

    // Check if email already exists in MySQL
    $sql = "SELECT * FROM user_details WHERE email=?";
    $stmt = $con->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user) {
        exit(json_encode([
            'status' => 'error',
            'status_code' => 400,
            'message' => 'Email id already exists.'
        ]));
    }

    // Insert data into MySQL
    $stmt = $con->prepare("INSERT INTO user_details (name, email, age, dob, contact, address, password) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssss", $name, $email, $age, $date_of_birth, $number, $address, $password);

    if ($stmt->execute()) {
        // Store user data in Redis using email as key
        $userDataKey = "user:$email";
        $redisClient->hmset($userDataKey, [
            'name' => $name,
            'email' => $email,
            'age' => $age,
            'dob' => $date_of_birth,
            'contact' => $number,
            'address' => $address,
            'password' => $password,
        ]);

        // Insert data into MongoDB
        $document = [
            'name' => $name,
            'email' => $email,
            'age' => $age,
            'dob' => $date_of_birth,
            'contact' => $number,
            'address' => $address,
            'password' => $password,
        ];

        $bulk = new MongoDB\Driver\BulkWrite();
        $bulk->insert($document);

        try {
            $mongoClient->executeBulkWrite("$mongoDatabase.$mongoCollection", $bulk);
            exit(json_encode([
                'status' => 'success',
                'status_code' => 200,
                'message' => 'REGISTER successfully.',
            ]));
        } catch (MongoDB\Driver\Exception\BulkWriteException $e) {
            exit(json_encode([
                'status' => 'error',
                'status_code' => 500,
                'message' => 'Failed to insert data into MongoDB.',
            ]));
        }
    } else {
        exit(json_encode([
            'status' => 'error',
            'status_code' => 500,
            'message' => 'Failed to insert data into the database.',
        ]));
    }
}
?>
