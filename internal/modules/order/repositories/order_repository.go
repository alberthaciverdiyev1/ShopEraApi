// Package repositories holds the data access for orders.
package repositories

import (
	"errors"

	"gorm.io/gorm"

	"shopera/internal/helpers"
	"shopera/internal/modules/order/models"
)

// OrderRepository is the data access for orders.
type OrderRepository struct {
	db *gorm.DB
}

func NewOrderRepository(db *gorm.DB) *OrderRepository { return &OrderRepository{db: db} }

// preload loads the relations the order resources need.
func (r *OrderRepository) preload(db *gorm.DB) *gorm.DB {
	return db.Preload("Items.Product").Preload("Items.Color").Preload("Items.Size").
		Preload("Statuses").Preload("Address").Preload("User")
}

// ListForUser returns the user's orders (paginated).
func (r *OrderRepository) ListForUser(userID int64, q helpers.Query) ([]models.Order, int64, error) {
	db := r.db.Model(&models.Order{}).Where("user_id = ?", userID)
	var total int64
	if err := db.Count(&total).Error; err != nil {
		return nil, 0, err
	}
	var items []models.Order
	err := q.ApplyPage(r.preload(db).Order("id desc")).Find(&items).Error
	return items, total, err
}

// ListAll returns every order (admin, paginated).
func (r *OrderRepository) ListAll(q helpers.Query) ([]models.Order, int64, error) {
	db := r.db.Model(&models.Order{})
	var total int64
	if err := db.Count(&total).Error; err != nil {
		return nil, 0, err
	}
	var items []models.Order
	err := q.ApplyPage(r.preload(db).Order("id desc")).Find(&items).Error
	return items, total, err
}

// FindByID returns an order with relations.
func (r *OrderRepository) FindByID(id int64) (*models.Order, error) {
	var o models.Order
	err := r.preload(r.db).First(&o, id).Error
	if errors.Is(err, gorm.ErrRecordNotFound) {
		return nil, nil
	}
	if err != nil {
		return nil, err
	}
	return &o, nil
}

// FindForUser returns the user's order by id.
func (r *OrderRepository) FindForUser(userID, id int64) (*models.Order, error) {
	var o models.Order
	err := r.preload(r.db).Where("user_id = ?", userID).First(&o, id).Error
	if errors.Is(err, gorm.ErrRecordNotFound) {
		return nil, nil
	}
	if err != nil {
		return nil, err
	}
	return &o, nil
}

// Create inserts an order (with items and an initial status) in one transaction.
func (r *OrderRepository) Create(o *models.Order, items []models.OrderItem, status models.OrderStatus) error {
	return r.db.Transaction(func(tx *gorm.DB) error {
		if err := tx.Create(o).Error; err != nil {
			return err
		}
		for i := range items {
			items[i].OrderID = o.ID
			if err := tx.Create(&items[i]).Error; err != nil {
				return err
			}
		}
		return tx.Create(&models.OrderStatusRow{OrderID: o.ID, Status: int(status)}).Error
	})
}

// UpdateFields applies field changes to an order.
func (r *OrderRepository) UpdateFields(id int64, fields map[string]any) (*models.Order, error) {
	if len(fields) > 0 {
		if err := r.db.Model(&models.Order{}).Where("id = ?", id).Updates(fields).Error; err != nil {
			return nil, err
		}
	}
	return r.FindByID(id)
}

// AddStatus appends a status row and returns the updated order.
func (r *OrderRepository) AddStatus(id int64, status models.OrderStatus) (*models.Order, error) {
	err := r.db.Transaction(func(tx *gorm.DB) error {
		return tx.Create(&models.OrderStatusRow{OrderID: id, Status: int(status)}).Error
	})
	if err != nil {
		return nil, err
	}
	return r.FindByID(id)
}

// Delete soft-deletes an order.
func (r *OrderRepository) Delete(id int64) error {
	return r.db.Delete(&models.Order{}, id).Error
}
