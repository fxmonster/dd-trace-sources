<?php

namespace DDTrace\Integrations\Redis;

use DDTrace\Integrations\Integration;
use DDTrace\Tag;
use DDTrace\Type;
use DDTrace\GlobalTracer;
use DDTrace\Util\TryCatchFinally;


class RedisIntegration extends Integration
{
    const NAME = 'redis';

    /**
     * @var self
     */
    private static $instance;

    /**
     * @var array
     */
    private static $connections = [];

    /**
     * @return self
     */
    public static function getInstance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * @return string The integration name.
     */
    public function getName()
    {
        return self::NAME;
    }


    /**
     * Static method to add instrumentation to the Redis library
     */
    public static function load()
    {
        if (!extension_loaded('redis')) {
            return Integration::NOT_AVAILABLE;
        }

        dd_trace('Redis', 'get', function ($key) {
            $args = func_get_args();
            return RedisIntegration::traceCommand($this, 'GET', $args, $key);
        });

        dd_trace('Redis', 'set', function ($key) {
            $args = func_get_args();
            return RedisIntegration::traceCommand($this, 'SET', $args, $key);
        });

        dd_trace('Redis', 'exists', function ($key) {
            $args = func_get_args();
            return RedisIntegration::traceCommand($this, 'EXISTS', $args, $key);
        });

        dd_trace('Redis', 'multi', function () {
            $args = func_get_args();
            return RedisIntegration::traceCommand($this, 'MULTI', $args);
        });

        dd_trace('Redis', 'exec', function () {
            $args = func_get_args();
            return RedisIntegration::traceCommand($this, 'EXEC', $args);
        });

        dd_trace('Redis', 'keys', function ($key) {
            $args = func_get_args();
            return RedisIntegration::traceCommand($this, 'KEYS', $args, $key);
        });

        dd_trace('Redis', 'hGetAll', function ($key) {
            $args = func_get_args();
            return RedisIntegration::traceCommand($this, 'HGETALL', $args, $key);
        });

        dd_trace('Redis', 'hMGet', function ($key) {
            $args = func_get_args();
            return RedisIntegration::traceCommand($this, 'HMGET', $args, $key);
        });

        dd_trace('Redis', 'hMSet', function ($key) {
            $args = func_get_args();
            return RedisIntegration::traceCommand($this, 'HMSET', $args, $key);
        });


        return Integration::LOADED;
    }




    public static function traceCommand($redis, $command, $args, $key = null)
    {
        $tracer = GlobalTracer::get();
        if ($tracer->limited()) {
            return dd_trace_forward_call();
        }

        $scope = $tracer->startIntegrationScopeAndSpan(
            RedisIntegration::getInstance(),
            "Redis.$command"
        );

        $span = $scope->getSpan();
        $span->setTag(Tag::SPAN_TYPE, Type::CACHE);
        $span->setTag(Tag::SERVICE_NAME, 'redis');
        $span->setTag('redis.command', $command);
        $span->setTag('redis.key', $key);

        if (!$key) {
            $key = '';
        }

        $span->setTag(Tag::RESOURCE_NAME, $command. " ". $key);

        $span->setTag(Tag::TARGET_HOST, $redis->getHost());
        $span->setTag(Tag::TARGET_PORT, $redis->getPort());

        $span->setTraceAnalyticsCandidate();

        return TryCatchFinally::executePublicMethod($scope, $redis, $command, $args);
    }



    public static function setConnectionTags($redis, $span)
    {
        $hash = spl_object_hash($redis);
        if (!isset(self::$connections[$hash])) {
            return;
        }

       foreach (self::$connections[$hash] as $tag => $value) {
            $span->setTag($tag, $value);
        }

    }




}
