@if (session('status'))
    <div class="row">
        <div class="col">
            <div class="alert alert-{{ session('statusClass') ?? 'success' }} alert-dismissible fade show" role="alert">
                {!! session('status') !!}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
    </div>
@endif
