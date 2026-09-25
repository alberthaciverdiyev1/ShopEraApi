package tests

import (
	"bytes"
	"testing"

	orderhelpers "shopera/internal/modules/order/helpers"
)

func TestBuildReceiptPDF(t *testing.T) {
	pdf, err := orderhelpers.BuildReceiptPDF(
		"az",
		orderhelpers.ReceiptSummary{
			OrderID: 42, TransactionID: "TX-42", OrderTime: "2026-09-25 10:00:00",
			ItemsTotal: 100, Discounts: 10, Shipping: 5, Total: 95,
		},
		orderhelpers.ReceiptPickup{City: "Bakı", Town: "Nərimanov", Street: "1", Apartment: "2", Phone: "+994501112233"},
		[]orderhelpers.ReceiptItem{{Title: "Ayaqqabı", Quantity: 2, UnitPrice: 50, TotalPrice: 100}},
		&orderhelpers.ReceiptPromo{Code: "SALE10", DiscountPercent: 10},
	)
	if err != nil {
		t.Fatalf("BuildReceiptPDF: %v", err)
	}
	if !bytes.HasPrefix(pdf, []byte("%PDF")) {
		t.Fatalf("expected PDF header, got %q", pdf[:min(8, len(pdf))])
	}
}

func min(a, b int) int {
	if a < b {
		return a
	}
	return b
}
