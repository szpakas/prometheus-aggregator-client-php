<?php

namespace Szpakas\PrometheusAggregator\Client\Model;

use Szpakas\PrometheusAggregator\Client\Enum\MetricType;

class Gauge extends AbstractMetric
{
    /** @inheritdoc */
    public function getType() { return MetricType::GAUGE; }

    /** @inheritdoc */
    public function getDefaultValue() { return 0.0; }

    public function inc()
    {
        $this->value += 1;
    }

    public function dec()
    {
        $this->value -= 1;
    }

    /**
     * @param float $v
     */
    public function add($v)
    {
        $this->value += $v;
    }

    /**
     * @param float $v
     */
    public function sub($v)
    {
        $this->value -= $v;
    }
}
