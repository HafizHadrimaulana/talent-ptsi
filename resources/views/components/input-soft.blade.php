<div class="u-space-y-sm mb-4">
    <label class="u-label uj-label">{{ $label }}</label>
    <div class="relative">
        <input class="u-input w-full" type="{{ $type ?? 'text' }}" name="{{ $name }}" value="{{ $val ?? '' }}" placeholder="{{ $ph ?? '' }}" {{ isset($readonly) ? 'readonly' : '' }}>
    </div>
</div>