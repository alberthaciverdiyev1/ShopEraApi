// Package services holds Product module business logic.
package services

import (
	"fmt"
	"time"

	"github.com/gin-gonic/gin"

	settingservices "shopera/internal/modules/setting/services"

	"shopera/internal/helpers"
	producthelpers "shopera/internal/modules/product/helpers"
	"shopera/internal/modules/product/models"
	productrepositories "shopera/internal/modules/product/repositories"
	productrequests "shopera/internal/modules/product/requests"
	productresponses "shopera/internal/modules/product/responses"
)

// ProductService holds the product business logic.
type ProductService struct {
	repo     *productrepositories.ProductRepository
	settings *settingservices.SettingService
}

func NewProductService(repo *productrepositories.ProductRepository, settings *settingservices.SettingService) *ProductService {
	return &ProductService{repo: repo, settings: settings}
}

// List returns the paginated product list.
func (s *ProductService) List(q helpers.Query, lang string) (gin.H, error) {
	items, total, err := s.repo.List(q)
	if err != nil {
		return nil, err
	}
	ids := make([]int64, 0, len(items))
	for _, p := range items {
		ids = append(ids, p.ID)
	}
	pivots, err := s.repo.SizePivots(ids)
	if err != nil {
		return nil, err
	}
	return gin.H{"data": productresponses.Collection(items, lang, pivots), "meta": q.Meta(total)}, nil
}

// Details returns a single product shape, or nil when not found.
func (s *ProductService) Details(id int64, lang string) (gin.H, error) {
	p, err := s.repo.FindByID(id)
	if err != nil || p == nil {
		return nil, err
	}
	pivots, err := s.repo.SizePivots([]int64{id})
	if err != nil {
		return nil, err
	}
	return productresponses.JSON(*p, lang, pivots[id]), nil
}

// UpdatePrices bulk-updates prices for size variants and standalone products.
func (s *ProductService) UpdatePrices(req productrequests.UpdatePricesRequest) (string, error) {
	priceVal := 0.0
	if req.IsPercentage {
		priceVal = deref(req.Percentage)
	} else {
		priceVal = deref(req.Price)
	}
	discountVal := 0.0
	if req.IsPercentage {
		discountVal = deref(req.DiscountPercentage)
	} else {
		discountVal = deref(req.DiscountPrice)
	}

	if priceVal <= 0 && discountVal <= 0 {
		return "", helpers.NewAppError(400, "No valid values provided.")
	}
	if len(req.ProductIDs) == 0 && !req.ConfirmAllProducts {
		return "", helpers.NewAppError(422, "Updating all products requires explicit confirmation.")
	}

	standalone, variants, err := s.repo.UpdatePrices(req.ProductIDs, req.IsPercentage, req.Type == "increment", priceVal, discountVal)
	if err != nil {
		return "", err
	}
	return fmt.Sprintf("%d standalone products and %d size variants updated successfully.", standalone, variants), nil
}

func deref(value *float64) float64 {
	if value == nil {
		return 0
	}
	return *value
}

// Add creates a product with colors, sizes, images and videos.
func (s *ProductService) Add(
	req productrequests.SaveRequest,
	images []productrepositories.ImageCreate,
	videos []string,
) (*models.Product, error) {
	if req.Title == nil || req.Title["az"] == "" {
		return nil, helpers.NewAppError(422, "The title.az field is required.")
	}

	product := &models.Product{
		Title:       producthelpers.FillLower(req.Title),
		Description: producthelpers.FillLower(req.Description),
		Sku:         req.Sku,
		BrandID:     req.BrandID,
		CategoryID:  req.CategoryID,
		Price:       req.Price,
		Discount:    req.Discount,
		Weight:      req.Weight,
		IsActive:    true,
	}
	if req.StockCount != nil {
		product.StockCount = *req.StockCount
	}
	if req.PurchaseLimit != nil {
		product.PurchaseLimit = req.PurchaseLimit
	}
	if req.IsActive != nil {
		product.IsActive = *req.IsActive
	}
	if req.IsSuggest != nil {
		product.IsSuggest = *req.IsSuggest
	}
	if req.IsPinned != nil {
		product.IsPinned = *req.IsPinned
	}
	if req.Views != nil {
		product.Views = *req.Views
	}
	if req.SalesCount != nil {
		product.SalesCount = *req.SalesCount
	}
	if req.Gender != nil {
		product.Gender = producthelpers.GenderValue(*req.Gender)
	}
	if req.DiscountExpireDate != nil {
		product.DiscountExpireDate = producthelpers.ParseTime(*req.DiscountExpireDate)
	}

	if product.Sku == nil {
		sku, err := s.repo.NextSku()
		if err != nil {
			return nil, err
		}
		product.Sku = &sku
	}

	if err := s.repo.Create(product, req.Colors, toSizePivots(req.Sizes), images, videos); err != nil {
		return nil, err
	}
	return s.repo.FindByID(product.ID)
}

// Update applies changes to a product.
func (s *ProductService) Update(
	id int64,
	req productrequests.SaveRequest,
	newImages []productrepositories.ImageCreate,
	newVideos []string,
) (*models.Product, error) {
	fields := map[string]any{}
	set := func(key string, value any) {
		if value != nil {
			fields[key] = value
		}
	}
	set("sku", req.Sku)
	set("brand_id", req.BrandID)
	set("category_id", req.CategoryID)
	set("price", req.Price)
	set("discount", req.Discount)
	set("weight", req.Weight)
	set("purchase_limit", req.PurchaseLimit)
	set("is_active", req.IsActive)
	set("is_suggest", req.IsSuggest)
	set("is_pinned", req.IsPinned)
	set("views", req.Views)
	set("sales_count", req.SalesCount)
	set("stock_count", req.StockCount)
	if req.Gender != nil {
		if g := producthelpers.GenderValue(*req.Gender); g != nil {
			fields["gender"] = *g
		}
	}
	if req.DiscountExpireDate != nil {
		fields["discount_expire_date"] = producthelpers.ParseTime(*req.DiscountExpireDate)
	}

	var title, description map[string]string
	if len(req.Title) > 0 {
		title = producthelpers.FillLower(req.Title)
	}
	if len(req.Description) > 0 {
		description = producthelpers.FillLower(req.Description)
	}

	var colors *[]int64
	if len(req.Colors) > 0 || req.ColorsSynced {
		c := req.Colors
		colors = &c
	}
	var sizes *[]productrepositories.SizePivot
	if req.SizesProvided {
		sp := toSizePivots(req.Sizes)
		sizes = &sp
	}
	var existingImages *[]productrepositories.ExistingImageSync
	if req.ExistingImagesProvided || req.ImagesSynced {
		list := make([]productrepositories.ExistingImageSync, 0, len(req.ExistingImages))
		for _, e := range req.ExistingImages {
			list = append(list, productrepositories.ExistingImageSync{ID: e.ID, ColorID: e.ColorID})
		}
		existingImages = &list
	}
	var existingVideos *[]int64
	if req.ExistingVideosProvided {
		v := req.ExistingVideos
		existingVideos = &v
	}

	if _, err := s.repo.Update(id, fields, title, description, colors, sizes, newImages, existingImages, newVideos, existingVideos); err != nil {
		return nil, err
	}
	return s.repo.FindByID(id)
}

// Delete removes a product (and its basket lines).
func (s *ProductService) Delete(id int64) error {
	return s.repo.Delete(id)
}

func toSizePivots(sizes []productrequests.SizeInput) []productrepositories.SizePivot {
	out := make([]productrepositories.SizePivot, 0, len(sizes))
	for _, s := range sizes {
		out = append(out, productrepositories.SizePivot{SizeID: s.SizeID, Price: s.Price, Discount: s.Discount})
	}
	return out
}

// StoryVideos returns active public story videos (empty when the feature is off).
func (s *ProductService) StoryVideos() ([]gin.H, error) {
	if !s.settings.StoryVideosEnabled() {
		return []gin.H{}, nil
	}
	videos, err := s.repo.StoryVideos()
	if err != nil {
		return nil, err
	}
	return productresponses.StoryVideoCollection(videos), nil
}

// StoryVideosAdmin returns story videos for the admin panel.
func (s *ProductService) StoryVideosAdmin(search string, limit int) ([]gin.H, error) {
	videos, err := s.repo.AdminStoryVideos(search, limit)
	if err != nil {
		return nil, err
	}
	return productresponses.StoryVideoCollection(videos), nil
}

// ActivateStoryVideo unhides a story for the next 24 hours.
func (s *ProductService) ActivateStoryVideo(id int64) (gin.H, error) {
	video, err := s.repo.FindVideo(id)
	if err != nil {
		return nil, err
	}
	if video == nil {
		return nil, helpers.NewAppError(404, "Story video not found.")
	}
	expires := time.Now().Add(24 * time.Hour)
	updated, err := s.repo.SetStoryState(id, false, &expires)
	if err != nil {
		return nil, err
	}
	return productresponses.StoryVideoJSON(*updated), nil
}

// DeactivateStoryVideo hides a story.
func (s *ProductService) DeactivateStoryVideo(id int64) (gin.H, error) {
	video, err := s.repo.FindVideo(id)
	if err != nil {
		return nil, err
	}
	if video == nil {
		return nil, helpers.NewAppError(404, "Story video not found.")
	}
	updated, err := s.repo.SetStoryState(id, true, nil)
	if err != nil {
		return nil, err
	}
	return productresponses.StoryVideoJSON(*updated), nil
}

// Statistics returns product counts and the most-viewed products.
func (s *ProductService) Statistics(lang string) (gin.H, error) {
	total, discounted, top, err := s.repo.Statistics()
	if err != nil {
		return nil, err
	}
	ids := make([]int64, 0, len(top))
	for _, p := range top {
		ids = append(ids, p.ID)
	}
	pivots, err := s.repo.SizePivots(ids)
	if err != nil {
		return nil, err
	}
	return gin.H{
		"total_products":       total,
		"discounted_products":  discounted,
		"most_viewed_products": productresponses.Collection(top, lang, pivots),
	}, nil
}

// Recommended returns a random list of suggested products.
func (s *ProductService) Recommended(lang string) (gin.H, error) {
	items, total, err := s.repo.Recommended(helpers.DefaultPerPage)
	if err != nil {
		return nil, err
	}
	ids := make([]int64, 0, len(items))
	for _, p := range items {
		ids = append(ids, p.ID)
	}
	pivots, err := s.repo.SizePivots(ids)
	if err != nil {
		return nil, err
	}
	return gin.H{"data": productresponses.Collection(items, lang, pivots), "meta": gin.H{"total": total}}, nil
}
