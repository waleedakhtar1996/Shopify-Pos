@extends('layouts.app')

@section('title', 'Edit Product')

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
    .existing-badge { position:absolute; bottom:-6px; left:-6px; background:#008060; color:white; font-size:9px; padding:2px 5px; border-radius:4px; }

    .option-block { border:1px solid #eee; border-radius:6px; padding:15px; margin-bottom:12px; position:relative; }
    .option-block .remove-option { position:absolute; top:10px; right:10px; background:none; border:none; color:#dc3545; cursor:pointer; font-size:13px; }
    .add-btn { background:#f0f0f0; border:1px dashed #999; padding:10px; border-radius:4px; cursor:pointer; width:100%; font-size:14px; margin-top:5px; }

    table.variants-table { width:100%; border-collapse:collapse; margin-top:10px; }
    table.variants-table th { text-align:left; font-size:12px; color:#666; padding:8px 6px; border-bottom:1px solid #eee; }
    table.variants-table td { padding:6px; border-bottom:1px solid #f5f5f5; }
    table.variants-table input { width:100%; padding:6px; border:1px solid #ccc; border-radius:4px; font-size:13px; box-sizing:border-box; }
    table.variants-table .remove-variant { background:#fdecea; color:#c0392b; border:none; border-radius:4px; padding:6px 10px; cursor:pointer; font-size:12px; }

    .submit-bar { display:flex; justify-content:flex-end; margin-top:10px; }
    .submit-btn { padding:12px 30px; background:#008060; color:white; border:none; border-radius:4px; cursor:pointer; font-size:15px; }
</style>

<h1>Edit product</h1>

@if ($errors->any())
    <div class="error">
        <ul style="margin:0; padding-left:18px;">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form method="POST" action="{{ route('products.update', $product->id) }}" enctype="multipart/form-data" id="productForm">
    @csrf
    @method('PUT')

    <div class="shopify-layout">
        <!-- MAIN COLUMN -->
        <div class="main-col">

            <div class="card">
                <div class="field">
                    <label>Title</label>
                    <input type="text" name="title" value="{{ old('title', $product->title) }}" required>
                </div>
                <div class="field">
                    <label>Description</label>
                    <textarea name="body_html" rows="5">{{ old('body_html', $product->body_html) }}</textarea>
                </div>
            </div>

            <div class="card">
                <h3>Media</h3>
                <div class="image-preview" id="existingPreview">
                    @foreach ($product->images as $img)
                        <div class="thumb" data-existing-id="{{ $img->id }}">
                            <img src="{{ $img->src }}">
                            <span class="existing-badge">saved</span>
                        </div>
                    @endforeach
                </div>
                <p style="font-size:12px; color:#888; margin:10px 0;">Existing images stay as-is. Add new ones below (existing images can't be removed here yet).</p>
                <div class="dropzone" id="dropzone">
                    <p><strong>Upload new</strong></p>
                    <p>Drag and drop images here, or click to browse</p>
                    <input type="file" name="images[]" id="imageInput" multiple accept="image/*" style="display:none;">
                </div>
                <div class="image-preview" id="imagePreview"></div>
            </div>

            <div class="card">
                <h3>Inventory</h3>
                <div class="checkbox-row">
                    <input type="checkbox" name="track_quantity" id="track_quantity" value="1" {{ old('track_quantity', $product->track_quantity) ? 'checked' : '' }}>
                    <label for="track_quantity">Track quantity on Shopify</label>
                </div>
                <div class="checkbox-row">
                    <input type="checkbox" name="continue_selling_when_out_of_stock" id="continue_selling" value="1" {{ old('continue_selling_when_out_of_stock', $product->continue_selling_when_out_of_stock) ? 'checked' : '' }}>
                    <label for="continue_selling">Continue selling when out of stock</label>
                </div>
            </div>

            <div class="card">
                <h3>Shipping</h3>
                <div class="checkbox-row">
                    <input type="checkbox" name="is_physical_product" id="is_physical" value="1" {{ old('is_physical_product', $product->is_physical_product) ? 'checked' : '' }}>
                    <label for="is_physical">This is a physical product</label>
                </div>
            </div>

            <div class="card">
                <h3>Variants</h3>
                <p style="font-size:13px; color:#666;">Options and existing variants are pre-filled. Edit values, add new option values, or add/remove variants as needed.</p>

                <div id="optionsContainer"></div>
                <button type="button" class="add-btn" id="addOptionBtn" onclick="addOption()">+ Add another option</button>

                <div id="variantsTableWrap" style="margin-top:20px;">
                    <h3>Variant Details</h3>
                    <table class="variants-table" id="variantsTable">
                        <thead>
                            <tr id="variantsHeadRow"></tr>
                        </thead>
                        <tbody id="variantsBody"></tbody>
                    </table>
                    <button type="button" class="add-btn" onclick="addBlankVariantRow()">+ Add variant row</button>
                </div>
            </div>

            <div class="card">
                <h3>Search engine listing</h3>
                <div class="field">
                    <label>Meta Title</label>
                    <input type="text" name="meta_title" value="{{ old('meta_title', $product->meta_title) }}">
                </div>
                <div class="field">
                    <label>Meta Description</label>
                    <textarea name="meta_description" rows="2">{{ old('meta_description', $product->meta_description) }}</textarea>
                </div>
            </div>

        </div>

        <!-- SIDE COLUMN -->
        <div class="side-col">
            <div class="card">
                <h3>Status</h3>
                <select name="status">
                    <option value="active" {{ old('status', $product->status) == 'active' ? 'selected' : '' }}>Active</option>
                    <option value="draft" {{ old('status', $product->status) == 'draft' ? 'selected' : '' }}>Draft</option>
                    <option value="archived" {{ old('status', $product->status) == 'archived' ? 'selected' : '' }}>Archived</option>
                </select>
            </div>

            <div class="card">
                <h3>Product organization</h3>
                <div class="field">
                    <label>Type</label>
                    <input type="text" name="product_type" value="{{ old('product_type', $product->product_type) }}">
                </div>
                <div class="field">
                    <label>Vendor</label>
                    <input type="text" name="vendor" value="{{ old('vendor', $product->vendor) }}">
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
                    <input type="text" name="tags" value="{{ old('tags', $product->tags) }}" placeholder="summer, sale, new">
                </div>
            </div>

            <div class="submit-bar">
                <button type="submit" class="submit-btn">Save changes</button>
            </div>
        </div>
    </div>
</form>

<script>
// ---- Existing product data injected from backend ----
const existingOptions = [
    @if($product->option1_name) { name: @json($product->option1_name), idx: 0 }, @endif
    @if($product->option2_name) { name: @json($product->option2_name), idx: 1 }, @endif
    @if($product->option3_name) { name: @json($product->option3_name), idx: 2 }, @endif
];

const existingVariants = [
    @foreach($product->variants as $v)
    {
        id: {{ $v->id }},
        sku: @json($v->sku),
        barcode: @json($v->barcode),
        price: {{ $v->price ?? 0 }},
        compare_at_price: {{ $v->compare_at_price ?? 'null' }},
        inventory_quantity: {{ $v->inventory_quantity ?? 0 }},
        weight: {{ $v->weight ?? 'null' }},
        option1: @json($v->option1),
        option2: @json($v->option2),
        option3: @json($v->option3),
    },
    @endforeach
];

let optionIndex = 0;
const MAX_OPTIONS = 3;
const optionNames = ['option1_name', 'option2_name', 'option3_name'];

function addOption(prefillName, prefillValues) {
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
            <input type="text" name="${optionNames[idx]}" class="opt-name" data-idx="${idx}" placeholder="e.g. Size" value="${prefillName || ''}">
        </div>
        <div class="field">
            <label>Option values (comma separated)</label>
            <input type="text" class="opt-values" data-idx="${idx}" placeholder="e.g. Small, Medium, Large" value="${(prefillValues || []).join(', ')}">
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
}

function rebuildVariantsHeader() {
    const names = document.querySelectorAll('.opt-name');
    const headRow = document.getElementById('variantsHeadRow');
    headRow.innerHTML = '';
    names.forEach(n => {
        const th = document.createElement('th');
        th.textContent = n.value || ('Option ' + (parseInt(n.dataset.idx) + 1));
        headRow.appendChild(th);
    });
    ['SKU','Barcode','Price','Compare-at','Qty','Weight (kg)',''].forEach(label => {
        const th = document.createElement('th');
        th.textContent = label;
        headRow.appendChild(th);
    });
}

let variantRowIndex = 0;

function addVariantRow(data) {
    data = data || {};
    const body = document.getElementById('variantsBody');
    const rowIndex = variantRowIndex++;
    const tr = document.createElement('tr');

    const optCount = document.querySelectorAll('.opt-name').length;
    let cellsHtml = '';
    for (let i = 1; i <= optCount; i++) {
        const val = data['option' + i] || '';
        cellsHtml += `<td><input type="text" name="variants[${rowIndex}][option${i}]" value="${val}"></td>`;
    }

    cellsHtml += `
        <input type="hidden" name="variants[${rowIndex}][id]" value="${data.id || ''}">
        <td><input type="text" name="variants[${rowIndex}][sku]" value="${data.sku || ''}"></td>
        <td><input type="text" name="variants[${rowIndex}][barcode]" value="${data.barcode || ''}"></td>
        <td><input type="number" step="0.01" name="variants[${rowIndex}][price]" value="${data.price ?? ''}" required></td>
        <td><input type="number" step="0.01" name="variants[${rowIndex}][compare_at_price]" value="${data.compare_at_price ?? ''}"></td>
        <td><input type="number" name="variants[${rowIndex}][inventory_quantity]" value="${data.inventory_quantity ?? 0}" required></td>
        <td><input type="number" step="0.01" name="variants[${rowIndex}][weight]" value="${data.weight ?? ''}"></td>
        <td><button type="button" class="remove-variant" onclick="this.closest('tr').remove()">Remove</button></td>
    `;

    tr.innerHTML = cellsHtml;
    body.appendChild(tr);
}

function addBlankVariantRow() {
    addVariantRow({});
}

// --- Init: prefill options and variants from backend data ---
existingOptions.forEach(opt => {
    const values = [...new Set(existingVariants.map(v => v['option' + (opt.idx + 1)]).filter(v => v))];
    addOption(opt.name, values);
});

rebuildVariantsHeader();

if (existingVariants.length > 0) {
    existingVariants.forEach(v => addVariantRow(v));
} else {
    addVariantRow({});
}

// Rebuild header whenever an option name changes
document.getElementById('optionsContainer').addEventListener('input', function(e) {
    if (e.target.classList.contains('opt-name')) {
        rebuildVariantsHeader();
    }
});

// --- Drag & drop image upload (new images only) ---
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
