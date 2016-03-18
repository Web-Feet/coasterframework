<?php namespace CoasterCms\Models;

use CoasterCms\Helpers\View\FormMessage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;

Class BlockBeacon extends _BaseEloquent
{
    protected $table = 'block_beacons';

    private static $_client;
    private static $_beacons;
    private static $_bitly;

    public static function preload($addMissing = false)
    {
        if (!isset(self::$_beacons)) {
            self::$_beacons = [];
            foreach (self::all() as $beacon) {
                self::$_beacons[$beacon->unique_id] = $beacon;
            }
            try {
                $devicesData = json_decode(self::_client()->request('GET', 'device')->getBody());
                foreach ($devicesData->devices as $device) {
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
                    if ($beacon->url == self::_getUrl($beacon->device) && !empty($beacon->page_id)) {
                        $beacon->page_name = PageLang::full_name($beacon->page_id);
                    } else {
                        $beacon->page_id = 0;
                    }
                }
            } catch (\GuzzleHttp\Exception\RequestException $e) {
                self::$_beacons = [];
            }
        }
    }

    public static function getVisibleBeacons($allTypes = true)
    {
        self::preload();
        $visibleBeacons = [];
        foreach (self::$_beacons as $uniqueId => $beacon) {
            if (!$beacon->removed && ($allTypes || strtolower($beacon->device->deviceType) == 'beacon' && $beacon->device->profiles[0] == 'EDDYSTONE')) {
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
            $options[$beacon->unique_id] = $beacon->device->alias;
            if ($beacon->page_id == $pageId) {
                $selected[] = $beacon->unique_id;
            }
        }
        $selectData = new \stdClass;
        $selectData->options = $options;
        $selectData->selected = $selected;
        return $selectData;
    }

    public static function updateUrl($uniqueId, $pageId)
    {
        $beacon = self::where('unique_id', '=', $uniqueId)->first();

        if (!empty($beacon) && $pageId != $beacon->page_id) {

            $beaconUrl = URL::to('/');
            $beaconUrlParts = parse_url($beaconUrl);
            $beaconUrlEncoded = '02' . bin2hex($beaconUrlParts['host']);

            if ($pageId) {
                $pageUrl = PageLang::full_url($pageId);
                $pageUrl = URL::to($pageUrl);
                $bitlyResponse = json_decode(self::_bitly()->request('GET', 'v3/shorten', [
                    'query' => [
                        'access_token' => config('coaster::key.bitly'),
                        'longUrl' => $pageUrl
                    ]
                ])->getBody());
                if ($bitlyResponse->status_code == 200) {
                    $beaconUrl = 'http://bit.ly/' . $bitlyResponse->data->hash;
                    $beaconUrlEncoded = '02' . bin2hex('bit.ly/' . $bitlyResponse->data->hash);
                } else {
                    FormMessage::add('page_info[beacons]', 'Error generating bit.ly url (invalid API key or hostname)');
                    return 0;
                }
            }

            if ($beacon->url == $beaconUrl && $beacon->page_id == $pageId) {
                return 1;
            }

            self::_client()->request('POST', 'config/delete',
                ['query' =>
                    [
                        'uniqueId' => $uniqueId
                    ]
                ]
            );

            $response = json_decode(self::_client()->request('POST', 'device/update',
                ['query' =>
                    [
                        'uniqueId' => $uniqueId,
                        'deviceType' => 'beacon',
                        'url' => $beaconUrlEncoded
                    ]
                ]
            )->getBody());

            if ($response == 'Update successful.') {

                self::_client()->request('POST', 'config/create',
                    ['query' =>
                        [
                            'uniqueId' => $uniqueId,
                            'deviceType' => 'beacon',
                            'url' => $beaconUrlEncoded
                        ]
                    ]
                );

                $beacon->page_id = $pageId;
                $beacon->url = $beaconUrl;
                $beacon->save();
                return 1;
            } else {
                FormMessage::add('page_info[beacons]', 'Error updating device Url');
            }
        } else {
            FormMessage::add('page_info[beacons]', 'A selected beacon was not found');
        }

        return 0;
    }

    public static function addId($uniqueId = null)
    {
        if ($uniqueId) {

        } else {
            self::preload(true);
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

    private static function _client()
    {
        if (!isset(self::$_client)) {
            self::$_client = new \GuzzleHttp\Client(
                [
                    'base_uri' => 'https://api.kontakt.io/',
                    'headers' => [
                        'Accept' => 'application/vnd.com.kontakt+json;version=8',
                        'Content-Type' => 'application/x-www-form-urlencoded',
                        'Api-Key' => config('coaster::key.kontakt'),
                        'User-Agent' => 'Coaster CMS'
                    ]
                ]
            );
        }
        return self::$_client;
    }

    private static function _bitly()
    {
        if (!isset(self::$_bitly)) {
            self::$_bitly = new \GuzzleHttp\Client(
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
