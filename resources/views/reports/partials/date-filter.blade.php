<div style="background:white; border-radius:8px; padding:12px 20px; margin-bottom:15px; display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
    <span style="font-size:13px; color:#555; font-weight:600;">Date Range:</span>
    <form method="GET" style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;" id="dateRangeForm">
        @foreach(request()->except(['range', 'page']) as $key => $val)
            <input type="hidden" name="{{ $key }}" value="{{ $val }}">
        @endforeach
        <select name="range" onchange="document.getElementById('dateRangeForm').submit()" style="padding:7px 10px; border:1px solid #ccc; border-radius:4px; font-size:14px;">
            <option value="all" {{ ($range ?? 'all') == 'all' ? 'selected' : '' }}>All Time</option>
            <option value="7days" {{ ($range ?? '') == '7days' ? 'selected' : '' }}>Last 7 Days</option>
            <option value="15days" {{ ($range ?? '') == '15days' ? 'selected' : '' }}>Last 15 Days</option>
            <option value="weekly" {{ ($range ?? '') == 'weekly' ? 'selected' : '' }}>This Week</option>
            <option value="monthly" {{ ($range ?? '') == 'monthly' ? 'selected' : '' }}>This Month</option>
        </select>
    </form>
    <span style="font-size:12px; color:#999;">Showing: {{ $rangeLabel ?? 'All Time' }}</span>
</div>
