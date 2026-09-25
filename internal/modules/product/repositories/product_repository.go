// Package repositories holds the data access for the Product model.
package repositories

import (
	"errors"
	"fmt"
	"strconv"
	"strings"
	"time"

	"gorm.io/gorm"

	"shopera/internal/helpers"
	"shopera/internal/modules/product/models"
)

// ProductRepository is the data access for the Product model.
type ProductRepository struct {
	db *gorm.DB
}

func NewProductRepository(db *gorm.DB) *ProductRepository { return &ProductRepository{db: db} }

// List returns active products (paginated) and the total count.
func (r *ProductRepository) List(q helpers.Query) ([]models.Product, int64, error) {
	db := r.buildListQuery(q)

	var total int64
	if err := db.Count(&total).Error; err != nil {
		return nil, 0, err
	}

	var products []models.Product
	err := q.ApplyPage(db.Order("id desc")).Find(&products).Error
	return products, total, err
}

// FindByID returns a product with its relations.
func (r *ProductRepository) FindByID(id int64) (*models.Product, error) {
	var p models.Product
	err := r.preload(r.db).First(&p, id).Error
	if errors.Is(err, gorm.ErrRecordNotFound) {
		return nil, nil
	}
	if err != nil {
		return nil, err
	}
	return &p, nil
}

// buildListQuery applies publicly-available scope and all list filters.
func (r *ProductRepository) buildListQuery(q helpers.Query) *gorm.DB {
	db := r.preload(r.db.Model(&models.Product{}).Where("products.is_active = ?", true))

	if v, ok := q.Params["is_suggest"]; ok && v != "" {
		db = db.Where("is_suggest = ?", v == "1" || v == "true")
	}
	if q.Params["discount"] != "" {
		db = db.Where("discount IS NOT NULL")
	}
	if v := q.Params["gender"]; v == "male" || v == "female" || v == "kids" {
		db = db.Where("gender = ?", v)
	}
	db = q.ApplyRange(db, "price")

	if ids := paramList(q, "category_ids"); len(ids) > 0 {
		db = db.Where("category_id IN ?", r.withChildCategories(ids))
	}
	if ids := paramList(q, "brand_ids"); len(ids) > 0 {
		db = db.Where("brand_id IN ?", ids)
	}
	if ids := paramList(q, "color_ids"); len(ids) > 0 {
		db = db.Where("EXISTS (SELECT 1 FROM color_product cp WHERE cp.product_id = products.id AND cp.color_id IN ?)", ids)
	}
	if ids := paramList(q, "size_ids"); len(ids) > 0 {
		db = db.Where("EXISTS (SELECT 1 FROM product_size ps WHERE ps.product_id = products.id AND ps.size_id IN ?)", ids)
	}

	db = q.ApplySearch(db,
		helpers.SearchColumn{Column: "title", Translatable: true},
		helpers.SearchColumn{Column: "description", Translatable: true},
		helpers.SearchColumn{Column: "sku"},
	)
	return db
}

func (r *ProductRepository) preload(db *gorm.DB) *gorm.DB {
	return db.Preload("Colors").Preload("Sizes").Preload("Images").
		Preload("Videos").Preload("Category").Preload("Brand")
}

// withChildCategories expands the given category ids with all descendants.
func (r *ProductRepository) withChildCategories(ids []string) []string {
	all := append([]string{}, ids...)
	frontier := ids
	for len(frontier) > 0 {
		var children []string
		r.db.Table("categories").Where("parent_id IN ?", frontier).Pluck("id", &children)
		if len(children) == 0 {
			break
		}
		all = append(all, children...)
		frontier = children
	}
	return all
}

func paramList(q helpers.Query, param string) []string {
	raw := strings.TrimSpace(q.Params[param])
	if raw == "" {
		return nil
	}
	parts := strings.Split(raw, ",")
	out := make([]string, 0, len(parts))
	for _, p := range parts {
		if p = strings.TrimSpace(p); p != "" {
			out = append(out, p)
		}
	}
	return out
}

// SizePivot is a size variant row for the product_size table.
type SizePivot struct {
	SizeID   int64
	Price    *float64
	Discount *float64
}

// ImageCreate is a new product image.
type ImageCreate struct {
	Path    string
	ColorID *int64
}

// ExistingImageSync is an image the client keeps, with its (possibly updated) color.
type ExistingImageSync struct {
	ID      int64
	ColorID *int64
}

// NextSku generates the next "P000001" style SKU.
func (r *ProductRepository) NextSku() (string, error) {
	var last *string
	r.db.Model(&models.Product{}).Select("sku").Order("id desc").Limit(1).Scan(&last)

	next := 1
	if last != nil && *last != "" {
		var n int
		if _, err := fmt.Sscanf(*last, "P%d", &n); err == nil {
			next = n + 1
		}
	}

	for {
		sku := fmt.Sprintf("P%06d", next)
		var count int64
		if err := r.db.Model(&models.Product{}).Where("sku = ?", sku).Count(&count).Error; err != nil {
			return "", err
		}
		if count == 0 {
			return sku, nil
		}
		next++
	}
}

// Create inserts a product with its colors, sizes, images and videos.
func (r *ProductRepository) Create(p *models.Product, colors []int64, sizes []SizePivot, images []ImageCreate, videos []string) error {
	return r.db.Transaction(func(tx *gorm.DB) error {
		if err := tx.Create(p).Error; err != nil {
			return err
		}
		if err := r.replaceColors(tx, p.ID, colors); err != nil {
			return err
		}
		if err := r.replaceSizes(tx, p.ID, sizes); err != nil {
			return err
		}
		for _, img := range images {
			if err := tx.Create(&models.ProductImage{ProductID: p.ID, ImagePath: img.Path, ColorID: img.ColorID}).Error; err != nil {
				return err
			}
		}
		for _, path := range videos {
			if err := tx.Create(&models.ProductVideo{ProductID: p.ID, VideoPath: path}).Error; err != nil {
				return err
			}
		}
		return nil
	})
}

// Update applies changes, syncing colors/sizes/images/videos when provided (nil = unchanged).
func (r *ProductRepository) Update(
	id int64,
	fields map[string]any,
	title, description map[string]string,
	colors *[]int64,
	sizes *[]SizePivot,
	newImages []ImageCreate,
	existingImages *[]ExistingImageSync,
	newVideos []string,
	existingVideoIDs *[]int64,
) (*models.Product, error) {
	var result models.Product
	err := r.db.Transaction(func(tx *gorm.DB) error {
		var p models.Product
		if err := tx.First(&p, id).Error; err != nil {
			return err
		}
		if title != nil {
			fields["title"] = title
		}
		if description != nil {
			fields["description"] = description
		}
		if len(fields) > 0 {
			if err := tx.Model(&p).Updates(fields).Error; err != nil {
				return err
			}
		}
		if colors != nil {
			if err := r.replaceColors(tx, id, *colors); err != nil {
				return err
			}
		}
		if sizes != nil {
			if err := r.replaceSizes(tx, id, *sizes); err != nil {
				return err
			}
		}
		if existingImages != nil {
			kept := make([]int64, 0, len(*existingImages))
			for _, e := range *existingImages {
				kept = append(kept, e.ID)
			}
			q := tx.Where("product_id = ?", id)
			if len(kept) > 0 {
				q = q.Where("id NOT IN ?", kept)
			}
			if err := q.Delete(&models.ProductImage{}).Error; err != nil {
				return err
			}
			for _, e := range *existingImages {
				if err := tx.Model(&models.ProductImage{}).
					Where("id = ? AND product_id = ?", e.ID, id).
					Update("color_id", e.ColorID).Error; err != nil {
					return err
				}
			}
		}
		for _, img := range newImages {
			if err := tx.Create(&models.ProductImage{ProductID: id, ImagePath: img.Path, ColorID: img.ColorID}).Error; err != nil {
				return err
			}
		}
		if existingVideoIDs != nil {
			q := tx.Where("product_id = ?", id)
			if len(*existingVideoIDs) > 0 {
				q = q.Where("id NOT IN ?", *existingVideoIDs)
			}
			if err := q.Delete(&models.ProductVideo{}).Error; err != nil {
				return err
			}
		}
		for _, path := range newVideos {
			if err := tx.Create(&models.ProductVideo{ProductID: id, VideoPath: path}).Error; err != nil {
				return err
			}
		}
		return tx.First(&result, id).Error
	})
	if err != nil {
		return nil, err
	}
	return &result, nil
}

// Delete removes baskets and soft-deletes the product.
func (r *ProductRepository) Delete(id int64) error {
	return r.db.Transaction(func(tx *gorm.DB) error {
		if err := tx.Exec("DELETE FROM baskets WHERE product_id = ?", id).Error; err != nil {
			return err
		}
		return tx.Delete(&models.Product{}, id).Error
	})
}

func (r *ProductRepository) replaceColors(tx *gorm.DB, productID int64, colors []int64) error {
	if err := tx.Exec("DELETE FROM color_product WHERE product_id = ?", productID).Error; err != nil {
		return err
	}
	for _, colorID := range colors {
		if err := tx.Exec("INSERT INTO color_product (product_id, color_id) VALUES (?, ?)", productID, colorID).Error; err != nil {
			return err
		}
	}
	return nil
}

func (r *ProductRepository) replaceSizes(tx *gorm.DB, productID int64, sizes []SizePivot) error {
	if err := tx.Exec("DELETE FROM product_size WHERE product_id = ?", productID).Error; err != nil {
		return err
	}
	for _, s := range sizes {
		if err := tx.Exec(
			"INSERT INTO product_size (product_id, size_id, price, discount) VALUES (?, ?, ?, ?)",
			productID, s.SizeID, s.Price, s.Discount,
		).Error; err != nil {
			return err
		}
	}
	return nil
}

// SizePivots returns product_size rows grouped by product id.
func (r *ProductRepository) SizePivots(productIDs []int64) (map[int64][]models.ProductSize, error) {
	out := map[int64][]models.ProductSize{}
	if len(productIDs) == 0 {
		return out, nil
	}
	var rows []models.ProductSize
	if err := r.db.Where("product_id IN ?", productIDs).Find(&rows).Error; err != nil {
		return nil, err
	}
	for _, row := range rows {
		out[row.ProductID] = append(out[row.ProductID], row)
	}
	return out, nil
}

// UpdatePrices bulk-updates pivots and standalone products. Returns the affected
// counts (standalone products, size variants).
func (r *ProductRepository) UpdatePrices(ids []int64, isPercentage, increment bool, priceVal, discountVal float64) (int64, int64, error) {
	var standalone, variants int64

	err := r.db.Transaction(func(tx *gorm.DB) error {
		var sets []string
		var setArgs []any
		if priceVal > 0 {
			sets = append(sets, "price = "+priceExpression("price", isPercentage, increment))
			setArgs = append(setArgs, priceVal)
		}
		if discountVal > 0 {
			sets = append(sets, "discount = "+priceExpression("discount", isPercentage, increment))
			setArgs = append(setArgs, discountVal)
		}
		if len(sets) == 0 {
			return nil
		}
		setClause := strings.Join(sets, ", ")

		// Size variants.
		args := append([]any{}, setArgs...)
		query := "UPDATE product_size SET " + setClause
		if len(ids) > 0 {
			query += " WHERE product_id IN (" + placeholders(len(ids)) + ")"
			for _, id := range ids {
				args = append(args, id)
			}
		}
		res := tx.Exec(query, args...)
		if res.Error != nil {
			return res.Error
		}
		variants = res.RowsAffected

		// Standalone products (no size variants).
		args = append([]any{}, setArgs...)
		query = "UPDATE products SET " + setClause +
			" WHERE NOT EXISTS (SELECT 1 FROM product_size WHERE product_size.product_id = products.id)"
		if len(ids) > 0 {
			query += " AND id IN (" + placeholders(len(ids)) + ")"
			for _, id := range ids {
				args = append(args, id)
			}
		}
		res = tx.Exec(query, args...)
		if res.Error != nil {
			return res.Error
		}
		standalone = res.RowsAffected
		return nil
	})
	if err != nil {
		return 0, 0, err
	}
	return standalone, variants, nil
}

func priceExpression(column string, isPercentage, increment bool) string {
	operator := "+"
	if !increment {
		operator = "-"
	}
	if isPercentage {
		return fmt.Sprintf("GREATEST(0, ROUND((COALESCE(%s, 0) * (1 %s (? / 100.0)))::numeric, 2))", column, operator)
	}
	return fmt.Sprintf("GREATEST(0, ROUND((COALESCE(%s, 0) %s ?)::numeric, 2))", column, operator)
}

func placeholders(n int) string {
	return strings.TrimRight(strings.Repeat("?,", n), ",")
}

// StoryVideos returns public, active story videos (Laravel storyVideos).
func (r *ProductRepository) StoryVideos() ([]models.ProductVideo, error) {
	now := time.Now()
	var videos []models.ProductVideo
	err := r.db.
		Preload("Product", func(db *gorm.DB) *gorm.DB { return db.Where("is_active = ?", true) }).
		Preload("Product.Images").
		Where("is_story_hidden = ?", false).
		Where("(story_expires_at >= ? OR (story_expires_at IS NULL AND created_at >= ?))", now, now.Add(-24*time.Hour)).
		Where("EXISTS (SELECT 1 FROM products WHERE products.id = product_videos.product_id AND products.is_active = true AND products.deleted_at IS NULL)").
		Order("id desc").
		Find(&videos).Error
	return videos, err
}

// AdminStoryVideos returns all story videos for the admin list.
func (r *ProductRepository) AdminStoryVideos(search string, limit int) ([]models.ProductVideo, error) {
	db := r.db.Preload("Product.Images").Order("id desc")
	if search != "" {
		if n, err := strconv.ParseInt(search, 10, 64); err == nil {
			db = db.Where("id = ? OR product_id = ?", n, n)
		} else {
			db = db.Where("video_path ILIKE ?", "%"+search+"%")
		}
	}
	if limit > 0 {
		db = db.Limit(limit)
	}
	var videos []models.ProductVideo
	err := db.Find(&videos).Error
	return videos, err
}

// FindVideo returns a story video by id.
func (r *ProductRepository) FindVideo(id int64) (*models.ProductVideo, error) {
	var v models.ProductVideo
	err := r.db.Preload("Product.Images").First(&v, id).Error
	if errors.Is(err, gorm.ErrRecordNotFound) {
		return nil, nil
	}
	if err != nil {
		return nil, err
	}
	return &v, nil
}

// SetStoryState activates or deactivates a story video.
func (r *ProductRepository) SetStoryState(id int64, hidden bool, expiresAt *time.Time) (*models.ProductVideo, error) {
	var v models.ProductVideo
	err := r.db.Transaction(func(tx *gorm.DB) error {
		if err := tx.Model(&models.ProductVideo{}).Where("id = ?", id).
			Updates(map[string]any{"is_story_hidden": hidden, "story_expires_at": expiresAt}).Error; err != nil {
			return err
		}
		return tx.Preload("Product.Images").First(&v, id).Error
	})
	if err != nil {
		return nil, err
	}
	return &v, nil
}
