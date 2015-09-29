<?php

namespace Litvinenko\Combinatorics\Pdp;
class Path extends \Litvinenko\Common\Object
{
    protected $dataRules = array(
        'points' => 'required|array'
    );

    function _construct()
    {
        if (!$this->hasPoints()) $this->setPoints([]);
    }

    public function getPointsCount()
    {
        $points = $this->getPoints();
        return (is_array($points)) ? count($points) : 0;
    }

    /**
     * Returns array of ids of path points
     *
     * @return array
     */
    public function getPointIds()
    {
        $result = [];

        foreach ($this->getPoints() as $point)
        {
            $result[] = $point->getId();
        }

        return $result;
    }

    /**
     * Returns certain params of all path points
     *
     * @return array
     */
    public function getPointsParams($params)
    {
        $result = [];

        foreach ($this->getPoints() as $point)
        {
            if (is_array($params))
            {
                $row = [];
                foreach ($params as $param)
                {
                    $row[] = $point->getData($param);
                }

                $result[] = $row;
            }
            else
            {
                $result[] = $point->getData($params);
            }
        }

        return $result;
    }

    /**
     * Returns total weight of currently loaded boxes
     *
     * @return array
     */
    public function getCurrentWeight()
    {
        $result = 0;

        foreach ($this->getPoints() as $point)
        {
            $result += $point->getBoxWeight();
        }

        return $result;
    }

    /**
     * Returns total volume of currently loaded boxes
     *
     * @return array
     */
    public function getCurrentVolume()
    {
        $result = 0;

        foreach ($this->getPoints() as $point)
        {
            if ($point->isPickup())
            {
                $result += $point->getBoxVolume();
            }
            elseif ($point->isDelivery())
            {
                $result += $point->getBoxVolume();
            }

        }

        return $result;
    }

    /**
     * Returns TRUE if path contains given point (or point withh given ID)
     *
     * @param  int|\Litvinenko\Combinatorics\Pdp\Point $point point object or just point ID
     *
     * @return bool
     */
    public function doesContain($point)
    {
        if ($point instanceof Point)
        {
            $pointId = $point->getId();
        }
        elseif (is_integer($point))
        {
            $pointId = $point;
        }
        else
        {
            throw new \Exception("given point is not integer or Pdp\\Point object");
        }

        $result = false;
        foreach ($this->getPoints() as $point)
        {
            if ($point->getId() == $pointId)
            {
                $result = true;
                break;
            }
        }

        return $result;
    }

    public function __toString()
    {
        return implode(' ', $this->getPointIds());
    }
    // public function addItem($obj, $key = null) {
    // }

    // public function deleteItem($key) {
    // }

    // public function getItem($key) {
    // }
}
