<?php

/**
 * Description of Gateway
 *
 * @author Andreas Kollaros
 */
abstract class Merchant_Billing_Gateway {

  public $money_format = 'dollars'; # or cents
  public $default_currency;
  public $supported_countries = array();
  public $supported_cardtypes = array('visa', 'master', 'american_express', 'switch', 'solo', 'maestro');
  public $homepage_url;
  public $display_name;
  private $DEBIT_CARDS = array('switch', 'solo');

  public function supports($card_type) {
    return in_array($card_type, $this->supported_cardtypes);
  }

  public function is_test() {
    return (Merchant_Billing_Base::$gateway_mode == 'test');
  }

  public function mode() {
    return Merchant_Billing_Base::$gateway_mode;
  }

  public function amount($money) {
    if (null === $money)
      return null;

    $money = number_format($money, 2);
    $cents = $money * 100;
    if (!is_numeric($money) || $cents < 0) {
      throw new Exception('money amount must be a positive Integer in cents.');
    }
    return ($this->money_format == 'cents') ? $cents : $money;
//    if ($this->money_format == 'cents') {
//      return $cents;
//    } else {
//      return sprintf("%.2f", $cents / 100);
//    }
  }

  private function card_brand($source) {
    $result = isset($source->brand) ? $source->brand : $source->type;
    return strtolower($result);
  }

  public function requires_start_date_or_issue_number(CreditCard $creditcard) {
    $card_band = $this->card_brand($creditcard);
    if (empty($card_band))
      return false;
    return in_array($this->card_brand($creditcard), $this->DEBIT_CARDS);
  }

  /**
   * PostsData
   */
  public function ssl_get($endpoint, $data, $timeout = '0', $headers = array()) {
    return $this->ssl_request('get', $endpoint, $data, $timeout, $headers);
  }

  public function ssl_post($endpoint, $data, $timeout = '0', $headers = array()) {
    return $this->ssl_request('post', $endpoint, $data, $timeout, $headers);
  }

  private function ssl_request($method, $endpoint, $data, $timeout = '0', $headers = array()) {
    $connection = new Merchant_Connection($endpoint);
    return $connection->request($method, $data, $timeout, $headers);
  }

  /**
   * Utils
   */
  public function generate_unique_id() {
    return substr(uniqid(rand(), true), 0, 10);
  }

  /**
   * PostData
   */

  /**
   * Convert an associative array to url parameters
   * @params array key/value hash of parameters
   * @return string
   */
  function urlize($params){
    $string = "";
    foreach ($params as $key => $value) {
      $string .= $key . '=' . urlencode(trim($value)) . '&';
    }
    return rtrim($string,"& ");
  }

  /**
   * RequiresParameters
   * @param string comma seperated parameters. Represent keys of $options array
   * @param array the key/value hash of options to compare with
   */
  public function required_options($required, $options = array()) {
    $required = explode(',', $required);
    foreach ($required as $r) {
      if (!array_key_exists(trim($r), $options)) {
        throw new Exception($r . " parameter is required!");
        break;
        return false;
      }
    }
    return true;
  }

  /**
   * CreditCardFormatting
   */
  public function cc_format($number, $options) {
    if (empty($number))
      return '';

    switch ($options) {
      case 'two_digits':
        $number = sprintf("%02d", $number);
        return substr($number, -2);
        break;
      case 'four_digits':
        return sprintf("%04d", $number);
        break;
      default:
        return $number;
        break;
    }
  }

}
?>