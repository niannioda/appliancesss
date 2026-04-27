<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cart = json_decode($_POST['cart'], true);

    if (!$cart) {
        echo json_encode(['status' => 'error']);
        exit;
    }

    foreach ($cart as $item) {
        $id = (int)$item['id'];
        $qty = (int)$item['qty'];

        // Deduct stock safely
        $stmt = $conn->prepare("UPDATE products 
                               SET stock = stock - ? 
                               WHERE id = ? AND stock >= ?");
        $stmt->bind_param("iii", $qty, $id, $qty);
        $stmt->execute();
    }

    echo json_encode(['status' => 'success']);
}
?>