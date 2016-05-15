<?php


namespace Szpakas\PrometheusAggregator\Client\Model;

use Szpakas\PrometheusAggregator\Client\Exception\PrometheusException;

abstract class AbstractMetric implements MetricInterface
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var float
     */
    protected $value;

    /**
     * [string => string]
     * [labelName => labelValue]
     * @var array
     */
    protected $labelPairs = [];

    /**
     * @return float
     */
    abstract public function getDefaultValue();

    /**
     * @return string
     */
    protected function getSuffix()
    {
        return "";
    }

    /**
     * AbstractMetric constructor.
     * @param $name
     * @param array|null $labelPairs
     */
    public function __construct($name, $labelPairs = null)
    {
        $this->setName($name);
        $this->setValue($this->getDefaultValue());
        if ($labelPairs) {
            $this->setLabelPairs($labelPairs);
        }
    }

    /**
     * @throws PrometheusException
     */
    public function validate()
    {
        if (empty($this->name)) {
            throw new PrometheusException("A name is required for a metric");
        }
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getFullName()
    {
        return implode("", [$this->getName(), $this->getSuffix()]);
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return float
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param float $value
     */
    public function setValue($value)
    {
        $this->value = $value;
    }

    /**
     * @return array
     */
    public function getLabelPairs()
    {
        return $this->labelPairs;
    }

    /**
     * @param array $labelPairs
     */
    public function setLabelPairs($labelPairs)
    {
        $this->labelPairs = $labelPairs;
    }
}
