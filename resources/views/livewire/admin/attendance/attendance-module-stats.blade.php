<div class="stat-grid" wire:poll.10s.visible>
    @foreach ($stats as $index => $stat)
        <article
            class="stat-card stat-card--{{ $stat['tone'] ?? 'blue' }}"
            style="--stat-i: {{ $index }}"
            wire:key="att-stat-{{ $stat['label'] }}"
        >
            <div class="stat-card__top">
                <p class="stat-card__label">{{ $stat['label'] }}</p>
                <span class="stat-card__mark" aria-hidden="true"></span>
            </div>
            <p class="stat-card__value">{{ $stat['value'] }}</p>
            <p class="stat-card__hint">{{ $stat['hint'] ?? '' }}</p>
        </article>
    @endforeach
</div>
