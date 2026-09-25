package middleware

import (
	"github.com/gin-gonic/gin"

	"shopera/internal/helpers"
)

// PermissionChecker returns the set of permission names a user holds.
type PermissionChecker func(userID int64) (map[string]struct{}, error)

// RequirePermission aborts with 403 unless the authenticated user holds the
// named permission. It must run after AuthRequired.
func RequirePermission(check PermissionChecker, permission string) gin.HandlerFunc {
	return func(c *gin.Context) {
		permissions, err := check(c.GetInt64("userID"))
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
