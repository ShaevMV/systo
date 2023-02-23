<?php

declare(strict_types =1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CORS
{
    /**
     * @var bool
     */
    private bool $allowCredentials;
    /**
     * @var int
     */
    private int $maxAge;
    /**
     * @var string[]
     */
    private array $exposeHeaders;
    /**
     * @var string[]
     */
    private array $headers  = [
        'origin' => 'Access-Control-Allow-Origin',
        'Access-Control-Request-Headers' => 'Access-Control-Allow-Headers',
        'Access-Control-Request-Method' => 'Access-Control-Allow-Methods'
    ];
    /**
     * @var string[]
     */
    private array $allowOrigins;

    public function __construct()
    {
        $this->allowCredentials = true;
        $this->maxAge = 600;
        $this->exposeHeaders = [];
        $this->allowOrigins = [
    /*        'http://localhost:8080',
            'http://localhost:8081',*/
            'http://api.tickets.loc',
            'http://193.106.175.59:8081',
       //     'http://localhost',
            'http://api.solarsysto.ru',
            'http://org.solarsysto.ru',
            'http://org.tickets.loc/',
            'http://193.106.175.59',
        ];

        if(env('APP_DEBUG')) {
            $this->allowOrigins[] = 'http://localhost:8081';
        }
    }

    public function handle(Request $request, Closure $next)
    {
        if (
            !empty($this->allowOrigins)
            && $request->hasHeader('origin')
            && !in_array($request->header('origin'), $this->allowOrigins, true)
        ) {
            return new JsonResponse("origin: {$request->header('origin')} not allowed");
        }
        if ($request->hasHeader('origin')
            && $request->isMethod(Request::METHOD_OPTIONS)) {
            $response = new JsonResponse('cors pre response');
        } else {
            $response = $next($request);
        }
        foreach ($this->headers as $key => $value) {
            if ($request->hasHeader($key)) {
                $response->header($value, $request->header($key));
            }
        }
        //$response->header('Access-Control-Max-Age', $this->maxAge);
        $response->header('Access-Control-Allow-Credentials', 'true');
        $response->header('Access-Control-Expose-Headers', implode(', ', $this->exposeHeaders));
        return $response;
    }
}
