package product

// Service holds the product business logic.
type Service struct {
	repo *Repository
}

func NewService(repo *Repository) *Service { return &Service{repo: repo} }

// List returns the paginated product list.
func (s *Service) List(filter Filter, lang string) (*ListResult, error) {
	products, total, err := s.repo.List(filter)
	if err != nil {
		return nil, err
	}

	data := make([]map[string]any, 0, len(products))
	for _, p := range products {
		data = append(data, map[string]any(JSON(p, lang)))
	}

	return &ListResult{
		Data: data,
		Meta: map[string]any{"total": total, "page": filter.Page, "per_page": filter.PerPage},
	}, nil
}

// Details returns a single product shape, or nil when not found.
func (s *Service) Details(id int64, lang string) (map[string]any, error) {
	p, err := s.repo.FindByID(id)
	if err != nil || p == nil {
		return nil, err
	}
	return map[string]any(JSON(*p, lang)), nil
}

// Create stores a new product.
func (s *Service) Create(p *Product) error {
	return s.repo.Create(p)
}
