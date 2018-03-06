<?php

namespace ZipkinTests\Unit\Reporters;

use PHPUnit_Framework_TestCase;
use RuntimeException;
use Zipkin\Endpoint;
use Zipkin\Propagation\TraceContext;
use Zipkin\Recording\Span;
use Zipkin\Reporters\Http;
use Zipkin\Reporters\Metrics;

final class HttpTest extends PHPUnit_Framework_TestCase
{
    const PAYLOAD = '[{"id":"%s","name":null,"traceId":"%s","parentId":null,'
        . '"timestamp":null,"duration":null,"debug":false,"localEndpoint":{"serviceName":""}}]';

    public function testHttpReporterSuccess()
    {
        $context = TraceContext::createAsRoot();
        $localEndpoint = Endpoint::createAsEmpty();
        $span = Span::createFromContext($context, $localEndpoint);
        $metrics = $this->prophesize(Metrics::class);
        $metrics->incrementSpans(1)->shouldBeCalled();

        $mockFactory = HttpMockFactory::createAsSuccess();
        $httpReporter = new Http($mockFactory, [], $metrics->reveal());
        $httpReporter->report([$span]);

        $this->assertEquals(
            sprintf(self::PAYLOAD, $context->getSpanId(), $context->getTraceId()),
            $mockFactory->retrieveContent()
        );
    }

    public function testHttpReporterFails()
    {
        $context = TraceContext::createAsRoot();
        $localEndpoint = Endpoint::createAsEmpty();
        $span = Span::createFromContext($context, $localEndpoint);
        $metrics = $this->prophesize(Metrics::class);
        $metrics->incrementSpansDropped(1)->shouldBeCalled();

        $mockFactory = HttpMockFactory::createAsFailing();
        $httpReporter = new Http($mockFactory, [], $metrics->reveal());
        $httpReporter->report([$span]);
    }
}
