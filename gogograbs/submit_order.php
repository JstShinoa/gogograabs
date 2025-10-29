<?php
include 'db_connect.php';

$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    echo "No order data received.";
    exit;
}

$customer_name = $data['customer_name'];
$payment_method = $data['payment_method'];
$items = $data['items'];

$total = 0;
foreach ($items as $item) {
    $total += $item['price'] * $item['quantity'];
}

// Save to orders
$order_stmt = $conn->prepare("INSERT INTO orders (customer_name, total_amount, status) VALUES (?, ?, 'Pending')");
$order_stmt->bind_param("sd", $customer_name, $total);
$order_stmt->execute();
$order_id = $order_stmt->insert_id;

// Save order items
foreach ($items as $item) {
    $subtotal = $item['price'] * $item['quantity'];
    // Try to match product_id from database by name
    $product_query = $conn->prepare("SELECT product_id FROM products WHERE product_name = ? LIMIT 1");
    $product_query->bind_param("s", $item['name']);
    $product_query->execute();
    $result = $product_query->get_result();
    $product_id = $result->num_rows > 0 ? $result->fetch_assoc()['product_id'] : null;
    $product_query->close();

    $order_item_stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, subtotal) VALUES (?, ?, ?, ?)");
    $order_item_stmt->bind_param("iiid", $order_id, $product_id, $item['quantity'], $subtotal);
    $order_item_stmt->execute();
}

// Save transaction
$trans_stmt = $conn->prepare("INSERT INTO transactions (order_id, payment_method, amount_paid) VALUES (?, ?, ?)");
$trans_stmt->bind_param("isd", $order_id, $payment_method, $total);
$trans_stmt->execute();

echo "✅ Order #$order_id saved! Total ₱" . number_format($total, 2);

$conn->close();
?>
