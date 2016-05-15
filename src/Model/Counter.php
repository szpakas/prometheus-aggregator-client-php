<?php

namespace Szpakas\PrometheusAggregator\Client\Model;

use Szpakas\PrometheusAggregator\Client\Enum\MetricType;
use Szpakas\PrometheusAggregator\Client\Exception\PrometheusException;

class Counter extends AbstractMetric
{
    /** @inheritdoc */
    public function getType() { return MetricType::COUNTER; }

    /** @inheritdoc */
    public function getDefaultValue() { return 0.0; }

    /** @inheritdoc */
    protected function getSuffix()
    {
        return "_total";
    }

    public function inc()
    {
        $this->value += 1;
    }

    /**
     * @param float $v
     * @throws PrometheusException
     */
    public function add($v)
    {
        if ($v < 0) {
            throw new PrometheusException("counter cannot decrease in value");
        }
        
        $this->value += $v;
    }
}
