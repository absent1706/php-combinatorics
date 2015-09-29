# php-combinatorics
This code can:
 * generate permutations and some special cases of permutations
 * solve combinatorial problems by means of branch and bound and beam search
 
## PDP problem
Main interface class is Pdp.php. 
Uses https://bitbucket.org/absent1706/grebennik-pdp-python to check 3D constraints
Uses https://github.com/absent1706/common_php_app to handle event-observer pattern
Uses https://github.com/absent1706/extended_varien_object as a foundation of all objects

####Usage example

```php
require_once 'vendor/autoload.php';

$xmlConfigFile  = __DIR__.'/config.xml';
$paramsFile = __DIR__.'/data/params.json';

\Litvinenko\Common\App::init($xmlConfigFile);

$launcher = new \Litvinenko\Combinatorics\Pdp\Pdp;
$params     = json_decode(file_get_contents($paramsFile), true);

echo json_encode($launcher->getSolution($params['data'], $params['config']));
```

####Input params
Example if input JSON:
```json
{
   "method": "gen", // hardcoded to use one solution method for now
   "config": { // config section. sets how to solve pdp problem
      "check_final_loading": false, // check 3D constraints for finally generated 'best' paths
      "check_transitional_loading_probability": "20", // probability of checking 3D constraints in partial paths
      "python_file": "/home/litvinenko/www/pdp-php/public_html/demo/pdphelper/pdphelper.py", // where script for checking 3D constraints is located
      "precise": "5", // beam width in % of generated path variants
      "weight_capacity": "1000", // vehicle weight_capacity
      "load_area": { // vehicle load area size
         "x": "100",
         "y": "100",
         "z": "100"
      }
   },
   "data": {
      "depot": [
         "200", // depot x
         "200" // depot y
      ],
      "points": {
        "1": [        // point id. Points with id < <point count> (first 2 points in this example) will be treated as pickups, other half of points - as deliveries
            "117463", // point X coord
            "476120", // point Y coord
            "27",     // point box size x (for pickups)
            "38",     // point box size y (for pickups)
            "47",     // point box size z (for pickups)
            "38"      // point weight (for pickups)
        ],
         "2": [
            "400.39",
            "152.36",
            "9.37",
            "9.62",
            "9.34",
            "13.43"
         ],
         "3": [
            "345.42",
            "414.24",
            null,
            null,
            null,
            null
         ],
         "4": [
            "10.35",
            "17.68",
            null,
            null,
            null,
            null
         ]
      }
   }
}
```

On base of input points, point ojects are created in Litvinenko\Combinatorics\Pdp\Pdp::createPointsFromArray method.
To correctly create PDP-pairs, points must have ids from 1 to 2N , where N - number of PDP pairs.
Pickup with number i (i<N) will be associated to delivery with number i+N. 
I.g., for 2 PDP pairs we have 2 pickups (ids 1 and 2) and 2 deliveries (ids 3 and 4). Pdp pairs are: 1-3 and 2-4. So we must deliver one box from 1 to 3 and another from 2 to 4.

XML config for app:
```xml
<?xml version="1.0"?>
<config>
    <events>
<!--         <new_path_generated>
            <observers>
                <solutioninfocollector_new_path_generated>
                    <class>\SolutionInfoCollector</class>
                    <method>logGeneratedPath</method>
                    <singleton>1</singleton>
                </solutioninfocollector_new_path_generated>
            </observers>
        </new_path_generated>
        <cant_load>
            <observers>
                <solutioninfocollector_cant_load>
                    <class>\SolutionInfoCollector</class>
                    <method>logNotLoadedPath</method>
                    <singleton>1</singleton>
                </solutioninfocollector_cant_load>
            </observers>
        </cant_load> -->
        <point_add_before>
            <observers>
                <precise_generation_solver_point_add_before>
                    <class>\Litvinenko\Combinatorics\Pdp\Solver\PreciseGenerationSolver</class>
                    <method>canLoadObserver</method>
                    <singleton>1</singleton>
                </precise_generation_solver_point_add_before>
            </observers>
        </point_add_before>
     </events>
    <developer_mode>1</developer_mode>
</config>
```
