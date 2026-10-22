@props([
    'action',
    'label' => 'Delete',
    'confirm' => 'Delete this record? This cannot be undone.',
    'small' => true,
])

{{-- DELETE form with a browser confirmation. --}}
<form method="POST" action="{{ $action }}" class="d-inline"
      onsubmit="return confirm(@js($confirm));">
    @csrf
    @method('DELETE')
    <button type="submit" class="btn-glass btn-danger-glass {{ $small ? 'btn-sm-custom' : '' }}">
        <i class="bi bi-trash3"></i> {{ $label }}
    </button>
</form>
