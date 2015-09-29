<?php
namespace Litvinenko\Combinatorics\Pdp\Solver;

use Litvinenko\Combinatorics\Pdp\IO;
use Litvinenko\Combinatorics\Pdp\Helper;
use Litvinenko\Combinatorics\Pdp\Path;
use Litvinenko\Combinatorics\Pdp\Point;
// use Litvinenko\Combinatorics\Common\Generators\Recursive\PermutationWithRepetitionsGenerator as Generator;
use Litvinenko\Combinatorics\Common\Evaluator\AbstractEvaluator;
use Litvinenko\Combinatorics\Pdp\Generators\Recursive\PrecisedPdpPermutationGenerator as Generator;

use Litvinenko\Common\App;

class PreciseGenerationSolver extends \Litvinenko\Combinatorics\Common\Solver\AbstractSolver
{

    protected $dataRules = array(
        // rules from abstract solver
        'maximize_cost'                          => 'required|boolean',

        // specifically data rules for this class
        'depot'                                  => 'required|object:\Litvinenko\Combinatorics\Pdp\Point',
        'points'                                 => 'required|array',
        'weight_capacity'                        => 'required|float_strict',
        'load_area'                              => 'required|array',
        'check_final_loading'                    => 'required|boolean',
        'check_transitional_loading_probability' => 'required|boolean',
        'python_file'                            => 'required',

        'evaluator'                              => 'required|object:\Litvinenko\Combinatorics\Common\Evaluator\AbstractEvaluator',

        'precise'                                => 'required|float_strict',
    );

    protected $_boxFileName = 'boxes.txt';

    public function _construct()
    {
        parent::_construct();
        $this->setHelper(new Helper);
    }

    public function getSolution()
    {
        // $this->validate();
        // $this->getHelper()->validateObjects($this->getPoints());
        // $this->_checks=0;
        $generator = new Generator([
            'tuple_length'        => Point::getPointCount($this->getPoints()),
            'generating_elements' => Helper::getGeneratorDataFromPoints($this->getPoints()),
            'weight_capacity'     => $this->getWeightCapacity(),
            'load_area'           => $this->getLoadArea(),

            'precise'             => $this->getPrecise(),
            'metrics'             => $this->getEvaluator()->getMetrics(),
            'initial_object'      => Helper::getGeneratorDataFromPoints([$this->getDepot()]),

            'log_steps' => true
        ]);

        $pointSequences = Helper::getPointSequencesFromGeneratorData($generator->generateAll());

        $bestPointSequence = null;
        foreach ($pointSequences as &$pointSequence)
        {
            $pointSequence[] = $this->getDepot();
            $currentCost = $this->_getCost($pointSequence);
            if ((is_null($bestPointSequence) || ($this->_compareCosts($currentCost, $bestCost) === 1)))
            {
                // if needed, check 3D constraints and skip path if it's incorrect
                if ($this->getData('check_final_loading'))
                {
                    if (!$this->canLoad($pointSequence)) continue;
                }

                $bestPointSequence = $pointSequence;
                $bestCost          = $currentCost;
            }
        }

        $this->setGeneratedPointSequences($pointSequences);
        // echo $this->_checks."\n";
        return new Path(['points' => $bestPointSequence]);
    }

    public function _getCost($pointSequence)
    {
        $path = ($pointSequence instanceof Path) ? $pointSequence : (new Path(['points' => $pointSequence]));
        return $this->getEvaluator()->getBound($path, AbstractEvaluator::BOUND_TYPE_OPTIMISTIC);
    }

    public function canLoadObserver($event)
    {
        if (!($current_tuple = $event->getPointSequence())) throw new Exception ('Event ' . $event->getName() . 'does not have "point_sequence" param!');
        if (!($candidate = $event->getPoint())) throw new Exception ('Event ' . $event->getName() . 'does not have "point_sequence" param!');


        $pointSequence = array_merge(Helper::getPointSequenceFromTuple($current_tuple), [$candidate]);

        $checkLoading = ($this->getPrecise() == 100)
                        ? false
                        : (
                            rand(0,100) < $this->getData('check_transitional_loading_probability') &&
                            $this->_lastPointIsPickup($pointSequence)
                          );

        // if needed, check 3D constraints
        $event->getResultContainer()->result = ($checkLoading) ? $this->canLoad($pointSequence) : true;
    }

    protected function _lastPointIsPickup($pointSequence)
    {
        return end($pointSequence)->isPickup();
    }

    protected function canLoad($pointSequence)
    {
        // $this->_checks++;
        $canLoad = App::getSingleton('\Litvinenko\Combinatorics\Pdp\Helper')->canLoad($pointSequence, $this->getPythonFile(), $this->getLoadArea(), $this->getWeightCapacity(), $this->getPoints());
        if (!$canLoad)
        {
            App::dispatchEvent('cant_load', ['point_sequence' => $pointSequence]);
        }

        return $canLoad;
        // return ($this->getCheckLoading()) ? App::getSingleton('\Litvinenko\Combinatorics\Pdp\Helper')->canLoad($pointSequence, $this->getCheckLoadingCommandPrefix(), $this->getLoadArea(), $this->getWeightCapacity()) : true;
    }
}