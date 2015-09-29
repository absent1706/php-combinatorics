<?php
namespace Litvinenko\Combinatorics\Pdp\Evaluator;

class DummyEvaluator extends \Litvinenko\Combinatorics\Common\Evaluator\AbstractEvaluator
{

    protected $dataRules = array(
        'avaliable_bound_types' => 'required|array',
    );

    /**
     * Stub for calculating OPTIMISTIC node bound for PDP
     * NEEDED $additionalInfo content: array('parent_node' => <parent node>, 'total_point_count' => <number of gererating elements used for bound calculation>)
     *
     * @param  \Litvinenko\Combinatorics\Pdp\Path $path
     * @param  string                             $boundType
     * @param  array                              $additionalInfo
     *
     * @return float
     */
    protected function _calculateBound(\Litvinenko\Combinatorics\Pdp\Path $path, $boundType, array $additionalInfo = array())
    {
        $result = null;

        // this stub calculates only optimistic bounds
        if ($boundType == self::BOUND_TYPE_OPTIMISTIC)
        {
            $points     = $path->getPoints();
            $parentNode = $additionalInfo['parent_node'];

            // if parent node is empty, just return id of first point
            if (!$parentNode->getContent()->getPoints())
            {
                $firstPoint = reset($points);
                $result     = (float)$firstPoint->getId();
            }
            else
            {
                // otherwise, return : parent bound PLUS sum: (<point 1 id> - <total_point_count>)^2 + (<point 2 id> - <total_point_count>)^2 + ...
                $result = $parentNode->getOptimisticBound() + 1; // we add 1 to be sure that child bound will be greater than parent one
                $count  = $additionalInfo['total_point_count'];
                foreach ($points as $point)
                {
                    $result += pow(($count/2 - (float)$point->getId()), 2);
                }
            }
        }

        return $result;
    }
}
