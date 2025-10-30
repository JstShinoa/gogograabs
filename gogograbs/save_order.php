<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');

$servername = "localhost";
$username = "root";  // default WAMP user
$password = "";      // usually blank on WAMP
$dbname = "gogograbs";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    echo json_encode(["status" => "error", "message" => "DB connection failed: " . $conn->connect_error]);
    exit;
}

// Receive JSON data from fetch()
$data = json_decode(file_get_contents("php://input"), true);

if (!$data || !isset($data['cart']) || count($data['cart']) === 0) {
    echo json_encode(["status" => "error", "message" => "No valid order data received"]);
    exit;
}

$cart = $data['cart'];
$total = 0;

foreach ($cart as $item) {
    $total += $item['price'] * $item['quantity'];
}

// Step 1: Insert into `orders` table
$order_sql = "INSERT INTO orders (total_amount, order_date) VALUES (?, NOW())";
$stmt = $conn->prepare($order_sql);
$stmt->bind_param("d", $total);
$stmt->execute();
$order_id = $stmt->insert_id;  // get the new order id

// Step 2: Insert each product into `transactions` table
$item_sql = "INSERT INTO transactions (order_id, product_name, quantity, price) VALUES (?, ?, ?, ?)";
$item_stmt = $conn->prepare($item_sql);

foreach ($cart as $item) {
    $name = $item['name'];
    $qty = $item['quantity'];
    $price = $item['price'];
    $item_stmt->bind_param("isid", $order_id, $name, $qty, $price);
    $item_stmt->execute();
}

// ✅ Output success as JSON
echo json_encode([
    "status" => "success",
    "message" => "Order saved successfully!",
    "order_id" => $order_id,
    "total" => $total
]);

$conn->close();
?>
