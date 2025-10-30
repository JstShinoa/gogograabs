<?php
header("Content-Type: application/json");
error_reporting(E_ALL);
ini_set("display_errors", 1);

// ✅ Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "gogograbs";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
  echo json_encode(["status" => "error", "message" => "DB connection failed: " . $conn->connect_error]);
  exit;
}

// ✅ Read raw input
$raw = file_get_contents("php://input");
$data = json_decode($raw, true);

if (!$data || !isset($data["cart"]) || !is_array($data["cart"])) {
  echo json_encode(["status" => "error", "message" => "No valid order data received", "raw" => $raw]);
  exit;
}

$cart = $data["cart"];

// ✅ Create order
$sql = "INSERT INTO orders (order_date, total_price) VALUES (NOW(), 0)";
if (!$conn->query($sql)) {
  echo json_encode(["status" => "error", "message" => "Failed to create order: " . $conn->error]);
  exit;
}

$order_id = $conn->insert_id;
$total = 0;

// ✅ Insert each item
foreach ($cart as $item) {
  $name = $conn->real_escape_string($item["name"]);
  $price = floatval($item["price"]);
  $quantity = intval($item["quantity"]);
  $subtotal = $price * $quantity;
  $total += $subtotal;

  $conn->query("INSERT INTO products (product_name, price) VALUES ('$name', $price) ON DUPLICATE KEY UPDATE price=$price");
  $conn->query("INSERT INTO transactions (order_id, product_name, quantity, subtotal) VALUES ($order_id, '$name', $quantity, $subtotal)");
}

// ✅ Update total price
$conn->query("UPDATE orders SET total_price=$total WHERE order_id=$order_id");

echo json_encode(["status" => "success", "message" => "Order saved", "order_id" => $order_id]);
$conn->close();
?>
