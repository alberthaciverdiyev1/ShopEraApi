<?php

/**
 * Real product catalogue data for ProductDatabaseSeeder.
 * Prices are in AZN. `discount` is the sale price (absolute value, 0 = no sale).
 * Titles are kept in all four supported locales.
 */
return [
    // ---------------- Electronics / Phones ----------------
    [
        'category' => 'Phones', 'brand' => 'Apple',
        'title' => ['az' => 'Apple iPhone 15 Pro 256GB', 'en' => 'Apple iPhone 15 Pro 256GB', 'ru' => 'Apple iPhone 15 Pro 256GB', 'tr' => 'Apple iPhone 15 Pro 256GB'],
        'price' => 2799, 'discount' => 2549, 'stock' => 18, 'weight' => 0.22, 'colors' => ['Black', 'Blue', 'White'], 'views' => 4820, 'sales' => 126, 'pinned' => true,
    ],
    [
        'category' => 'Phones', 'brand' => 'Apple',
        'title' => ['az' => 'Apple iPhone 14 128GB', 'en' => 'Apple iPhone 14 128GB', 'ru' => 'Apple iPhone 14 128GB', 'tr' => 'Apple iPhone 14 128GB'],
        'price' => 1999, 'discount' => 1799, 'stock' => 24, 'weight' => 0.20, 'colors' => ['Black', 'Blue', 'Purple'], 'views' => 3610, 'sales' => 98,
    ],
    [
        'category' => 'Phones', 'brand' => 'Samsung',
        'title' => ['az' => 'Samsung Galaxy S24 Ultra 512GB', 'en' => 'Samsung Galaxy S24 Ultra 512GB', 'ru' => 'Samsung Galaxy S24 Ultra 512GB', 'tr' => 'Samsung Galaxy S24 Ultra 512GB'],
        'price' => 2899, 'discount' => 2699, 'stock' => 15, 'weight' => 0.23, 'colors' => ['Black', 'Gray'], 'views' => 5240, 'sales' => 143, 'pinned' => true,
    ],
    [
        'category' => 'Phones', 'brand' => 'Samsung',
        'title' => ['az' => 'Samsung Galaxy A55 5G 256GB', 'en' => 'Samsung Galaxy A55 5G 256GB', 'ru' => 'Samsung Galaxy A55 5G 256GB', 'tr' => 'Samsung Galaxy A55 5G 256GB'],
        'price' => 899, 'discount' => 799, 'stock' => 40, 'weight' => 0.21, 'colors' => ['Blue', 'Black'], 'views' => 2980, 'sales' => 210,
    ],
    [
        'category' => 'Phones', 'brand' => 'Xiaomi',
        'title' => ['az' => 'Xiaomi Redmi Note 13 Pro 256GB', 'en' => 'Xiaomi Redmi Note 13 Pro 256GB', 'ru' => 'Xiaomi Redmi Note 13 Pro 256GB', 'tr' => 'Xiaomi Redmi Note 13 Pro 256GB'],
        'price' => 649, 'discount' => 579, 'stock' => 55, 'weight' => 0.19, 'colors' => ['Black', 'Green'], 'views' => 4120, 'sales' => 312,
    ],
    [
        'category' => 'Phones', 'brand' => 'Xiaomi',
        'title' => ['az' => 'Xiaomi 14 256GB', 'en' => 'Xiaomi 14 256GB', 'ru' => 'Xiaomi 14 256GB', 'tr' => 'Xiaomi 14 256GB'],
        'price' => 1799, 'discount' => 0, 'stock' => 20, 'weight' => 0.19, 'colors' => ['Black', 'White'], 'views' => 2210, 'sales' => 74,
    ],
    [
        'category' => 'Phones', 'brand' => 'Huawei',
        'title' => ['az' => 'Huawei Nova 12i 128GB', 'en' => 'Huawei Nova 12i 128GB', 'ru' => 'Huawei Nova 12i 128GB', 'tr' => 'Huawei Nova 12i 128GB'],
        'price' => 549, 'discount' => 499, 'stock' => 30, 'weight' => 0.19, 'colors' => ['Black', 'Blue'], 'views' => 1540, 'sales' => 61,
    ],

    // ---------------- Electronics / Laptops ----------------
    [
        'category' => 'Laptops', 'brand' => 'Apple',
        'title' => ['az' => 'Apple MacBook Air 13" M3 256GB', 'en' => 'Apple MacBook Air 13" M3 256GB', 'ru' => 'Apple MacBook Air 13" M3 256GB', 'tr' => 'Apple MacBook Air 13" M3 256GB'],
        'price' => 2599, 'discount' => 2399, 'stock' => 12, 'weight' => 1.24, 'colors' => ['Silver', 'Gray'], 'views' => 3980, 'sales' => 84, 'pinned' => true,
    ],
    [
        'category' => 'Laptops', 'brand' => 'Asus',
        'title' => ['az' => 'Asus Vivobook 15 i5 16GB', 'en' => 'Asus Vivobook 15 i5 16GB', 'ru' => 'Asus Vivobook 15 i5 16GB', 'tr' => 'Asus Vivobook 15 i5 16GB'],
        'price' => 1299, 'discount' => 1149, 'stock' => 22, 'weight' => 1.70, 'colors' => ['Silver', 'Black'], 'views' => 2340, 'sales' => 96,
    ],
    [
        'category' => 'Laptops', 'brand' => 'Lenovo',
        'title' => ['az' => 'Lenovo IdeaPad Slim 3 15', 'en' => 'Lenovo IdeaPad Slim 3 15', 'ru' => 'Lenovo IdeaPad Slim 3 15', 'tr' => 'Lenovo IdeaPad Slim 3 15'],
        'price' => 999, 'discount' => 899, 'stock' => 25, 'weight' => 1.62, 'colors' => ['Gray', 'Blue'], 'views' => 1870, 'sales' => 73,
    ],
    [
        'category' => 'Laptops', 'brand' => 'HP',
        'title' => ['az' => 'HP Pavilion 15 i7 16GB', 'en' => 'HP Pavilion 15 i7 16GB', 'ru' => 'HP Pavilion 15 i7 16GB', 'tr' => 'HP Pavilion 15 i7 16GB'],
        'price' => 1599, 'discount' => 1449, 'stock' => 14, 'weight' => 1.75, 'colors' => ['Silver'], 'views' => 2050, 'sales' => 52,
    ],
    [
        'category' => 'Laptops', 'brand' => 'Dell',
        'title' => ['az' => 'Dell Inspiron 15 3530', 'en' => 'Dell Inspiron 15 3530', 'ru' => 'Dell Inspiron 15 3530', 'tr' => 'Dell Inspiron 15 3530'],
        'price' => 1099, 'discount' => 999, 'stock' => 18, 'weight' => 1.65, 'colors' => ['Black'], 'views' => 1620, 'sales' => 48,
    ],
    [
        'category' => 'Laptops', 'brand' => 'Acer',
        'title' => ['az' => 'Acer Aspire 5 15', 'en' => 'Acer Aspire 5 15', 'ru' => 'Acer Aspire 5 15', 'tr' => 'Acer Aspire 5 15'],
        'price' => 899, 'discount' => 0, 'stock' => 20, 'weight' => 1.78, 'colors' => ['Gray'], 'views' => 1310, 'sales' => 39,
    ],

    // ---------------- Electronics / Tablets ----------------
    [
        'category' => 'Tablets', 'brand' => 'Apple',
        'title' => ['az' => 'Apple iPad 10.9" 64GB', 'en' => 'Apple iPad 10.9" 64GB', 'ru' => 'Apple iPad 10.9" 64GB', 'tr' => 'Apple iPad 10.9" 64GB'],
        'price' => 1099, 'discount' => 999, 'stock' => 16, 'weight' => 0.48, 'colors' => ['Silver', 'Blue'], 'views' => 2140, 'sales' => 67,
    ],
    [
        'category' => 'Tablets', 'brand' => 'Samsung',
        'title' => ['az' => 'Samsung Galaxy Tab A9+ 128GB', 'en' => 'Samsung Galaxy Tab A9+ 128GB', 'ru' => 'Samsung Galaxy Tab A9+ 128GB', 'tr' => 'Samsung Galaxy Tab A9+ 128GB'],
        'price' => 649, 'discount' => 599, 'stock' => 24, 'weight' => 0.48, 'colors' => ['Gray', 'Silver'], 'views' => 1420, 'sales' => 58,
    ],
    [
        'category' => 'Tablets', 'brand' => 'Xiaomi',
        'title' => ['az' => 'Xiaomi Pad 6 128GB', 'en' => 'Xiaomi Pad 6 128GB', 'ru' => 'Xiaomi Pad 6 128GB', 'tr' => 'Xiaomi Pad 6 128GB'],
        'price' => 749, 'discount' => 0, 'stock' => 18, 'weight' => 0.49, 'colors' => ['Gray', 'Blue'], 'views' => 1130, 'sales' => 34,
    ],

    // ---------------- Electronics / TVs ----------------
    [
        'category' => 'TVs', 'brand' => 'Samsung',
        'title' => ['az' => 'Samsung 55" Crystal UHD 4K', 'en' => 'Samsung 55" Crystal UHD 4K', 'ru' => 'Samsung 55" Crystal UHD 4K', 'tr' => 'Samsung 55" Crystal UHD 4K'],
        'price' => 1499, 'discount' => 1299, 'stock' => 10, 'weight' => 15.5, 'colors' => ['Black'], 'views' => 1760, 'sales' => 29,
    ],
    [
        'category' => 'TVs', 'brand' => 'LG',
        'title' => ['az' => 'LG 50" UHD 4K Smart TV', 'en' => 'LG 50" UHD 4K Smart TV', 'ru' => 'LG 50" UHD 4K Smart TV', 'tr' => 'LG 50" UHD 4K Smart TV'],
        'price' => 1199, 'discount' => 1099, 'stock' => 12, 'weight' => 12.8, 'colors' => ['Black'], 'views' => 1450, 'sales' => 26,
    ],
    [
        'category' => 'TVs', 'brand' => 'Sony',
        'title' => ['az' => 'Sony 65" Bravia XR 4K', 'en' => 'Sony 65" Bravia XR 4K', 'ru' => 'Sony 65" Bravia XR 4K', 'tr' => 'Sony 65" Bravia XR 4K'],
        'price' => 3499, 'discount' => 3199, 'stock' => 6, 'weight' => 22.0, 'colors' => ['Black'], 'views' => 980, 'sales' => 11,
    ],
    [
        'category' => 'TVs', 'brand' => 'Xiaomi',
        'title' => ['az' => 'Xiaomi TV A2 43"', 'en' => 'Xiaomi TV A2 43"', 'ru' => 'Xiaomi TV A2 43"', 'tr' => 'Xiaomi TV A2 43"'],
        'price' => 699, 'discount' => 649, 'stock' => 20, 'weight' => 8.4, 'colors' => ['Black'], 'views' => 1320, 'sales' => 44,
    ],

    // ---------------- Fashion / Men ----------------
    [
        'category' => 'Men', 'brand' => "Levi's", 'gender' => 0,
        'title' => ['az' => "Levi's 501 Original Cins şalvar", 'en' => "Levi's 501 Original Jeans", 'ru' => "Levi's 501 Original Джинсы", 'tr' => "Levi's 501 Original Kot Pantolon"],
        'price' => 129, 'discount' => 99, 'stock' => 40, 'colors' => ['Blue', 'Black'], 'sizes' => ['S', 'M', 'L', 'XL', 'XXL'], 'views' => 3210, 'sales' => 187,
    ],
    [
        'category' => 'Men', 'brand' => 'Tommy Hilfiger', 'gender' => 0,
        'title' => ['az' => 'Tommy Hilfiger Klassik Polo', 'en' => 'Tommy Hilfiger Classic Polo', 'ru' => 'Tommy Hilfiger Классическое поло', 'tr' => 'Tommy Hilfiger Klasik Polo'],
        'price' => 89, 'discount' => 0, 'stock' => 50, 'colors' => ['Navy', 'White'], 'sizes' => ['S', 'M', 'L', 'XL'], 'views' => 1540, 'sales' => 92,
    ],
    [
        'category' => 'Men', 'brand' => 'Calvin Klein', 'gender' => 0,
        'title' => ['az' => 'Calvin Klein Pambıq T-shirt', 'en' => 'Calvin Klein Cotton T-Shirt', 'ru' => 'Calvin Klein Хлопковая футболка', 'tr' => 'Calvin Klein Pamuklu Tişört'],
        'price' => 59, 'discount' => 49, 'stock' => 60, 'colors' => ['White', 'Black'], 'sizes' => ['XS', 'S', 'M', 'L', 'XL'], 'views' => 2010, 'sales' => 143,
    ],
    [
        'category' => 'Men', 'brand' => 'Nike', 'gender' => 0,
        'title' => ['az' => 'Nike Sportswear Club Hudi', 'en' => 'Nike Sportswear Club Hoodie', 'ru' => 'Nike Sportswear Club Худи', 'tr' => 'Nike Sportswear Club Kapüşonlu'],
        'price' => 109, 'discount' => 89, 'stock' => 45, 'colors' => ['Gray', 'Black'], 'sizes' => ['S', 'M', 'L', 'XL'], 'views' => 2680, 'sales' => 121,
    ],
    [
        'category' => 'Men', 'brand' => 'Adidas', 'gender' => 0,
        'title' => ['az' => 'Adidas Essentials 3-Zolaqlı Şalvar', 'en' => 'Adidas Essentials 3-Stripes Track Pants', 'ru' => 'Adidas Essentials Штаны с 3 полосками', 'tr' => 'Adidas Essentials 3 Şeritli Eşofman'],
        'price' => 89, 'discount' => 0, 'stock' => 38, 'colors' => ['Black'], 'sizes' => ['S', 'M', 'L', 'XL'], 'views' => 1890, 'sales' => 77,
    ],

    // ---------------- Fashion / Women ----------------
    [
        'category' => 'Women', 'brand' => 'Zara', 'gender' => 1,
        'title' => ['az' => 'Zara Çiçəkli Midi Paltar', 'en' => 'Zara Floral Midi Dress', 'ru' => 'Zara Платье миди в цветочек', 'tr' => 'Zara Çiçekli Midi Elbise'],
        'price' => 119, 'discount' => 89, 'stock' => 30, 'colors' => ['Pink', 'White'], 'sizes' => ['XS', 'S', 'M', 'L'], 'views' => 2450, 'sales' => 104,
    ],
    [
        'category' => 'Women', 'brand' => 'H&M', 'gender' => 1,
        'title' => ['az' => 'H&M Oversize Trikotaj Sviter', 'en' => 'H&M Oversized Knit Sweater', 'ru' => 'H&M Оверсайз свитер', 'tr' => 'H&M Oversize Triko Kazak'],
        'price' => 69, 'discount' => 0, 'stock' => 42, 'colors' => ['Beige', 'White'], 'sizes' => ['S', 'M', 'L', 'XL'], 'views' => 1670, 'sales' => 88,
    ],
    [
        'category' => 'Women', 'brand' => 'Mango', 'gender' => 1,
        'title' => ['az' => 'Mango Süni Dəri Gödəkçə', 'en' => 'Mango Faux Leather Jacket', 'ru' => 'Mango Куртка из экокожи', 'tr' => 'Mango Suni Deri Ceket'],
        'price' => 159, 'discount' => 129, 'stock' => 22, 'colors' => ['Black', 'Brown'], 'sizes' => ['XS', 'S', 'M', 'L'], 'views' => 1390, 'sales' => 46,
    ],
    [
        'category' => 'Women', 'brand' => 'Puma', 'gender' => 1,
        'title' => ['az' => 'Puma Qadın Essentials Legginqs', 'en' => "Puma Women's Essentials Leggings", 'ru' => 'Puma Женские леггинсы Essentials', 'tr' => 'Puma Kadın Essentials Tayt'],
        'price' => 59, 'discount' => 49, 'stock' => 55, 'colors' => ['Black'], 'sizes' => ['XS', 'S', 'M', 'L'], 'views' => 1820, 'sales' => 132,
    ],

    // ---------------- Fashion / Kids ----------------
    [
        'category' => 'Kids', 'brand' => 'H&M', 'gender' => 2,
        'title' => ['az' => 'H&M Uşaq Pambıq Hudi', 'en' => 'H&M Kids Cotton Hoodie', 'ru' => 'H&M Детское худи из хлопка', 'tr' => 'H&M Çocuk Pamuklu Kapüşonlu'],
        'price' => 39, 'discount' => 0, 'stock' => 60, 'colors' => ['Blue', 'Pink'], 'sizes' => ['XS', 'S', 'M', 'L'], 'views' => 1240, 'sales' => 96,
    ],
    [
        'category' => 'Kids', 'brand' => 'Nike', 'gender' => 2,
        'title' => ['az' => 'Nike Uşaq Downshifter İdman Ayaqqabısı', 'en' => 'Nike Kids Downshifter Sneakers', 'ru' => 'Nike Детские кроссовки Downshifter', 'tr' => 'Nike Çocuk Downshifter Spor Ayakkabı'],
        'price' => 79, 'discount' => 65, 'stock' => 30, 'colors' => ['Black', 'Blue'], 'sizes' => ['36', '37', '38', '39', '40'], 'views' => 1130, 'sales' => 51,
    ],

    // ---------------- Fashion / Shoes ----------------
    [
        'category' => 'Shoes', 'brand' => 'Nike',
        'title' => ['az' => "Nike Air Force 1 '07", 'en' => "Nike Air Force 1 '07", 'ru' => "Nike Air Force 1 '07", 'tr' => "Nike Air Force 1 '07"],
        'price' => 189, 'discount' => 169, 'stock' => 35, 'colors' => ['White'], 'sizes' => ['36', '37', '38', '39', '40', '41', '42', '43', '44'], 'views' => 4890, 'sales' => 245, 'pinned' => true,
    ],
    [
        'category' => 'Shoes', 'brand' => 'Adidas',
        'title' => ['az' => 'Adidas Ultraboost 22', 'en' => 'Adidas Ultraboost 22', 'ru' => 'Adidas Ultraboost 22', 'tr' => 'Adidas Ultraboost 22'],
        'price' => 229, 'discount' => 199, 'stock' => 28, 'colors' => ['Black', 'Gray'], 'sizes' => ['38', '39', '40', '41', '42', '43', '44'], 'views' => 3670, 'sales' => 168,
    ],
    [
        'category' => 'Shoes', 'brand' => 'New Balance',
        'title' => ['az' => 'New Balance 574', 'en' => 'New Balance 574', 'ru' => 'New Balance 574', 'tr' => 'New Balance 574'],
        'price' => 149, 'discount' => 0, 'stock' => 33, 'colors' => ['Gray', 'Navy'], 'sizes' => ['36', '37', '38', '39', '40', '41', '42', '43'], 'views' => 2890, 'sales' => 112,
    ],
    [
        'category' => 'Shoes', 'brand' => 'Converse',
        'title' => ['az' => 'Converse Chuck Taylor All Star', 'en' => 'Converse Chuck Taylor All Star', 'ru' => 'Converse Chuck Taylor All Star', 'tr' => 'Converse Chuck Taylor All Star'],
        'price' => 109, 'discount' => 95, 'stock' => 40, 'colors' => ['Black', 'White', 'Red'], 'sizes' => ['36', '37', '38', '39', '40', '41', '42', '43', '44'], 'views' => 3120, 'sales' => 176,
    ],
    [
        'category' => 'Shoes', 'brand' => 'Vans',
        'title' => ['az' => 'Vans Old Skool', 'en' => 'Vans Old Skool', 'ru' => 'Vans Old Skool', 'tr' => 'Vans Old Skool'],
        'price' => 119, 'discount' => 0, 'stock' => 37, 'colors' => ['Black', 'Navy'], 'sizes' => ['36', '37', '38', '39', '40', '41', '42', '43'], 'views' => 2340, 'sales' => 118,
    ],
    [
        'category' => 'Shoes', 'brand' => 'Puma',
        'title' => ['az' => 'Puma RS-X', 'en' => 'Puma RS-X', 'ru' => 'Puma RS-X', 'tr' => 'Puma RS-X'],
        'price' => 139, 'discount' => 119, 'stock' => 26, 'colors' => ['White', 'Blue'], 'sizes' => ['37', '38', '39', '40', '41', '42', '43'], 'views' => 1980, 'sales' => 84,
    ],

    // ---------------- Home / Furniture ----------------
    [
        'category' => 'Furniture', 'brand' => 'IKEA',
        'title' => ['az' => 'IKEA Billy Kitab Rəfi', 'en' => 'IKEA Billy Bookcase', 'ru' => 'IKEA Billy Книжный шкаф', 'tr' => 'IKEA Billy Kitaplık'],
        'price' => 89, 'discount' => 0, 'stock' => 25, 'weight' => 28.0, 'colors' => ['White', 'Brown'], 'views' => 1450, 'sales' => 37,
    ],
    [
        'category' => 'Furniture', 'brand' => 'IKEA',
        'title' => ['az' => 'IKEA Malm Çarpayı 160x200', 'en' => 'IKEA Malm Bed Frame 160x200', 'ru' => 'IKEA Malm Каркас кровати 160x200', 'tr' => 'IKEA Malm Yatak Çerçevesi 160x200'],
        'price' => 349, 'discount' => 299, 'stock' => 10, 'weight' => 42.0, 'colors' => ['White', 'Brown'], 'views' => 980, 'sales' => 14,
    ],

    // ---------------- Home / Kitchen ----------------
    [
        'category' => 'Kitchen', 'brand' => 'Tefal',
        'title' => ['az' => 'Tefal Ingenio 10 Hissə Qazan Dəsti', 'en' => 'Tefal Ingenio 10-Piece Cookware Set', 'ru' => 'Tefal Ingenio Набор посуды 10 предметов', 'tr' => 'Tefal Ingenio 10 Parça Tencere Seti'],
        'price' => 249, 'discount' => 209, 'stock' => 15, 'weight' => 6.2, 'colors' => ['Black', 'Silver'], 'views' => 1120, 'sales' => 28,
    ],
    [
        'category' => 'Kitchen', 'brand' => 'Philips',
        'title' => ['az' => 'Philips Airfryer XL', 'en' => 'Philips Airfryer XL', 'ru' => 'Philips Airfryer XL', 'tr' => 'Philips Airfryer XL'],
        'price' => 299, 'discount' => 249, 'stock' => 18, 'weight' => 5.5, 'colors' => ['Black'], 'views' => 2680, 'sales' => 76, 'pinned' => true,
    ],
    [
        'category' => 'Kitchen', 'brand' => 'Bosch',
        'title' => ['az' => 'Bosch MUM5 Mətbəx Maşını', 'en' => 'Bosch MUM5 Kitchen Machine', 'ru' => 'Bosch MUM5 Кухонная машина', 'tr' => 'Bosch MUM5 Mutfak Şefi'],
        'price' => 399, 'discount' => 0, 'stock' => 8, 'weight' => 7.8, 'colors' => ['Silver', 'White'], 'views' => 890, 'sales' => 12,
    ],

    // ---------------- Home / Decor & Lighting ----------------
    [
        'category' => 'Decor', 'brand' => 'IKEA',
        'title' => ['az' => 'IKEA Fejka Süni Bitki', 'en' => 'IKEA Fejka Artificial Plant', 'ru' => 'IKEA Fejka Искусственное растение', 'tr' => 'IKEA Fejka Yapay Bitki'],
        'price' => 19, 'discount' => 0, 'stock' => 80, 'colors' => ['Green'], 'views' => 760, 'sales' => 64,
    ],
    [
        'category' => 'Lighting', 'brand' => 'Philips',
        'title' => ['az' => 'Philips LED Masa Lampası', 'en' => 'Philips LED Desk Lamp', 'ru' => 'Philips LED Настольная лампа', 'tr' => 'Philips LED Masa Lambası'],
        'price' => 59, 'discount' => 49, 'stock' => 40, 'colors' => ['White', 'Black'], 'views' => 1040, 'sales' => 58,
    ],

    // ---------------- Beauty / Makeup ----------------
    [
        'category' => 'Makeup', 'brand' => 'Maybelline',
        'title' => ['az' => 'Maybelline Fit Me Tonmayı', 'en' => 'Maybelline Fit Me Foundation', 'ru' => 'Maybelline Fit Me Тональный крем', 'tr' => 'Maybelline Fit Me Fondöten'],
        'price' => 39, 'discount' => 0, 'stock' => 70, 'colors' => ['Beige', 'Brown'], 'views' => 2210, 'sales' => 187,
    ],
    [
        'category' => 'Makeup', 'brand' => "L'Oréal",
        'title' => ['az' => "L'Oréal Volume Million Lashes Maskara", 'en' => "L'Oréal Volume Million Lashes Mascara", 'ru' => "L'Oréal Volume Million Lashes Тушь", 'tr' => "L'Oréal Volume Million Lashes Maskara"],
        'price' => 45, 'discount' => 39, 'stock' => 55, 'colors' => ['Black'], 'views' => 1890, 'sales' => 134,
    ],
    [
        'category' => 'Makeup', 'brand' => 'Maybelline',
        'title' => ['az' => 'Maybelline SuperStay Matte Ink Pomada', 'en' => 'Maybelline SuperStay Matte Ink Lipstick', 'ru' => 'Maybelline SuperStay Matte Ink Помада', 'tr' => 'Maybelline SuperStay Matte Ink Ruj'],
        'price' => 35, 'discount' => 0, 'stock' => 90, 'colors' => ['Red', 'Pink', 'Purple'], 'views' => 2760, 'sales' => 245,
    ],

    // ---------------- Beauty / Skincare ----------------
    [
        'category' => 'Skincare', 'brand' => 'Nivea',
        'title' => ['az' => 'Nivea Soft Nəmləndirici Krem', 'en' => 'Nivea Soft Moisturizing Cream', 'ru' => 'Nivea Soft Увлажняющий крем', 'tr' => 'Nivea Soft Nemlendirici Krem'],
        'price' => 25, 'discount' => 0, 'stock' => 120, 'views' => 3320, 'sales' => 412,
    ],
    [
        'category' => 'Skincare', 'brand' => 'Garnier',
        'title' => ['az' => 'Garnier C Vitamini Parladıcı Serum', 'en' => 'Garnier Vitamin C Brightening Serum', 'ru' => 'Garnier Сыворотка с витамином C', 'tr' => 'Garnier C Vitamini Aydınlatıcı Serum'],
        'price' => 59, 'discount' => 49, 'stock' => 65, 'views' => 2470, 'sales' => 156,
    ],

    // ---------------- Beauty / Fragrance ----------------
    [
        'category' => 'Fragrance', 'brand' => 'Calvin Klein',
        'title' => ['az' => 'Calvin Klein CK One Eau de Toilette 100ml', 'en' => 'Calvin Klein CK One Eau de Toilette 100ml', 'ru' => 'Calvin Klein CK One Eau de Toilette 100ml', 'tr' => 'Calvin Klein CK One Eau de Toilette 100ml'],
        'price' => 149, 'discount' => 129, 'stock' => 30, 'views' => 1980, 'sales' => 88,
    ],

    // ---------------- Beauty / Hair ----------------
    [
        'category' => 'Hair', 'brand' => "L'Oréal",
        'title' => ['az' => "L'Oréal Elsev Şampun 400ml", 'en' => "L'Oréal Elseve Shampoo 400ml", 'ru' => "L'Oréal Elseve Шампунь 400мл", 'tr' => "L'Oréal Elseve Şampuan 400ml"],
        'price' => 29, 'discount' => 0, 'stock' => 100, 'views' => 2140, 'sales' => 268,
    ],

    // ---------------- Sports / Fitness ----------------
    [
        'category' => 'Fitness', 'brand' => 'Nike',
        'title' => ['az' => 'Nike Pro Dri-FIT Məşq Üstü', 'en' => 'Nike Pro Dri-FIT Training Top', 'ru' => 'Nike Pro Dri-FIT Тренировочный топ', 'tr' => 'Nike Pro Dri-FIT Antrenman Üstü'],
        'price' => 69, 'discount' => 55, 'stock' => 45, 'colors' => ['Black', 'Gray'], 'sizes' => ['S', 'M', 'L', 'XL'], 'views' => 1560, 'sales' => 72,
    ],
    [
        'category' => 'Fitness', 'brand' => 'Adidas',
        'title' => ['az' => 'Adidas Məşq Matı', 'en' => 'Adidas Training Mat', 'ru' => 'Adidas Коврик для тренировок', 'tr' => 'Adidas Antrenman Matı'],
        'price' => 79, 'discount' => 0, 'stock' => 30, 'colors' => ['Black', 'Blue'], 'views' => 980, 'sales' => 41,
    ],

    // ---------------- Sports / Football ----------------
    [
        'category' => 'Football', 'brand' => 'Nike',
        'title' => ['az' => 'Nike Mercurial Futbol Ayaqqabısı', 'en' => 'Nike Mercurial Football Boots', 'ru' => 'Nike Mercurial Футбольные бутсы', 'tr' => 'Nike Mercurial Futbol Kramponu'],
        'price' => 199, 'discount' => 179, 'stock' => 20, 'colors' => ['Red', 'Black'], 'sizes' => ['38', '39', '40', '41', '42', '43', '44'], 'views' => 1780, 'sales' => 58,
    ],
    [
        'category' => 'Football', 'brand' => 'Adidas',
        'title' => ['az' => 'Adidas Predator Futbol Topu', 'en' => 'Adidas Predator Football', 'ru' => 'Adidas Predator Футбольный мяч', 'tr' => 'Adidas Predator Futbol Topu'],
        'price' => 119, 'discount' => 0, 'stock' => 40, 'colors' => ['White', 'Blue'], 'views' => 1340, 'sales' => 83,
    ],

    // ---------------- Sports / Cycling ----------------
    [
        'category' => 'Cycling', 'brand' => 'Trek',
        'title' => ['az' => 'Trek FX 2 Disc Şəhər Velosipedi', 'en' => 'Trek FX 2 Disc City Bike', 'ru' => 'Trek FX 2 Disc Городской велосипед', 'tr' => 'Trek FX 2 Disc Şehir Bisikleti'],
        'price' => 749, 'discount' => 0, 'stock' => 7, 'colors' => ['Black', 'Blue'], 'views' => 760, 'sales' => 9,
    ],
    [
        'category' => 'Cycling', 'brand' => 'Giant',
        'title' => ['az' => 'Giant Escape 3 Hibrid Velosiped', 'en' => 'Giant Escape 3 Hybrid Bike', 'ru' => 'Giant Escape 3 Гибридный велосипед', 'tr' => 'Giant Escape 3 Hibrit Bisiklet'],
        'price' => 699, 'discount' => 649, 'stock' => 6, 'colors' => ['Gray', 'Blue'], 'views' => 640, 'sales' => 7,
    ],

    // ---------------- Sports / Outdoor ----------------
    [
        'category' => 'Outdoor', 'brand' => 'The North Face',
        'title' => ['az' => 'The North Face Resolve 2 Yağış Gödəkçəsi', 'en' => 'The North Face Resolve 2 Rain Jacket', 'ru' => 'The North Face Resolve 2 Дождевик', 'tr' => 'The North Face Resolve 2 Yağmurluk'],
        'price' => 199, 'discount' => 169, 'stock' => 22, 'colors' => ['Black', 'Blue'], 'sizes' => ['S', 'M', 'L', 'XL'], 'views' => 1120, 'sales' => 44,
    ],
    [
        'category' => 'Outdoor', 'brand' => 'Columbia',
        'title' => ['az' => 'Columbia Newton Ridge Trekking Çəkməsi', 'en' => 'Columbia Newton Ridge Hiking Boots', 'ru' => 'Columbia Newton Ridge Туристические ботинки', 'tr' => 'Columbia Newton Ridge Trekking Botu'],
        'price' => 179, 'discount' => 0, 'stock' => 18, 'colors' => ['Brown', 'Black'], 'sizes' => ['39', '40', '41', '42', '43', '44'], 'views' => 840, 'sales' => 26,
    ],
    [
        'category' => 'Outdoor', 'brand' => 'Decathlon',
        'title' => ['az' => 'Decathlon Quechua 2 Nəfərlik Çadır', 'en' => 'Decathlon Quechua 2-Person Tent', 'ru' => 'Decathlon Quechua Палатка на 2 человек', 'tr' => 'Decathlon Quechua 2 Kişilik Çadır'],
        'price' => 129, 'discount' => 0, 'stock' => 15, 'colors' => ['Green', 'Blue'], 'views' => 560, 'sales' => 18,
    ],

    // ---------------- Toys ----------------
    [
        'category' => 'Kids Toys', 'brand' => 'LEGO',
        'title' => ['az' => 'LEGO City Yanğınsöndürmə Stansiyası 60320', 'en' => 'LEGO City Fire Station 60320', 'ru' => 'LEGO City Пожарная станция 60320', 'tr' => 'LEGO City İtfaiye İstasyonu 60320'],
        'price' => 129, 'discount' => 0, 'stock' => 25, 'views' => 1560, 'sales' => 47,
    ],
    [
        'category' => 'Kids Toys', 'brand' => 'LEGO',
        'title' => ['az' => 'LEGO Technic Ferrari 488 GTE', 'en' => 'LEGO Technic Ferrari 488 GTE', 'ru' => 'LEGO Technic Ferrari 488 GTE', 'tr' => 'LEGO Technic Ferrari 488 GTE'],
        'price' => 199, 'discount' => 179, 'stock' => 18, 'views' => 1230, 'sales' => 33,
    ],
    [
        'category' => 'Kids Toys', 'brand' => 'Mattel',
        'title' => ['az' => 'Mattel Barbie Arzular Evi', 'en' => 'Mattel Barbie Dreamhouse', 'ru' => 'Mattel Barbie Дом мечты', 'tr' => 'Mattel Barbie Hayal Evi'],
        'price' => 249, 'discount' => 219, 'stock' => 12, 'views' => 1420, 'sales' => 21,
    ],
    [
        'category' => 'Kids Toys', 'brand' => 'Hasbro',
        'title' => ['az' => 'Hasbro Monopoly Klassik Oyunu', 'en' => 'Hasbro Monopoly Classic', 'ru' => 'Hasbro Monopoly Классическая игра', 'tr' => 'Hasbro Monopoly Klasik'],
        'price' => 59, 'discount' => 0, 'stock' => 45, 'views' => 2140, 'sales' => 118,
    ],
    [
        'category' => 'Kids Toys', 'brand' => 'LEGO',
        'title' => ['az' => 'LEGO Classic Yaradıcı Kərpiclər', 'en' => 'LEGO Classic Creative Bricks', 'ru' => 'LEGO Classic Творческие кирпичики', 'tr' => 'LEGO Classic Yaratıcı Parçalar'],
        'price' => 79, 'discount' => 65, 'stock' => 38, 'views' => 1760, 'sales' => 62,
    ],

    // ---------------- Educational ----------------
    [
        'category' => 'Educational', 'brand' => 'LEGO',
        'title' => ['az' => 'LEGO Education SPIKE Essential', 'en' => 'LEGO Education SPIKE Essential', 'ru' => 'LEGO Education SPIKE Essential', 'tr' => 'LEGO Education SPIKE Essential'],
        'price' => 349, 'discount' => 0, 'stock' => 10, 'views' => 620, 'sales' => 8,
    ],

    // ---------------- Puzzles ----------------
    [
        'category' => 'Puzzles', 'brand' => 'Ravensburger',
        'title' => ['az' => 'Ravensburger 1000 Hissəli Puzzle', 'en' => 'Ravensburger 1000-Piece Puzzle', 'ru' => 'Ravensburger Пазл 1000 деталей', 'tr' => 'Ravensburger 1000 Parça Puzzle'],
        'price' => 39, 'discount' => 0, 'stock' => 50, 'views' => 890, 'sales' => 34,
    ],

    // ---------------- Books (no brand) ----------------
    [
        'category' => 'Fiction',
        'title' => ['az' => 'Gecə Kitabxanası — Mett Heyq', 'en' => 'The Midnight Library — Matt Haig', 'ru' => 'Полуночная библиотека — Мэтт Хейг', 'tr' => 'Gece Yarısı Kütüphanesi — Matt Haig'],
        'price' => 29, 'discount' => 0, 'stock' => 60, 'views' => 1450, 'sales' => 96,
    ],
    [
        'category' => 'Fiction',
        'title' => ['az' => 'Atom Vərdişləri — Ceyms Klir', 'en' => 'Atomic Habits — James Clear', 'ru' => 'Атомные привычки — Джеймс Клир', 'tr' => 'Atomik Alışkanlıklar — James Clear'],
        'price' => 35, 'discount' => 29, 'stock' => 55, 'views' => 2140, 'sales' => 173,
    ],
    [
        'category' => 'Science',
        'title' => ['az' => 'Sapiens — Yuval Noah Harari', 'en' => 'Sapiens — Yuval Noah Harari', 'ru' => 'Sapiens — Юваль Ной Харари', 'tr' => 'Sapiens — Yuval Noah Harari'],
        'price' => 45, 'discount' => 0, 'stock' => 40, 'views' => 1780, 'sales' => 88,
    ],
    [
        'category' => 'Children',
        'title' => ['az' => 'Çox Aclıq Tırtıl — Erik Karl', 'en' => 'The Very Hungry Caterpillar — Eric Carle', 'ru' => 'Очень голодная гусеница — Эрик Карл', 'tr' => 'Aç Tırtıl — Eric Carle'],
        'price' => 25, 'discount' => 0, 'stock' => 70, 'views' => 1230, 'sales' => 112,
    ],

    // ---------------- Grocery ----------------
    [
        'category' => 'Fruits & Vegetables',
        'title' => ['az' => 'Təzə Banan 1 kq', 'en' => 'Fresh Bananas 1kg', 'ru' => 'Свежие бананы 1 кг', 'tr' => 'Taze Muz 1 kg'],
        'price' => 3.5, 'discount' => 0, 'stock' => 200, 'views' => 980, 'sales' => 320,
    ],
    [
        'category' => 'Dairy', 'brand' => 'Danone',
        'title' => ['az' => 'Danone UHT Süd 1L', 'en' => 'Danone UHT Milk 1L', 'ru' => 'Danone Молоко UHT 1л', 'tr' => 'Danone UHT Süt 1L'],
        'price' => 3, 'discount' => 0, 'stock' => 150, 'views' => 760, 'sales' => 410,
    ],
    [
        'category' => 'Beverages', 'brand' => 'Coca-Cola',
        'title' => ['az' => 'Coca-Cola 1.5L', 'en' => 'Coca-Cola 1.5L', 'ru' => 'Coca-Cola 1.5л', 'tr' => 'Coca-Cola 1.5L'],
        'price' => 2.5, 'discount' => 0, 'stock' => 300, 'views' => 1420, 'sales' => 520,
    ],
    [
        'category' => 'Beverages', 'brand' => 'Nestlé',
        'title' => ['az' => 'Nestlé Pure Life Su 5L', 'en' => 'Nestlé Pure Life Water 5L', 'ru' => 'Nestlé Pure Life Вода 5л', 'tr' => 'Nestlé Pure Life Su 5L'],
        'price' => 3, 'discount' => 0, 'stock' => 250, 'views' => 640, 'sales' => 380,
    ],
];
