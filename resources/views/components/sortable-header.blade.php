@props([
    'column',
    'label',
    'align' => 'left', // left, center, right
])

@php
    $currentSort = request('sort');
    $currentDirection = request('direction', 'desc');
    
    $isActive = $currentSort === $column;
    $nextDirection = ($isActive && $currentDirection === 'asc') ? 'desc' : 'asc';
    
    // Build URL dengan query string existing
    $queryParams = array_merge(request()->query(), [
        'sort' => $column,
        'direction' => $nextDirection,
    ]);
    $url = request()->url() . '?' . http_build_query($queryParams);
    
    // Icon
    $icon = '';
    if ($isActive) {
        $icon = $currentDirection === 'asc' ? '▲' : '▼';
    }
@endphp

<th class="px-5 py-3 text-{{ $align }} text-[10px] font-mono font-semibold text-navy-400 uppercase tracking-widest">
    <a href="{{ $url }}" 
       class="inline-flex items-center gap-1 hover:text-gold-500 transition {{ $isActive ? 'text-gold-500' : '' }}">
        {{ $label }}
        @if($isActive)
            <span class="text-xs">{{ $icon }}</span>
        @else
            <span class="text-navy-600 text-xs">⇅</span>
        @endif
    </a>
</th>