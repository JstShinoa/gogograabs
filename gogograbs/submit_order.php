<?php
include 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $customer_name = $_POST['customer_name'];
    $product_id = $_POST['product_id'];
    $quantity = $_POST['quantity'];
    $payment_method = $_POST['payment_method'];

    // Get product price
    $product_query = $conn->prepare("SELECT price FROM products WHERE product_id = ?");
    $product_query->bind_param("i", $product_id);
    $product_query->execute();
    $result = $product_query->get_result();

    if ($result->num_rows > 0) {
        $product = $result->fetch_assoc();
        $price = $product['price'];
        $subtotal = $price * $quantity;

        // Insert into orders table
        $order_stmt = $conn->prepare("INSERT INTO orders (customer_name, total_amount, status) VALUES (?, ?, 'Pending')");
        $order_stmt->bind_param("sd", $customer_name, $subtotal);
        $order_stmt->execute();
        $order_id = $order_stmt->insert_id;

        // Insert into order_items
        $order_item_stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, subtotal) VALUES (?, ?, ?, ?)");
        $order_item_stmt->bind_param("iiid", $order_id, $product_id, $quantity, $subtotal);
        $order_item_stmt->execute();

        // Insert into transactions
        $trans_stmt = $conn->prepare("INSERT INTO transactions (order_id, payment_method, amount_paid) VALUES (?, ?, ?)");
        $trans_stmt->bind_param("isd", $order_id, $payment_method, $subtotal);
        $trans_stmt->execute();

        echo "<h2>✅ Order placed successfully!</h2>";
        echo "<p>Customer: $customer_name</p>";
        echo "<p>Order ID: $order_id</p>";
        echo "<p>Total: ₱$subtotal</p>";
        echo "<a href='odoo.html'>Back to POS</a>";

    } else {
        echo "<h3>❌ Product not found.</h3>";
    }

    $product_query->close();
    $conn->close();
}
?>
