package services

import (
	"sort"
	"time"

	"github.com/gin-gonic/gin"

	ordermodels "shopera/internal/modules/order/models"
	productrepositories "shopera/internal/modules/product/repositories"
	productresponses "shopera/internal/modules/product/responses"
	settingrepositories "shopera/internal/modules/setting/repositories"
)

// StatisticService composes the admin dashboard statistics.
type StatisticService struct {
	stats    *settingrepositories.StatisticRepository
	products *productrepositories.ProductRepository
}

func NewStatisticService(stats *settingrepositories.StatisticRepository, products *productrepositories.ProductRepository) *StatisticService {
	return &StatisticService{stats: stats, products: products}
}

// Statistics returns the global statistics since the start of the month.
func (s *StatisticService) Statistics(lang string) (gin.H, error) {
	now := time.Now()
	from := time.Date(now.Year(), now.Month(), 1, 0, 0, 0, 0, now.Location())

	topProducts, err := s.topProducts(from, lang)
	if err != nil {
		return nil, err
	}
	topCustomers, err := s.topCustomers(from)
	if err != nil {
		return nil, err
	}
	topCities, err := s.topCities(from)
	if err != nil {
		return nil, err
	}
	discounted, err := s.stats.DiscountedProductsCount()
	if err != nil {
		return nil, err
	}
	statusMap, err := s.stats.OrdersByStatus(from)
	if err != nil {
		return nil, err
	}

	ordersByStatus := gin.H{}
	for _, status := range []ordermodels.OrderStatus{
		ordermodels.StatusPlaced,
		ordermodels.StatusProcessing,
		ordermodels.StatusDelivered,
		ordermodels.StatusReturned,
		ordermodels.StatusWaitingPayment,
		ordermodels.StatusFailed,
		ordermodels.StatusCancelled,
		ordermodels.StatusAdminWaiting,
	} {
		ordersByStatus[status.Label()] = statusMap[int(status)]
	}

	return gin.H{
		"date_range": gin.H{
			"from": from.Format("2006-01-02"),
			"to":   now.Format("2006-01-02"),
		},
		"top_products":              topProducts,
		"top_customers":             topCustomers,
		"top_cities":                topCities,
		"discounted_products_count": discounted,
		"orders_by_status":          ordersByStatus,
	}, nil
}

func (s *StatisticService) topProducts(from time.Time, lang string) ([]gin.H, error) {
	sold, err := s.stats.TopProducts(from, 20)
	if err != nil {
		return nil, err
	}

	ids := make([]int64, 0, len(sold))
	soldByProduct := make(map[int64]int64, len(sold))
	for _, row := range sold {
		ids = append(ids, row.ProductID)
		soldByProduct[row.ProductID] = row.TotalSold
	}

	products, err := s.products.ByIDs(ids)
	if err != nil {
		return nil, err
	}
	pivots, err := s.products.SizePivots(ids)
	if err != nil {
		return nil, err
	}

	sort.SliceStable(products, func(i, j int) bool {
		return soldByProduct[products[i].ID] > soldByProduct[products[j].ID]
	})

	out := make([]gin.H, 0, len(products))
	for _, p := range products {
		item := productresponses.JSON(p, lang, pivots[p.ID])
		item["total_sold"] = soldByProduct[p.ID]
		out = append(out, item)
	}
	return out, nil
}

func (s *StatisticService) topCustomers(from time.Time) ([]gin.H, error) {
	customers, err := s.stats.TopCustomers(from, 100)
	if err != nil {
		return nil, err
	}
	out := make([]gin.H, 0, len(customers))
	for _, c := range customers {
		out = append(out, gin.H{
			"id":           c.ID,
			"name":         c.Name,
			"email":        c.Email,
			"phone":        c.Phone,
			"orders_count": c.OrdersCount,
			"total_spent":  c.TotalSpent,
		})
	}
	return out, nil
}

func (s *StatisticService) topCities(from time.Time) ([]gin.H, error) {
	rows, err := s.stats.CityOrders(from)
	if err != nil {
		return nil, err
	}
	labels, err := s.stats.CityLabels()
	if err != nil {
		return nil, err
	}

	out := make([]gin.H, 0, 10)
	for _, row := range rows {
		if len(out) >= 10 {
			break
		}
		label := labels[row.City]
		if label == "" {
			label = row.City
		}
		out = append(out, gin.H{
			"city":         row.City,
			"city_label":   label,
			"orders_count": row.OrdersCount,
		})
	}
	return out, nil
}
