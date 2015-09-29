<?php
namespace Litvinenko\Combinatorics\Pdp\Solver;

use Litvinenko\Combinatorics\BranchBound\Node;
use Litvinenko\Combinatorics\Pdp\Helper;
use Litvinenko\Combinatorics\Pdp\Path;
use Litvinenko\Combinatorics\Pdp\Point;
// use Litvinenko\Combinatorics\Common\Generators\Recursive\PermutationWithRepetitionsGenerator as Generator;
use Litvinenko\Combinatorics\Pdp\Generators\Recursive\PdpPermutationGenerator as Generator;
use Litvinenko\Combinatorics\Pdp\Evaluator\PdpEvaluator as Evaluator;

use Litvinenko\Common\App;
class BranchBoundSolver extends \Litvinenko\Combinatorics\BranchBound\AbstractSolver
{
    protected $dataRules = array(
        // rules from abstract solver
        'maximize_cost'                 => 'required|boolean',
        'initial_node_content'          => 'required',
        'initial_node_optimistic_bound' => 'required|float_strict',

        // specifically data rules for this class
        'depot'                            => 'required|object:\Litvinenko\Combinatorics\Pdp\Point',
        'points'                           => 'required|array',
        'weight_capacity'                  => 'required|float_strict',
        'load_area'                        => 'required|array',
        'check_loading'                    => 'required|boolean',
        'check_loading_for_every_new_node' => 'required|boolean', // TODO: delete it in future
        'python_file'                      => 'required',

        'evaluator' => 'required|object:\Litvinenko\Combinatorics\Common\Evaluator\AbstractEvaluator'
    );

    public function _construct()
    {
        parent::_construct();

        $initialOptimisticBound = ($this->getMaximizeCost()) ? 0 : PHP_INT_MAX;
        $initialPath = new Path(['points' => [$this->getDepot()] ]);

        $this->setInitialNodeContent($initialPath);
        $this->setInitialNodeOptimisticBound($initialOptimisticBound);

        $this->setHelper(new Helper);
    }

    // /**
    //  * Returns some initial bound from some real path
    //  *
    //  * @return float
    //  */
    // protected function _getInitialBound()
    // {
    //     $initialPath = new Path([
    //         'points' => array_merge( [$this->getDepot()], $this->getPoints(), [$this->getDepot()] )
    //         ]);

    //     return $this->getEvaluator()->getBound($initialPath, Evaluator::BOUND_TYPE_OPTIMISTIC);
    // }

    public function getSolution()
    {
        // $this->getHelper()->validate($this);
        // $this->getHelper()->validateObjects($this->getPoints());

        return parent::getSolution();
    }

    protected function _compareNodes($firstNode, $secondNode)
    {
        // for now, just compare optimistic bounds
        return ($this->_compareCosts($firstNode->getOptimisticBound(), $secondNode->getOptimisticBound()));
    }


    protected function _generateChildrenOf($node)
    {
        $result = [];
        if (!$this->_nodeIsCompleteSolution($node))
        {
            $newPointSequences = $this->_generateNestedPointSequences($node);

            foreach ($newPointSequences as $newPointSequence)
            {
                if ($this->canLoad($newPointSequence))
                {
                    $path    = new Path(['points'  => $newPointSequence]);
                    $newNode = new Node(['content' => $path]);

                    $newNode->setOptimisticBound($this->getEvaluator()->getBound($path, Evaluator::BOUND_TYPE_OPTIMISTIC));
                    $result[] = $newNode;
                }
            }
        }
        return $result;
    }

    protected function _generateNestedPointSequences($node)
    {
        $pointSequence = $node->getContent()->getPoints();
        $generator = new Generator([
            'tuple_length'        => Point::getPointCount($this->getPoints()),
            'generating_elements' => Helper::getGeneratorDataFromPoints($this->getPoints()),
            'current_path'        => $node->getContent(),
            'weight_capacity'     => $this->getWeightCapacity(),
            'load_area'           => $this->getLoadArea()
            ]);
        // $generator->validate();

        $points = Helper::getGeneratorDataFromPoints($pointSequence);
        $result = Helper::getPointSequencesFromGeneratorData($generator->generateNextObjects($points));

        if ($result)
        {
            // hack: if all PDP points except of depot are present, add depot
            $nodeHasAllPointsExceptOfDepot = Helper::pointSequenceIncludesAllPickupsAndDeliveries(reset($result), $this->getPoints());
            if ($nodeHasAllPointsExceptOfDepot)
            {
                foreach ($result as &$resultPointSequence)
                {
                    $resultPointSequence = array_merge($resultPointSequence, [$this->getDepot()]);
                }
            }
        }

        return $result;
    }

    protected function _nodeIsCompleteSolution($node)
    {
        $pointSequence = $node->getContent()->getPoints();

        // + 2 because at the begin and end of path should be depot
        $nodeHasAllPoints = (count($pointSequence) >= (2 + count($this->getPoints())));

        return ($nodeHasAllPoints);
    }


    /**
     * Helper function for packing points to data neede for generator
     *
     * @param  array $points
     *
     * @return array
     */
    protected function _getGeneratorDataFromPoints($points)
    {
        $result = [];
        foreach ($points as $point)
        {
            $result[] = [
                'id'                  => $point->getId(),
                'value'               => $point,
                'combinatorial_value' => $point->getCombinatorialValue()
            ];
        }

        return $result;
    }

    protected function _getPointSequencesFromGeneratorData($generatorData)
    {
        $result = [];
        foreach ($generatorData as $pointSequence)
        {
            $sequence = [];
            foreach ($pointSequence as $point)
            {
                $sequence[] = $point['value'];
            }
            $result[] = $sequence;
        }

        return $result;
    }

    protected function _logEvent($eventName, $data)
    {
        App::dispatchEvent("branch_bound_{$eventName}", $data);
    }

    protected function canLoad($pointSequence)
    {
        $result = true;
        if ($this->getCheckLoading())
        {
            $canLoad = App::getSingleton('\Litvinenko\Combinatorics\Pdp\Helper')->canLoad($pointSequence, $this->getPythonFile(), $this->getLoadArea(), $this->getWeightCapacity(), $this->getPoints());
            if (!$canLoad)
            {
                $this->_logEvent('cant_load', ['point_sequence' => $pointSequence]);
            }

            $result = $canLoad;
        }

        return $result;
    }

    protected function _nodeIsCorrect($node)
    {
        if ($this->_nodeIsCompleteSolution($node))
        {
            return $this->canLoad($node->getContent()->getPoints());
        }
        else
        {
            return true;
        }
    }
}
