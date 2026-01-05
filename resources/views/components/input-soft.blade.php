<div class="u-space-y-sm mb-4">
    <label class="u-block u-text-sm u-font-medium u-mb-sm text-gray-700">{{ $label }}</label>
    <div class="relative"><input class="u-input w-full" type="{{ $type ?? 'text' }}" name="{{ $name }}" value="{{ $val ?? '' }}" placeholder="{{ $ph ?? '' }}" {{ isset($readonly) ? 'readonly' : '' }}></div>
</div>