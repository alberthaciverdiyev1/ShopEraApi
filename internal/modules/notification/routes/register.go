package routes

import (
	notificationhandlers "shopera/internal/modules/notification/handlers"
	notificationrepositories "shopera/internal/modules/notification/repositories"
	notificationservices "shopera/internal/modules/notification/services"
	"shopera/internal/platform/module"
)

// Register wires the notification module and mounts its routes.
func Register(deps module.Deps) {
	service := notificationservices.NewNotificationService(
		notificationrepositories.NewNotificationRepository(deps.DB),
		notificationrepositories.NewNotificationTokenRepository(deps.DB),
		notificationservices.LogPusher{},
	)
	handler := notificationhandlers.NewNotificationHandler(service)
	tokenHandler := notificationhandlers.NewNotificationTokenHandler(
		notificationservices.NewNotificationTokenService(notificationrepositories.NewNotificationTokenRepository(deps.DB)),
	)
	mount(deps.API, handler, tokenHandler, deps.Auth)
}
