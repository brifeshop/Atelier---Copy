@props(['status'])

@php
    $classes = match($status) {
        'draft'     => 'bg-navy-500/10 border-navy-500/30 text-navy-300',
        'ready'     => 'bg-yellow-500/10 border-yellow-500/30 text-yellow-400',
        'shipped'   => 'bg-blue-500/10 border-blue-500/30 text-blue-400',
        'delivered' => 'bg-green-500/10 border-green-500/30 text-green-400',
        'cancelled' => 'bg-red-500/10 border-red-500/30 text-red-400',
        default     => 'bg-navy-500/10 border-navy-500/30 text-navy-300',
    };

    $labels = [
        'draft'     => 'Draft',
        'ready'     => 'Ready',
        'shipped'   => 'Shipped',
        'delivered' => 'Delivered',
        'cancelled' => 'Cancelled',
    ];
@endphp

<span class="inline-flex items-center gap-1.5 text-xs px-2 py-1 rounded-full border {{ $classes }}">
    {{ $labels[$status] ?? ucfirst($status) }}
</span>