<?php
require_once 'config.php';

if(isset($_POST['submit'])){
    $name = $_POST['product_name'];
    $price = $_POST['price'];
    $stock = $_POST['stock'];
    $category = $_POST['categories'];

    $sql = "INSERT INTO products (product_name, price, stock, categories)
            VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sdis", $name, $price, $stock, $category);
    $stmt->execute();

    header("Location: index.php?flash=success&msg=Product added!");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
  <title>Add Product</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>
<body class="container mt-5">

<h3>Add Product</h3>

<form method="POST">
  <input type="text" name="product_name" class="form-control mb-2" placeholder="Product Name" required>

  <input type="number" step="0.01" name="price" class="form-control mb-2" placeholder="Price" required>

  <input type="number" name="stock" class="form-control mb-2" placeholder="Stock" required>

  <select name="categories" class="form-control mb-3">
    <option>Air Conditioner</option>
    <option>Dishwasher</option>
    <option>Microwave</option>
    <option>Oven</option>
    <option>Refrigerator</option>
    <option>Washer</option>
  </select>

  <button type="submit" name="submit" class="btn btn-success">Add Product</button>
  <a href="index.php" class="btn btn-secondary">Back</a>
</form>

</body>
</html>