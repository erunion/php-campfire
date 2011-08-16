<?php

/**
 * Campfire
 *
 * @brief PHP API for Campfire
 * @version 1.0
 * @author Jon Ursenbach <jon@ursenba.ch>
 * @license http://www.gnu.org/licenses/gpl-2.0.txt
 * @link http://github.com/jonursenbach/php-campfire
 */
class Campfire {
  /**
   * This is the HTTPS URL for your Campfire installation.
   *
   * @example https://account.campfirenow.com
   *
   */
  private $campfire_url;

  /**
   * This is the API token that 37signals gives you. To obtain this, click on
   * "My info" in Campfire and copy the API authentication token.
   *
   */
  private $campfire_apitoken;

  /**
   * You can grab this by clicking "copy link" in your Lobby and pulling the
   * trailing didgits on the link.
   *
   * @example https://account.campfirenow.com/room/123456
   * Room ID = 123456
   *
   */
  private $campfire_room_id;

  private $useragent = 'php-campfire/1.0 (http://github.com/jonursenbach/php-campfire)';
  private $cookie = 'php-campfire.cookie';

  private $verbose = false;
  private $verbose_curl = false;

  /**
   * @param string $url
   * @param string $apitoken
   */
  public function __construct($url, $token) {
    $this->campfire_url = $url;
    $this->campfire_apitoken = $token;
  }

  /**
   * Enable/disable script verbose.
   *
   * @param boolean $enable
   */
  public function verbose($enable=true) {
    $this->verbose = $enable;
  }

  /**
   * Enable/disable script verbose.
   *
   * @param boolean $enable
   */
  public function verbose_curl($enable=true) {
    $this->verbose_curl = $enable;
  }

  /**
   * Set the current room you want to enter.
   *
   * @param integer $room_id
   */
  public function set_room($room_id) {
    $this->campfire_room_id = $room_id;
  }

  /**
   * Return the currently set room id.
   *
   * @return integer
   */
  public function get_room() {
    return $this->campfire_room_id;
  }

  /**
   * Join the room you set with set_room().
   *
   */
  public function join() {
    $this->post('/room/' . $this->campfire_room_id . '/join.json');
  }

  /**
   * Leave the room you set with set_room().
   *
   */
  public function leave() {
    $this->post('/room/' . $this->campfire_room_id . '/leave.json');
  }

  /**
   * Say a message.
   *
   * @param string $message
   */
  public function say($message) {
    if ($this->verbose) {
      echo "say: " . $message . "\n";
    }

    $this->post('/room/' . $this->campfire_room_id . '/speak.xml', array(
      'message' => array(
        'type' => 'TextMessage',
        'body' => $message
      )
    ));
  }

  /**
   * Paste a block of text.
   *
   * @param string $message
   */
  public function paste($message) {
    if ($this->verbose) {
      echo "paste: " . $message . "\n";
    }

    $this->post('/room/' . $this->campfire_room_id . '/speak.xml', array(
      'message' => array(
        'type' => 'PasteMessage',
        'body' => $message
      )
    ));
  }

  /**
   * Play sound: rimshot, crickets, trombone
   *
   * @param string $sound
   */
  public function play_sound($sound) {
    if ($this->verbose) {
      echo "play_sound: " . $message . "\n";
    }

    $this->post('/room/' . $this->campfire_room_id . '/speak.xml', array(
      'message' => array(
        'type' => 'SoundMessage',
        'body' => $sound
      )
    ));
  }

  /**
   * Return the live stream URL.
   *
   * @return string
   */
  public function get_stream_url() {
    return 'https://' . $this->campfire_apitoken . ':x@streaming.campfirenow.com/room/' . $this->campfire_room_id . '/live.json';
  }

  /**
   * Return data about the current user you are authenticated with.
   *
   * @return string|null
   */
  public function whoami() {
    $whoami = $this->get('/users/me.json');
    if (isset($whoami['user'])) {
      return $whoami['user'];
    }

    return null;
  }

  /**
   * Get information on a specific user.
   *
   * @param integer $user_id
   * @return array|boolean
   */
  public function get_user($user_id) {
    $user = $this->get('/users/' . $user_id . '.json');
    return (isset($user['user']) && !empty($user['user'])) ? $user['user'] : false;
  }

  /**
   * Pull an array of every room.
   *
   * @return array|boolean
   */
  public function get_rooms() {
    $rooms = $this->get('/rooms.json');
    return (isset($rooms['rooms']) && !empty($rooms['rooms'])) ? $rooms['rooms'] : false;
  }

  /**
   * Pull todays chatroom transcript.
   *
   * @return array|boolean
   */
  public function get_transcript() {
    $transcript = $this->get('/room/' . $this->campfire_room_id . '/transcript.json');
    return (isset($transcript['messages']) && !empty($transcript['messages'])) ? $transcript['messages'] : false;
  }

  /**
   * Post data to Campfire.
   *
   * @param string $page
   * @param null|array $data
   * @return boolean|string
   */
  private function post($page, $data=null) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $this->campfire_url . $page);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_USERAGENT, $this->useragent);
    curl_setopt($ch, CURLOPT_VERBOSE, $this->verbose_curl);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_USERPWD, $this->campfire_apitoken . ':x');
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type: application/json'));
    curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookie);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    $output = curl_exec($ch);
    $output = trim($output);
    curl_close($ch);

    return ((!empty($output)) ? json_decode($output, true) : true);
  }

  /**
   * Get data from Campfire.
   *
   * @param string $page
   * @return string
   */
  private function get($page) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $this->campfire_url . $page);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, $this->useragent);
    curl_setopt($ch, CURLOPT_VERBOSE, $this->verbose_curl);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookie);
    curl_setopt($ch, CURLOPT_USERPWD, $this->campfire_apitoken . ':x');
    $output = curl_exec($ch);
    $output = trim($output);
    curl_close($ch);

    return json_decode($output, true);
  }
}
