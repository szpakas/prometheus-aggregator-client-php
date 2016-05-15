<?php

namespace Szpakas\PrometheusAggregator\Client;

use Szpakas\PrometheusAggregator\Client\Enum\MetricType;
use Szpakas\PrometheusAggregator\Client\Model\MetricInterface;
use Szpakas\PrometheusAggregator\Client\Model\Counter;
use Szpakas\PrometheusAggregator\Client\Model\Gauge;

class Registry
{
    /**
     * @var MetricInterface[]
     */
    private $metrics = [];

    /**
     * @param string $name
     * @param array|null $labelPairs
     * @return Counter
     */
    public function getCounter($name, $labelPairs=null)
    {
        if (null === $labelPairs) {
            $labelPairs = [];
        }

        ksort($labelPairs, SORT_STRING);

        $hash = $this->hash(MetricType::COUNTER, $name, $labelPairs);

        if (!array_key_exists($hash, $this->metrics)) {
            $this->metrics[$hash] = new Counter($name, $labelPairs);
        }

        return $this->metrics[$hash];
    }

    /**
     * @param string $name
     * @param array|null $labelPairs
     * @return Gauge
     */
    public function getGauge($name, $labelPairs=null)
    {
        if (null === $labelPairs) {
            $labelPairs = [];
        }

        ksort($labelPairs, SORT_STRING);

        $hash = $this->hash(MetricType::GAUGE, $name, $labelPairs);

        if (!array_key_exists($hash, $this->metrics)) {
            $this->metrics[$hash] = new Gauge($name, $labelPairs);
        }

        return $this->metrics[$hash];
    }

    /**
     * return MetricInterface[]
     */
    public function getAll() {
        return $this->metrics;
    }

    /**
     * @return array
     */
    private function gatherSharedLabels()
    {
        $out = [];
        $state = "populate";
        foreach ($this->metrics as &$m) {
            if ("populate" === $state) {
                foreach ($m->getLabelPairs() as $l => $v) {
                    $lH = sprintf("%s=%s", $l, $v);
                    $out[$lH] = true;
                }
            }
            // checking -> first fail triggers bail
            // each key=value pair must exists in metrics pair to NOT to be removed
            else {
                $labelPairs = $m->getLabelPairs();
                foreach (array_keys($out) as $lH) {
                    $kv = explode("=", $lH);
                    if (!array_key_exists($kv[0], $labelPairs) || $labelPairs[$kv[0]] != $kv[1]) {
                        unset($out[$lH]);
                    }

                }
            }
            $state = "check";
        }
        
        return $out;
    }

    /**
     * @return array
     */
    public function collect()
    {
        $out = [
            "sharedLabels" => null,
            "metrics" => []
        ];

        // phase 1: determine shared labels
        $sharedLabels = $this->gatherSharedLabels();
        if ($sharedLabels) {
            $out["sharedLabels"] = implode(";", array_keys($sharedLabels));
        }

        // phase 2: construct export with shared labels excluded
        foreach ($this->metrics as &$m) {
            $om = [$m->getFullName(), $m->getType()];
            if ($m->getLabelPairs()) {
                $lp = [];
                foreach ($m->getLabelPairs() as $l => $v) {
                    $lH = sprintf("%s=%s", $l, $v);
                    if (!array_key_exists($lH, $sharedLabels)) {
                        $lp[] = $lH;
                    }
                }
                if ($lp) {
                    $om[] = implode(";", $lp);
                }
            }
            $om[] = $m->getValue();
            $out["metrics"][] = implode("|", $om);
        }

        return $out;
    }

    /**
     * Calculates hash based on name and label pairs.
     *
     * Label pairs should be already sorted.
     *
     * @param string $kind
     * @param string $name
     * @param array $labelPairs
     * @return string
     */
    protected function hash($kind, $name, $labelPairs)
    {
        $el = [$kind, $name];
        foreach ($labelPairs as $l => $v) {
            $el[] = $l . "|" . $v;
        }

        return md5(implode(";", $el));
    }
}
