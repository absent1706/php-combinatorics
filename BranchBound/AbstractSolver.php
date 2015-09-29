<?php
namespace Litvinenko\Combinatorics\BranchBound;
use Litvinenko\Combinatorics\BranchBound\Node;
abstract class AbstractSolver extends \Litvinenko\Combinatorics\Common\Solver\AbstractSolver
{
    protected $dataRules = array(
        'maximize_cost'                  => 'required|boolean',
        'initial_node_content'           => 'required',
        'initial_node_optimistic_bound'  => 'required|float_strict',
        'initial_node_pessimistic_bound' => 'required|float_strict'
    );

    abstract protected function _compareNodes($firstNode, $secondNode);
    abstract protected function _generateChildrenOf($node);
    abstract protected function _nodeIsCompleteSolution($node);

    public function getSolution()
    {
        $rootNode = new Node;
        $initialNode = new Node([
            'active'            => true,
            'content'           => $this->getInitialNodeContent(),
            'optimistic_bound'  => $this->getInitialNodeOptimisticBound(),
            'pessimistic_bound' => $this->getInitialNodePessimisticBound(),
        ]);

        $rootNode->addChild($initialNode);

        $activeNodes         = [$initialNode];
        $currentBestFullNode = $initialNode;
        $i = 0;
        while ($activeNodes)
        {
            $i++;
            if ($i == 202)
            {
                $a = 2;
            }
            $this->_logEvent('step_begin', ['root_node' => $rootNode, 'active_nodes' => $activeNodes, 'current_best_full_node' => $currentBestFullNode]);

            $branchingNode = $this->_getBestNodeFrom($activeNodes);
            $branchingNode->setActive(false);

            $this->_logEvent('step_branching_begin', ['root_node' => $rootNode, 'branching_node' => $branchingNode]);

            $children = $this->_generateChildrenOf($branchingNode);
            $branchingNode->setChildren($children);

            $this->_logEvent('step_branching_children_generated', ['root_node' => $rootNode, 'branching_node' => $branchingNode, 'children_generated' => $children]);
            foreach ($branchingNode->getChildren() as $newNode)
            {
                // if new node is better (or has better evaluation) than current best node
                if (($this->_compareNodes($newNode, $currentBestFullNode) > -1) && $this->_nodeIsCorrect($newNode))
                {
                    if ($this->_nodeIsCompleteSolution($newNode))
                    {
                        $currentBestFullNode = $newNode;
                    }
                    else
                    {
                        $newNode->setActive(true);
                    }
                }
                else
                {
                    $newNode->setActive(false);
                }
            }

            // get active nodes
            //  taking into account that nodes that were activated on previous steps (when they had best evaluation that solution on that step)
            //   CAN BECAME INACTIVE (due to solution on this step is better than their evaluations)
            //
            //   also we deactivate them if they don't satisfy some special node limitations (for PDP they are 3D loading constraints)
            $activeNodes = [];
            foreach ($rootNode->getActiveChildrenRecursive() as $node)
            {
                if ($this->_compareNodes($node, $currentBestFullNode) === 1)
                {
                    $activeNodes[] = $node;
                }
                else
                {
                    $newNode->setActive(false);
                }
            }

            $this->_logEvent('step_end', ['root_node' => $rootNode, 'active_nodes' => $activeNodes, 'children' => $children, 'current_best_full_node' => $currentBestFullNode]);
        }

        return $currentBestFullNode;
    }

    protected function _getActiveChildrenNodesBetterThan($rootNode, $currentBestNode)
    {
        return ($rootNode->getActiveChildrenRecursive());
    }

    protected function _nodeIsCorrect($node)
    {
        return true;
    }

    protected function _getBestNodeFrom($nodes)
    {
        $bestNode = reset($nodes);
        foreach ($nodes as $node)
        {
            if ($this->_compareNodes($node, $bestNode) === 1)
            {
                $bestNode = $node;
            }
        }

        return $bestNode;
    }

    protected function _logEvent($eventName, $params)
    {
        //nop
    }
}
