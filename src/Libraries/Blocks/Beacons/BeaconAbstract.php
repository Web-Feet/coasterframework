<?php namespace CoasterCms\Libraries\Blocks\Beacons;

/**
 * Beacon Abstract
 */
abstract class BeaconAbstract
{
  /**
   * Abstract for beacon "driver" Get list of beacons
   *
   * If uid is provided returns one beacon
   *
   * @param string [$uid (Beacon UID)]
   * @return stdClass
   */
  abstract function listBeacons($uid = '');

  /**
   * Abstract for beacon "driver" Set url of beacon
   *
   * @param string $uid (Beacon UID)
   * @param string $url
   * @return boolean|string
   */
  abstract function setUrl($uid, $url);

  /**
   * Abdstract for beacon "driver" Get list of beacons
   *
   * Returns collection of beacons with the url assigned to them
   *
   * @param string|null $uid (Beacon UID)
   * @return Collection
   */
  abstract function getBeaconsByUrl($url = '');


  /**
   * Get guzzle client abstract
   *
   * @return Client
   */
  abstract public function getClient();

  /**
   * Get pending configs
   *
   * @return  stdClass
   */
  abstract public function getPendingConfigs();

}
