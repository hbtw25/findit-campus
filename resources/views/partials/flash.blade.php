@if (session('success'))
    <div class="flash flash-success mb-3" role="status">
        <i class="bi bi-check-circle-fill"></i>
        <div>{{ session('success') }}</div>
    </div>
@endif
@if (session('error'))
    <div class="flash flash-error mb-3" role="alert">
        <i class="bi bi-exclamation-circle-fill"></i>
        <div>{{ session('error') }}</div>
    </div>
@endif
