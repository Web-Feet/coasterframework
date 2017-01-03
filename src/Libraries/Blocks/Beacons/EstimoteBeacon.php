<?php namespace CoasterCms\Libraries\Blocks\Beacons;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use URL;
use CoasterCms\Libraries\Builder\FormMessage;


/**
 * Estimote beacon "driver"
 */
class EstimoteBeacon extends BeaconAbstract
{
  public function getClient()
  {
    return new Client(
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

  public function setUrl($uid, $url)
  {
    try {
      //  Delete pending_settings
      $client = $this->getClient();
      // $r = $client->request('POST', 'devices/delete_pending_settings',
      //     ['json' => ['indentifiers' => [$uid]]]
      // );

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

      $client = $this->getClient();
      $r = $client->request('POST', 'devices/'.$uid,
          ['json' => $settings]
      );

      return true;
    } catch (RequestException $e) {
      FormMessage::add('page_info_other[beacons]', 'Error setting URL with Estimote API (response: '.$e->getMessage().')');

      return $e->getMessage();
    }
  }

  /**
   * Normalise Estimote return array to match up to expected for BlockBeaon
   *
   * @param boolean $pending
   * @return stdClass
   */
  public function normaliseDevices($pending = false)
  {
    $this->devices->devices = [];
    $this->pendingDevices = new \stdClass;
    $this->pendingDevices->devices = [];
    foreach ($this->originalDevices as $estimoteDevice) {

        $tmp = new \stdClass;

        $tmp->id = $estimoteDevice->identifier;
        $tmp->type = 'Estimote';
        $tmp->deviceType = 'Estimote';
        $tmp->alias = $estimoteDevice->shadow->name;
        $tmp->uniqueId = $estimoteDevice->identifier;
        $tmp->profiles = ['EDDYSTONE'];
        if ($pending) {
          if (!empty($estimoteDevice->pending_settings->advertisers->eddystone_url)) {
            $tmp->url = $estimoteDevice->pending_settings->advertisers->eddystone_url[0]->url;
          }
          $this->pendingDevices->devices[$tmp->uniqueId] = $tmp;
        }
        else
        {
          if (!empty($estimoteDevice->settings->advertisers->eddystone_url)) {
            $tmp->url = $estimoteDevice->settings->advertisers->eddystone_url[0]->url;
          }
          $this->devices->devices[$tmp->uniqueId] = $tmp;

        }
    }

    return ($pending) ? $this->pendingDevices : $this->devices;
  }

  public function listBeacons($uid = '')
  {
    $client = $this->getClient();
    try {
      $r = $client->request('GET', 'devices');
      $this->devices = new \stdClass;
      $this->originalDevices = json_decode($client->request('GET', 'devices')->getBody());
      $this->normaliseDevices();
      return $this->devices;
    } catch (Exception $e) {
      dd($e->getMessage());
    }

  }

  public function getPendingConfigs()
  {
    $tmp = new \stdClass;
    $this->normaliseDevices(true);
    $tmp->configs = $this->pendingDevices->devices;

    return $tmp;
  }

  public function getBeaconsByUrl($url = '')
  {
    # code...
  }
}
