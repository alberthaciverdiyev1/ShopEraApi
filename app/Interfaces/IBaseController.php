<?php

namespace App\Interfaces;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

interface IBaseController
{
    public function getAll(Request $request): JsonResponse;
    public function details(int $id): JsonResponse;
    public function add(Request $request): JsonResponse;
    public function update(int $id, Request  $request): JsonResponse;
    public function delete(int $id): JsonResponse;
}
