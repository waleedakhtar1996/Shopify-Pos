@extends('layouts.app')

@section('title', 'Collections')

@section('content')
<style>
    .top-bar { display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; }
    .btn { padding:10px 20px; background:#008060; color:white; border:none; border-radius:4px; cursor:pointer; text-decoration:none; font-size:14px; display:inline-block; }
    .grid { display:grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap:20px; }
    .col-card { background:white; border-radius:8px; overflow:hidden; box-shadow:0 1px 3px rgba(0,0,0,0.08); }
    .col-img { width:100%; height:150px; object-fit:cover; background:#f0f0f0; }
    .col-img-placeholder { width:100%; height:150px; background:#f0f0f0; display:flex; align-items:center; justify-content:center; color:#bbb; font-size:13px; }
    .col-body { padding:15px; }
    .col-title { font-weight:600; font-size:15px; margin-bottom:4px; }
    .col-count { font-size:13px; color:#888; margin-bottom:12px; }
    .col-actions { display:flex; gap:8px; }
    .action-link { flex:1; text-align:center; padding:8px; border-radius:4px; font-size:13px; text-decoration:none; }
    .edit-link { background:#e8f0fe; color:#1a56db; }
    .delete-btn { background:#fdecea; color:#c0392b; border:none; cursor:pointer; width:100%; }
    form.inline { display:inline; width:100%; }

    .sync-progress-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:9998; align-items:center; justify-content:center; }
    .sync-progress-overlay.open { display:flex; }
    .sync-progress-box { background:white; border-radius:12px; padding:30px 40px; min-width:340px; text-align:center; box-shadow:0 10px 40px rgba(0,0,0,0.2); }
    .sync-progress-bar-track { width:100%; height:10px; background:#eee; border-radius:6px; overflow:hidden; margin:16px 0 10px; }
    .sync-progress-bar-fill { height:100%; background:#008060; width:0%; transition:width .2s; }
    .sync-progress-label { font-size:14px; color:#555; }
    .sync-progress-current { font-size:13px; color:#888; margin-top:4px; }
</style>

<div class="top-bar">
    <h1>Collections</h1>
    <div>
        <button type="button" id="manualSyncBtn" class="btn" style="background:#555;">🔄 Sync from Shopify</button>
        <a href="{{ route('collections.create') }}" class="btn">+ Add Collection</a>
    </div>
</div>

<div class="grid">
    @forelse ($collections as $collection)
        <div class="col-card">
            @if ($collection->image)
                <img src="{{ $collection->image }}" class="col-img" alt="">
            @else
                <div class="col-img-placeholder">No image</div>
            @endif
            <div class="col-body">
                <div class="col-title">{{ $collection->title }}</div>
                <div class="col-count">{{ $collection->products_count_cache ?? 0 }} products</div>
                <div class="col-actions">
                    <a href="{{ route('collections.edit', $collection->id) }}" class="action-link edit-link">Edit</a>
                    <form method="POST" action="{{ route('collections.destroy', $collection->id) }}" class="inline" onsubmit="return confirm('Delete this collection?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="action-link delete-btn">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    @empty
        <p style="color:#888;">No collections yet. Create your first one!</p>
    @endforelse
</div>

<div class="sync-progress-overlay" id="syncProgressOverlay">
    <div class="sync-progress-box">
        <div style="font-weight:600; font-size:16px; margin-bottom:4px;">Syncing Collections from Shopify</div>
        <div class="sync-progress-bar-track">
            <div class="sync-progress-bar-fill" id="syncProgressFill"></div>
        </div>
        <div class="sync-progress-label" id="syncProgressLabel">0 of 0 synced</div>
        <div class="sync-progress-current" id="syncProgressCurrent"></div>
        <button type="button" id="stopSyncBtn" style="margin-top:16px; padding:8px 20px; background:#fdecea; color:#c0392b; border:none; border-radius:6px; cursor:pointer; font-size:13px; font-weight:600;">Stop Sync</button>
    </div>
</div>

<script>
(function() {
    const overlay = document.getElementById('syncProgressOverlay');
    const fill = document.getElementById('syncProgressFill');
    const label = document.getElementById('syncProgressLabel');
    const current = document.getElementById('syncProgressCurrent');
    const manualBtn = document.getElementById('manualSyncBtn');
    const stopBtn = document.getElementById('stopSyncBtn');

    let syncing = false;
    let stopRequested = false;

    stopBtn?.addEventListener('click', function() {
        stopRequested = true;
        label.textContent = 'Stopping...';
    });

    async function runBatchSync() {
        if (syncing) return;
        syncing = true;
        stopRequested = false;
        overlay.classList.add('open');
        fill.style.width = '0%';
        label.textContent = 'Loading collection list...';
        current.textContent = '';

        try {
            const listRes = await fetch("{{ route('collections.sync.list') }}", {
                headers: { 'Accept': 'application/json' },
            });
            const listData = await listRes.json();
            const ids = listData.ids || [];
            const total = ids.length;

            if (total === 0) {
                label.textContent = 'No collections to sync.';
                setTimeout(() => { overlay.classList.remove('open'); syncing = false; window.location.reload(); }, 1000);
                return;
            }

            for (let i = 0; i < total; i++) {
                if (stopRequested) {
                    label.textContent = `Stopped at ${i} of ${total}`;
                    current.textContent = '';
                    syncing = false;
                    stopRequested = false;
                    setTimeout(() => { overlay.classList.remove('open'); window.location.reload(); }, 1200);
                    return;
                }

                const id = ids[i];
                label.textContent = `${i} of ${total} synced`;
                fill.style.width = ((i / total) * 100) + '%';

                try {
                    const res = await fetch(`/collections/sync-one/${id}`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || '',
                            'Accept': 'application/json',
                        },
                    });
                    const data = await res.json();
                    current.textContent = data.title ? `Just synced: ${data.title}` : '';
                } catch (e) {
                    // Skip failed ones, continue with the rest
                }

                fill.style.width = (((i + 1) / total) * 100) + '%';
                label.textContent = `${i + 1} of ${total} synced`;

                // Small delay between requests to avoid Shopify API rate limiting
                await new Promise(resolve => setTimeout(resolve, 600));
            }

            label.textContent = 'Sync complete!';
            current.textContent = '';

            await fetch("{{ route('collections.mark.synced') }}", {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || '',
                    'Accept': 'application/json',
                },
            }).catch(() => {});

            setTimeout(() => {
                overlay.classList.remove('open');
                syncing = false;
                window.location.reload();
            }, 800);
        } catch (e) {
            overlay.classList.remove('open');
            syncing = false;
            alert('Sync failed to start. Please try again.');
        }
    }

    manualBtn?.addEventListener('click', runBatchSync);
    // Auto-sync is intentionally disabled on this page — sync only runs when the button is clicked manually.
})();
</script>
@endsection
