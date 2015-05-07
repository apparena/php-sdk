<?php
namespace AppManager;

use AppManager\API\Api;
use AppManager\Entity\Instance;

class AppManager
{

    protected $api; // API object
    protected $cache_dir = false; // E.g. ROOTPATH . /var/cache, When no path is set, then caching will be deactivated

    /**
     * Initialize the App-Manager object
     * @param array $params Parameter for the initialization
     *                      'cache_dir' Cache directory relative to the app source
     */
    function __construct($params = array())
    {
        if (isset($params['cache_dir'])){
            $this->cache_dir = $params['cache_dir'];
        }
        $this->api = new Api(array(
                'cache_dir' => $this->cache_dir
            ));
    }

    /**
     * Returns an instance object. Try to initialize the instance using available environment information
     * @param int $i_id Instance ID
     * @param array $params Additonal parameters to initialize the instance object
     * @return Entity\Instance|bool
     */
    function getInstance($i_id = 0, $params = array())
    {
        $params['i_id'] = $i_id;
        return new Instance($this->api, $params);
    }

}
