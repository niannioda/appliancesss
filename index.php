<?php
require_once 'config.php';

$category = trim($_GET['category'] ?? '');
$search   = trim($_GET['search']   ?? '');
$flash    = $_GET['flash'] ?? '';
$flashMsg = urldecode($_GET['msg'] ?? '');

$where = []; $params = []; $types = '';
if ($category && $category !== 'All') {
    $where[] = 'categories = ?'; $params[] = $category; $types .= 's';
}
if ($search !== '') {
    $where[] = 'product_name LIKE ?'; $params[] = "%$search%"; $types .= 's';
}
$sql = 'SELECT * FROM products' . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY product_name ASC';
$stmt = $conn->prepare($sql);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$cats = ['All Items','Air Conditioner','Dishwasher','Microwave','Oven','Refrigerator','Washer'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Appliances Inventory — POS</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<!-- ── TOP NAVBAR ── -->
 <a href="create.php" class="btn btn-primary btn-sm">
  <i class="bi bi-plus-lg"></i> Add Product
</a>
<nav class="top-navbar">
  <div class="navbar-brand-area">
    <div class="brand-icon"><i class="bi bi-plug-fill"></i></div>
    <div class="brand-text">
      <span class="brand-name">ApplianceLogix</span>
      <span class="brand-sub">Inventory POS</span>
    </div>
  </div>

  <div class="navbar-search">
    <div class="search-wrap">
      <i class="bi bi-search search-icon"></i>
      <input type="text" id="searchInput" class="search-input" placeholder="Search product..." value="<?= htmlspecialchars($search) ?>">
    </div>
  </div>

  <div class="navbar-actions">
    <a href="products.php" class="btn btn-outline-light btn-sm">
      <i class="bi bi-grid-3x3-gap"></i> Manage Products
    </a>
    <div class="cart-badge-wrap">
      <i class="bi bi-cart3 cart-icon"></i>
      <span class="cart-count" id="cartCount">0</span>
    </div>
  </div>
</nav>

<!-- ── CATEGORY PILLS ── -->
<div class="cat-bar">
  <?php foreach ($cats as $c): 
    $slug = $c === 'All Items' ? 'All' : $c;
    $active = ($category === $slug || ($slug === 'All' && !$category)) ? 'active' : '';
  ?>
  <button class="cat-pill <?= $active ?>" data-cat="<?= $slug ?>"><?= $c ?></button>
  <?php endforeach; ?>
</div>

<?php if ($flash): ?>
<div class="alert alert-<?= $flash === 'success' ? 'success' : 'danger' ?> alert-dismissible mx-3 mt-2 mb-0 py-2" role="alert">
  <?= htmlspecialchars($flashMsg) ?>
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- ── MAIN LAYOUT ── -->
<div class="pos-layout">

  <!-- LEFT: Product Grid -->
  <div class="products-area">
    <div class="products-header">
      <span class="results-label" id="resultsLabel"><?= count($products) ?> product<?= count($products) !== 1 ? 's' : '' ?></span>
    </div>

    <div class="products-grid" id="productsGrid">
      <?php if (empty($products)): ?>
        <div class="empty-state">
          <i class="bi bi-box-seam"></i>
          <p>No products found.</p>
          <a href="products.php?action=add" class="btn btn-primary btn-sm">Add Product</a>
        </div>
      <?php endif; ?>

      <?php foreach ($products as $p):
        $imgSrc = $p['image'] && file_exists($p['image']) ? htmlspecialchars($p['image']) : '';
        $outOfStock = $p['stock'] <= 0;
      ?>
      <div class="product-card <?= $outOfStock ? 'out-of-stock' : '' ?>"
           data-id="<?= $p['id'] ?>"
           data-name="<?= htmlspecialchars($p['product_name'], ENT_QUOTES) ?>"
           data-price="<?= $p['price'] ?>"
           data-stock="<?= $p['stock'] ?>"
           data-cat="<?= htmlspecialchars($p['categories']) ?>">
        <div class="card-img-wrap">
          <?php if ($imgSrc): ?>
            <img src="<?= $imgSrc ?>" alt="<?= htmlspecialchars($p['product_name']) ?>">
          <?php else: ?>
            <div class="card-img-placeholder">
              <?php
                $icons = ['Air Conditioner'=>'bi-wind','Dishwasher'=>'bi-droplet-fill',
                          'Microwave'=>'bi-broadcast','Oven'=>'bi-fire',
                          'Refrigerator'=>'bi-thermometer-snow','Washer'=>'bi-arrow-repeat'];
                $icon = $icons[$p['categories']] ?? 'bi-plug-fill';
              ?>
              <i class="bi <?= $icon ?>"></i>
            </div>
          <?php endif; ?>
          <div class="stock-badge <?= $p['stock'] <= 5 ? ($outOfStock ? 'badge-out' : 'badge-low') : 'badge-ok' ?>">
            <?= $outOfStock ? 'Out of Stock' : ($p['stock'] <= 5 ? 'Low: '.$p['stock'] : 'Stock: '.$p['stock']) ?>
          </div>
        </div>
        <div class="card-body-custom">
          <div class="cat-tag"><?= htmlspecialchars($p['categories']) ?></div>
          <h6 class="product-name"><?= htmlspecialchars($p['product_name']) ?></h6>
          <div class="price-row">
            <a href="edit.php?id=<?= $p['id'] ?>" class="btn btn-warning btn-sm">
  <i class="bi bi-pencil"></i>
</a>

<a href="delete.php?id=<?= $p['id'] ?>" 
   class="btn btn-danger btn-sm"
   onclick="return confirm('Delete this item?');">
  <i class="bi bi-trash"></i>
</a>
            <span class="price">₱<?= number_format($p['price'], 2) ?></span>
            <button class="add-btn" <?= $outOfStock ? 'disabled' : '' ?> onclick="addToCart(this)">
              <i class="bi bi-plus-lg"></i>
            </button>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- RIGHT: Order Summary -->
  <aside class="order-panel">
    <div class="order-header">
      <i class="bi bi-receipt"></i> Order Summary
      <button class="btn btn-outline-danger btn-sm ms-auto" id="clearCartBtn" onclick="clearCart()">
        <i class="bi bi-trash3"></i>
      </button>
    </div>

    <div class="order-items" id="orderItems">
      <div class="cart-empty" id="cartEmpty">
        <i class="bi bi-cart-x"></i>
        <p>No items yet</p>
        <small>Click + on a product to add</small>
      </div>
    </div>

    <div class="order-footer">
      <div class="summary-row">
        <span>Subtotal</span>
        <span id="subtotal">₱0.00</span>
      </div>
      <div class="summary-row vat-row">
        <span>
          VAT (12%)
          <label class="vat-toggle ms-1">
            <input type="checkbox" id="vatToggle" onchange="recalc()"> <span class="vat-label">Include</span>
          </label>
        </span>
        <span id="vatAmount">₱0.00</span>
      </div>
      <div class="summary-row total-row">
        <span>Total</span>
        <span id="total">₱0.00</span>
      </div>
      <button class="btn btn-pay w-100" id="payBtn" onclick="proceedPayment()" disabled>
        <i class="bi bi-cash-coin"></i> Proceed to Payment
      </button>
    </div>
  </aside>

</div>

<!-- Payment Modal -->
<div class="modal fade" id="payModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content pay-modal">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title"><i class="bi bi-cash-coin text-success me-2"></i>Payment</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="pay-total-display" id="payTotalDisplay">₱0.00</div>
        <label class="form-label fw-semibold">Cash Tendered</label>
        <input type="number" id="cashInput" class="form-control form-control-lg mb-3"
               placeholder="Enter amount" oninput="calcChange()">
        <div class="change-display" id="changeDisplay" style="display:none">
          <span>Change</span>
          <strong id="changeAmt">₱0.00</strong>
        </div>
      </div>
      <div class="modal-footer border-0 pt-0">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-success px-4" id="confirmPayBtn" onclick="confirmPayment()" disabled>
          <i class="bi bi-check-circle"></i> Confirm Payment
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Success Modal -->
<div class="modal fade" id="successModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content text-center p-4">
      
      <div class="mb-3">
        <i class="bi bi-check-circle-fill text-success" style="font-size: 3rem;"></i>
      </div>

      <h5 class="mb-2">Purchased Successfully!</h5>
      <p class="text-muted">Thank you for your purchase.</p>

      <button class="btn btn-success mt-3" data-bs-dismiss="modal" onclick="clearCart()">
        Done
      </button>

    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/app.js"></script>
</body>
</html>