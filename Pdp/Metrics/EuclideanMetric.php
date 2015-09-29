<?php

namespace Litvinenko\Combinatorics\Pdp\Metrics;
use Litvinenko\Combinatorics\Pdp\Point;

class EuclideanMetric extends AbstractMetric
{
    protected function _getDistanceBetweenPoints(\Litvinenko\Combinatorics\Pdp\Point $firstPoint, \Litvinenko\Combinatorics\Pdp\Point $secondPoint)
    {
        return sqrt(pow($firstPoint->getX() - $secondPoint->getX(),2) + pow($firstPoint->getY() - $secondPoint->getY(),2));
    }
}
