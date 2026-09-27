<?php

namespace App\Interfaces;

use Illuminate\Http\JsonResponse;

interface ICrudInterface
{

    public function getAll($request): JsonResponse;
    public function details(int $id): JsonResponse;
    public function add($request): JsonResponse;
    public function update(int $id,  $request): JsonResponse;
    public function delete(int $id): JsonResponse;
}
