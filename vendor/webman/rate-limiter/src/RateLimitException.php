<?php

namespace Webman\RateLimiter;

use support\exception\BusinessException;
use Webman\Http\Request;
use Webman\Http\Response;
use Throwable;

class RateLimitException extends BusinessException
{
    public function render(Request $request): ?Response
    {
        $code = $this->getCode() ?: 429;
        $data = $this->data ?? [];
        $message = $this->trans($this->getMessage(), $data);
        if ($request->expectsJson()) {
            $json = ['code' => $code, 'msg' => $message, 'data' => $data];
            return new Response(200, ['Content-Type' => 'application/json'],
                json_encode($json, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        }
        return new Response($code, [], $message);
    }

    /**
     * Translate message.
     * @param string $message
     * @param array $parameters
     * @param string|null $domain
     * @param string|null $locale
     * @return string
     */
    protected function trans(string $message, array $parameters = [], ?string $domain = null, ?string $locale = null): string
    {
        $args = [];
        foreach ($parameters as $key => $parameter) {
            $args[":$key"] = $parameter;
        }
        try {
            $message = trans($message, $args, $domain, $locale);
        } catch (Throwable $e) {
        }
        foreach ($parameters as $key => $value) {
            $message = str_replace(":$key", $value, $message);
        }
        return $message;
    }
}
