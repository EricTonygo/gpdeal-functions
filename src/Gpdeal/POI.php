<?php

namespace src\Gpdeal;

/**
 * Description of POI
 *
 * @author Eric TONYE
 */
class POI {

    private $latitude;
    private $longitude;

    public function __construct($latitude = null, $longitude = null) {
        if ($latitude && $longitude) {
            $this->latitude = deg2rad($latitude);
            $this->longitude = deg2rad($longitude);
        }
    }

    public function getLatitude() {
        return $this->latitude;
    }

    public function setLatitude($latitude) {
        $this->latitude = $latitude;
        return $this;
    }

    public function getLongitude() {
        return $this->longitude;
    }

    public function setLongitude($longitude) {
        $this->longitude = $longitude;
    }

    public static function getPOIByAddress($address) {
        $url = "http://maps.google.com/maps/api/geocode/json?address=" . urlencode($address) . "&language=en-US";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_PROXYPORT, 3128);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        $response = curl_exec($ch);
        curl_close($ch);
        $response_a = json_decode($response);
        $poi = new POI(floatval($response_a->results[0]->geometry->location->lat), floatval($response_a->results[0]->geometry->location->lng));
        return $poi;
    }

    public function getDistanceInMetersTo(POI $other) {
        $radiusOfEarth = 6371000; // Earth's radius in meters.
        $diffLatitude = $other->getLatitude() - $this->latitude;
        $diffLongitude = $other->getLongitude() - $this->longitude;
        $a = sin($diffLatitude / 2) * sin($diffLatitude / 2) +
                cos($this->latitude) * cos($other->getLatitude()) *
                sin($diffLongitude / 2) * sin($diffLongitude / 2);
        $c = 2 * asin(sqrt($a));
        $distance = $radiusOfEarth * $c;
        return $distance/1000;
    }

}
