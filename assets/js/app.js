let cart = {}; // { id: { name, price, qty, stock } }

/* ── Add to Cart ─────────────────────────────────────────────── */
function addToCart(btn) {
  const card = btn.closest('.product-card');

  const id    = card.dataset.id;
  const name  = card.dataset.name;
  const price = parseFloat(card.dataset.price);
  const stock = parseInt(card.dataset.stock);

  if (!cart[id]) {
    cart[id] = {
      id: id,
      name: name,
      price: price,
      qty: 1,
      stock: stock
    };
  } else {
    if (cart[id].qty < stock) {
      cart[id].qty++;
    } else {
      showToast("Not enough stock!", "warning");
      return;
    }
  }

  renderCart();
}

/* ── Change Quantity ─────────────────────────────────────────── */
function changeQty(id, delta) {
  if (!cart[id]) return;

  cart[id].qty += delta;

  if (cart[id].qty <= 0) delete cart[id];

  renderCart();
}

/* ── Clear Cart ──────────────────────────────────────────────── */
function clearCart() {
  cart = {};
  renderCart();
}

/* ── Render Cart ─────────────────────────────────────────────── */
function renderCart() {
  const container = document.getElementById('orderItems');
  const empty     = document.getElementById('cartEmpty');
  const ids       = Object.keys(cart);

  if (ids.length === 0) {
    container.innerHTML = '';
    container.appendChild(empty);
    empty.style.display = 'block';

    document.getElementById('cartCount').textContent = '0';
    document.getElementById('payBtn').disabled = true;

    recalc();
    return;
  }

  empty.style.display = 'none';
  let html = '';

  ids.forEach(id => {
    const item = cart[id];

    html += `
      <div class="cart-item">
        <div style="flex:1; min-width:0">
          <div class="ci-name">${escHtml(item.name)}</div>
          <div class="ci-price">
            ₱${fmt(item.price)} × ${item.qty} = ₱${fmt(item.price * item.qty)}
          </div>
        </div>

        <div class="qty-controls">
          <button class="qty-btn" onclick="changeQty('${id}', -1)">−</button>
          <span class="qty-val">${item.qty}</span>
          <button class="qty-btn" onclick="changeQty('${id}', 1)" ${item.qty >= item.stock ? 'disabled' : ''}>+</button>
        </div>
      </div>
    `;
  });

  container.innerHTML = html;

  const totalItems = ids.reduce((s, id) => s + cart[id].qty, 0);
  document.getElementById('cartCount').textContent = totalItems;
  document.getElementById('payBtn').disabled = false;

  recalc();
}

/* ── Recalculate ─────────────────────────────────────────────── */
function recalc() {
  const subtotal = Object.values(cart)
    .reduce((s, i) => s + i.price * i.qty, 0);

  const vatOn  = document.getElementById('vatToggle')?.checked;
  const vatAmt = vatOn ? subtotal * 0.12 : 0;
  const total  = subtotal + vatAmt;

  document.getElementById('subtotal').textContent = '₱' + fmt(subtotal);
  document.getElementById('vatAmount').textContent = '₱' + fmt(vatAmt);
  document.getElementById('total').textContent     = '₱' + fmt(total);
}

/* ── Payment Flow ────────────────────────────────────────────── */
function proceedPayment() {
  const totalText = document.getElementById('total').textContent;

  document.getElementById('payTotalDisplay').textContent = totalText;
  document.getElementById('cashInput').value = '';
  document.getElementById('changeDisplay').style.display = 'none';
  document.getElementById('confirmPayBtn').disabled = true;

  new bootstrap.Modal(document.getElementById('payModal')).show();
}

function calcChange() {
  const cash = parseFloat(document.getElementById('cashInput').value) || 0;
  const total = parseFloat(document.getElementById('total').textContent.replace(/[₱,]/g, '')) || 0;

  const change = cash - total;

  const disp = document.getElementById('changeDisplay');
  const btn  = document.getElementById('confirmPayBtn');

  if (cash >= total && total > 0) {
    disp.style.display = 'flex';
    document.getElementById('changeAmt').textContent = '₱' + fmt(change);
    btn.disabled = false;
  } else {
    disp.style.display = 'none';
    btn.disabled = true;
  }
}

/* ── FINAL FIXED CONFIRM PAYMENT (IMPORTANT) ─────────────────── */
function confirmPayment() {
  if (Object.keys(cart).length === 0) return;

  fetch("process_order.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/x-www-form-urlencoded"
    },
    body: "cart=" + encodeURIComponent(JSON.stringify(cart))
  })
  .then(res => res.json())
  .then(data => {
    if (data.status === "success") {

      // Close payment modal
      const payModal = bootstrap.Modal.getInstance(document.getElementById('payModal'));
      if (payModal) payModal.hide();

      // Show success modal
      const modal = new bootstrap.Modal(document.getElementById('successModal'));
      modal.show();

      // Reset cart
      clearCart();

      // Refresh stock display
      setTimeout(() => location.reload(), 1000);

    } else {
      showToast("Error processing order", "warning");
    }
  });
}

/* ── Helpers ─────────────────────────────────────────────────── */
function fmt(n) {
  return Number(n).toLocaleString('en-PH', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  });
}

function escHtml(str) {
  return String(str)
    .replace(/&/g,'&amp;')
    .replace(/</g,'&lt;')
    .replace(/>/g,'&gt;')
    .replace(/"/g,'&quot;');
}

function showToast(msg, type='info') {
  const t = document.createElement('div');
  t.textContent = msg;
  t.style.cssText = `
    position:fixed;bottom:20px;left:50%;
    transform:translateX(-50%);
    background:${type==='warning'?'#d97706':'#2563eb'};
    color:#fff;padding:10px 20px;
    border-radius:8px;font-size:13px;
    z-index:9999;
  `;
  document.body.appendChild(t);
  setTimeout(() => t.remove(), 2000);
}