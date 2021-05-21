<?php
namespace BitsoAPI;

class bitsoException extends \ErrorException {};

class bitso
{
  protected $key;
  protected $secret;
  protected $url;
	
  #constructor, default is dev url
  public function __construct($key='', $secret='', $url="https://bitso.com/api/v3"){
    $this->key = $key;
    $this->secret = $secret;
    $this->url = $url;
  }

  #function to perform curl url request depending on type and method
  function url_request($type, $path, $HTTPMethod, $JSONPayload, $authHeader=''){
    $ch = curl_init();
    if ($type == 'PUBLIC') {
      curl_setopt($ch, CURLOPT_URL, $path);
      curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
    } else if ($type == 'PRIVATE') {
      if ($HTTPMethod == 'GET' or $HTTPMethod == 'DELETE') {
        curl_setopt($ch, CURLOPT_URL, $path);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $HTTPMethod);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
          'Authorization: ' .  $authHeader,
          'Content-Type: application/json'));
      } else if ($HTTPMethod == 'POST') {
        curl_setopt($ch, CURLOPT_URL, $path);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $HTTPMethod);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $JSONPayload);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: ' .  $authHeader,'Content-Type: application/json'));
      } else {
        echo "Incorrect HTTP method";
      }
    }
    $result = curl_exec($ch);
    curl_close($ch);
    return $result;
  }

  private function makeNonce(){
    $nonce = round(microtime(true)*1000);
    return $nonce;
  }

  function checkAndDecode($result){
    $result = json_decode($result);
    if($result->success != 1){
      throw new bitsoException($result->error->message, 1);
    } else {
      return $result;
    }
  }
######          #######
###### PUBLIC QUERIES #######
######          #######

  function available_books(){
     /*
     Returns:
      A list of bitso.AvilableBook instances */

    $path = $this->url . "/available_books/";
    $type = 'PUBLIC';
    $HTTPMethod = 'GET';
    $JSONPayload = '';
    $result = $this->url_request($type, $path, $HTTPMethod, $JSONPayload);
    return $this->checkAndDecode($result);
  }

  function ticker($params){
    /*
    Get a Bitso price ticker.
      Args:
        book (str):
          Specifies which book to use. 
            
      Returns:
        A bitso.Ticker instance. */
    
    $parameters = http_build_query($params,'','&');
    $path = $this->url . "/ticker/?".$parameters;
    $type = 'PUBLIC';
    $HTTPMethod = 'GET';
    $JSONPayload = '';
    $result = $this->url_request($type, $path, $HTTPMethod, $JSONPayload);
    return $this->checkAndDecode($result);
  }

  function order_book($params){
    /*
    Get a public Bitso order book with a list of all open orders in the specified book
      Args:
        book (str):
          Specifies which book to use. Default is btc_mxn
        aggregate (bool):
          Specifies if orders should be aggregated by price
          
      Returns:
        A bitso.OrderBook instance. */

    $parameters = http_build_query($params,'','&');
    $path = $this->url . "/order_book/?".$parameters;
    $type = 'PUBLIC';
    $HTTPMethod = 'GET';
    $JSONPayload = '';
    $result = $this->url_request($type, $path, $HTTPMethod, $JSONPayload);

    return $this->checkAndDecode($result);
  }

  function trades($params){
    /*
    Get a list of recent trades from the specified book.
      Args:
        book (str):
          Specifies which book to use. Default is btc_mxn
        marker (str, optional):
          Returns objects that are older or newer (depending on 'sort') than the object which
          has the marker value as ID
        limit (int, optional):
          Limit the number of results to parameter value, max=100, default=25
        sort (str, optional):
          Sorting by datetime: 'asc', 'desc'
          Defuault is 'desc'
          
      Returns:
        A list of bitso.Trades instances. */

    $parameters = http_build_query($params,'','&');
    $path = $this->url . "/trades/?".$parameters;
    $type = 'PUBLIC';
    $HTTPMethod = 'GET';
    $JSONPayload = '';
    $result = $this->url_request($type, $path, $HTTPMethod, $JSONPayload);

    return $this->checkAndDecode($result);
  }




  #gets data and makes request
  function getData($nonce,$path,$RequestPath,$HTTPMethod,$JSONPayload,$type){
    $message = $nonce . $HTTPMethod . $RequestPath . $JSONPayload;
    $signature = hash_hmac('sha256', $message, $this->secret);
    $format = 'Bitso %s:%s:%s';
    $authHeader =  sprintf($format, $this->key, $nonce, $signature);
    $result = $this->url_request($type, $path, $HTTPMethod, $JSONPayload, $authHeader);

    return $this->checkAndDecode($result);
  }
    
    ######           #######
###### PRIVATE QUERIES #######
######           #######

  function account_status(){
  	/*
    Get a user's account status.
      Returns:
        A bitso.AccountStatus instance. */

    $path = $this->url . "/account_status/";
    $RequestPath = "/v3/account_status/";
    $nonce = $this->makeNonce();
    $HTTPMethod = 'GET';
    $JSONPayload = '';
    $type = 'PRIVATE';

    return $this->getData($nonce,$path,$RequestPath,$HTTPMethod,$JSONPayload,$type);

  }

  function balances(){
    /*
    Get a user's balance.
        Returns:
          A list of bitso.Balance instances.
    */

    $path = $this->url . "/balance/";
    $RequestPath = "/v3/balance/";
    $nonce = $this->makeNonce();
    $HTTPMethod = 'GET';
    $JSONPayload = '';
    $type = 'PRIVATE';

    return $this->getData($nonce,$path,$RequestPath,$HTTPMethod,$JSONPayload,$type);
  }

  function fees(){
    /*
    Get a user's fees for all availabel order books.
      Returns:
        A list bitso.Fees instances.
    */
    $path = $this->url . "/fees/";
    $RequestPath = "/v3/fees/";
    $nonce = $this->makeNonce();
    $HTTPMethod = 'GET';
    $JSONPayload = '';
    $type = 'PRIVATE';

    return $this->getData($nonce,$path,$RequestPath,$HTTPMethod,$JSONPayload,$type);
  }

  function ledger($params){
  	/*
    Get the ledger of user operations 
    Args:
      operations (str, optional):
        They type of operations to include. Enum of ('trades', 'fees', 'fundings', 'withdrawals')
        If None, returns all the operations.
      marker (str, optional):
        Returns objects that are older or newer (depending on 'sort') than the object which
        has the marker value as ID
      limit (int, optional):
        Limit the number of results to parameter value, max=100, default=25
      sort (str, optional):
        Sorting by datetime: 'asc', 'desc'
        Defuault is 'desc'
    Returns:
      A list bitso.LedgerEntry instances.
    */

    $parameters = http_build_query($params,'','&');
    $path = $this->url . "/ledger/?".$parameters;
    $RequestPath = "/v3/ledger/?".$parameters;
    $nonce = $this->makeNonce();
    $HTTPMethod = 'GET';
    $JSONPayload = '';
    $type = 'PRIVATE'; 
  	
    return $this->getData($nonce,$path,$RequestPath,$HTTPMethod,$JSONPayload,$type);
  }

  function withdrawals($params){
    /*
    Get the ledger of user operations 
    Args:
      wids (list, optional):
        Specifies which withdrawal objects to return
      marker (str, optional):
        Returns objects that are older or newer (depending on 'sort') than the object which
        has the marker value as ID
      limit (int, optional):
        Limit the number of results to parameter value, max=100, default=25
      sort (str, optional):
        Sorting by datetime: 'asc', 'desc'
        Defuault is 'desc'
    Returns:
      A list bitso.Withdrawal instances.
    */
    if(in_array('wids',$params)){
      $ids = $params('wids');
      unset($params['wids']);
      $id_nums = implode('', $ids);
      $path = $this->url . "/withdrawals/".$id_nums."/?".$parameters;
      $RequestPath = "/v3/withdrawals/".$id_nums."/?".$parameters;
    }
    $parameters = http_build_query($params,'','&');
    $path = $this->url . "/withdrawals/?".$parameters;
    $RequestPath = "/v3/withdrawals/?".$parameters;

    $nonce = $this->makeNonce();
    $HTTPMethod = 'GET';
    $JSONPayload = '';
    $type = 'PRIVATE';

    return $this->getData($nonce,$path,$RequestPath,$HTTPMethod,$JSONPayload,$type);
  }

  function fundings($params){
    /*
    Get the ledger of user operations 
    Args:
      fids (list, optional):
        Specifies which funding objects to return
      marker (str, optional):
        Returns objects that are older or newer (depending on 'sort') than the object which
        has the marker value as ID
      limit (int, optional):
        Limit the number of results to parameter value, max=100, default=25
      sort (str, optional):
        Sorting by datetime: 'asc', 'desc'
        Defuault is 'desc'
    Returns:
      A list bitso.Funding instances.
    */
    if(in_array('fids',$params)){
      $ids = $params('fids');
      unset($params['fids']);
      $id_nums = implode('', $ids);
      $path = $this->url . "/withdrawals/".$id_nums."/?".$parameters;
      $RequestPath = "/v3/withdrawals/".$id_nums."/?".$parameters;
    }
    $parameters = http_build_query($params,'','&');
	  $path = $this->url . "/fundings/?".$parameters;
    $RequestPath = "/v3/fundings/?".$parameters;
    $nonce = $this->makeNonce();
    $HTTPMethod = 'GET';
    $JSONPayload = '';
    $type = 'PRIVATE';

    return $this->getData($nonce,$path,$RequestPath,$HTTPMethod,$JSONPayload,$type);
  }

  function order_trades($id){
    /*
      Returns all Trades Associated with an order
    */
    $path = $this->url . "/order_trades/".$id;
    $RequestPath = "/v3/order_trades/".$id;
    $nonce = $this->makeNonce();
    $HTTPMethod = 'GET';
    $JSONPayload = '';
    $type = 'PRIVATE';

    return getData($nonce,$path,$RequestPath,$HTTPMethod,$JSONPayload,$type);
  }

  function user_trades($params, $ids=[]){
  	/*
    Get a list of the user's transactions
    Args:
       book (str):
        Specifies which order book to get user trades from. 
      marker (str, optional):
        Returns objects that are older or newer (depending on 'sort') than the object which
        has the marker value as ID
      limit (int, optional):
        Limit the number of results to parameter value, max=100, default=25
      sort (str, optional):
        Sorting by datetime: 'asc', 'desc'
        Defuault is 'desc'
     
    Returns:
      A list bitso.UserTrade instances.
    */
    $id_nums = implode('', $ids);
    $parameters = http_build_query($params,'','&');
    $path = $this->url . "/user_trades/".$id_nums."/?".$parameters;
    $RequestPath = "/v3/user_trades/".$id_nums."/?".$parameters;
    $nonce = $this->makeNonce();
    $HTTPMethod = 'GET';
    $JSONPayload = '';
    $type = 'PRIVATE';

    return $this->getData($nonce,$path,$RequestPath,$HTTPMethod,$JSONPayload,$type);
  }

  function open_orders($params){
  	/*
    Get a list of the user's open orders
    Args:
      book (str):
        Specifies which book to use. Default is btc_mxn
        
    Returns:
      A list of bitso.Order instances.
    */
    $parameters = http_build_query($params,'','&');
    $path = $this->url . "/open_orders/?".$parameters;
    $RequestPath = "/v3/open_orders/?".$parameters;
    $nonce = $this->makeNonce();
    $HTTPMethod = 'GET';
    $JSONPayload = '';
    $type = 'PRIVATE';

    return $this->getData($nonce,$path,$RequestPath,$HTTPMethod,$JSONPayload,$type);
  }

  function lookup_order($ids){
    /*
    Get a list of details for one or more orders
    Args:
      order_ids (list):
        A list of Bitso Order IDs
        
    Returns:
      A list of bitso.Order instances. 
    */
    $parameters = implode(',', $ids);
    $path = $this->url . "/orders/".$parameters;
    $RequestPath = "/v3/orders/".$parameters;
    $nonce = $this->makeNonce();
    $HTTPMethod = 'GET';
    $JSONPayload = '';
    $type = 'PRIVATE';

    return $this->getData($nonce,$path,$RequestPath,$HTTPMethod,$JSONPayload,$type);
  }

  function cancel_order($ids){
  	/*
    Cancels an open order
    Args:
      order_id (str):
        A Bitso Order ID.
        
    Returns:
      A list of Order IDs (OIDs) for the canceled orders. Orders may not be successfully cancelled if they have been filled, have been already cancelled, or the OIDs are incorrect
    */
    if ($ids === 'all') {
	$parameters = 'all';
    } else {
	$parameters = implode(',', $ids);
    }

    $path = $this->url . "/orders/".$parameters;
    $RequestPath = "/v3/orders/".$parameters;
    $nonce = $this->makeNonce();
    $HTTPMethod = 'DELETE';
    $JSONPayload = '';
    $type = 'PRIVATE';

    return $this->getData($nonce,$path,$RequestPath,$HTTPMethod,$JSONPayload,$type);
  }

  function place_order($params){
  	/*
    Places a buy limit order.
      Args:
        book (str):
          Specifies which book to use. 
        side (str):
          the order side (buy or sell) 
        order_type (str):
          Order type (limit or market)
        major (str):
          The amount of major currency for this order. An order could be specified in terms of major or minor, never both.
        minor (str):
          The amount of minor currency for this order. An order could be specified in terms of major or minor, never both.
        price (str):
          Price per unit of major. For use only with limit orders.
      Returns:
        A bitso.Order instance. 
    */
    $path = $this->url . "/orders/";
    $RequestPath = "/v3/orders/";
    $nonce = $this->makeNonce();
    $HTTPMethod = 'POST';
    $JSONPayload = json_encode($params);
    $type = 'PRIVATE';

    return $this->getData($nonce,$path,$RequestPath,$HTTPMethod,$JSONPayload,$type);
  }

  function funding_destination($params){
  	/*
    Returns account funding information for specified currencies.
      Args:
        fund_currency (str):
          Specifies which book to use. 
      
      Returns:
        A bitso.Funding Destination instance.
    */
    $parameters = http_build_query($params,'','&');
    $path = $this->url . "/funding_destination/?".$parameters;
    $RequestPath = "/v3/funding_destination/?".$parameters;
    $nonce = $this->makeNonce();
    $HTTPMethod = 'GET';
    $JSONPayload = '';
    $type = 'PRIVATE';

    return $this->getData($nonce,$path,$RequestPath,$HTTPMethod,$JSONPayload,$type);
  }

  function btc_withdrawal($params){
		/*
    Triggers a bitcoin withdrawal from your account
      Args:
        amount (str):
          The amount of BTC to withdraw from your account
        address (str):
          The Bitcoin address to send the amount to
      
      Returns:
        ok
    */
    $path = $this->url . "/bitcoin_withdrawal/";
    $RequestPath = "/v3/bitcoin_withdrawal/";
    $nonce = $this->makeNonce();
    $HTTPMethod = 'POST';	
    $JSONPayload = json_encode($params);
    $type = 'PRIVATE';

    return $this->getData($nonce,$path,$RequestPath,$HTTPMethod,$JSONPayload,$type);
  }

  function eth_withdrawal($params){
  	/*
    Triggers an ether withdrawal from your account
      Args:
        amount (str):
          The amount of BTC to withdraw from your account
        address (str):
          The Bitcoin address to send the amount to
      
      Returns:
        ok
    */
    $path = $this->url . "/ether_withdrawal/";
    $RequestPath = "/v3/ether_withdrawal/";
    $nonce = $this->makeNonce();
    $HTTPMethod = 'POST';
    $JSONPayload = json_encode($params);
    $type = 'PRIVATE';

    return $this->getData($nonce,$path,$RequestPath,$HTTPMethod,$JSONPayload,$type);
  }

    function ripple_withdrawal($params){
    	/*
      Triggers a ripple withdrawal from your account
        Args:
          currency (str):
            The currency to withdraw
          amount (str):
            The amount of BTC to withdraw from your account
          address (str):
            The ripple address to send the amount to
        
        Returns:
          ok
      */
      $path = $this->url . "/ripple_withdrawal/";
      $RequestPath = "/v3/ripple_withdrawal/";
      $nonce = $this->makeNonce();
      $HTTPMethod = 'POST';
      $JSONPayload = json_encode($params);
      $type = 'PRIVATE';

      return $this->getData($nonce,$path,$RequestPath,$HTTPMethod,$JSONPayload,$type);

    }
    function spei_withdrawal($params){
    	/*
      Triggers a SPEI withdrawal from your account.
        These withdrawals are immediate during banking hours for some banks (M-F 9:00AM - 5:00PM Mexico City Time), 24 hours for others.
        Args:
          amount (str):
            The amount of MXN to withdraw from your account
          recipient_given_names (str):
            The recipient's first and middle name(s)
          recipient_family_names (str):
            The recipient's last names
          clabe (str):
            The CLABE number where the funds will be sent to
            https://en.wikipedia.org/wiki/CLABE
          notes_ref (str):
            The alpha-numeric reference number for this SPEI
          numeric_ref (str):
            The numeric reference for this SPEI
        
        Returns:
          ok      
      */
      $path = $this->url . "/spei_withdrawal/";
      $RequestPath = "/v3/spei_withdrawal/";
      $nonce = $this->makeNonce();
      $HTTPMethod = 'POST';
      $JSONPayload = json_encode($params);
      $type = 'PRIVATE';

      return $this->getData($nonce,$path,$RequestPath,$HTTPMethod,$JSONPayload,$type);
    }

}

?>
