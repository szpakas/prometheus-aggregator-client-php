<?php

namespace Szpakas\PrometheusAggregator\Client\Model;

interface MetricInterface
{
    /**
     * @return string
     */
    function getName();

    /**
     * Returns name of the metric with suffix/prefix.
     *
     * As of now only counters have "_total" suffix.
     *
     * @return string
     */
    function getFullName();

    /**
     * @return string
     */
    function getType();

    /**
     * Returns associative array of labels and values.
     * Both labels and values are of string type
     * @return string[]
     */
    function getLabelPairs();

    /**
     * @return float
     */
    function getValue();
}
