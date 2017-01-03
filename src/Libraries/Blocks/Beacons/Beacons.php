<?php namespace CoasterCms\Libraries;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;


/**
 * Beacon library
 */
class Beacons
{
  protected $class = null;

  /**
   * Construct the class
   *
   *
   * @param array $args
   * @return return type
   */
  function __construct(array $args = [])
  {
    $this->className = ($args['type']) ? 'CoasterCms\Libraries\Beacons\\'.$args['type'].'Beacon' : 'CoasterCms\Libraries\Beacons\KontaktBeacon';
    $this->class = new $this->className();

  }

  /**
   * Get list of beacons
   *
   * If uid is provided returns one beacon
   *
   * @param string $uid (Beacon UID)
   * @return Collection
   */
  public function getBeacons($uid = '')
  {
    return $this->class->listBeacons($uid);
  }

  /**
   * undocumented function summary
   *
   * Undocumented function long description
   *
   * @param type var Description
   * @return return type
   */
  public function bitlyCheck(Client $bitlyClient)
  {
      try {
          $bitlyResponse = json_decode($bitlyClient->request('GET', 'v3/user/info', [
              'query' => [
                  'access_token' => config('coaster::key.bitly')
              ]
          ])->getBody());
      } catch (RequestException $e) {
          return false;
      }
      return $bitlyResponse->status_code == 200;
  }

  /**
   * Bitly - ify a url
   *
   *
   * @param strung $url
   * @return 0|string
   */
  public function getBitly(String $url)
  {
    try {
        $bitlyClient = new Client(
            [
                'base_uri' => 'https://api-ssl.bitly.com/',
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded'
                ]
            ]
        );
        $bitlyResponse = json_decode($bitlyClient->request('GET', 'v3/shorten', [
            'query' => [
                'access_token' => config('coaster::key.bitly'),
                'longUrl' => $pageUrl.'?beacon_id='.$uniqueId
            ]
        ])->getBody());
        if ($bitlyResponse->status_code == 200) {
            $beaconUrl = 'http://bit.ly/' . $bitlyResponse->data->hash;
            $beaconUrlEncoded = '02' . bin2hex('bit.ly/' . $bitlyResponse->data->hash);
            return $beaconUrlEncoded;
        } else {
            FormMessage::add('page_info_other[beacons]', 'Error generating bit.ly url (response:  '.$bitlyResponse->status_txt.')');
            return 0;
        }
    } catch (RequestException $e) {
        FormMessage::add('page_info_other[beacons]', 'Error generating bit.ly url (response: '.$e->getCode().')');
        return 0;
    }
  }

  /**
   * Set a url for a beacon
   *
   * @param string $uid Beacon UID
   * @param string $url URL to set
   * @return boolean | string (error)
   */
  public function setBeaconUrl(String $uid, String $url)
  {
    $bitlyUrl = $this->getBitly($url);

    return $this->class->setUrl($uid, $url);
  }

  /**
   * Get a beacon by the id of a page
   *
   *
   * @param int $page_id
   * @return BeaconAbstract
   */
  public function getBeaconsByPageId(int $page_id)
  {
    $url = PageLang::getUrl($page_id);

    return $this->class->getBeaconsByUrl($url);
  }
}
