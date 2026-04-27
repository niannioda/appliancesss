<?php
require_once 'config.php';

$action  = $_GET['action'] ?? 'list';
$id      = (int)($_GET['id'] ?? 0);
$flash   = $_GET['flash'] ?? '';
$flashMsg= urldecode($_GET['msg'] ?? '');
$errors  = [];

// ── FETCH SINGLE for edit ──────────────────────────────────────
$editing = null;
if ($action === 'edit' && $id) {
    $s = $conn->prepare('SELECT * FROM products WHERE id=?');
    $s->bind_param('i', $id); $s->execute();
    $editing = $s->get_result()->fetch_assoc();
    if (!$editing) { header('Location: products.php'); exit; }
}

// ── SAVE (add / edit) ─────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pid   = (int)($_POST['id'] ?? 0);
    $name  = trim($_POST['product_name'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
    $stock = (int)($_POST['stock'] ?? 0);
    $cat   = trim($_POST['categories'] ?? '');

    if (!$name)         $errors[] = 'Product name is required.';
    if ($price <= 0)    $errors[] = 'Price must be greater than 0.';
    if ($stock < 0)     $errors[] = 'Stock cannot be negative.';
    if (!$cat)          $errors[] = 'Category is required.';

    // image upload
    $imagePath = $_POST['existing_image'] ?? null;
    if (!empty($_FILES['image']['name'])) {
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','gif','webp'];
        if (!in_array($ext, $allowed)) {
            $errors[] = 'Image must be jpg, jpeg, png, gif, or webp.';
        } elseif ($_FILES['image']['size'] > 5 * 1024 * 1024) {
            $errors[] = 'Image must be under 5 MB.';
        } else {
            $filename  = uniqid('img_', true) . '.' . $ext;
            $dest      = 'uploads/' . $filename;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $dest)) {
                // delete old image
                if ($imagePath && file_exists($imagePath)) @unlink($imagePath);
                $imagePath = $dest;
            } else {
                $errors[] = 'Failed to upload image.';
            }
        }
    }

    if (empty($errors)) {
        if ($pid) {
            $s = $conn->prepare('UPDATE products SET product_name=?,price=?,stock=?,categories=?,image=? WHERE id=?');
            $s->bind_param('sdissi', $name,$price,$stock,$cat,$imagePath,$pid);
            $ok  = $s->execute();
            $msg = $ok ? urlencode("✅ «$name» updated!") : urlencode('❌ '.$conn->error);
            $ft  = $ok ? 'success' : 'error';
        } else {
            $s = $conn->prepare('INSERT INTO products (product_name,price,stock,categories,image) VALUES (?,?,?,?,?)');
            $s->bind_param('sdiss', $name,$price,$stock,$cat,$imagePath);
            $ok  = $s->execute();
            $msg = $ok ? urlencode("✅ «$name» added!") : urlencode('❌ '.$conn->error);
            $ft  = $ok ? 'success' : 'error';
        }
        header("Location: products.php?flash=$ft&msg=$msg"); exit;
    }
    // repopulate form on error
    $editing = ['id'=>$pid,'product_name'=>$name,'price'=>$price,'stock'=>$stock,'categories'=>$cat,'image'=>$imagePath];
    $action  = $pid ? 'edit' : 'add';
}

// ── DELETE ────────────────────────────────────────────────────
if ($action === 'delete' && $id) {
    $s = $conn->prepare('SELECT product_name,image FROM products WHERE id=?');
    $s->bind_param('i',$id); $s->execute();
    $row = $s->get_result()->fetch_assoc();
    if ($row) {
        if ($row['image'] && file_exists($row['image'])) @unlink($row['image']);
        $conn->prepare('DELETE FROM products WHERE id=?')->bind_param('i',$id);
        $conn->prepare('DELETE FROM products WHERE id=?')->execute(); // safe re-prepare
        $d = $conn->prepare('DELETE FROM products WHERE id=?');
        $d->bind_param('i',$id); $d->execute();
        $msg = urlencode("🗑 «{$row['product_name']}» deleted.");
        header("Location: products.php?flash=success&msg=$msg"); exit;
    }
    header('Location: products.php'); exit;
}

// ── LIST ──────────────────────────────────────────────────────
$products = [];
if ($action === 'list') {
    $products = $conn->query('SELECT * FROM products ORDER BY created_at DESC')->fetch_all(MYSQLI_ASSOC);
}

$cats = ['Air Conditioner','Dishwasher','Microwave','Oven','Refrigerator','Washer'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Manage Products — ApplianceLogix</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="admin-body">

<nav class="top-navbar">
  <div class="navbar-brand-area">
    <div class="brand-icon"><i class="bi bi-plug-fill"></i></div>
    <div class="brand-text">
      <span class="brand-name">ApplianceLogix</span>
      <span class="brand-sub">Product Management</span>
    </div>
  </div>
  <div class="navbar-actions">
    <a href="index.php" class="btn btn-outline-light btn-sm"><i class="bi bi-house"></i> POS</a>
    <?php if ($action === 'list'): ?>
    <a href="products.php?action=add" class="btn btn-warning btn-sm fw-semibold">
      <i class="bi bi-plus-lg"></i> Add Product
    </a>
    <?php endif; ?>
  </div>
</nav>

<div class="admin-container">

<?php if ($flash): ?>
<div class="alert alert-<?= $flash === 'success' ? 'success' : 'danger' ?> alert-dismissible py-2" role="alert">
  <?= htmlspecialchars($flashMsg) ?>
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- ── LIST VIEW ── -->
<?php if ($action === 'list'): ?>
<div class="d-flex align-items-center mb-3 gap-3">
  <h4 class="mb-0 fw-bold">Products <span class="badge bg-primary"><?= count($products) ?></span></h4>
  <input type="text" id="adminSearch" class="form-control form-control-sm ms-auto" style="max-width:260px" placeholder="Search…">
</div>

<div class="table-responsive admin-table-wrap">
<table class="table table-hover align-middle admin-table" id="adminTable">
  <thead>
    <tr>
      <th>#</th>
      <th>Image</th>
      <th>Product Name</th>
      <th>Category</th>
      <th>Price</th>
      <th>Stock</th>
      <th class="text-center">Actions</th>
    </tr>
  </thead>
  <tbody>
  <?php if (empty($products)): ?>
    <tr><td colspan="7" class="text-center py-4 text-muted">No products yet. <a href="products.php?action=add">Add one →</a></td></tr>
  <?php endif; ?>
  <?php foreach ($products as $i => $p):
    $img = $p['image'] && file_exists($p['image']) ? $p['image'] : null;
    $stockClass = $p['stock'] === 0 ? 'text-danger fw-bold' : ($p['stock'] <= 5 ? 'text-warning fw-bold' : 'text-success fw-bold');
  ?>
  <tr>
    <td class="text-muted small"><?= $i+1 ?></td>
    <td>
      <?php if ($img): ?>
        <img src="<?= htmlspecialchars($img) ?>" class="admin-thumb" alt="">
      <?php else: ?>
        <div class="admin-thumb-placeholder"><i class="bi bi-plug-fill"></i></div>
      <?php endif; ?>
    </td>
    <td class="fw-semibold"><?= htmlspecialchars($p['product_name']) ?></td>
    <td><span class="badge bg-secondary-subtle text-secondary"><?= htmlspecialchars($p['categories']) ?></span></td>
    <td>₱<?= number_format($p['price'], 2) ?></td>
    <td class="<?= $stockClass ?>"><?= $p['stock'] ?></td>
    <td class="text-center">
      <a href="products.php?action=edit&id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-primary me-1">
        <i class="bi bi-pencil"></i> Edit
      </a>
      <a href="products.php?action=delete&id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-danger"
         onclick="return confirm('Delete «<?= addslashes($p['product_name']) ?>»?')">
        <i class="bi bi-trash3"></i> Delete
      </a>
    </td>
  </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div>

<!-- ── ADD / EDIT FORM ── -->
<?php else: ?>
<div class="form-card">
  <h5 class="form-card-title">
    <i class="bi bi-<?= $editing && ($editing['id'] ?? 0) ? 'pencil-square' : 'plus-circle' ?>"></i>
    <?= ($editing && ($editing['id'] ?? 0)) ? 'Edit Product' : 'Add New Product' ?>
  </h5>

  <?php if ($errors): ?>
  <div class="alert alert-danger py-2">
    <ul class="mb-0 ps-3">
      <?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
    </ul>
  </div>
  <?php endif; ?>

  <form method="POST" enctype="multipart/form-data" class="product-form">
    <input type="hidden" name="id" value="<?= $editing['id'] ?? 0 ?>">
    <input type="hidden" name="existing_image" value="<?= htmlspecialchars($editing['image'] ?? '') ?>">

    <div class="form-row-grid">
      <div class="form-group-custom">
        <label>Product Name <span class="req">*</span></label>
        <input type="text" name="product_name" class="form-control"
               value="<?= htmlspecialchars($editing['product_name'] ?? '') ?>" required>
      </div>
      <div class="form-group-custom">
        <label>Category <span class="req">*</span></label>
        <select name="categories" class="form-select" required>
          <option value="">— Select —</option>
          <?php foreach ($cats as $c): ?>
          <option value="<?= $c ?>" <?= ($editing['categories'] ?? '') === $c ? 'selected' : '' ?>><?= $c ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group-custom">
        <label>Price (₱) <span class="req">*</span></label>
        <input type="number" name="price" class="form-control" step="0.01" min="0.01"
               value="<?= $editing['price'] ?? '' ?>" required>
      </div>
      <div class="form-group-custom">
        <label>Stock <span class="req">*</span></label>
        <input type="number" name="stock" class="form-control" min="0"
               value="<?= $editing['stock'] ?? 0 ?>" required>
      </div>
    </div>

    <div class="form-group-custom mt-3">
      <label>Product Image</label>
      <input type="file" name="image" class="form-control" accept="image/*" id="imgInput" onchange="previewImg(this)">
      <small class="text-muted">Max 5 MB · JPG, PNG, GIF, WebP</small>
    </div>

    <?php if (!empty($editing['image']) && file_exists($editing['image'])): ?>
    <div class="mt-2">
      <span class="text-muted small">Current image:</span><br>
      <img src="<?= htmlspecialchars($editing['image']) ?>" class="img-preview-existing" alt="">
    </div>
    <?php endif; ?>

    <div class="mt-2" id="imgPreviewWrap" style="display:none">
      <span class="text-muted small">New image preview:</span><br>
      <img id="imgPreview" class="img-preview-existing" alt="">
    </div>

    <div class="form-actions-row">
      <a href="products.php" class="btn btn-outline-secondary">Cancel</a>
      <button type="submit" class="btn btn-primary px-4">
        <i class="bi bi-check-lg"></i>
        <?= ($editing && ($editing['id'] ?? 0)) ? 'Save Changes' : 'Add Product' ?>
      </button>
    </div>
  </form>
</div>
<?php endif; ?>

</div><!-- /admin-container -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Admin table search
const adminSearch = document.getElementById('adminSearch');
if (adminSearch) {
  adminSearch.addEventListener('input', function() {
    const q = this.value.toLowerCase();
    document.querySelectorAll('#adminTable tbody tr').forEach(tr => {
      tr.style.display = tr.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
  });
}
// Image preview
function previewImg(input) {
  const wrap = document.getElementById('imgPreviewWrap');
  const img  = document.getElementById('imgPreview');
  if (input.files && input.files[0]) {
    const reader = new FileReader();
    reader.onload = e => { img.src = e.target.result; wrap.style.display = 'block'; };
    reader.readAsDataURL(input.files[0]);
  }
}
</script>
</body>
</html>
