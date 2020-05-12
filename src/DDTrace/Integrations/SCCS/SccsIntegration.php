<?php

namespace DDTrace\Integrations\SCCS;

use DDTrace\Integrations\Integration;
use DDTrace\Tag;
use DDTrace\Type;
use DDTrace\GlobalTracer;
use DDTrace\Util\TryCatchFinally;


class SccsIntegration extends Integration
{
    const NAME = 'sccs';

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

        dd_trace('SCCS', 'sendBySocket', function () {
            $args = func_get_args();
            return SccsIntegration::traceCommand($this, 'sendBySocket', $args);
        });

        return Integration::LOADED;
    }



    public static function traceCommand($sccs, $command, $args)
    {
        $tracer = GlobalTracer::get();
        if ($tracer->limited()) {
            return dd_trace_forward_call();
        }

        $scope = $tracer->startIntegrationScopeAndSpan(
            SccsIntegration::getInstance(),
            "SCCS.$command"
        );

        $span = $scope->getSpan();
        $span->setTag(Tag::SPAN_TYPE, Type::CUSTOM);
        $span->setTag(Tag::SERVICE_NAME, 'sccs');
        $span->setTag('sccs.method', $command);


        $span->setTag(Tag::RESOURCE_NAME, $command);

        $span->setTraceAnalyticsCandidate();

        return TryCatchFinally::executePublicMethod($scope, $sccs, $command, $args);
    }

// not used before optimization
//
//    public static function setConnectionTags($redis, $span)
//    {
//        $hash = spl_object_hash($redis);
//        if (!isset(self::$connections[$hash])) {
//            return;
//        }
//
//       foreach (self::$connections[$hash] as $tag => $value) {
//            $span->setTag($tag, $value);
//        }
//
//    }
//



}
