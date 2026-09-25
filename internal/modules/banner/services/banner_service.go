// Package services holds Banner module business logic.
package services

import (
	"github.com/gin-gonic/gin"

	"shopera/internal/helpers"
	bannermodels "shopera/internal/modules/banner/models"
	bannerrepositories "shopera/internal/modules/banner/repositories"
	bannerresponses "shopera/internal/modules/banner/responses"
)

// BannerService holds the banner business logic.
type BannerService struct {
	repo *bannerrepositories.BannerRepository
}

func NewBannerService(repo *bannerrepositories.BannerRepository) *BannerService {
	return &BannerService{repo: repo}
}

// List returns banners as a paginated or full list.
func (s *BannerService) List(q helpers.Query, bannerType *string) (gin.H, error) {
	items, total, err := s.repo.List(q, bannerType)
	if err != nil {
		return nil, err
	}
	if q.All {
		return gin.H{"data": bannerresponses.Collection(items)}, nil
	}
	return gin.H{"data": bannerresponses.Collection(items), "meta": q.Meta(total)}, nil
}

// Add stores a banner.
func (s *BannerService) Add(image string, secondImage *string, bannerType string, url *string) error {
	return s.repo.Create(&bannermodels.Banner{
		Image:       image,
		SecondImage: secondImage,
		Type:        bannerType,
		URL:         url,
	})
}

// Delete removes a banner.
func (s *BannerService) Delete(id int64) error {
	banner, err := s.repo.FindByID(id)
	if err != nil {
		return err
	}
	if banner == nil {
		return helpers.NewAppError(404, "Banner not found")
	}
	return s.repo.Delete(id)
}
