@include('errors.layout', [
    'status' => 404,
    'heading' => __('Səhifə tapılmadı'),
    'message' => __('Axtardığınız səhifə mövcud deyil və ya köçürülüb.'),
])
