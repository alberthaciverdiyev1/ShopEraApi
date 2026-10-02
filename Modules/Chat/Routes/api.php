<?php

use Illuminate\Support\Facades\Route;
use Modules\Chat\Http\Controllers\AutoReplyController;
use Modules\Chat\Http\Controllers\ChatController;

Route::middleware(['auth:sanctum', 'feature:chat'])->controller(ChatController::class)
    ->prefix('chat')
    ->group(function () {

        Route::get('/', 'messageList')->name('chat.list');
        Route::post('/send', 'send')->name('chat.send');
        Route::delete('/message/{messageId}', 'delete')->name('chat.delete');

        Route::prefix('conversation')->group(function () {
            Route::get('/', 'conversationList')->name('chat.conversationList');
            Route::delete('/{conversationId}', 'deleteConversation')->name('chat.deleteConversation');
            Route::get('/{conversationId}', 'messages')->name('chat.messages');
            Route::post('read/{conversationId}', 'markAsRead')->name('chat.markAsRead');
        });
    });

Route::middleware(['auth:sanctum', 'feature:auto_reply'])->prefix('auto-reply')->controller(AutoReplyController::class)->group(function () {
    Route::get('/', 'getAll')->name('auto-reply.list');
    Route::post('/', 'create')->name('auto-reply.add');
    Route::put('/{id}', 'update')->name('auto-reply.update');
    Route::delete('/{id}', 'delete')->name('auto-reply.delete');
});
