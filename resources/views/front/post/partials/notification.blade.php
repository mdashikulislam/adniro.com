@if (isset($errors) && $errors->any())
    <div class="col-12">
        <div class="alert alert-danger alert-dismissible">
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="{{ t('Close') }}"></button>
            <h5 class="fw-bold text-danger-emphasis mb-3">
                {{ t('validation_errors_title') }}
            </h5>
            <ul class="mb-0 list-unstyled">
                @foreach ($errors->all() as $error)
                    <li class="lh-lg"><i class="bi bi-check-lg me-1"></i>{!! $error !!}</li>
                @endforeach
            </ul>
        </div>
    </div>
@endif

@php
    $withMessage = !session()->has('flash_messages');
	$resendVerificationLink = getResendVerificationLink(withMessage: $withMessage);
@endphp

@if (!empty($resendVerificationLink))
    <div class="col-12">
        <div class="alert alert-info text-center">
            {!! $resendVerificationLink !!}
        </div>
    </div>
@endif
