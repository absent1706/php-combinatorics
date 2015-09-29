<?php
namespace Litvinenko\Combinatorics\Pdp\Evaluator;

class PdpEvaluator extends \Litvinenko\Combinatorics\Common\Evaluator\AbstractEvaluator
{

    protected $dataRules = array(
        'avaliable_bound_types' => 'required|array',
        'metrics'               => 'required|object:\Litvinenko\Combinatorics\Pdp\Metrics\AbstractMetric'
    );

    /**
     * Calculates OPTIMISTIC path bound for PDP permutation
     *
     * @param  \Litvinenko\Combinatorics\Pdp\Path $path
     * @param  string                             $boundType
     * @param  array                              $additionalInfo
     *
     * @return float
     */
    protected function _calculateBound($path, $boundType, array $additionalInfo = array())
    {
        $result = null;

        // calculates only optimistic bounds
        if ($boundType == self::BOUND_TYPE_OPTIMISTIC)
        {
            $result = $this->getTotalDistance($path->getPoints());
        }

        return $result;
    }

    public function getTotalDistance($points)
    {
        $result = null;
        if (is_array($points))
        {
            $result = 0;
            $keys   = array_keys($points);

            for ($i = 0; $i < count($points)-1; $i++)
            {
                $result += $this->getMetrics()->getDistanceBetweenPoints($points[$keys[$i]], $points[$keys[$i+1]]);
            }
        }

        return $result;
    }

}
