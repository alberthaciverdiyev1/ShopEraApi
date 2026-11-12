<?php

namespace Modules\Setting\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Setting\Http\Requests\ContactRequest;
use Modules\Setting\Services\ContactService;

class ContactController extends Controller
{
    public function __construct(private readonly ContactService $service) {}

    /**
     * Get store contact and social media information.
     */
    public function info(): JsonResponse
    {
        return responseHelper('Contact information retrieved successfully.', 200, $this->service->info());
    }

    /**
     * Submit a contact message.
     */
    public function send(ContactRequest $request): JsonResponse
    {
        $message = $this->service->send($request->validated(), $request->ip());

        return responseHelper('Mesajınız uğurla göndərildi. Tezliklə sizinlə əlaqə saxlanılacaq.', 200, [
            'id' => $message->id,
        ]);
    }
}
