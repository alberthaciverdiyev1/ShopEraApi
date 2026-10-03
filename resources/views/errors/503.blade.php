@include('errors.layout', [
    'status' => 503,
    'heading' => __('Xidmət müvəqqəti əlçatan deyil'),
    'message' => __('Qısa texniki yeniləmə aparılır. Bir neçə saniyə sonra yenidən cəhd edin.'),
])
