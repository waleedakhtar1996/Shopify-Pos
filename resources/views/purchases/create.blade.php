@extends('layouts.app')

@section('title', 'New Purchase')

@section('content')
<style>
    .card { background:white; border-radius:8px; padding:20px; margin-bottom:20px; box-shadow:0 1px 3px rgba(0,0,0,0.08); }
    .field { margin-bottom:15px; }
    .field label { display:block; font-weight:600; font-size:13px; margin-bottom:5px; }
    .field input, .field select, .field textarea { width:100%; padding:8px; border:1px solid #ccc; border-radius:4px; font-size:14px; box-sizing:border-box; }
    .row { display:flex; gap:15px; flex-wrap:wrap; }
    .row > div { flex:1; min-width:180px; }
    .submit-btn { padding:12px 30px; background:#008060; color:white; border:none; border-radius:4px; cursor:pointer; font-size:15px; }
    .back-link { color:#1a56db; text-decoration:none; font-size:14px; }

    .product-search-wrap { position:relative; margin-bottom:5px; }
    .product-search-wrap input {
        width:100%; padding:14px 16px 14px 44px; border:1.5px solid #dcdcdc; border-radius:8px;
        font-size:15px; box-sizing:border-box; background:#fafafa url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23999' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Ccircle cx='11' cy='11' r='8'%3E%3C/circle%3E%3Cline x1='21' y1='21' x2='16.65' y2='16.65'%3E%3C/line%3E%3C/svg%3E") no-repeat 14px center;
        background-size:18px; transition:border-color .15s, background-color .15s;
    }
    .product-search-wrap input:focus { outline:none; border-color:#008060; background-color:#fff; box-shadow:0 0 0 3px rgba(0,128,96,0.1); }
    .search-results { position:absolute; top:calc(100% + 6px); left:0; right:0; background:white; border:1px solid #e2e2e2; border-radius:10px; max-height:340px; overflow-y:auto; z-index:50; box-shadow:0 10px 30px rgba(0,0,0,0.12); display:none; padding:6px; }
    .search-results.open { display:block; }
    .search-result-item { display:flex; align-items:center; gap:12px; padding:10px; cursor:pointer; border-radius:8px; }
    .search-result-item:hover { background:#f2f8f6; }
    .search-result-item img { width:44px; height:44px; object-fit:cover; border-radius:6px; background:#f0f0f0; border:1px solid #eee; flex-shrink:0; }
    .search-result-item .no-img { width:44px; height:44px; border-radius:6px; background:#f0f0f0; flex-shrink:0; display:flex; align-items:center; justify-content:center; color:#bbb; font-size:10px; }
    .search-result-item .info { flex:1; min-width:0; }
    .search-result-item .info .title { font-size:14px; font-weight:600; color:#1a1a1a; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .search-result-item .info .meta { font-size:12px; color:#888; margin-top:2px; }
    .search-result-item .add-badge { background:#008060; color:white; font-size:11px; font-weight:600; padding:5px 12px; border-radius:20px; flex-shrink:0; }

    table.items-table { width:100%; border-collapse:collapse; margin-top:16px; border:1px solid #eee; border-radius:8px; overflow:hidden; }
    table.items-table th { text-align:left; padding:12px; background:#f7f7f8; font-size:12px; color:#666; font-weight:600; text-transform:uppercase; letter-spacing:.3px; }
    table.items-table td { padding:12px; border-bottom:1px solid #f0f0f0; font-size:14px; vertical-align:middle; }
    table.items-table tr:last-child td { border-bottom:none; }
    table.items-table img { width:42px; height:42px; object-fit:cover; border-radius:6px; background:#f0f0f0; border:1px solid #eee; }
    table.items-table input[type=number] { width:80px; padding:7px 8px; border:1px solid #ccc; border-radius:6px; font-size:13px; }
    .remove-item { background:#fdecea; color:#c0392b; border:none; padding:6px 12px; border-radius:6px; cursor:pointer; font-size:12px; font-weight:600; }
    .remove-item:hover { background:#f8d7da; }
    .totals-box { max-width:340px; margin-left:auto; margin-top:15px; }
    .totals-box .trow { display:flex; justify-content:space-between; padding:6px 0; font-size:14px; }
    .totals-box .trow.grand { font-weight:700; font-size:16px; border-top:1px solid #ddd; padding-top:10px; margin-top:6px; }
</style>

<a href="{{ route('purchases.index') }}" class="back-link">&larr; Back to Purchases</a>
<h1>New Purchase Entry</h1>

@if ($errors->any())
    <div class="error">
        <ul style="margin:0; padding-left:18px;">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form method="POST" action="{{ route('purchases.store') }}" id="purchaseForm">
    @csrf

    <div class="card">
        <h3 style="margin-top:0;">Purchase Info</h3>
        <div class="row">
            <div class="field">
                <label>PO Number</label>
                <input type="text" name="purchase_number" value="{{ old('purchase_number', $nextNumber) }}">
            </div>
            <div class="field">
                <label>Purchase Date *</label>
                <input type="date" name="purchase_date" value="{{ old('purchase_date', date('Y-m-d')) }}" required>
            </div>
        </div>
        <div class="row">
            <div class="field">
                <label>Supplier Name</label>
                <input type="text" name="supplier_name" value="{{ old('supplier_name') }}">
            </div>
            <div class="field">
                <label>Supplier Contact</label>
                <input type="text" name="supplier_contact" value="{{ old('supplier_contact') }}">
            </div>
        </div>
    </div>

    <div class="card">
        <h3 style="margin-top:0;">Add Products</h3>
        <div class="product-search-wrap">
            <input type="text" id="productSearchInput" placeholder="Search products by name or SKU" autocomplete="off">
            <div class="search-results" id="searchResults"></div>
        </div>

        <table class="items-table" id="itemsTable">
            <thead>
                <tr>
                    <th></th>
                    <th>Product</th>
                    <th>SKU</th>
                    <th>Qty</th>
                    <th>Cost Price ({{ $currencySymbol }})</th>
                    <th>Total</th>
                    <th></th>
                </tr>
            </thead>
            <tbody id="itemsBody">
                <tr id="emptyRow"><td colspan="7" style="text-align:center; padding:20px; color:#888;">No products added yet. Search above to add.</td></tr>
            </tbody>
        </table>
    </div>

    <div class="card">
        <h3 style="margin-top:0;">Costs & Payment</h3>
        <div class="row">
            <div class="field">
                <label>Discount ({{ $currencySymbol }})</label>
                <input type="number" step="0.01" name="discount" id="discountInput" value="{{ old('discount', 0) }}">
            </div>
            <div class="field">
                <label>Tax ({{ $currencySymbol }})</label>
                <input type="number" step="0.01" name="tax" id="taxInput" value="{{ old('tax', 0) }}">
            </div>
            <div class="field">
                <label>Shipping Cost ({{ $currencySymbol }})</label>
                <input type="number" step="0.01" name="shipping_cost" id="shippingInput" value="{{ old('shipping_cost', 0) }}">
            </div>
        </div>
        <div class="row">
            <div class="field">
                <label>Payment Status *</label>
                <select name="payment_status" id="paymentStatusSelect" required>
                    <option value="unpaid">Unpaid</option>
                    <option value="partial">Partial</option>
                    <option value="paid">Paid</option>
                </select>
            </div>
            <div class="field">
                <label>Payment Type</label>
                <select name="payment_type">
                    <option value="">Select payment type</option>
                    @foreach ($paymentTypes as $type)
                        <option value="{{ $type }}" {{ old('payment_type') == $type ? 'selected' : '' }}>{{ $type }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label>Amount Paid ({{ $currencySymbol }})</label>
                <input type="number" step="0.01" name="amount_paid" id="amountPaidInput" value="{{ old('amount_paid', 0) }}">
            </div>
            <div class="field">
                <label>Purchase Status *</label>
                <select name="status" required>
                    <option value="pending">Pending</option>
                    <option value="received">Received</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>
        </div>
        <div class="field">
            <label>Notes</label>
            <textarea name="notes" rows="3">{{ old('notes') }}</textarea>
        </div>

        <div class="totals-box">
            <div class="trow"><span>Subtotal</span><span id="subtotalDisplay">{{ $currencySymbol }}0.00</span></div>
            <div class="trow"><span>Discount</span><span id="discountDisplay">-{{ $currencySymbol }}0.00</span></div>
            <div class="trow"><span>Tax</span><span id="taxDisplay">+{{ $currencySymbol }}0.00</span></div>
            <div class="trow"><span>Shipping</span><span id="shippingDisplay">+{{ $currencySymbol }}0.00</span></div>
            <div class="trow grand"><span>Grand Total</span><span id="grandTotalDisplay">{{ $currencySymbol }}0.00</span></div>
        </div>
    </div>

    <button type="submit" class="submit-btn">Save Purchase</button>
</form>

<script>
const currencySymbol = @json($currencySymbol);
let items = [];
let searchTimeout = null;

const searchInput = document.getElementById('productSearchInput');
const searchResults = document.getElementById('searchResults');

searchInput.addEventListener('input', function() {
    clearTimeout(searchTimeout);
    const q = this.value.trim();
    if (q.length < 2) {
        searchResults.classList.remove('open');
        return;
    }
    searchTimeout = setTimeout(() => {
        fetch("{{ route('purchases.search-products') }}?q=" + encodeURIComponent(q))
            .then(r => r.json())
            .then(data => {
                renderSearchResults(data);
            });
    }, 300);
});

function renderSearchResults(results) {
    if (!results.length) {
        searchResults.innerHTML = '<div style="padding:15px; text-align:center; color:#888; font-size:13px;">No products found.</div>';
        searchResults.classList.add('open');
        return;
    }
    searchResults.innerHTML = results.map((r, i) => `
        <div class="search-result-item" data-index="${i}">
            ${r.image ? `<img src="${r.image}">` : `<div class="no-img">No img</div>`}
            <div class="info">
                <div class="title">${r.title}${r.variant_title ? ' - ' + r.variant_title : ''}</div>
                <div class="meta">SKU: ${r.sku || '-'} &middot; Stock: ${r.inventory_quantity ?? 0}</div>
            </div>
            <div class="add-badge">+ Add</div>
        </div>
    `).join('');
    searchResults.classList.add('open');

    document.querySelectorAll('.search-result-item').forEach(el => {
        el.addEventListener('click', function() {
            const idx = this.dataset.index;
            addItem(results[idx]);
            searchInput.value = '';
            searchResults.classList.remove('open');
        });
    });
}

document.addEventListener('click', function(e) {
    if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
        searchResults.classList.remove('open');
    }
});

function addItem(product) {
    items.push({
        product_id: product.product_id,
        variant_id: product.variant_id,
        product_title: product.title,
        variant_title: product.variant_title,
        sku: product.sku,
        image: product.image,
        quantity: 1,
        cost_price: product.cost || product.price || 0,
    });
    renderItems();
}

function removeItem(index) {
    items.splice(index, 1);
    renderItems();
}

function renderItems() {
    const body = document.getElementById('itemsBody');
    if (!items.length) {
        body.innerHTML = '<tr id="emptyRow"><td colspan="7" style="text-align:center; padding:20px; color:#888;">No products added yet. Search above to add.</td></tr>';
        updateTotals();
        return;
    }

    body.innerHTML = items.map((item, i) => `
        <tr>
            <td>${item.image ? `<img src="${item.image}">` : ''}</td>
            <td>${item.product_title}${item.variant_title ? ' - ' + item.variant_title : ''}</td>
            <td>${item.sku || '-'}</td>
            <td><input type="number" min="1" value="${item.quantity}" onchange="updateItemField(${i}, 'quantity', this.value)"></td>
            <td><input type="number" step="0.01" min="0" value="${item.cost_price}" onchange="updateItemField(${i}, 'cost_price', this.value)"></td>
            <td>${currencySymbol}${(item.quantity * item.cost_price).toFixed(2)}</td>
            <td><button type="button" class="remove-item" onclick="removeItem(${i})">Remove</button></td>
        </tr>
    `).join('');

    updateTotals();
}

function updateItemField(index, field, value) {
    items[index][field] = parseFloat(value) || 0;
    renderItems();
}

function updateTotals() {
    const subtotal = items.reduce((sum, item) => sum + (item.quantity * item.cost_price), 0);
    const discount = parseFloat(document.getElementById('discountInput').value) || 0;
    const tax = parseFloat(document.getElementById('taxInput').value) || 0;
    const shipping = parseFloat(document.getElementById('shippingInput').value) || 0;
    const grandTotal = subtotal - discount + tax + shipping;

    document.getElementById('subtotalDisplay').textContent = currencySymbol + subtotal.toFixed(2);
    document.getElementById('discountDisplay').textContent = '-' + currencySymbol + discount.toFixed(2);
    document.getElementById('taxDisplay').textContent = '+' + currencySymbol + tax.toFixed(2);
    document.getElementById('shippingDisplay').textContent = '+' + currencySymbol + shipping.toFixed(2);
    document.getElementById('grandTotalDisplay').textContent = currencySymbol + grandTotal.toFixed(2);
}

['discountInput', 'taxInput', 'shippingInput'].forEach(id => {
    document.getElementById(id).addEventListener('input', updateTotals);
});

document.getElementById('purchaseForm').addEventListener('submit', function(e) {
    if (!items.length) {
        e.preventDefault();
        alert('Please add at least one product to the purchase.');
        return;
    }

    // Remove any previously injected hidden fields
    document.querySelectorAll('.item-hidden-field').forEach(el => el.remove());

    items.forEach((item, i) => {
        Object.keys(item).forEach(key => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = `items[${i}][${key}]`;
            input.value = item[key] ?? '';
            input.className = 'item-hidden-field';
            this.appendChild(input);
        });
    });
});
</script>
@endsection
