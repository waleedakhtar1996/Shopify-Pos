@extends('layouts.app')

@section('title', 'Edit Purchase')

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

    .product-search-wrap { position:relative; }
    .search-results { position:absolute; top:100%; left:0; right:0; background:white; border:1px solid #ddd; border-radius:6px; max-height:320px; overflow-y:auto; z-index:50; box-shadow:0 4px 12px rgba(0,0,0,0.1); display:none; }
    .search-results.open { display:block; }
    .search-result-item { display:flex; align-items:center; gap:10px; padding:10px; cursor:pointer; border-bottom:1px solid #f0f0f0; }
    .search-result-item:hover { background:#f5f5f5; }
    .search-result-item img { width:36px; height:36px; object-fit:cover; border-radius:4px; background:#f0f0f0; }
    .search-result-item .info { flex:1; }
    .search-result-item .info .title { font-size:13px; font-weight:600; }
    .search-result-item .info .meta { font-size:12px; color:#888; }

    table.items-table { width:100%; border-collapse:collapse; margin-top:10px; }
    table.items-table th { text-align:left; padding:8px; background:#f5f5f5; font-size:12px; color:#555; }
    table.items-table td { padding:8px; border-bottom:1px solid #eee; font-size:13px; vertical-align:middle; }
    table.items-table img { width:36px; height:36px; object-fit:cover; border-radius:4px; background:#f0f0f0; }
    table.items-table input[type=number] { width:80px; padding:6px; border:1px solid #ccc; border-radius:4px; }
    .remove-item { background:#fdecea; color:#c0392b; border:none; padding:5px 10px; border-radius:4px; cursor:pointer; font-size:12px; }
    .totals-box { max-width:340px; margin-left:auto; margin-top:15px; }
    .totals-box .trow { display:flex; justify-content:space-between; padding:6px 0; font-size:14px; }
    .totals-box .trow.grand { font-weight:700; font-size:16px; border-top:1px solid #ddd; padding-top:10px; margin-top:6px; }
</style>

<a href="{{ route('purchases.index') }}" class="back-link">&larr; Back to Purchases</a>
<h1>Edit Purchase</h1>

@if ($errors->any())
    <div class="error">
        <ul style="margin:0; padding-left:18px;">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form method="POST" action="{{ route('purchases.update', $purchase->id) }}" id="purchaseForm">
    @csrf
    @method('PUT')

    <div class="card">
        <h3 style="margin-top:0;">Purchase Info</h3>
        <div class="row">
            <div class="field">
                <label>PO Number</label>
                <input type="text" name="purchase_number" value="{{ old('purchase_number', $purchase->purchase_number) }}">
            </div>
            <div class="field">
                <label>Purchase Date *</label>
                <input type="date" name="purchase_date" value="{{ old('purchase_date', $purchase->purchase_date->format('Y-m-d')) }}" required>
            </div>
        </div>
        <div class="row">
            <div class="field">
                <label>Supplier Name</label>
                <input type="text" name="supplier_name" value="{{ old('supplier_name', $purchase->supplier_name) }}">
            </div>
            <div class="field">
                <label>Supplier Contact</label>
                <input type="text" name="supplier_contact" value="{{ old('supplier_contact', $purchase->supplier_contact) }}">
            </div>
        </div>
    </div>

    <div class="card">
        <h3 style="margin-top:0;">Products</h3>
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
            <tbody id="itemsBody"></tbody>
        </table>
    </div>

    <div class="card">
        <h3 style="margin-top:0;">Costs & Payment</h3>
        <div class="row">
            <div class="field">
                <label>Discount ({{ $currencySymbol }})</label>
                <input type="number" step="0.01" name="discount" id="discountInput" value="{{ old('discount', $purchase->discount) }}">
            </div>
            <div class="field">
                <label>Tax ({{ $currencySymbol }})</label>
                <input type="number" step="0.01" name="tax" id="taxInput" value="{{ old('tax', $purchase->tax) }}">
            </div>
            <div class="field">
                <label>Shipping Cost ({{ $currencySymbol }})</label>
                <input type="number" step="0.01" name="shipping_cost" id="shippingInput" value="{{ old('shipping_cost', $purchase->shipping_cost) }}">
            </div>
        </div>
        <div class="row">
            <div class="field">
                <label>Payment Status *</label>
                <select name="payment_status" required>
                    <option value="unpaid" {{ $purchase->payment_status == 'unpaid' ? 'selected' : '' }}>Unpaid</option>
                    <option value="partial" {{ $purchase->payment_status == 'partial' ? 'selected' : '' }}>Partial</option>
                    <option value="paid" {{ $purchase->payment_status == 'paid' ? 'selected' : '' }}>Paid</option>
                </select>
            </div>
            <div class="field">
                <label>Payment Type</label>
                <select name="payment_type">
                    <option value="">Select payment type</option>
                    @foreach ($paymentTypes as $type)
                        <option value="{{ $type }}" {{ old('payment_type', $purchase->payment_type) == $type ? 'selected' : '' }}>{{ $type }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label>Amount Paid ({{ $currencySymbol }})</label>
                <input type="number" step="0.01" name="amount_paid" value="{{ old('amount_paid', $purchase->amount_paid) }}">
            </div>
            <div class="field">
                <label>Purchase Status *</label>
                <select name="status" required>
                    <option value="pending" {{ $purchase->status == 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="received" {{ $purchase->status == 'received' ? 'selected' : '' }}>Received</option>
                    <option value="cancelled" {{ $purchase->status == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                </select>
            </div>
        </div>
        <div class="field">
            <label>Notes</label>
            <textarea name="notes" rows="3">{{ old('notes', $purchase->notes) }}</textarea>
        </div>

        <div class="totals-box">
            <div class="trow"><span>Subtotal</span><span id="subtotalDisplay">{{ $currencySymbol }}0.00</span></div>
            <div class="trow"><span>Discount</span><span id="discountDisplay">-{{ $currencySymbol }}0.00</span></div>
            <div class="trow"><span>Tax</span><span id="taxDisplay">+{{ $currencySymbol }}0.00</span></div>
            <div class="trow"><span>Shipping</span><span id="shippingDisplay">+{{ $currencySymbol }}0.00</span></div>
            <div class="trow grand"><span>Grand Total</span><span id="grandTotalDisplay">{{ $currencySymbol }}0.00</span></div>
        </div>
    </div>

    <button type="submit" class="submit-btn">Update Purchase</button>
</form>

<script>
const currencySymbol = @json($currencySymbol);
let items = @json($existingItems);
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
            ${r.image ? `<img src="${r.image}">` : `<div style="width:36px;height:36px;background:#f0f0f0;border-radius:4px;"></div>`}
            <div class="info">
                <div class="title">${r.title}${r.variant_title ? ' - ' + r.variant_title : ''}</div>
                <div class="meta">SKU: ${r.sku || '-'} &middot; Stock: ${r.inventory_quantity ?? 0}</div>
            </div>
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

renderItems();
</script>
@endsection
