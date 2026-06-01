<?php

declare(strict_types=1);

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
    private array $headers = [
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
        // Браузер шлёт Origin в формате `scheme://host[:port]` БЕЗ trailing slash —
        // элементы со слэшами на конце никогда не сматчатся, поэтому их тут нет.
        // Группировка: dev → prod → staging → legacy. Дубли удалены.
        $this->allowOrigins = [
            // dev (локальная разработка)
            'http://localhost',
            'http://localhost:8080',
            'http://localhost:8081',
            'http://api.tickets.loc',
            'http://org.tickets.loc',

            // prod
            'http://org.spaceofjoy.ru',
            'https://org.spaceofjoy.ru',
            'http://api.spaceofjoy.ru',
            'https://api.spaceofjoy.ru',

            // staging (77.222.32.244)
            'http://staging.spaceofjoy.ru',
            'https://staging.spaceofjoy.ru',
            'http://api.staging.spaceofjoy.ru',
            'https://api.staging.spaceofjoy.ru',

            // legacy (старый домен solarsysto.ru, до переезда на spaceofjoy.ru)
            'http://org.solarsysto.ru',
            'https://org.solarsysto.ru',
            'http://api.solarsysto.ru',
            'https://api.solarsysto.ru',

            // legacy (старый stage по IP)
            'http://193.106.175.59',
            'http://193.106.175.59:8081',
        ];
    }

    public function handle(Request $request, Closure $next)
    {
        if (
            !empty($this->allowOrigins)
            && ($request->hasHeader('origin'))
            && (!in_array($request->header('origin'), $this->allowOrigins, true))
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
        $response->header('Access-Control-Max-Age', $this->maxAge);
        $response->header('Access-Control-Allow-Credentials', 'true');
        $response->header('Access-Control-Expose-Headers', implode(', ', $this->exposeHeaders));
        return $response;
    }
}
