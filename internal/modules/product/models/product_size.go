package models

// ProductSize maps the `product_size` pivot (extra columns beyond size_id).
type ProductSize struct {
	ProductID      int64    `gorm:"column:product_id;primaryKey"`
	SizeID         int64    `gorm:"column:size_id;primaryKey"`
	Price          *float64 `gorm:"column:price"`
	WholesalePrice *float64 `gorm:"column:wholesale_price"`
	Discount       *float64 `gorm:"column:discount"`
}

func (ProductSize) TableName() string { return "product_size" }
