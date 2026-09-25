package repositories

import (
	"time"

	"gorm.io/gorm"
)

// StatisticRepository runs the cross-table aggregate queries for the admin
// statistics endpoint (read-only).
type StatisticRepository struct {
	db *gorm.DB
}

func NewStatisticRepository(db *gorm.DB) *StatisticRepository {
	return &StatisticRepository{db: db}
}

// ProductSold is a product id with the units sold since a date.
type ProductSold struct {
	ProductID int64
	TotalSold int64
}

// TopCustomer is an aggregated buyer row.
type TopCustomer struct {
	ID          int64
	Name        *string
	Email       *string
	Phone       *string
	OrdersCount int64
	TotalSpent  float64
}

// CityCount is the number of orders per address city key.
type CityCount struct {
	City        string
	OrdersCount int64
}

// TopProducts returns the best-selling products since from (by quantity).
func (r *StatisticRepository) TopProducts(from time.Time, limit int) ([]ProductSold, error) {
	var rows []ProductSold
	err := r.db.Table("order_items oi").
		Select("oi.product_id AS product_id, SUM(oi.quantity) AS total_sold").
		Joins("JOIN orders o ON o.id = oi.order_id").
		Where("o.created_at >= ? AND o.deleted_at IS NULL AND oi.deleted_at IS NULL", from).
		Group("oi.product_id").
		Order("total_sold DESC").
		Limit(limit).
		Scan(&rows).Error
	return rows, err
}

// TopCustomers returns the highest-spending buyers since from.
func (r *StatisticRepository) TopCustomers(from time.Time, limit int) ([]TopCustomer, error) {
	var rows []TopCustomer
	err := r.db.Table("orders o").
		Select("o.user_id AS id, u.name AS name, u.email AS email, u.phone AS phone, COUNT(*) AS orders_count, COALESCE(SUM(o.total_price), 0) AS total_spent").
		Joins("JOIN users u ON u.id = o.user_id").
		Where("o.created_at >= ? AND o.deleted_at IS NULL", from).
		Group("o.user_id, u.name, u.email, u.phone").
		Order("total_spent DESC").
		Limit(limit).
		Scan(&rows).Error
	return rows, err
}

// CityOrders returns order counts per address city key since from.
func (r *StatisticRepository) CityOrders(from time.Time) ([]CityCount, error) {
	var rows []CityCount
	err := r.db.Table("orders o").
		Select("COALESCE(a.city, 'Unknown') AS city, COUNT(*) AS orders_count").
		Joins("LEFT JOIN user_addresses a ON a.id = o.address_id AND a.deleted_at IS NULL").
		Where("o.created_at >= ? AND o.deleted_at IS NULL", from).
		Group("COALESCE(a.city, 'Unknown')").
		Order("orders_count DESC").
		Scan(&rows).Error
	return rows, err
}

// CityLabels returns key → name for every city (including soft-deleted).
func (r *StatisticRepository) CityLabels() (map[string]string, error) {
	var rows []struct {
		Key  string
		Name string
	}
	err := r.db.Table("cities").Select("key, name").Scan(&rows).Error
	if err != nil {
		return nil, err
	}
	labels := make(map[string]string, len(rows))
	for _, row := range rows {
		labels[row.Key] = row.Name
	}
	return labels, nil
}

// DiscountedProductsCount counts products currently on sale.
func (r *StatisticRepository) DiscountedProductsCount() (int64, error) {
	var count int64
	err := r.db.Table("products").
		Where("discount IS NOT NULL AND discount < price AND deleted_at IS NULL").
		Count(&count).Error
	return count, err
}

// OrdersByStatus counts orders since from by their latest status value.
func (r *StatisticRepository) OrdersByStatus(from time.Time) (map[int]int64, error) {
	var rows []struct {
		Status int
		Total  int64
	}
	err := r.db.Table("order_statuses").
		Select("status, COUNT(*) AS total").
		Where(`id IN (
			SELECT MAX(id) FROM order_statuses
			WHERE order_id IN (SELECT id FROM orders WHERE created_at >= ? AND deleted_at IS NULL)
			GROUP BY order_id
		)`, from).
		Group("status").
		Scan(&rows).Error
	if err != nil {
		return nil, err
	}
	counts := make(map[int]int64, len(rows))
	for _, row := range rows {
		counts[row.Status] = row.Total
	}
	return counts, nil
}
