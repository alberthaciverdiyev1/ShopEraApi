<?php
if (!function_exists('receiptPdf')) {
    function receiptPdf($order, array $pickup, array $orderSummary): string
    {
        $locale = app()->getLocale(); // 'az', 'en', 'ru', 'tr'
        $trans = fn(array $texts) => $texts[$locale] ?? reset($texts);

        $itemsHtml = '';
        $hasPromo = !empty($orderSummary['promo']['discount_percent']);
        $promoPercent = $hasPromo ? $orderSummary['promo']['discount_percent'] : 0;

        if (!empty($order->items)) {
            foreach ($order->items as $item) {
                $title = is_array($item->product->title)
                    ? ($item->product->title[$locale] ?? reset($item->product->title))
                    : $item->product->title;
                $unitPrice = round((float) $item->unit_price, 2);
                $lineTotal = round((float) $item->total_price, 2);

                $discountedPriceHtml = '';
                if ($hasPromo) {
                    $discountAmount = round($lineTotal * ($promoPercent / 100), 2);
                    $discountedTotal = round($lineTotal - $discountAmount, 2);
                    $discountedPriceHtml = "<td>{$discountAmount} {$trans(['az' => 'manat', 'en' => 'AZN', 'ru' => 'манат', 'tr' => 'AZN'])}</td>
                                            <td>{$discountedTotal} {$trans(['az' => 'manat', 'en' => 'AZN', 'ru' => 'манат', 'tr' => 'AZN'])}</td>";
                }

                $itemsHtml .= "<tr>
                                    <td>{$title}</td>
                                    <td>{$item->quantity}</td>
                                    <td>{$unitPrice} {$trans(['az' => 'manat', 'en' => 'AZN', 'ru' => 'манат', 'tr' => 'AZN'])}</td>
                                    <td>{$lineTotal} {$trans(['az' => 'manat', 'en' => 'AZN', 'ru' => 'манат', 'tr' => 'AZN'])}</td>
                                    {$discountedPriceHtml}
                               </tr>";
            }
        }

        $promoHtml = '';
        if (!empty($orderSummary['promo'])) {
            $promo = $orderSummary['promo'];
            $promoHtml = "<div class=\"section\">
                              <h3>{$trans(['az' => 'Promo Kodu', 'en' => 'Promo Code', 'ru' => 'Промо код', 'tr' => 'Promosyon Kodu'])}</h3>
                              <p><strong>{$trans(['az' => 'Kod', 'en' => 'Code', 'ru' => 'Код', 'tr' => 'Kod'])}:</strong> {$promo['code']}</p>
                              <p><strong>{$trans(['az' => 'Endirim Faizi', 'en' => 'Discount Percent', 'ru' => 'Процент скидки', 'tr' => 'İndirim Yüzdesi'])}:</strong> {$promo['discount_percent']}%</p>
                          </div>";
        }

        $discountedHeader = $hasPromo
            ? "<th>{$trans(['az' => 'Endirim Məbləği', 'en' => 'Discount Amount', 'ru' => 'Сумма скидки', 'tr' => 'İndirim Meblağı'])}</th>
               <th>{$trans(['az' => 'Ümumi Endirimli Qiymət', 'en' => 'Total Discounted Price', 'ru' => 'Общая цена со скидкой', 'tr' => 'Toplam İndirimli Fiyat'])}</th>"
            : '';

        $htmlContent = "
        <!DOCTYPE html>
        <html>
            <head>
                <meta charset=\"UTF-8\">
                <title>{$trans(['az' => 'Sifariş Qəbzi', 'en' => 'Order Receipt', 'ru' => 'Чек заказа', 'tr' => 'Sipariş Makbuzu'])}</title>
                <style>
                    body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
                    h2 { margin-bottom: 10px; }
                    .section { margin-bottom: 20px; }
                    .table { width: 100%; border-collapse: collapse; margin-top: 10px; }
                    .table th, .table td { border: 1px solid #ccc; padding: 6px; text-align: left; }
                </style>
            </head>
            <body>
                <h2>{$trans(['az' => 'Sifariş Qəbzi', 'en' => 'Order Receipt', 'ru' => 'Чек заказа', 'tr' => 'Sipariş Makbuzu'])}</h2>

                <div class=\"section\">
                    <h3>{$trans(['az' => 'Sifariş Xülasəsi', 'en' => 'Order Summary', 'ru' => 'Сводка заказа', 'tr' => 'Sipariş Özeti'])}</h3>
                    <p><strong>{$trans(['az' => 'Sifariş ID', 'en' => 'Order ID', 'ru' => 'ID заказа', 'tr' => 'Sipariş ID'])}:</strong> {$orderSummary['order_id']}</p>
                    <p><strong>Transaction ID:</strong> {$orderSummary['transaction_id']}</p>
                    <p><strong>{$trans(['az' => 'Sifariş Vaxtı', 'en' => 'Order Time', 'ru' => 'Время заказа', 'tr' => 'Sipariş Zamanı'])}:</strong> {$orderSummary['order_time']}</p>
                    <p><strong>{$trans(['az' => 'Məhsulların Cəmi', 'en' => 'Items Total', 'ru' => 'Итого товаров', 'tr' => 'Ürün Toplamı'])}:</strong> {$orderSummary['items_totals']}</p>
                    <p><strong>{$trans(['az' => 'Endirimlər', 'en' => 'Discounts', 'ru' => 'Скидки', 'tr' => 'İndirimler'])}:</strong> {$orderSummary['items_discounts']}</p>
                    <p><strong>{$trans(['az' => 'Çatdırılma Qiyməti', 'en' => 'Shipping Price', 'ru' => 'Стоимость доставки', 'tr' => 'Kargo Fiyatı'])}:</strong> {$orderSummary['shipping']}</p>
                    <p><strong>{$trans(['az' => 'Ümumi', 'en' => 'Total', 'ru' => 'Общая сумма', 'tr' => 'Toplam'])}:</strong> {$orderSummary['total']}</p>
                </div>

                {$promoHtml}

                <div class=\"section\">
                    <h3>{$trans(['az' => 'Çatdırılma', 'en' => 'Pickup', 'ru' => 'Доставка', 'tr' => 'Teslimat'])}</h3>
                    <p><strong>{$trans(['az' => 'Şəhər', 'en' => 'City', 'ru' => 'Город', 'tr' => 'Şehir'])}:</strong> {$pickup['city']}</p>
                    <p><strong>{$trans(['az' => 'Qəsəbə', 'en' => 'Town', 'ru' => 'Поселок', 'tr' => 'Kasaba'])}:</strong> {$pickup['town']}</p>
                    <p><strong>{$trans(['az' => 'Küçə', 'en' => 'Street', 'ru' => 'Улица', 'tr' => 'Cadde'])}:</strong> {$pickup['street']}</p>
                    <p><strong>{$trans(['az' => 'Bina', 'en' => 'Apartment', 'ru' => 'Квартира', 'tr' => 'Daire'])}:</strong> {$pickup['apartment']}</p>
                    <p><strong>{$trans(['az' => 'Telefon', 'en' => 'Phone', 'ru' => 'Телефон', 'tr' => 'Telefon'])}:</strong> {$pickup['phone']}</p>
                </div>

                <div class=\"section\">
                    <h3>{$trans(['az' => 'Məhsullar', 'en' => 'Items', 'ru' => 'Товары', 'tr' => 'Ürünler'])}</h3>
                    <table class=\"table\">
                        <thead>
                            <tr>
                                <th>{$trans(['az' => 'Məhsul Adı', 'en' => 'Product Name', 'ru' => 'Название товара', 'tr' => 'Ürün Adı'])}</th>
                                <th>{$trans(['az' => 'Miqdar', 'en' => 'Quantity', 'ru' => 'Количество', 'tr' => 'Miktar'])}</th>
                                <th>{$trans(['az' => 'Bir Məhsulun Qiyməti', 'en' => 'Unit Price', 'ru' => 'Цена за единицу', 'tr' => 'Birim Fiyat'])}</th>
                                <th>{$trans(['az' => 'Ümumi Qiymət', 'en' => 'Total Price', 'ru' => 'Общая цена', 'tr' => 'Toplam Fiyat'])}</th>
                                {$discountedHeader}
                            </tr>
                        </thead>
                        <tbody>
                            {$itemsHtml}
                        </tbody>
                    </table>
                </div>

            </body>
        </html>";

        return $htmlContent;
    }
}
