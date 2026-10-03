@include('errors.layout', [
    'status' => 500,
    'heading' => __('Daxili xəta'),
    'message' => __('Gözlənilməz xəta baş verdi. Komanda məlumatlandırıldı — bir az sonra yenidən cəhd edin.'),
])
