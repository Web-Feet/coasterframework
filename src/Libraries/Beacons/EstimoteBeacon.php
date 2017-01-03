<?php namespace CoasterCms\Libraries\Beacons;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use URL;
use CoasterCms\Libraries\Builder\FormMessage;


/**
 * Estimote beacon "driver"
 */
class EstimoteBeacon extends BeaconAbstract
{
    private $_client;
    public $devices;
    public $pendingDevices;
    public $originalDevices;

    public function getClient()
    {
        if (empty($this->_client)) {
            $this->_client = new Client(
                [
                    'base_uri' => 'https://cloud.estimote.com/v2/',
                    'headers' => [
                        'Accept' => 'application/json',
                        'Content-Type' => 'application/json'
                    ],
                    'auth' => [config('coaster::appid.estimote'), config('coaster::key.estimote')]
                ]
            );
        }
        return $this->_client;
    }

    public function setUrl($uid, $url)
    {
        try {
            $client = $this->getClient();

            //  Delete pending_settings
            $client->request('POST', 'devices/delete_pending_settings',
                ['json' => ['identifiers' => [$uid]]]
            );

            // New settings
            $settings = [
                'pending_settings' =>
                    [
                        'advertisers' =>
                            [
                                'eddystone_url' => [
                                    [
                                        'index' => 1,
                                        'name' => 'Eddystone URL',
                                        'enabled' => true,
                                        'interval' => 528,
                                        'power' => -20,
                                        'url' => $url
                                    ]
                                ]
                            ]
                    ]
            ];


            $client->request('POST', 'devices/'.$uid,
                ['json' => $settings]
            );

            return true;
        } catch (RequestException $e) {
            FormMessage::add('page_info_other[beacons]', 'Error setting URL with Estimote API (response: '.$e->getMessage().')');

            return $e->getMessage();
        }
    }

    /**
     * Normalise Estimote return array to match up to expected for BlockBeacon
     *
     * @param boolean $pending
     * @return array
     */
    public function normaliseDevices($pending = false)
    {
        try {
            $this->originalDevices = json_decode($this->getClient()->request('GET', 'devices')->getBody());
        } catch (\Exception $e) {
            dd($e->getMessage());
        }

        $this->devices = [];
        $this->pendingDevices= [];

        foreach ($this->originalDevices as $estimoteDevice) {

            $tmp = new \stdClass;
            $tmp->id = $estimoteDevice->identifier;
            $tmp->deviceType = $estimoteDevice->hardware_type;
            $tmp->alias = $estimoteDevice->shadow->name;
            $tmp->uniqueId = $estimoteDevice->identifier;
            $tmp->profiles = ['EDDYSTONE'];
            $tmp->url = '';

            if (!empty($estimoteDevice->pending_settings->advertisers->eddystone_url)) {
                $this->pendingDevices[$tmp->uniqueId] = clone $tmp;
                $this->pendingDevices[$tmp->uniqueId]->url = $estimoteDevice->pending_settings->advertisers->eddystone_url[0]->url;
            }

            $this->devices[$tmp->uniqueId] = clone $tmp;
            if (!empty($estimoteDevice->settings->advertisers->eddystone_url)) {
                $this->devices[$tmp->uniqueId]->url = $estimoteDevice->settings->advertisers->eddystone_url[0]->url;
            }

        }

        return ($pending) ? $this->pendingDevices : $this->devices;
    }

    public function listBeacons($uid = '')
    {
        return $this->normaliseDevices();
    }

    public function getPendingConfigs()
    {
        return $this->normaliseDevices(true);
    }

    public function getBeaconsByUrl($url = '')
    {
        # code...
    }
}
