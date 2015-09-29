<?php
namespace Litvinenko\Combinatorics\Common\Solver;

abstract class AbstractSolver extends \Litvinenko\Common\Object
{
    protected $dataRules = array(
        'maximize_cost' => 'required|boolean'
    );

    abstract public function getSolution();

    /**
     * Compares 2 costs taking into account, whether user we want to maximize or minimize cost
     * Returns 1, if first cost is better
     *        -1, if second cost is better
     *         0, if costs are equal
     *
     * @param  float $firstCost
     * @param  float $secondCost
     *
     * @return 1|0|-1
     */
    protected function _compareCosts($firstCost, $secondCost)
    {
        if ($firstCost === $secondCost)
        {
            $result = 0;
        }
        else
        {
            if ($this->getMaximizeCost())
            {
               $result = ($firstCost > $secondCost) ? 1 : -1;
            }
            else
            {
                $result = ($firstCost < $secondCost) ? 1 : -1;
            }
        }

        return $result;
    }
}