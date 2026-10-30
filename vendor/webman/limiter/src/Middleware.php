<?php
/**
 * This file is part of webman.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the MIT-LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @author    walkor<walkor@workerman.net>
 * @copyright walkor<walkor@workerman.net>
 * @link      http://www.workerman.net/
 * @license   http://www.opensource.org/licenses/mit-license.php MIT License
 */

namespace Webman\Limiter;

use Closure;
use Exception;
use ReflectionAttribute;
use ReflectionException;
use ReflectionFunction;
use ReflectionFunctionAbstract;
use ReflectionMethod;
use Webman\MiddlewareInterface;
use Webman\Http\Response;
use Webman\Http\Request;
use Webman\Limiter\Annotation\Limit;

/**
 * Class Middleware
 */
class Middleware implements MiddlewareInterface
{
    /**
     * 中间件逻辑
     * @param Request $request
     * @param callable $handler
     * @return Response
     * @throws ReflectionException|Exception
     */
    public function process(Request $request, callable $handler) : Response
    {

        [$keyPrefix, $annotations] = $this->getAttributes($request);
        if (!$annotations) {
            return $handler($request);
        }

        foreach ($annotations as $annotation) {
            switch ($annotation->key) {
                case Limit::UID:
                    $uid = session('user.id', session()->getId());
                    $key = $keyPrefix . '-' . $annotation->key . '-' . $uid;
                    break;
                case Limit::SID:
                    $key = $keyPrefix . '-' . $annotation->key . '-' . session()->getId();
                    break;
                case Limit::IP:
                    $ip = $request->getRealIp();
                    if (Limiter::isIpWhiteListed($ip)) {
                        continue 2;
                    }
                    $key = $keyPrefix . '-' . $annotation->key . '-' . $ip;
                    break;
                default:
                    if (is_array($annotation->key)) {
                        $key = (string)call_user_func($annotation->key);
                    } else {
                        $key = (string)$annotation->key;
                    }
            }
            Limiter::check($key, $annotation->limit, $annotation->ttl, $annotation->message);
        }

        return $handler($request);
    }

    /**
     * @param Request $request
     * @return array{0:string,1:array<int,Limit>}
     * @throws ReflectionException
     */
    private function getAttributes(Request $request) : array
    {
        static $cacheByKeyPrefix = [];
        static $closureCacheByObjectId = [];

        // 1) Controller action
        if ($request->controller) {
            $keyPrefix = $request->controller . '::' . $request->action;
            if (isset($cacheByKeyPrefix[$keyPrefix])) {
                return [$keyPrefix, $cacheByKeyPrefix[$keyPrefix]];
            }
            $reflection = new ReflectionMethod($request->controller, $request->action);
            $annotations = $this->instantiateAnnotations($reflection);
            $cacheByKeyPrefix[$keyPrefix] = $annotations;
            return [$keyPrefix, $annotations];
        }

        // 2) Route callback (function / closure only)
        $route = $request->route ?? null;
        if ($route instanceof \Webman\Route\Route) {
            $callback = $route->getCallback();

            if ($callback instanceof Closure) {
                $objectId = spl_object_id($callback);
                if (isset($closureCacheByObjectId[$objectId])) {
                    return $closureCacheByObjectId[$objectId];
                }
                $reflection = new ReflectionFunction($callback);
                $keyPrefix = $this->closureKeyPrefix($reflection);
                $annotations = $this->instantiateAnnotations($reflection);
                $cacheByKeyPrefix[$keyPrefix] = $annotations;
                return $closureCacheByObjectId[$objectId] = [$keyPrefix, $annotations];
            }

            if (is_string($callback) && function_exists($callback)) {
                $keyPrefix = $callback;
                if (isset($cacheByKeyPrefix[$keyPrefix])) {
                    return [$keyPrefix, $cacheByKeyPrefix[$keyPrefix]];
                }
                $reflection = new ReflectionFunction($callback);
                $annotations = $this->instantiateAnnotations($reflection);
                $cacheByKeyPrefix[$keyPrefix] = $annotations;
                return [$keyPrefix, $annotations];
            }
        }

        return ['', []];
    }

    /**
     * @return array<int,Limit>
     */
    private function instantiateAnnotations(ReflectionFunctionAbstract $reflection) : array
    {
        $attributes = $reflection->getAttributes(Limit::class, ReflectionAttribute::IS_INSTANCEOF);
        if (!$attributes) {
            return [];
        }
        $annotations = [];
        foreach ($attributes as $attribute) {
            $annotations[] = $attribute->newInstance();
        }
        return $annotations;
    }

    private function closureKeyPrefix(ReflectionFunction $reflection) : string
    {
        $file = (string)$reflection->getFileName();
        $startLine = (int)$reflection->getStartLine();
        $endLine = (int)$reflection->getEndLine();
        if ($file !== '' && $startLine > 0 && $endLine > 0) {
            return "closure@{$file}:{$startLine}-{$endLine}";
        }
        return 'closure@internal';
    }

}