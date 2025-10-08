<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use App\Models\Setting;

abstract class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    /**
     * Common data that should be available in all views.
     */
    protected function getCommonViewData(): array
    {
        return [
            'settings' => Setting::getPublic(),
            'user' => auth()->user(),
        ];
    }

    /**
     * Return a response with common view data.
     */
    protected function view(string $view, array $data = []): \Illuminate\Contracts\View\View
    {
        return view($view, array_merge($this->getCommonViewData(), $data));
    }

    /**
     * Return a JSON response.
     */
    protected function jsonResponse($data, int $status = 200): \Illuminate\Http\JsonResponse
    {
        return response()->json($data, $status);
    }

    /**
     * Return a success JSON response.
     */
    protected function successResponse(string $message = 'Success', $data = null): \Illuminate\Http\JsonResponse
    {
        return $this->jsonResponse([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ]);
    }

    /**
     * Return an error JSON response.
     */
    protected function errorResponse(string $message = 'Error', $data = null, int $status = 400): \Illuminate\Http\JsonResponse
    {
        return $this->jsonResponse([
            'success' => false,
            'message' => $message,
            'data' => $data,
        ], $status);
    }
}
