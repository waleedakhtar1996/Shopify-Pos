@extends('layouts.app')

@section('title', 'Add Collection')

@section('content')
<style>
    .card { background:white; border-radius:8px; padding:20px; margin-bottom:20px; max-width:600px; box-shadow:0 1px 3px rgba(0,0,0,0.08); }
    .field { margin-bottom:15px; }
    .field label { display:block; font-weight:600; font-size:13px; margin-bottom:5px; }
    .field input[type=text], .field textarea { width:100%; padding:8px; border:1px solid #ccc; border-radius:4px; font-size:14px; box-sizing:border-box; }
    .dropzone { border:2px dashed #ccc; border-radius:8px; padding:30px 20px; text-align:center; cursor:pointer; background:#fafafa; }
    .dropzone p { margin:5px 0; color:#666; font-size:14px; }
    .image-preview { margin-top:15px; }
    .image-preview img { width:120px; height:120px; object-fit:cover; border-radius:6px; border:1px solid #ddd; }
    .submit-btn { padding:12px 30px; background:#008060; color:white; border:none; border-radius:4px; cursor:pointer; font-size:15px; }
</style>

<h1>Add Collection</h1>

@if ($errors->any())
    <div class="error">
        <ul style="margin:0; padding-left:18px;">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form method="POST" action="{{ route('collections.store') }}" enctype="multipart/form-data">
    @csrf
    <div class="card">
        <div class="field">
            <label>Title</label>
            <input type="text" name="title" value="{{ old('title') }}" required>
        </div>
        <div class="field">
            <label>Description</label>
            <textarea name="description" rows="4">{{ old('description') }}</textarea>
        </div>
        <div class="field">
            <label>Image</label>
            <div class="dropzone" onclick="document.getElementById('imageInput').click()">
                <p><strong>Upload image</strong></p>
                <p>Click to browse</p>
                <input type="file" name="image" id="imageInput" accept="image/*" style="display:none;" onchange="previewImage(this)">
            </div>
            <div class="image-preview" id="imagePreview"></div>
        </div>
    </div>
    <button type="submit" class="submit-btn">Save collection</button>
</form>

<script>
function previewImage(input) {
    const preview = document.getElementById('imagePreview');
    preview.innerHTML = '';
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => {
            const img = document.createElement('img');
            img.src = e.target.result;
            preview.appendChild(img);
        };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>
@endsection
