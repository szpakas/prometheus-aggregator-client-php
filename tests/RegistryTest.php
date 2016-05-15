<?php

namespace Szpakas\PrometheusAggregator\Client;

use PHPUnit_Framework_TestCase;
use Szpakas\PrometheusAggregator\Client\Enum\MetricType;
use Szpakas\PrometheusAggregator\Client\Model;

class RegistryTest extends PHPUnit_Framework_TestCase
{
    public function testGetCounterNoLabelsNew()
    {
        $name = "counterA";
        $r = new Registry();
        $m = $r->getCounter($name);

        $this->assertEquals(MetricType::COUNTER, $m->getType());
        $this->assertEquals($name, $m->getName());
        $this->assertEmpty($m->getLabelPairs());
        $this->assertEquals(0.0, $m->getValue());

        $this->assertEquals([$m], array_values($r->getAll()));
    }

    public function testGetCounterWithLabelsNew()
    {
        $name = "counterB";
        $labels = ["labelA"=>"valueA", "labelB"=>"valueB"];
        $r = new Registry();
        $m = $r->getCounter($name, $labels);

        $this->assertEquals(MetricType::COUNTER, $m->getType());
        $this->assertEquals($name, $m->getName());
        $this->assertEquals($labels, $m->getLabelPairs());
        $this->assertEquals(0.0, $m->getValue());

        $this->assertEquals([$m], array_values($r->getAll()));
    }

    public function testGetCountersMix()
    {
        $r = new Registry();
        $cAA = $r->getCounter("counterA", ["lA" => "vA"]);
        $cBA = $r->getCounter("counterA");
        $cCA = $r->getCounter("counterC");
        $cAB = $r->getCounter("counterA", ["lA" => "vA"]);
        $cBB = $r->getCounter("counterA");
        $cCB = $r->getCounter("counterC");

        $this->assertSame($cAA, $cAB);
        $this->assertSame($cBA, $cBB);
        $this->assertSame($cCA, $cCB);
        $this->assertNotSame($cAA, $cBA);
        $this->assertNotSame($cAA, $cCA);

        $this->assertCount(3, $r->getAll());
        $this->assertContains($cAA, $r->getAll());
        $this->assertContains($cBA, $r->getAll());
        $this->assertContains($cCA, $r->getAll());
    }

    public function testGetMix()
    {
        $r = new Registry();
        $cAA = $r->getCounter("counterA", ["lA" => "vA"]);
        $cBA = $r->getCounter("counterA");
        $gAA = $r->getGauge("gaugeA", ["lA" => "vA"]);
        $gBA = $r->getGauge("gaugeA");

        $cAB = $r->getCounter("counterA", ["lA" => "vA"]);
        $cBB = $r->getCounter("counterA");
        $gAB = $r->getGauge("gaugeA", ["lA" => "vA"]);
        $gBB = $r->getGauge("gaugeA");


        $this->assertSame($cAA, $cAB);
        $this->assertSame($cBA, $cBB);
        $this->assertSame($gAA, $gAB);
        $this->assertSame($gBA, $gBB);
        $this->assertNotSame($cAA, $cBA);
        $this->assertNotSame($gAA, $gBA);

        $this->assertCount(4, $r->getAll());
        $this->assertContains($cAA, $r->getAll());
        $this->assertContains($cBA, $r->getAll());
        $this->assertContains($gAA, $r->getAll());
        $this->assertContains($gBA, $r->getAll());
    }
    
    public function testCollectLabelsNotShared()
    {
        $r = new Registry();
        $r->getCounter("counterA", ["lA" => "vA"])->setValue(1.21);
        $r->getCounter("counterA", ["lA" => "vA", "lB" => "vB"])->setValue(1.22);
        $r->getCounter("counterA")->setValue(2.3);
        $r->getGauge("gaugeA", ["lB" => "vB"])->setValue(3.4);
        $r->getGauge("gaugeA")->setValue(4.5);

        // order is not important
        $exp[] = "counterA_total|c|lA=vA|1.21";
        $exp[] = "gaugeA|g|lB=vB|3.4";
        $exp[] = "counterA_total|c|2.3";
        $exp[] = "gaugeA|g|4.5";
        $exp[] = "counterA_total|c|lA=vA;lB=vB|1.22";

        $got = $r->collect();
        $this->assertEmpty($got["sharedLabels"]);
        $this->assertEmpty(array_diff($exp, $got["metrics"]), "output from collect mismatch");
    }

    public function testCollectLabelsShared()
    {
        $r = new Registry();
        $r->getCounter("counterA", ["lA" => "vA", "lB" => "vB"])->setValue(1.21);
        $r->getCounter("counterA", ["lA" => "vA", "lB" => "vB", "lC" => "vC"])->setValue(1.22);
        $r->getCounter("counterA", ["lA" => "vA", "lB" => "vB", "lC" => "vC", "lD" => "vD"])->setValue(2.3);
        $r->getGauge("gaugeA", ["lA" => "vA", "lB" => "vB"])->setValue(3.4);
        $r->getGauge("gaugeA", ["lA" => "vA", "lB" => "vB", "lE" => "vE"])->setValue(4.5);
        
        $expFirst = "lA=vA;lB=vB";
        // order is not important
        $exp[] = "counterA_total|c|1.21";
        $exp[] = "counterA_total|c|lC=vC|1.22";
        $exp[] = "counterA_total|c|lC=vC;lD=vD|2.3";
        $exp[] = "gaugeA|g|3.4";
        $exp[] = "gaugeA|g|lE=vE|4.5";

        $got = $r->collect();

        $this->assertEquals($expFirst, $got["sharedLabels"], "shared labels mismatch");
        $this->assertEmpty(array_diff($exp, $got["metrics"]), "output from collect mismatch");
    }
}
