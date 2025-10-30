<?php
include 'db_connect.php';

// Get JSON data from fetch()
$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    echo json_encode(["success" => false, "message" => "No data received"]);
    exit;
}

$customer_name = $data['customer_name'] ?? 'Guest';
$payment_method = $data['payment_method'] ?? 'Cash';
$cart = $data['cart'] ?? [];

$total_amount = 0;
foreach ($cart as $item) {
    $total_amount += $item['price'] * $item['quantity'];
}

// 1️⃣ Insert into orders
$order_stmt = $conn->prepare("INSERT INTO orders (customer_name, total_amount, status) VALUES (?, ?, 'Completed')");
$order_stmt->bind_param("sd", $customer_name, $total_amount);
$order_stmt->execute();
$order_id = $order_stmt->insert_id;

// 2️⃣ Insert order items
foreach ($cart as $item) {
    $product_name = $item['name'];
    $quantity = $item['quantity'];
    $price = $item['price'];
    $subtotal = $price * $quantity;

    // If product doesn’t exist, add it to `products`
    $check = $conn->prepare("SELECT product_id FROM products WHERE name=?");
    $check->bind_param("s", $product_name);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $product_id = $row['product_id'];
    } else {
        $insert_product = $conn->prepare("INSERT INTO products (name, price) VALUES (?, ?)");
        $insert_product->bind_param("sd", $product_name, $price);
        $insert_product->execute();
        $product_id = $insert_product->insert_id;
    }

    $item_stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, subtotal) VALUES (?, ?, ?, ?)");
    $item_stmt->bind_param("iiid", $order_id, $product_id, $quantity, $subtotal);
    $item_stmt->execute();
}

// 3️⃣ Insert transaction
$trans_stmt = $conn->prepare("INSERT INTO transactions (order_id, payment_method, amount_paid) VALUES (?, ?, ?)");
$trans_stmt->bind_param("isd", $order_id, $payment_method, $total_amount);
$trans_stmt->execute();

echo json_encode(["success" => true, "message" => "Order saved successfully!", "order_id" => $order_id]);

$conn->close();
?>
