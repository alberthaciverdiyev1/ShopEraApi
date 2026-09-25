// Package module defines the shared dependencies passed to each module's Register.
package module

import (
	"github.com/gin-gonic/gin"
	"gorm.io/gorm"

	"shopera/internal/config"
)

// Deps is what a module needs to wire itself and mount its routes.
type Deps struct {
	API  *gin.RouterGroup
	DB   *gorm.DB
	Cfg  *config.Config
	Auth gin.HandlerFunc
}
