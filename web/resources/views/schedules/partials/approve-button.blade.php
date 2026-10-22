{{-- Approve the previewed routine. Expects $token and $validation. --}}
<form method="POST" action="{{ route('schedules.approve', $token) }}" class="js-busy-form">
    @csrf
    <button type="submit" class="btn-indigo btn-success-glow" @disabled(!$validation['is_valid'])>
        <i class="bi bi-check2-circle"></i> Approve &amp; Save Routine
    </button>
</form>
