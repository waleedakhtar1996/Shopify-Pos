@extends('layouts.app')

@section('title', 'Add Product')

@section('content')
<style>
    .shopify-layout { display:flex; gap:20px; align-items:flex-start; }
    .main-col { flex:2; min-width:0; }
    .side-col { flex:1; min-width:280px; }
    .card { background:white; border-radius:8px; padding:20px; margin-bottom:20px; box-shadow:0 1px 3px rgba(0,0,0,0.08); }
    .card h3 { margin-top:0; font-size:15px; }
    .field { margin-bottom:15px; }
    .field label { display:block; font-weight:600; font-size:13px; margin-bottom:5px; }
    .field input[type=text], .field input[type=number], .field textarea, .field select {
        width:100%; padding:8px; border:1px solid #ccc; border-radius:4px; font-size:14px; box-sizing:border-box;
    }
    .row { display:flex; gap:15px; }
    .row > div { flex:1; }
    .checkbox-row { display:flex; align-items:center; gap:8px; margin-bottom:10px; }
    .checkbox-row label { font-weight:normal; font-size:14px; margin:0; }

    .dropzone {
        border:2px dashed #ccc; border-radius:8px; padding:40px 20px; text-align:center;
        cursor:pointer; transition:0.2s; background:#fafafa;
    }
    .dropzone.dragover { border-color:#008060; background:#f0faf7; }
    .dropzone p { margin:5px 0; color:#666; font-size:14px; }
    .image-preview { display:flex; gap:10px; flex-wrap:wrap; margin-top:15px; }
    .image-preview .thumb { position:relative; width:90px; height:90px; }
    .image-preview img { width:90px; height:90px; object-fit:cover; border-radius:6px; border:1px solid #ddd; }
    .image-preview .remove-img { position:absolute; top:-6px; right:-6px; background:#dc3545; color:white; border:none; border-radius:50%; width:20px; height:20px; cursor:pointer; font-size:12px; line-height:1; }

    .option-block { border:1px solid #eee; border-radius:6px; padding:15px; margin-bottom:12px; position:relative; }
    .option-block .remove-option { position:absolute; top:10px; right:10px; background:none; border:none; color:#dc3545; cursor:pointer; font-size:13px; }
    .add-btn { background:#f0f0f0; border:1px dashed #999; padding:10px; border-radius:4px; cursor:pointer; width:100%; font-size:14px; margin-top:5px; }

    table.variants-table { width:100%; border-collapse:collapse; margin-top:10px; }
    table.variants-table th { text-align:left; font-size:12px; color:#666; padding:8px 6px; border-bottom:1px solid #eee; }
    table.variants-table td { padding:6px; border-bottom:1px solid #f5f5f5; }
    table.variants-table input { width:100%; padding:6px; border:1px solid #ccc; border-radius:4px; font-size:13px; box-sizing:border-box; }

    .submit-bar { display:flex; justify-content:flex-end; margin-top:10px; }
    .submit-btn { padding:12px 30px; background:#008060; color:white; border:none; border-radius:4px; cursor:pointer; font-size:15px; }
</style>

<h1>Add product</h1>

@if ($errors->any())
    <div class="error">
        <ul style="margin:0; padding-left:18px;">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form method="POST" action="{{ route('products.store') }}" enctype="multipart/form-data" id="productForm">
    @csrf

    <div class="shopify-layout">
        <!-- MAIN COLUMN -->
        <div class="main-col">

            <div class="card">
                <div class="field">
                    <label>Title</label>
                    <input type="text" name="title" value="{{ old('title') }}" required>
                </div>
                <div class="field">
                    <label>Description</label>
                    <textarea name="body_html" rows="5">{{ old('body_html') }}</textarea>
                </div>
            </div>

            <div class="card">
                <h3>Media</h3>
                <div class="dropzone" id="dropzone">
                    <p><strong>Upload new</strong></p>
                    <p>Drag and drop images here, or click to browse</p>
                    <input type="file" name="images[]" id="imageInput" multiple accept="image/*" style="display:none;">
                </div>
                <div class="image-preview" id="imagePreview"></div>
            </div>

            <div class="card">
                <h3>Pricing</h3>
                <div class="row">
                    <div class="field">
                        <label>Price ({{ $currencySymbol }})</label>
                        <input type="number" step="0.01" id="basePrice" placeholder="0.00">
                    </div>
                    <div class="field">
                        <label>Compare-at price ({{ $currencySymbol }})</label>
                        <input type="number" step="0.01" id="baseCompareAt" placeholder="0.00">
                    </div>
                </div>
                <p style="font-size:12px; color:#888;">Used as default for all variants below (editable per variant).</p>
            </div>

            <div class="card">
                <h3>Inventory</h3>
                <div class="checkbox-row">
                    <input type="checkbox" name="track_quantity" id="track_quantity" value="1" checked>
                    <label for="track_quantity">Track quantity on Shopify</label>
                </div>
                <div class="checkbox-row">
                    <input type="checkbox" name="continue_selling_when_out_of_stock" id="continue_selling" value="1">
                    <label for="continue_selling">Continue selling when out of stock</label>
                </div>
            </div>

            <div class="card">
                <h3>Shipping</h3>
                <div class="checkbox-row">
                    <input type="checkbox" name="is_physical_product" id="is_physical" value="1" checked>
                    <label for="is_physical">This is a physical product</label>
                </div>
            </div>

            <div class="card">
                <h3>Variants</h3>
                <p style="font-size:13px; color:#666;">Add options like size or color (max 3 option types — Shopify's own limit — but unlimited values per option).</p>

                <div id="optionsContainer"></div>
                <button type="button" class="add-btn" id="addOptionBtn" onclick="addOption()">+ Add another option</button>

                <div id="variantsTableWrap" style="display:none; margin-top:20px;">
                    <h3>Variant Details</h3>
                    <table class="variants-table" id="variantsTable">
                        <thead>
                            <tr id="variantsHeadRow">
                                <th>SKU</th>
                                <th>Barcode</th>
                                <th>Price</th>
                                <th>Compare-at</th>
                                <th>Qty</th>
                                <th>Weight (kg)</th>
                            </tr>
                        </thead>
                        <tbody id="variantsBody"></tbody>
                    </table>
                </div>
            </div>

            <div class="card">
                <h3>Search engine listing</h3>
                <div class="field">
                    <label>Meta Title</label>
                    <input type="text" name="meta_title" value="{{ old('meta_title') }}">
                </div>
                <div class="field">
                    <label>Meta Description</label>
                    <textarea name="meta_description" rows="2">{{ old('meta_description') }}</textarea>
                </div>
            </div>

        </div>

        <!-- SIDE COLUMN -->
        <div class="side-col">
            <div class="card">
                <h3>Status</h3>
                <select name="status">
                    <option value="active" selected>Active</option>
                    <option value="draft">Draft</option>
                    <option value="archived">Archived</option>
                </select>
            </div>

            <div class="card">
                <h3>Product organization</h3>
                <div class="field">
                    <label>Type</label>
                    <input type="text" name="product_type" value="{{ old('product_type') }}">
                </div>
                <div class="field">
                    <label>Vendor</label>
                    <input type="text" name="vendor" value="{{ old('vendor') }}">
                </div>
                <div class="field">
                    <label>Collection</label>
                    <select name="collection_id" id="collectionSelect">
                        <option value="">None</option>
                        @foreach($collections ?? [] as $col)
                            <option value="{{ $col->id }}">{{ $col->title }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label>Tags (comma separated)</label>
                    <input type="text" name="tags" value="{{ old('tags') }}" placeholder="summer, sale, new">
                </div>
            </div>

            <div class="submit-bar">
                <button type="submit" class="submit-btn">Save product</button>
            </div>
        </div>
    </div>
</form>

<script>
let optionIndex = 0;
const MAX_OPTIONS = 3;
const optionNames = ['option1_name', 'option2_name', 'option3_name'];

function addOption() {
    if (optionIndex >= MAX_OPTIONS) return;

    const container = document.getElementById('optionsContainer');
    const idx = optionIndex;
    const div = document.createElement('div');
    div.className = 'option-block';
    div.id = 'option-' + idx;
    div.innerHTML = `
        <button type="button" class="remove-option" onclick="removeOption(${idx})">Remove</button>
        <div class="field">
            <label>Option name</label>
            <input type="text" name="${optionNames[idx]}" class="opt-name" data-idx="${idx}" placeholder="e.g. Size" oninput="generateVariants()">
        </div>
        <div class="field">
            <label>Option values (comma separated)</label>
            <input type="text" class="opt-values" data-idx="${idx}" placeholder="e.g. Small, Medium, Large" oninput="generateVariants()">
        </div>
    `;
    container.appendChild(div);
    optionIndex++;

    if (optionIndex >= MAX_OPTIONS) {
        document.getElementById('addOptionBtn').style.display = 'none';
    }
}

function removeOption(idx) {
    const el = document.getElementById('option-' + idx);
    if (el) el.remove();
    document.getElementById('addOptionBtn').style.display = 'block';
    generateVariants();
}

function getActiveOptions() {
    const names = document.querySelectorAll('.opt-name');
    const options = [];
    names.forEach(nameInput => {
        const idx = nameInput.dataset.idx;
        const name = nameInput.value.trim();
        const valuesInput = document.querySelector(`.opt-values[data-idx="${idx}"]`);
        const values = valuesInput ? valuesInput.value.split(',').map(v => v.trim()).filter(v => v) : [];
        if (name && values.length > 0) {
            options.push({ name, values });
        }
    });
    return options;
}

function cartesian(arrays) {
    return arrays.reduce((acc, curr) => {
        const res = [];
        acc.forEach(a => curr.forEach(c => res.push([...a, c])));
        return res;
    }, [[]]);
}

function generateVariants() {
    const options = getActiveOptions();
    const wrap = document.getElementById('variantsTableWrap');
    const headRow = document.getElementById('variantsHeadRow');
    const body = document.getElementById('variantsBody');

    body.innerHTML = '';

    // Rebuild header with option columns first
    headRow.innerHTML = '';
    options.forEach(opt => {
        const th = document.createElement('th');
        th.textContent = opt.name;
        headRow.appendChild(th);
    });
    ['SKU','Barcode','Price','Compare-at','Qty','Weight (kg)'].forEach(label => {
        const th = document.createElement('th');
        th.textContent = label;
        headRow.appendChild(th);
    });

    if (options.length === 0) {
        // no options -> single default variant
        wrap.style.display = 'block';
        addVariantRow(0, []);
        return;
    }

    const valueArrays = options.map(o => o.values);
    const combos = cartesian(valueArrays);

    wrap.style.display = 'block';
    combos.forEach((combo, i) => addVariantRow(i, combo));
}

function addVariantRow(rowIndex, comboValues) {
    const body = document.getElementById('variantsBody');
    const tr = document.createElement('tr');

    let cellsHtml = '';
    comboValues.forEach((val, optIdx) => {
        cellsHtml += `<td>
            <input type="hidden" name="variants[${rowIndex}][option${optIdx+1}]" value="${val}">
            ${val}
        </td>`;
    });

    const basePrice = document.getElementById('basePrice').value || '';
    const baseCompareAt = document.getElementById('baseCompareAt').value || '';

    cellsHtml += `
        <td><input type="text" name="variants[${rowIndex}][sku]"></td>
        <td><input type="text" name="variants[${rowIndex}][barcode]"></td>
        <td><input type="number" step="0.01" name="variants[${rowIndex}][price]" value="${basePrice}" required></td>
        <td><input type="number" step="0.01" name="variants[${rowIndex}][compare_at_price]" value="${baseCompareAt}"></td>
        <td><input type="number" name="variants[${rowIndex}][inventory_quantity]" value="0" required></td>
        <td><input type="number" step="0.01" name="variants[${rowIndex}][weight]"></td>
    `;

    tr.innerHTML = cellsHtml;
    body.appendChild(tr);
}

// Init with one default variant row (no options)
generateVariants();

// Update variant prices when base price changes (only for empty rows, before first edit)
document.getElementById('basePrice').addEventListener('change', generateVariants);
document.getElementById('baseCompareAt').addEventListener('change', generateVariants);

// --- Drag & drop image upload ---
const dropzone = document.getElementById('dropzone');
const imageInput = document.getElementById('imageInput');
const preview = document.getElementById('imagePreview');
let selectedFiles = [];

dropzone.addEventListener('click', () => imageInput.click());

dropzone.addEventListener('dragover', (e) => {
    e.preventDefault();
    dropzone.classList.add('dragover');
});
dropzone.addEventListener('dragleave', () => dropzone.classList.remove('dragover'));
dropzone.addEventListener('drop', (e) => {
    e.preventDefault();
    dropzone.classList.remove('dragover');
    handleFiles(e.dataTransfer.files);
});

imageInput.addEventListener('change', (e) => handleFiles(e.target.files));

function handleFiles(fileList) {
    Array.from(fileList).forEach(file => {
        if (!file.type.startsWith('image/')) return;
        selectedFiles.push(file);
    });
    renderPreview();
    syncFileInput();
}

function renderPreview() {
    preview.innerHTML = '';
    selectedFiles.forEach((file, i) => {
        const reader = new FileReader();
        reader.onload = function(ev) {
            const thumb = document.createElement('div');
            thumb.className = 'thumb';
            thumb.innerHTML = `<img src="${ev.target.result}"><button type="button" class="remove-img" onclick="removeImage(${i})">×</button>`;
            preview.appendChild(thumb);
        };
        reader.readAsDataURL(file);
    });
}

function removeImage(index) {
    selectedFiles.splice(index, 1);
    renderPreview();
    syncFileInput();
}

function syncFileInput() {
    const dt = new DataTransfer();
    selectedFiles.forEach(file => dt.items.add(file));
    imageInput.files = dt.files;
}
</script>
@endsection
