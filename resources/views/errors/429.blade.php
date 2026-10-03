@include('errors.layout', [
    'status' => 429,
    'heading' => __('Çox sayda sorğu'),
    'message' => __('Qısa müddətdə çox sorğu göndərildi. Bir az sonra yenidən cəhd edin.'),
])
