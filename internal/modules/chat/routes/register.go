package routes

import (
	chathandlers "shopera/internal/modules/chat/handlers"
	chatrepositories "shopera/internal/modules/chat/repositories"
	chatservices "shopera/internal/modules/chat/services"
	userrepositories "shopera/internal/modules/user/repositories"
	"shopera/internal/platform/module"
)

// Register wires the chat module and mounts its routes.
func Register(deps module.Deps) {
	autoReplyService := chatservices.NewAutoReplyService(chatrepositories.NewAutoReplyRepository(deps.DB))
	chatService := chatservices.NewChatService(chatrepositories.NewChatRepository(deps.DB), autoReplyService, userrepositories.NewUserRepository(deps.DB))
	handler := chathandlers.NewChatHandler(chatService)
	autoReplyHandler := chathandlers.NewAutoReplyHandler(autoReplyService)
	mount(deps.API, handler, autoReplyHandler, deps.Auth)
}
