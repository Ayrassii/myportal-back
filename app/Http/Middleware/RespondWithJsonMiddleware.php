<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\ResponseFactory;

class RespondWithJsonMiddleware
{

    public function __construct(ResponseFactory $responseFactory)
    {
        $this->responseFactory = $responseFactory;
    }

    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $is_api = explode('/',$request->getUri())[3] === "api";
        if ($is_api) {
            // First, set the header so any other middleware knows we're
            // dealing with a should-be JSON response.
            $request->headers->set('Accept', 'application/json');

            // Get the response
            $response = $next($request);

            // If the response is not strictly a JsonResponse, we make it
            if (!$response instanceof JsonResponse) {
                $response = $this->responseFactory->json(
                    $response->content(),
                    $response->status(),
                    $response->headers->all()
                );
            }
            return $response;
        }
        return $next($request);
    }
}
