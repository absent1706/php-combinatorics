<?php

namespace Litvinenko\Combinatorics\Pdp\Metrics;
use Litvinenko\Combinatorics\Pdp\Point;

abstract class AbstractMetric
{
    protected $distanceCache;

    public function getDistanceBetweenPoints(\Litvinenko\Combinatorics\Pdp\Point $firstPoint, \Litvinenko\Combinatorics\Pdp\Point $secondPoint)
    {
        $id1 = $firstPoint->getId();
        $id2 = $secondPoint->getId();

        if (!isset($this->distanceCache[$id1][$id2]))
        {
            $this->distanceCache[$id1][$id2] = $this->_getDistanceBetweenPoints($firstPoint, $secondPoint);
        }

        return $this->distanceCache[$id1][$id2];
    }

    abstract protected function _getDistanceBetweenPoints(\Litvinenko\Combinatorics\Pdp\Point $firstPoint, \Litvinenko\Combinatorics\Pdp\Point $secondPoint);
}
