<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines contain the default error messages used by
    | the validator class. Some of these rules have multiple versions such
    | as the size rules. Feel free to tweak each of these messages here.
    |
    */

    'accepted' => ':attribute qəbul edilməlidir.',
    'accepted_if' => 'The :attribute field must be accepted when :other is :value.',
    'active_url' => ':attribute düzgün URL deyil.',
    'after' => ':attribute :date tarixindən sonra olmalıdır.',
    'after_or_equal' => 'The :attribute field must be a date after or equal to :date.',
    'alpha' => ':attribute yalnız hərflərdən ibarət ola bilər.',
    'alpha_dash' => ':attribute yalnız hərf, rəqəm, tire və alt xəttdən ibarət ola bilər.',
    'alpha_num' => ':attribute yalnız hərf və rəqəmlərdən ibarət ola bilər.',
    'any_of' => 'The :attribute field is invalid.',
    'array' => ':attribute siyahı formatında olmalıdır.',
    'ascii' => 'The :attribute field must only contain single-byte alphanumeric characters and symbols.',
    'before' => ':attribute :date tarixindən əvvəl olmalıdır.',
    'before_or_equal' => 'The :attribute field must be a date before or equal to :date.',
    'between' => [
        'array' => 'The :attribute field must have between :min and :max items.',
        'file' => 'The :attribute field must be between :min and :max kilobytes.',
        'numeric' => 'The :attribute field must be between :min and :max.',
        'string' => 'The :attribute field must be between :min and :max characters.',
    ],
    'boolean' => ':attribute yalnız doğru və ya yanlış ola bilər.',
    'can' => 'The :attribute field contains an unauthorized value.',
    'confirmed' => ':attribute təsdiqi uyğun gəlmir.',
    'contains' => 'The :attribute field is missing a required value.',
    'current_password' => 'The password is incorrect.',
    'date' => ':attribute düzgün tarix deyil.',
    'date_equals' => 'The :attribute field must be a date equal to :date.',
    'date_format' => ':attribute :format formatına uyğun deyil.',
    'decimal' => 'The :attribute field must have :decimal decimal places.',
    'declined' => ':attribute rədd edilməlidir.',
    'declined_if' => 'The :attribute field must be declined when :other is :value.',
    'different' => ':attribute və :other fərqli olmalıdır.',
    'digits' => ':attribute :digits rəqəmdən ibarət olmalıdır.',
    'digits_between' => 'The :attribute field must be between :min and :max digits.',
    'dimensions' => 'The :attribute field has invalid image dimensions.',
    'distinct' => 'The :attribute field has a duplicate value.',
    'doesnt_contain' => 'The :attribute field must not contain any of the following: :values.',
    'doesnt_end_with' => 'The :attribute field must not end with one of the following: :values.',
    'doesnt_start_with' => 'The :attribute field must not start with one of the following: :values.',
    'email' => ':attribute düzgün e-poçt ünvanı olmalıdır.',
    'encoding' => 'The :attribute field must be encoded in :encoding.',
    'ends_with' => 'The :attribute field must end with one of the following: :values.',
    'enum' => 'The selected :attribute is invalid.',
    'exists' => 'Seçilmiş :attribute yanlışdır.',
    'extensions' => 'The :attribute field must have one of the following extensions: :values.',
    'file' => ':attribute fayl olmalıdır.',
    'filled' => ':attribute doldurulmalıdır.',
    'gt' => [
        'array' => 'The :attribute field must have more than :value items.',
        'file' => 'The :attribute field must be greater than :value kilobytes.',
        'numeric' => 'The :attribute field must be greater than :value.',
        'string' => 'The :attribute field must be greater than :value characters.',
    ],
    'gte' => [
        'array' => 'The :attribute field must have :value items or more.',
        'file' => 'The :attribute field must be greater than or equal to :value kilobytes.',
        'numeric' => 'The :attribute field must be greater than or equal to :value.',
        'string' => 'The :attribute field must be greater than or equal to :value characters.',
    ],
    'hex_color' => 'The :attribute field must be a valid hexadecimal color.',
    'image' => ':attribute şəkil olmalıdır.',
    'in' => 'Seçilmiş :attribute yanlışdır.',
    'in_array' => 'The :attribute field must exist in :other.',
    'in_array_keys' => 'The :attribute field must contain at least one of the following keys: :values.',
    'integer' => ':attribute tam rəqəm olmalıdır.',
    'ip' => 'The :attribute field must be a valid IP address.',
    'ipv4' => 'The :attribute field must be a valid IPv4 address.',
    'ipv6' => 'The :attribute field must be a valid IPv6 address.',
    'json' => 'The :attribute field must be a valid JSON string.',
    'list' => 'The :attribute field must be a list.',
    'lowercase' => 'The :attribute field must be lowercase.',
    'lt' => [
        'array' => 'The :attribute field must have less than :value items.',
        'file' => 'The :attribute field must be less than :value kilobytes.',
        'numeric' => 'The :attribute field must be less than :value.',
        'string' => 'The :attribute field must be less than :value characters.',
    ],
    'lte' => [
        'array' => 'The :attribute field must not have more than :value items.',
        'file' => 'The :attribute field must be less than or equal to :value kilobytes.',
        'numeric' => 'The :attribute field must be less than or equal to :value.',
        'string' => 'The :attribute field must be less than or equal to :value characters.',
    ],
    'mac_address' => 'The :attribute field must be a valid MAC address.',
    'max' => [
        'array' => ':attribute :max elementdən çox ola bilməz.',
        'file' => ':attribute :max kilobaytdan böyük ola bilməz.',
        'numeric' => ':attribute :max dəyərindən böyük ola bilməz.',
        'string' => ':attribute :max simvoldan uzun ola bilməz.',
    ],
    'max_digits' => 'The :attribute field must not have more than :max digits.',
    'mimes' => ':attribute :values tipli fayl olmalıdır.',
    'mimetypes' => ':attribute :values tipli fayl olmalıdır.',
    'min' => [
        'array' => ':attribute ən azı :min element olmalıdır.',
        'file' => ':attribute ən azı :min kilobayt olmalıdır.',
        'numeric' => ':attribute ən azı :min olmalıdır.',
        'string' => ':attribute ən azı :min simvol olmalıdır.',
    ],
    'min_digits' => 'The :attribute field must have at least :min digits.',
    'missing' => 'The :attribute field must be missing.',
    'missing_if' => 'The :attribute field must be missing when :other is :value.',
    'missing_unless' => 'The :attribute field must be missing unless :other is :value.',
    'missing_with' => 'The :attribute field must be missing when :values is present.',
    'missing_with_all' => 'The :attribute field must be missing when :values are present.',
    'multiple_of' => 'The :attribute field must be a multiple of :value.',
    'not_in' => 'Seçilmiş :attribute yanlışdır.',
    'not_regex' => 'The :attribute field format is invalid.',
    'numeric' => ':attribute rəqəm olmalıdır.',
    'password' => [
        'letters' => 'The :attribute field must contain at least one letter.',
        'mixed' => 'The :attribute field must contain at least one uppercase and one lowercase letter.',
        'numbers' => 'The :attribute field must contain at least one number.',
        'symbols' => 'The :attribute field must contain at least one symbol.',
        'uncompromised' => 'The given :attribute has appeared in a data leak. Please choose a different :attribute.',
    ],
    'present' => ':attribute göndərilməlidir.',
    'present_if' => 'The :attribute field must be present when :other is :value.',
    'present_unless' => 'The :attribute field must be present unless :other is :value.',
    'present_with' => 'The :attribute field must be present when :values is present.',
    'present_with_all' => 'The :attribute field must be present when :values are present.',
    'prohibited' => ':attribute qadağandır.',
    'prohibited_if' => 'The :attribute field is prohibited when :other is :value.',
    'prohibited_if_accepted' => 'The :attribute field is prohibited when :other is accepted.',
    'prohibited_if_declined' => 'The :attribute field is prohibited when :other is declined.',
    'prohibited_unless' => 'The :attribute field is prohibited unless :other is in :values.',
    'prohibits' => 'The :attribute field prohibits :other from being present.',
    'regex' => ':attribute formatı yanlışdır.',
    'required' => ':attribute mütləq doldurulmalıdır.',
    'required_array_keys' => 'The :attribute field must contain entries for: :values.',
    'required_if' => ':other :value olduqda :attribute mütləq doldurulmalıdır.',
    'required_if_accepted' => 'The :attribute field is required when :other is accepted.',
    'required_if_declined' => 'The :attribute field is required when :other is declined.',
    'required_unless' => 'The :attribute field is required unless :other is in :values.',
    'required_with' => ':values olduqda :attribute mütləq doldurulmalıdır.',
    'required_with_all' => 'The :attribute field is required when :values are present.',
    'required_without' => ':values olmadıqda :attribute mütləq doldurulmalıdır.',
    'required_without_all' => 'The :attribute field is required when none of :values are present.',
    'same' => ':attribute və :other eyni olmalıdır.',
    'size' => [
        'array' => ':attribute :size element olmalıdır.',
        'file' => ':attribute :size kilobayt olmalıdır.',
        'numeric' => ':attribute :size olmalıdır.',
        'string' => ':attribute :size simvol olmalıdır.',
    ],
    'starts_with' => 'The :attribute field must start with one of the following: :values.',
    'string' => ':attribute mətn olmalıdır.',
    'timezone' => 'The :attribute field must be a valid timezone.',
    'unique' => ':attribute artıq istifadə olunur.',
    'uploaded' => ':attribute yüklənmədi.',
    'uppercase' => 'The :attribute field must be uppercase.',
    'url' => ':attribute düzgün URL olmalıdır.',
    'ulid' => 'The :attribute field must be a valid ULID.',
    'uuid' => ':attribute düzgün UUID olmalıdır.',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Here you may specify custom validation messages for attributes using the
    | convention "attribute.rule" to name the lines. This makes it quick to
    | specify a specific custom language line for a given attribute rule.
    |
    */

    'custom' => [
        'attribute-name' => [
            'rule-name' => 'custom-message',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | The following language lines are used to swap our attribute placeholder
    | with something more reader friendly such as "E-Mail Address" instead
    | of "email". This simply helps us make our message more expressive.
    |
    */

    'attributes' => [
        // Nested rules otherwise surface the raw key, e.g. "sizes.0.price".
        'sizes.*.price' => 'ölçünün qiyməti',
        'sizes.*.wholesale_price' => 'ölçünün topdan qiyməti',
        'sizes.*.discount' => 'ölçünün endirimli qiyməti',
        'sizes.*.size_id' => 'ölçü',
        'colors.*' => 'rəng',
        'images.*.file' => 'şəkil',
        'title.az' => 'başlıq (Azərbaycanca)',
        'description.az' => 'təsvir (Azərbaycanca)',
        'stock_count' => 'stok sayı',
        'wholesale_price' => 'topdan qiymət',
        'discount' => 'endirimli qiymət',
        'category_id' => 'kateqoriya',
        'brand_id' => 'brend',
        'phone' => 'Telefon nömrəsi',
        'email' => 'E-poçt ünvanı',
        'password' => 'Şifrə',
        'password_confirmation' => 'Şifrə təsdiqi',
        'name' => 'Ad',
        'surname' => 'Soyad',
        'otp' => 'OTP kodu',
        'code' => 'Kod',
        'amount' => 'Məbləğ',
        'quantity' => 'Say',
        'price' => 'Qiymət',
        'title' => 'Başlıq',
        'description' => 'Təsvir',
        'image' => 'Şəkil',
        'images' => 'Şəkillər',
        'video' => 'Video',
        'address' => 'Ünvan',
        'note' => 'Qeyd',
        'product_id' => 'Məhsul',
        'category_id' => 'Kateqoriya',
        'brand_id' => 'Brend',
        'color_id' => 'Rəng',
        'size_id' => 'Ölçü',
        'stock_count' => 'Stok sayı',
        'owner_full_name' => 'Sahibin adı və soyadı',
        'identity_front' => 'Vəsiqənin ön hissəsi',
        'identity_back' => 'Vəsiqənin arxa hissəsi',
        'logo' => 'Loqo',
        'instructions_accepted' => 'Satıcı təlimatı',
        'rejection_reason' => 'İmtina səbəbi',
        'status' => 'Status',
        'payment_type' => 'Ödəniş növü',
        'sku' => 'SKU',
        'weight' => 'Çəki',
        'discount' => 'Endirim',
        'promo_code' => 'Promo kod',
        'transaction_id' => 'Əməliyyat nömrəsi',
    ],

];
