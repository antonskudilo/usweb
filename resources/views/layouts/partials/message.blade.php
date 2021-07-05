@if (session('status'))
    <div class="row">
        <div class="col">
            <div class="alert alert-{{ session('statusClass') ?? 'success' }} alert-dismissible fade show" role="alert">
                {!! session('status') !!}
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        </div>
    </div>
@endif
