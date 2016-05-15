<?php

namespace Szpakas\PrometheusAggregator\Client\Model\Test;

use PHPUnit_Framework_TestCase;
use Szpakas\PrometheusAggregator\Client\Enum\MetricType;
use Szpakas\PrometheusAggregator\Client\Model\Gauge;

class GaugeTest extends \PHPUnit_Framework_TestCase
{
    public function testCreateWithLabel()
    {
        $n = "nameA";
        $l = ["lA"=>"vA", "lB"=>"vB"];
        $m = new Gauge($n, $l);
        $this->assertEquals(MetricType::GAUGE, $m->getType());
        $this->assertEquals($n, $m->getName());
        $this->assertEquals($l, $m->getLabelPairs());
    }

    public function testGetFullName()
    {
        $m = new Gauge("nameA");
        $this->assertEquals("nameA", $m->getFullName());
    }

    public function testSetValue()
    {
        $m = new Gauge("nameA");
        $m->setValue(123.02);
        $this->assertEquals($m->getValue(), 123.02);
    }

    /**
     * @param []float $vals
     * @param float $exp
     * @dataProvider addSuccessProvider
     */
    public function testAddSuccess($vals, $exp)
    {
        $m = new Gauge("nameA");
        foreach ($vals as $val) {
            $m->add($val);
        }
        $this->assertEquals($m->getValue(), $exp);
    }

    public function addSuccessProvider()
    {
        return [
            "single zero"       => [ [0], 0 ],
            "single positive"   => [ [3], 3 ],
            "single negative"   => [ [-3], -3 ],
            "multiple"          => [ [1, 3, 0, -15, 8], -3 ]
        ];
    }

    /**
     * @param int $count
     * @param float $exp
     * @dataProvider incSuccessProvider
     */
    public function testIncSuccess($count, $exp)
    {
        $m = new Gauge("nameA");
        for ($i = 0; $i < $count; $i++) {
            $m->inc();
        }
        $this->assertEquals($m->getValue(), $exp);
    }

    public function incSuccessProvider()
    {
        return [
            "1"     => [1, 1],
            "5"     => [5, 5],
            "53"    => [53, 53]
        ];
    }

    /**
     * @param int $count
     * @param float $exp
     * @dataProvider decSuccessProvider
     */
    public function testDecSuccess($count, $exp)
    {
        $m = new Gauge("nameA");
        for ($i = 0; $i < $count; $i++) {
            $m->dec();
        }
        $this->assertEquals($m->getValue(), $exp);
    }

    public function decSuccessProvider()
    {
        return [
            "1"     => [1, -1],
            "5"     => [5, -5],
            "53"    => [53, -53]
        ];
    }
}
