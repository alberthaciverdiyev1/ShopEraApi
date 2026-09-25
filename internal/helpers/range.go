package helpers

import "gorm.io/gorm"

// ApplyRange applies an exact, between, or min/max filter on a column
// (Laravel rangeFilter karşılığı): column, column_min, column_max.
func (q Query) ApplyRange(db *gorm.DB, column string) *gorm.DB {
	exact := q.Params[column]
	min := q.Params[column+"_min"]
	max := q.Params[column+"_max"]

	switch {
	case exact != "" && min == "" && max == "":
		return db.Where(column+" = ?", exact)
	case min != "" && max != "":
		return db.Where(column+" BETWEEN ? AND ?", min, max)
	default:
		if min != "" {
			db = db.Where(column+" >= ?", min)
		}
		if max != "" {
			db = db.Where(column+" <= ?", max)
		}
		return db
	}
}

// ApplyRangeDate applies a between filter using the column_min/column_max params
// (Laravel rangeDateFilter karşılığı).
func (q Query) ApplyRangeDate(db *gorm.DB, column string) *gorm.DB {
	min := q.Params[column+"_min"]
	max := q.Params[column+"_max"]

	switch {
	case min != "" && max != "":
		return db.Where(column+" BETWEEN ? AND ?", min, max)
	case min != "":
		return db.Where(column+" >= ?", min)
	case max != "":
		return db.Where(column+" <= ?", max)
	default:
		return db
	}
}
