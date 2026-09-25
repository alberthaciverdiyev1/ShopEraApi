// Package helpers holds Order module helpers (receipt PDF).
package helpers

import (
	"bytes"
	"fmt"
	"os"
	"path/filepath"

	"github.com/go-pdf/fpdf"
)

// ReceiptSummary is the order-summary block.
type ReceiptSummary struct {
	OrderID       int64
	TransactionID string
	OrderTime     string
	ItemsTotal    float64
	Discounts     float64
	Shipping      float64
	Total         float64
}

// ReceiptPickup is the delivery block.
type ReceiptPickup struct {
	City      string
	Town      string
	Street    string
	Apartment string
	Phone     string
}

// ReceiptItem is one product line.
type ReceiptItem struct {
	Title      string
	Quantity   int
	UnitPrice  float64
	TotalPrice float64
}

// ReceiptPromo is the optional promo-code block.
type ReceiptPromo struct {
	Code            string
	DiscountPercent float64
}

// receiptLangs orders the translation tuple.
var receiptLangs = []string{"az", "en", "ru", "tr"}

// receiptLabels maps a key to {az, en, ru, tr} (Laravel receiptPdf translations).
var receiptLabels = map[string][4]string{
	"title":       {"Sifariş Qəbzi", "Order Receipt", "Чек заказа", "Sipariş Makbuzu"},
	"summary":     {"Sifariş Xülasəsi", "Order Summary", "Сводка заказа", "Sipariş Özeti"},
	"order_id":    {"Sifariş ID", "Order ID", "ID заказа", "Sipariş ID"},
	"transaction": {"Transaction ID", "Transaction ID", "ID транзакции", "Transaction ID"},
	"order_time":  {"Sifariş Vaxtı", "Order Time", "Время заказа", "Sipariş Zamanı"},
	"items_total": {"Məhsulların Cəmi", "Items Total", "Итого товаров", "Ürün Toplamı"},
	"discounts":   {"Endirimlər", "Discounts", "Скидки", "İndirimler"},
	"shipping":    {"Çatdırılma Qiyməti", "Shipping Price", "Стоимость доставки", "Kargo Fiyatı"},
	"total":       {"Ümumi", "Total", "Общая сумма", "Toplam"},
	"promo":       {"Promo Kodu", "Promo Code", "Промо код", "Promosyon Kodu"},
	"code":        {"Kod", "Code", "Код", "Kod"},
	"percent":     {"Endirim Faizi", "Discount Percent", "Процент скидки", "İndirim Yüzdesi"},
	"pickup":      {"Çatdırılma", "Pickup", "Доставка", "Teslimat"},
	"city":        {"Şəhər", "City", "Город", "Şehir"},
	"town":        {"Qəsəbə", "Town", "Поселок", "Kasaba"},
	"street":      {"Küçə", "Street", "Улица", "Cadde"},
	"apartment":   {"Bina", "Apartment", "Квартира", "Daire"},
	"phone":       {"Telefon", "Phone", "Телефон", "Telefon"},
	"items":       {"Məhsullar", "Items", "Товары", "Ürünler"},
	"product":     {"Məhsul Adı", "Product Name", "Название товара", "Ürün Adı"},
	"qty":         {"Miqdar", "Quantity", "Количество", "Miktar"},
	"unit":        {"Bir Məhsulun Qiyməti", "Unit Price", "Цена за единицу", "Birim Fiyat"},
	"line_total":  {"Ümumi Qiymət", "Total Price", "Общая цена", "Toplam Fiyat"},
	"disc_amount": {"Endirim Məbləği", "Discount Amount", "Сумма скидки", "İndirim Meblağı"},
	"disc_total":  {"Ümumi Endirimli Qiymət", "Total Discounted Price", "Общая цена со скидкой", "Toplam İndirimli Fiyat"},
	"manat":       {"manat", "AZN", "манат", "AZN"},
}

// BuildReceiptPDF renders the order receipt (Laravel receiptPdf + dompdf).
func BuildReceiptPDF(lang string, summary ReceiptSummary, pickup ReceiptPickup, items []ReceiptItem, promo *ReceiptPromo) ([]byte, error) {
	tr := func(key string) string { return label(lang, key) }
	currency := tr("manat")

	pdf := fpdf.New("P", "mm", "A4", "")
	pdf.SetMargins(15, 15, 15)
	pdf.AddPage()

	if font := findReceiptFont(); font != "" {
		pdf.SetFontLocation(filepath.Dir(font))
		pdf.AddUTF8Font("receipt", "", filepath.Base(font))
		pdf.SetFont("receipt", "", 11)
	} else {
		pdf.SetFont("Helvetica", "", 11)
	}

	pdf.SetFontSize(16)
	pdf.CellFormat(0, 10, tr("title"), "", 1, "L", false, 0, "")
	pdf.Ln(2)

	pdf.SetFontSize(12)
	pdf.CellFormat(0, 8, tr("summary"), "", 1, "L", false, 0, "")
	pdf.SetFontSize(10)
	line := func(labelText, value string) {
		pdf.CellFormat(60, 6, labelText+":", "", 0, "L", false, 0, "")
		pdf.CellFormat(0, 6, value, "", 1, "L", false, 0, "")
	}
	line(tr("order_id"), fmt.Sprintf("%d", summary.OrderID))
	line(tr("transaction"), summary.TransactionID)
	line(tr("order_time"), summary.OrderTime)
	line(tr("items_total"), money(summary.ItemsTotal, currency))
	line(tr("discounts"), money(summary.Discounts, currency))
	line(tr("shipping"), money(summary.Shipping, currency))
	line(tr("total"), money(summary.Total, currency))
	pdf.Ln(3)

	if promo != nil {
		pdf.SetFontSize(12)
		pdf.CellFormat(0, 8, tr("promo"), "", 1, "L", false, 0, "")
		pdf.SetFontSize(10)
		line(tr("code"), promo.Code)
		line(tr("percent"), fmt.Sprintf("%g%%", promo.DiscountPercent))
		pdf.Ln(3)
	}

	pdf.SetFontSize(12)
	pdf.CellFormat(0, 8, tr("pickup"), "", 1, "L", false, 0, "")
	pdf.SetFontSize(10)
	line(tr("city"), pickup.City)
	line(tr("town"), pickup.Town)
	line(tr("street"), pickup.Street)
	line(tr("apartment"), pickup.Apartment)
	line(tr("phone"), pickup.Phone)
	pdf.Ln(3)

	pdf.SetFontSize(12)
	pdf.CellFormat(0, 8, tr("items"), "", 1, "L", false, 0, "")
	pdf.SetFontSize(9)

	hasPromo := promo != nil && promo.DiscountPercent != 0
	widths := []float64{60, 15, 30, 30}
	headers := []string{tr("product"), tr("qty"), tr("unit"), tr("line_total")}
	if hasPromo {
		widths = append(widths, 25, 30)
		headers = append(headers, tr("disc_amount"), tr("disc_total"))
	}
	for i, header := range headers {
		pdf.CellFormat(widths[i], 7, header, "1", 0, "C", false, 0, "")
	}
	pdf.Ln(-1)

	for _, item := range items {
		pdf.CellFormat(widths[0], 6, truncate(item.Title, 42), "1", 0, "L", false, 0, "")
		pdf.CellFormat(widths[1], 6, fmt.Sprintf("%d", item.Quantity), "1", 0, "C", false, 0, "")
		pdf.CellFormat(widths[2], 6, money(item.UnitPrice, currency), "1", 0, "R", false, 0, "")
		pdf.CellFormat(widths[3], 6, money(item.TotalPrice, currency), "1", 0, "R", false, 0, "")
		if hasPromo {
			discount := round2(item.TotalPrice * promo.DiscountPercent / 100)
			pdf.CellFormat(widths[4], 6, money(discount, currency), "1", 0, "R", false, 0, "")
			pdf.CellFormat(widths[5], 6, money(item.TotalPrice-discount, currency), "1", 0, "R", false, 0, "")
		}
		pdf.Ln(-1)
	}

	var buf bytes.Buffer
	if err := pdf.Output(&buf); err != nil {
		return nil, err
	}
	return buf.Bytes(), nil
}

func label(lang, key string) string {
	texts, ok := receiptLabels[key]
	if !ok {
		return key
	}
	for i, code := range receiptLangs {
		if code == lang {
			return texts[i]
		}
	}
	return texts[0]
}

func money(value float64, currency string) string {
	return fmt.Sprintf("%.2f %s", value, currency)
}

func round2(v float64) float64 { return float64(int64(v*100+0.5)) / 100 }

func truncate(value string, max int) string {
	runes := []rune(value)
	if len(runes) <= max {
		return value
	}
	return string(runes[:max-1]) + "…"
}

// findReceiptFont returns a Unicode TTF for Cyrillic/Azerbaijani glyphs.
func findReceiptFont() string {
	if path := os.Getenv("PDF_FONT_PATH"); path != "" {
		if _, err := os.Stat(path); err == nil {
			return path
		}
	}
	candidates := []string{
		"/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf",
		"/usr/share/fonts/TTF/DejaVuSans.ttf",
		"/usr/share/fonts/dejavu/DejaVuSans.ttf",
	}
	for _, path := range candidates {
		if _, err := os.Stat(path); err == nil {
			return path
		}
	}
	return ""
}
