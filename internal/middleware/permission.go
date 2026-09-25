package middleware

import (
	"github.com/gin-gonic/gin"

	"shopera/internal/helpers"
)

// PermissionChecker returns the set of permission names a user holds.
type PermissionChecker func(userID int64) (map[string]struct{}, error)

// PermissionExists reports whether a permission is defined in the system.
type PermissionExists func(name string) (bool, error)

// PermissionGate enforces spatie-style permissions.
//
// Not every Laravel permission is seeded in the database (most are commented
// out in the seeder). To keep the API working while still enforcing the
// permissions that are actually configured, an undefined permission is allowed
// unless Strict is set. Run with PERMISSIONS_STRICT=1 once every permission has
// been created to deny undefined ones as well.
type PermissionGate struct {
	Check  PermissionChecker
	Exists PermissionExists
	Strict bool
}

// Require returns a middleware that requires the named permission. It must run
// after AuthRequired.
func (g PermissionGate) Require(permission string) gin.HandlerFunc {
	return func(c *gin.Context) {
		if !g.Strict {
			defined, err := g.Exists(permission)
			if err != nil {
				helpers.Respond(c, 500, "Server Error", nil)
				c.Abort()
				return
			}
			if !defined {
				c.Next()
				return
			}
		}

		permissions, err := g.Check(c.GetInt64("userID"))
		if err != nil {
			helpers.Respond(c, 500, "Server Error", nil)
			c.Abort()
			return
		}
		if _, ok := permissions[permission]; !ok {
			helpers.Respond(c, 403, "You do not have permission to perform this action.", nil)
			c.Abort()
			return
		}
		c.Next()
	}
}
