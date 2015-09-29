<?php
namespace Litvinenko\Combinatorics\BranchBound;

class Node extends \Tree\Node\Node
{
    /**
     * @method string getOptimisticBound()
     * @method BranchBound\Node setOptimisticBound(float $optimisticBound)
     */

    public $dataObject;

    /**
     * Set/Get attribute wrapper. Calls Varien_Object
     *
     * @param   string $method
     * @param   array $args
     * @return  mixed
     */
    public function __call($method, $args)
    {
        return $this->dataObject->__call($method, $args);
    }

    /**
     * @param array $data
     */
    public function __construct($data = null)
    {
        $this->dataObject = new \Varien_Object;
        $this->dataObject->setData($data);
    }

    protected function _getChildrenRecursiveOf($currentNode)
    {
        $result = [];
        foreach ($currentNode->getChildren() as $child)
        {
            if ($child->isLeaf())
            {
                $result[] = $child;
            }
            else
            {
                $result = array_merge($result, $this->_getChildrenRecursiveOf($child));
            }
        }

        return $result;
    }

    public function getChildrenRecursive()
    {
        return $this->_getChildrenRecursiveOf($this);
    }

    public function getActiveChildrenRecursive()
    {
        $result = [];
        foreach ($this->getChildrenRecursive() as $child)
        {
            if ($child->getActive())
            {
                $result[] = $child;
            }
        }

        return $result;
    }

//    public function __toString()
//    {
//        return \Litvinenko\Combinatorics\Pdp\IO::getPathAsText($this->getContent());
//    }
}