// Package models holds the Order GORM models and the status enum.
package models

// OrderStatus mirrors App\Enums\OrderStatus.
type OrderStatus int

const (
	StatusPlaced         OrderStatus = 0
	StatusProcessing     OrderStatus = 1
	StatusDelivered      OrderStatus = 2
	StatusReturned       OrderStatus = 3
	StatusWaitingPayment OrderStatus = 4
	StatusFailed         OrderStatus = 5
	StatusCancelled      OrderStatus = 6
	StatusAdminWaiting   OrderStatus = 7
)

// Label is the human-readable status label.
func (s OrderStatus) Label() string {
	switch s {
	case StatusPlaced:
		return "Order Placed"
	case StatusProcessing:
		return "Processing"
	case StatusDelivered:
		return "Delivered"
	case StatusReturned:
		return "Returned"
	case StatusWaitingPayment:
		return "Waiting Payment"
	case StatusFailed:
		return "Failed"
	case StatusCancelled:
		return "Cancelled"
	case StatusAdminWaiting:
		return "Admin Waiting"
	default:
		return ""
	}
}

// Key is the enum name.
func (s OrderStatus) Key() string {
	switch s {
	case StatusPlaced:
		return "PLACED"
	case StatusProcessing:
		return "PROCESSING"
	case StatusDelivered:
		return "DELIVERED"
	case StatusReturned:
		return "RETURNED"
	case StatusWaitingPayment:
		return "WAITING_PAYMENT"
	case StatusFailed:
		return "FAILED"
	case StatusCancelled:
		return "CANCELLED"
	case StatusAdminWaiting:
		return "ADMIN_WAITING"
	default:
		return ""
	}
}

// IsPaid reports whether the status counts as paid.
func (s OrderStatus) IsPaid() bool {
	return s == StatusPlaced || s == StatusProcessing || s == StatusDelivered
}

// AllStatuses lists every value (for statistics).
func AllStatuses() []OrderStatus {
	return []OrderStatus{StatusPlaced, StatusProcessing, StatusDelivered, StatusReturned,
		StatusWaitingPayment, StatusFailed, StatusCancelled, StatusAdminWaiting}
}
