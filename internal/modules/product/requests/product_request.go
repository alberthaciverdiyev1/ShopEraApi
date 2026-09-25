// Package requests holds Product module request payloads and parsing.
package requests

import (
	"mime/multipart"
	"regexp"
	"strconv"
	"strings"
)

// SizeInput is a size variant with its own pricing.
type SizeInput struct {
	SizeID   int64    `json:"size_id"`
	Price    *float64 `json:"price"`
	Discount *float64 `json:"discount"`
}

// ImageInput is a new image's color tag.
type ImageInput struct {
	ColorID *int64 `json:"color_id"`
}

// ExistingImage is an image the client keeps.
type ExistingImage struct {
	ID      int64  `json:"id"`
	ColorID *int64 `json:"color_id"`
}

// SaveRequest holds every product field, for both create and update.
type SaveRequest struct {
	Title              map[string]string
	Description        map[string]string
	Sku                *string
	BrandID            *int64
	CategoryID         *int64
	Gender             *string
	Price              *float64
	Discount           *float64
	DiscountExpireDate *string
	StockCount         *int
	Weight             *float64
	PurchaseLimit      *int
	IsActive           *bool
	IsSuggest          *bool
	IsPinned           *bool
	Views              *int
	SalesCount         *int

	Colors       []int64
	ColorsSynced bool

	Sizes         []SizeInput
	SizesProvided bool

	Images       []ImageInput
	ImagesSynced bool

	ExistingImages         []ExistingImage
	ExistingImagesProvided bool

	ExistingVideos         []int64
	ExistingVideosProvided bool
}

var keyPattern = regexp.MustCompile(`^([a-zA-Z_]+)(?:\[([^\]]*)\])?(?:\[([^\]]*)\])?$`)

// FromMultipart parses a multipart/form-data form (bracket notation) into SaveRequest.
func FromMultipart(form *multipart.Form) SaveRequest {
	req := SaveRequest{}
	sizeFields := map[int]map[string]string{}
	imageColors := map[int]string{}
	existingImageFields := map[int]map[string]string{}

	for key, values := range form.Value {
		if len(values) == 0 {
			continue
		}
		value := values[0]
		m := keyPattern.FindStringSubmatch(key)
		if m == nil {
			continue
		}
		base, first, second := m[1], m[2], m[3]

		switch base {
		case "title":
			if first != "" && second == "" {
				if req.Title == nil {
					req.Title = map[string]string{}
				}
				req.Title[first] = value
			}
		case "description":
			if first != "" && second == "" {
				if req.Description == nil {
					req.Description = map[string]string{}
				}
				req.Description[first] = value
			}
		case "colors":
			if id, ok := parseInt(value); ok {
				req.Colors = append(req.Colors, id)
				req.ColorsSynced = true
			}
		case "sizes":
			if idx, ok := parseInt(first); ok {
				if sizeFields[int(idx)] == nil {
					sizeFields[int(idx)] = map[string]string{}
				}
				if second != "" {
					sizeFields[int(idx)][second] = value
				}
			}
		case "images":
			if second == "color_id" {
				if idx, ok := parseInt(first); ok {
					imageColors[int(idx)] = value
				}
			}
		case "existing_images":
			if idx, ok := parseInt(first); ok {
				if existingImageFields[int(idx)] == nil {
					existingImageFields[int(idx)] = map[string]string{}
				}
				if second != "" {
					existingImageFields[int(idx)][second] = value
				}
			}
		case "existing_videos":
			req.ExistingVideosProvided = true
			if id, ok := parseInt(value); ok {
				req.ExistingVideos = append(req.ExistingVideos, id)
			}
		case "colors_synced":
			req.ColorsSynced = value == "1" || value == "true"
		case "images_synced":
			req.ImagesSynced = value == "1" || value == "true"
		default:
			if first == "" {
				applyScalar(&req, base, value)
			}
		}
	}

	req.SizesProvided = len(sizeFields) > 0
	for idx := 0; idx < len(sizeFields); idx++ {
		fields, ok := sizeFields[idx]
		if !ok {
			continue
		}
		sizeID, ok := parseInt(fields["size_id"])
		if !ok {
			continue
		}
		req.Sizes = append(req.Sizes, SizeInput{
			SizeID:   sizeID,
			Price:    parseFloat(fields["price"]),
			Discount: parseFloat(fields["discount"]),
		})
	}

	if len(imageColors) > 0 {
		req.Images = make([]ImageInput, 0, len(imageColors))
		for idx := 0; idx < len(imageColors); idx++ {
			req.Images = append(req.Images, ImageInput{ColorID: parseInt64Ptr(imageColors[idx])})
		}
	}

	req.ExistingImagesProvided = len(existingImageFields) > 0
	for idx := 0; idx < len(existingImageFields); idx++ {
		fields, ok := existingImageFields[idx]
		if !ok {
			continue
		}
		id, ok := parseInt(fields["id"])
		if !ok {
			continue
		}
		req.ExistingImages = append(req.ExistingImages, ExistingImage{ID: id, ColorID: parseInt64Ptr(fields["color_id"])})
	}

	return req
}

func applyScalar(req *SaveRequest, key, value string) {
	switch key {
	case "sku":
		req.Sku = &value
	case "gender":
		req.Gender = &value
	case "price":
		req.Price = parseFloat(value)
	case "discount":
		req.Discount = parseFloat(value)
	case "discount_expire_date":
		req.DiscountExpireDate = &value
	case "weight":
		req.Weight = parseFloat(value)
	case "category_id":
		req.CategoryID = parseInt64Ptr(value)
	case "brand_id":
		req.BrandID = parseInt64Ptr(value)
	case "stock_count":
		req.StockCount = parseIntPtr(value)
	case "purchase_limit":
		req.PurchaseLimit = parseIntPtr(value)
	case "views":
		req.Views = parseIntPtr(value)
	case "sales_count":
		req.SalesCount = parseIntPtr(value)
	case "is_active":
		req.IsActive = parseBool(value)
	case "is_suggest":
		req.IsSuggest = parseBool(value)
	case "is_pinned":
		req.IsPinned = parseBool(value)
	}
}

func parseInt(value string) (int64, bool) {
	n, err := strconv.ParseInt(strings.TrimSpace(value), 10, 64)
	return n, err == nil
}

func parseFloat(value string) *float64 {
	value = strings.TrimSpace(value)
	if value == "" {
		return nil
	}
	f, err := strconv.ParseFloat(value, 64)
	if err != nil {
		return nil
	}
	return &f
}

func parseIntPtr(value string) *int {
	if n, ok := parseInt(value); ok {
		v := int(n)
		return &v
	}
	return nil
}

func parseInt64Ptr(value string) *int64 {
	if n, ok := parseInt(value); ok {
		return &n
	}
	return nil
}

func parseBool(value string) *bool {
	b := value == "1" || value == "true"
	return &b
}
