<?php

namespace DDTrace\Integrations\Phalcon;

use DDTrace\Integrations\Integration;
use DDTrace\Tag;
use DDTrace\Type;
use DDTrace\GlobalTracer;
use DDTrace\Util\TryCatchFinally;


class PhalconIntegration extends Integration
{
    const NAME = 'phalcon';

    /**
     * @var self
     */
    private static $instance;

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
     * Loads the integration.
     *
     * @return int
     */
    public static function load()
    {
        $instance = new self();
        return $instance->doLoad();
    }


    /**
     * Static method to add instrumentation to the Redis library
     */
    public function doLoad()
    {
        if (!extension_loaded('phalcon')) {
            return Integration::NOT_AVAILABLE;
        }

        dd_trace('Phalcon\Mvc\Model', 'find', function ($parameters = null) {
            $tracer = GlobalTracer::get();
            if ($tracer->limited()) {
                return dd_trace_forward_call();
            }

            $scope = $tracer->startIntegrationScopeAndSpan(
                PhalconIntegration::getInstance(),
                'Model.find'
            );

            $span = $scope->getSpan();
            $span->setTag(Tag::SPAN_TYPE, Type::SQL);
            $span->setTag(Tag::SERVICE_NAME, 'ORM');
            $span->setTag(Tag::RESOURCE_NAME, 'Model.find()');

            if ($parameters) {
                $span->setTag('sql.parameters', json_encode($parameters));
            }
            $span->setTag('sql.class', self::class);

            $span->setTraceAnalyticsCandidate();

            return include __DIR__ . '/../../try_catch_finally.php';
        });


        dd_trace('Phalcon\Mvc\Model', 'query', function ($parameters = null) {
            $tracer = GlobalTracer::get();
            if ($tracer->limited()) {
                return dd_trace_forward_call();
            }

            $scope = $tracer->startIntegrationScopeAndSpan(
                PhalconIntegration::getInstance(),
                'Model.query'
            );

            $span = $scope->getSpan();
            $span->setTag(Tag::SPAN_TYPE, Type::SQL);
            $span->setTag(Tag::SERVICE_NAME, 'ORM');
            $span->setTag(Tag::RESOURCE_NAME, 'Model.query()');

            if ($parameters) {
                $span->setTag('sql.parameters', json_encode($parameters));
            }
            $span->setTag('sql.class', self::class);

            $span->setTraceAnalyticsCandidate();

            return include __DIR__ . '/../../try_catch_finally.php';
        });


        dd_trace('Phalcon\Db\Adapter\Pdo\Mysql', 'getSQLStatement', function () {
            $tracer = GlobalTracer::get();
            if ($tracer->limited()) {
                return dd_trace_forward_call();
            }

            $scope = $tracer->startIntegrationScopeAndSpan(
                PhalconIntegration::getInstance(),
                'Mysql.getSQLStatement'
            );

            $span = $scope->getSpan();
            $span->setTag(Tag::SPAN_TYPE, Type::SQL);
            $span->setTag(Tag::SERVICE_NAME, 'ORM');


            $thrown = null;
            $result = null;
            /** @var DDTrace\Contracts\Scope $scope */
            $span = $scope->getSpan();
            try {
                $result = dd_trace_forward_call();
                if (isset($afterResult)) {
                    $afterResult($result, $span, $scope);
                }
            } catch (\Exception $ex) {
                $thrown = $ex;
                $span->setError($ex);
            }

            $span->setTag(Tag::RESOURCE_NAME, $result);
            $span->setTraceAnalyticsCandidate();

            $scope->close();
            if ($thrown) {
                throw $thrown;
            }

            return $result;


        });


        dd_trace('Phalcon\Db\Adapter\Pdo\Mysql', 'query', function ($sqlStatement, $bindParams = null, $bindTypes = null) {
            $tracer = GlobalTracer::get();
            if ($tracer->limited()) {
                return dd_trace_forward_call();
            }

            $scope = $tracer->startIntegrationScopeAndSpan(
                PhalconIntegration::getInstance(),
                'Mysql.query'
            );

            $span = $scope->getSpan();
            $span->setTag(Tag::SPAN_TYPE, Type::SQL);
            $span->setTag(Tag::SERVICE_NAME, 'ORM');
            $span->setTag(Tag::RESOURCE_NAME, 'Mysql.query');
            $span->setTraceAnalyticsCandidate();

            return include __DIR__ . '/../../try_catch_finally.php';
        });



        return Integration::LOADED;
    }




}
