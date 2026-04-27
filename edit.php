<?php
require_once 'config.php';

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$id = (int)$_GET['id'];

// Fetch product
$stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();

if (!$product) {
    header("Location: index.php?flash=danger&msg=Product not found");
    exit;
}

// Update
if (isset($_POST['update'])) {
    $name = $_POST['product_name'];
    $price = $_POST['price'];
    $stock = $_POST['stock'];
    $category = $_POST['categories'];

    $stmt = $conn->prepare("UPDATE products 
                           SET product_name=?, price=?, stock=?, categories=? 
                           WHERE id=?");
    $stmt->bind_param("sdisi", $name, $price, $stock, $category, $id);
    $stmt->execute();

    header("Location: index.php?flash=success&msg=Product updated!");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
  <title>Edit Product</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>
<body class="container mt-5">

<h3>Edit Product</h3>

<form method="POST">
  <input type="text" name="product_name" class="form-control mb-2"
         value="<?= htmlspecialchars($product['product_name']) ?>" required>

  <input type="number" step="0.01" name="price" class="form-control mb-2"
         value="<?= $product['price'] ?>" required>

  <input type="number" name="stock" class="form-control mb-2"
         value="<?= $product['stock'] ?>" required>

  <select name="categories" class="form-control mb-3">
    <?php
    $cats = ['Air Conditioner','Dishwasher','Microwave','Oven','Refrigerator','Washer'];
    foreach ($cats as $c) {
        $selected = ($product['categories'] == $c) ? 'selected' : '';
        echo "<option $selected>$c</option>";
    }
    ?>
  </select>

  <button type="submit" name="update" class="btn btn-success">Update</button>
  <a href="index.php" class="btn btn-secondary">Cancel</a>
</form>

</body>
</html>