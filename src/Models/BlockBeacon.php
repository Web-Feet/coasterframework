<?php namespace CoasterCms\Models;

use Auth;
use CoasterCms\Helpers\Cms\Page\Path;
use CoasterCms\Libraries\Builder\FormMessage;
use Eloquent;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use URL;
use View;

Class BlockBeacon extends Eloquent
{
    protected $table = 'block_beacons';

    private static $_client;
    private static $_beacons;
    private static $_bitly;
    private static $_class;
    private static $_className;

    public static function getClass($type = 'Kontakt')
    {
      if (stristr(static::$_className, $type) !== false && ! empty(static::$_class)) {
        return static::$_class;
      }
      static::$_className = 'CoasterCms\Libraries\Blocks\Beacons\\'.$type.'Beacon';
      static::$_class = new static::$_className;
      return static::$_class;
    }

    public static function preload($addMissing = false)
    {
        if (!isset(self::$_beacons)) {
            self::$_beacons = [];
            foreach (self::all() as $beacon) {
                self::$_beacons[$beacon->unique_id] = $beacon;
            }
            try {
                $devicesData = new \stdClass;
                $devicesData->devices = [];
                $getPendingConfigs = new \stdClass;
                $getPendingConfigs->configs = [];
                $pendingConfigs = [];
                if (config('coaster::key.kontakt')) {
                  $devicesData = static::getClass()->listBeacons();

                  $getPendingConfigs = static::getClass()->getPendingConfigs();
                }
                if (config('coaster::key.estimote')) {

                  $estimoteDevices = static::getClass('Estimote')->listBeacons();
                  $devicesData->devices += $estimoteDevices->devices;
                  $estimotePendingConfigs = static::getClass('Estimote')->getPendingConfigs();
                  $getPendingConfigs->configs += $estimotePendingConfigs->configs;
                }

                foreach ($getPendingConfigs->configs as $pendingConfig) {
                    if (!empty($pendingConfig->url)) {
                        $pendingConfigs[$pendingConfig->uniqueId] = $pendingConfig;
                    }
                }
                foreach ($devicesData->devices as $device) {
                    if (!empty($pendingConfigs[$device->uniqueId]) && isset($device->url) && $pendingConfigs[$device->uniqueId]->url != $device->url) {
                        $device->url = $pendingConfigs[$device->uniqueId]->url;
                        $device->pending = true;
                    } else {
                        $device->pending = false;
                    }
                    if (isset(self::$_beacons[$device->uniqueId])) {
                        if (!self::$_beacons[$device->uniqueId]->visible && $addMissing) {
                            self::$_beacons[$device->uniqueId]->removed = 0;
                            self::$_beacons[$device->uniqueId]->save();
                        }
                        if (self::$_beacons[$device->uniqueId]->url != self::_getUrl($device)) {
                            self::$_beacons[$device->uniqueId]->page_id = 0;
                            self::$_beacons[$device->uniqueId]->url = self::_getUrl($device);
                            self::$_beacons[$device->uniqueId]->save();
                        }
                        self::$_beacons[$device->uniqueId]->device = $device;
                    } elseif ($addMissing) {

                        $newBeacon = new self;
                        $newBeacon->type = (empty($device->type)) ? 'Kontakt' : $device->type;
                        $newBeacon->unique_id = $device->uniqueId;
                        $newBeacon->url = self::_getUrl($device);
                        $newBeacon->page_id = 0;
                        $newBeacon->removed = 0;
                        $newBeacon->save();
                        self::$_beacons[$device->uniqueId] = $newBeacon;
                        self::$_beacons[$device->uniqueId]->device = $device;
                    }
                }
                foreach (self::$_beacons as $k => $beacon) {
                    if (empty($beacon->device)) {
                        unset(self::$_beacons[$k]);
                        continue;
                    }
                    $beacon->page_name = '';
                    if ($beacon->url == self::_getUrl($beacon->device) && ! empty($beacon->page_id)) {
                        $beacon->page_name = Path::getFullName($beacon->page_id);
                    } else {
                        $beacon->page_id = 0;
                    }
                }
            } catch (RequestException $e) {
                self::$_beacons = [];
                return 0;
            }
        }
        return 1;
    }

    public static function getVisibleBeacons($allTypes = true)
    {
        self::preload();
        $visibleBeacons = [];
        foreach (self::$_beacons as $uniqueId => $beacon) {
          $loweredDeviceType = strtolower($beacon->device->deviceType);
            if (!$beacon->removed && ($allTypes || in_array($loweredDeviceType, ['beacon', 'estimote']) && $beacon->device->profiles[0] == 'EDDYSTONE')) {
                $visibleBeacons[$uniqueId] = $beacon;
            }
        }
        return $visibleBeacons;
    }

    private static function _getUrl($device)
    {
        if (!isset($device->url) || $device->profiles[0] != 'EDDYSTONE') {
            return '';
        }
        if (substr($device->url, 0,2) == 'ht') {
          return $device->url;
        }
        $prefixes = [
            '00' => 'http://www.',
            '01' => 'https://www.',
            '02' => 'http://',
            '03' => 'https://'
        ];
        $prefix = $prefixes[substr($device->url, 0, 2)];

        return $prefix . hex2bin(substr($device->url, 2));
    }

    public static function getTableRows()
    {
        if ($beaconsData = self::getVisibleBeacons()) {
            return View::make('coaster::partials.themes.beacons.rows', ['beaconsData' => $beaconsData]);
        } else {
            return '';
        }
    }

    public static function getDropdownOptions($pageId)
    {
        $options = [];
        $selected = [];
        foreach (self::getVisibleBeacons(false) as $beacon) {
            $options[$beacon->unique_id] = $beacon->device->uniqueId;
            if ($beacon->device->alias) {
                $options[$beacon->unique_id] .= ' ('.$beacon->device->alias.')';
            }
            if ($beacon->page_id == $pageId) {
                $selected[] = $beacon->unique_id;
            }
        }
        $selectData = new \stdClass;
        $selectData->options = $options;
        $selectData->selected = $selected;
        return $selectData;
    }

    public static function updatePage($pageId, $setUniqueIds)
    {
        $setBeacons = BlockBeacon::where('page_id', '=', $pageId)->get();
        $existingBeacons = [];
        foreach ($setBeacons as $setBeacon) {
            $existingBeacons[$setBeacon->unique_id] = $setBeacon->unique_id;
        }
        if (!empty($existingBeacons)) {
            BlockBeacon::preload(); // check page relations (remove page id off beacons if url changed)
        }
        // update url of beacons
        foreach ($setUniqueIds as $uniqueId) {
            if (!empty($existingBeacons[$uniqueId])) {
                unset($existingBeacons[$uniqueId]);
            }
            BlockBeacon::updateUrl($uniqueId, $pageId);
        }
        // unset url of no longer linked beacons
        foreach ($existingBeacons as $uniqueId) {
            BlockBeacon::updateUrl($uniqueId, 0);
        }
    }

    public static function updateUrl($uniqueId, $pageId)
    {
        $beacon = self::where('unique_id', '=', $uniqueId)->first();

        if (empty($beacon)) {
            FormMessage::add('page_info_other[beacons]', 'A selected beacon was not found');
        } else {
            $beaconUrl = URL::to('/');

            if ($pageId) {
                $pageUrl = Path::getFullUrl($pageId);
                $pageUrl = URL::to($pageUrl);
                try {
                    $bitlyResponse = json_decode(self::_bitly()->request('GET', 'v3/shorten', [
                        'query' => [
                            'access_token' => config('coaster::key.bitly'),
                            'longUrl' => $pageUrl.'?beacon_id='.$uniqueId
                        ]
                    ])->getBody());
                    if ($bitlyResponse->status_code == 200) {
                        $beaconUrl = 'http://bit.ly/' . $bitlyResponse->data->hash;
                    } else {
                        FormMessage::add('page_info_other[beacons]', 'Error generating bit.ly url (response:  '.$bitlyResponse->status_txt.')');
                        return 0;
                    }
                } catch (RequestException $e) {
                    FormMessage::add('page_info_other[beacons]', 'Error generating bit.ly url (response: '.$e->getCode().')');
                    return 0;
                }
            }

            if ($beacon->url == $beaconUrl && $beacon->page_id == $pageId) {
                return 1;
            }

            $updated = static::getClass($beacon->type)->setUrl($uniqueId, $beaconUrl);
            if ($updated)
            {
              $beacon->page_id = $pageId;
              $beacon->url = $beaconUrl;
              $beacon->save();
            }
            return 1;

        }

        return 0;
    }

    public static function addId($uniqueId = null)
    {
        if ($uniqueId) {
            return null;
        } else {
            return self::preload(true);
        }
    }

    public static function removeId($uniqueId)
    {
        $beacon = self::where('unique_id', '=', $uniqueId)->first();
        if (!empty($beacon)) {
            $beacon->removed = 1;
            $beacon->save();
        }
        unset(self::$_beacons[$uniqueId]);
    }

    public static function bitlyCheck()
    {
        try {
            $bitlyResponse = json_decode(self::_bitly()->request('GET', 'v3/user/info', [
                'query' => [
                    'access_token' => config('coaster::key.bitly')
                ]
            ])->getBody());
        } catch (RequestException $e) {
            return false;
        }
        return $bitlyResponse->status_code == 200;
    }

    private static function _bitly()
    {
        if (!isset(self::$_bitly)) {
            self::$_bitly = new Client(
                [
                    'base_uri' => 'https://api-ssl.bitly.com/',
                    'headers' => [
                        'Content-Type' => 'application/x-www-form-urlencoded'
                    ]
                ]
            );
        }
        return self::$_bitly;
    }

}
