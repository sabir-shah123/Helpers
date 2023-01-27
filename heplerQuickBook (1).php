<?php
namespace App\Helpers;
class QuickBook
{
    private $client_id;
    private $client_secret;
    private $scope;
    private $tokenURL;
    private $baseURL;
    private $sandbox;

    /**
     * HTTP Methods
     */
    const HTTP_METHOD_GET    = 'GET';
    const HTTP_METHOD_POST   = 'POST';
    const HTTP_METHOD_PUT    = 'PUT';
    const HTTP_METHOD_DELETE = 'DELETE';
    const HTTP_METHOD_HEAD   = 'HEAD';
    const HTTP_METHOD_PATCH   = 'PATCH';

    public function __construct($certificate_file = null)
    {
        if (!extension_loaded('curl')) {
            throw new Exception('The PHP exention curl must be installed to use this library.', Exception::CURL_NOT_FOUND);
        }
         $this->sandbox='';
       // $this->sandbox='sandbox-';
        if(!empty($this->sandbox)){
            $this->client_id     = '';
            $this->client_secret = '';
            
        }else{
            $this->client_id     = '';
            $this->client_secret = '';
        }
        $this->baseURL='https://'.$this->sandbox.'quickbooks.api.intuit.com/v3/company/';
        $this->tokenURL='https://oauth.platform.intuit.com/oauth2/v1/tokens/bearer';
        $this->scope='com.intuit.quickbooks.accounting openid profile email';
        
       // $this->setCertificate($certificate_file);
    }

    public function setCertificate($certificate_file){
      $this->certificate_file = $certificate_file;
      if (!empty($this->certificate_file)  && !is_file($this->certificate_file)) {
          throw new InvalidArgumentException('The certificate file was not found', InvalidArgumentException::CERTIFICATE_NOT_FOUND);
      }
    }
    
    

    public function callForOpenIDEndpoint($access_token){
      $authorizationHeaderInfo = $this->generateAccessTokenHeader($access_token);
      $http_header = array(
        'Accept' => 'application/json',
        'Authorization' => $authorizationHeaderInfo
      );
      $result = $this->executeRequest('https://'.$this->sandbox.'accounts.platform.intuit.com/v1/openid_connect/userinfo' , null, $http_header, self::HTTP_METHOD_GET);
      return $result;
    }
    
    public function api_call($path='',$access_token,$method='get',$data=''){
      $authorizationHeaderInfo = $this->generateAccessTokenHeader($access_token);
      $http_header = array(
        'Accept' => 'application/json',
        
        'Content-Type'=>'application/json'
      );
      if(session('qbpdf')){
          session()->forget('qbpdf');
          $http_header = array(
        'Content-Type'=>'application/pdf'
      );
      }
      
      $http_header['Authorization']=$authorizationHeaderInfo;
      
      $result = $this->executeRequest($this->baseURL.$path , $data, $http_header, $method);
      return $result;
    }

    private function generateAccessTokenHeader($access_token){
      $authorizationheader = 'Bearer ' . $access_token;
      return $authorizationheader;
    }


    public function getAuthorizationURL($state,$response_type='code'){
        $parameters = array(
          'client_id' => $this->client_id,
          'scope' => $this->scope,
          'redirect_uri' => route('oauth.callback','quickbook'),
          'response_type' => $response_type,
          'state' => $state
          //The include_granted_scope is always set to false. No need to pass.
          //'include_granted_scope' => $include_granted_scope
        );
        $authorizationRequestUrl='https://appcenter.intuit.com/connect/oauth2';
        $authorizationRequestUrl .= '?' . http_build_query($parameters, null, '&', PHP_QUERY_RFC1738);
        return $authorizationRequestUrl;
    }

    public function getAccessToken($code,$grant_type='authorization_code'){
       if(!isset($grant_type)){
          throw new InvalidArgumentException('The grant_type is mandatory.', InvalidArgumentException::INVALID_GRANT_TYPE);
       }

       $parameters = array(
         'grant_type' => $grant_type,
         'code' => $code,
         'redirect_uri' => route('oauth.callback','quickbook')
       );
       $authorizationHeaderInfo = $this->generateAuthorizationHeader();
       $http_header = array(
         'Accept' => 'application/json',
         'Authorization' => $authorizationHeaderInfo,
         'Content-Type' => 'application/x-www-form-urlencoded'
       );

       //Try catch???
       $result = $this->executeRequest($this->tokenURL , $parameters, $http_header, self::HTTP_METHOD_POST);
       return $result;
    }
    
    

    public function refreshAccessToken($refresh_token){
      $parameters = array(
        'grant_type' => 'refresh_token',
        'refresh_token' => $refresh_token
      );

      $authorizationHeaderInfo = $this->generateAuthorizationHeader();
      $http_header = array(
        'Accept' => 'application/json',
        'Authorization' => $authorizationHeaderInfo,
        'Content-Type' => 'application/x-www-form-urlencoded'
      );
      $result = $this->executeRequest($this->tokenURL , $parameters, $http_header, self::HTTP_METHOD_POST);
      return $result;
    }

    private function generateAuthorizationHeader(){
        $encodedClientIDClientSecrets = base64_encode($this->client_id . ':' . $this->client_secret);
        $authorizationheader = 'Basic ' . $encodedClientIDClientSecrets;
        return $authorizationheader;
    }

    private function executeRequest($url, $parameters = array(), $http_header, $http_method)
    {

      $curl_options = array();

      switch($http_method){
            case self::HTTP_METHOD_GET:
              $curl_options[CURLOPT_HTTPGET] = 'true';
              if (is_array($parameters) && count($parameters) > 0) {
                $url .= '?' . http_build_query($parameters);
              } elseif ($parameters) {
                $url .= '?' . $parameters;
              }
              break;
            case self:: HTTP_METHOD_POST:
              $curl_options[CURLOPT_POST] = '1';
              if(is_array($parameters) && count($parameters) > 0){
                $body = http_build_query($parameters);
                
              }
              else if(is_object($parameters)){
                  $body = json_decode($parameters);
              }
              else if(!empty($parameters)){
                  $body=$parameters;
              }
              $curl_options[CURLOPT_POSTFIELDS] = $body;
              break;
            default:
              break;
      }
      /**
      * An array of HTTP header fields to set, in the format array('Content-type: text/plain', 'Content-length: 100')
      */
      if(is_array($http_header)){
            $header = array();
            foreach($http_header as $key => $value) {
                $header[] = "$key: $value";
            }
            $curl_options[CURLOPT_HTTPHEADER] = $header;
      }

      $curl_options[CURLOPT_URL] = $url;
      $ch = curl_init();

      //debug_backtrace
      //curl_setopt($ch, CURLOPT_VERBOSE, true);
      //$verbose = fopen('php://temp', 'w+');
      //curl_setopt($ch, CURLOPT_STDERR, $verbose);


      curl_setopt_array($ch, $curl_options);
      // Require SSL Certificate

      if (!empty($this->certificate_file)) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
            //curl_setopt($ch, CURLOPT_CAPATH, $this->certificate_file);
            //curl_setopt($ch, CURLOPT_CAPATH, "/Library/WebServer/Documents/OAuth_2/Certificate/");
            curl_setopt($ch, CURLOPT_CAINFO, $this->certificate_file);
         //curl_setopt($ch, CURLOPT_CAINFO, "/Library/WebServer/Documents/OAuth_2/Certificate/VeriSignClass3PublicPrimaryCertificationAuthority-G5.pem");
      } else {
            // throw exception
           // throw new Exception('Cannot find the SSL certificate_file.');
      }


      curl_setopt($ch, CURLINFO_HEADER_OUT, true);
      //Don't display, save it on result
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

      //Execute the Curl Request
      $result = curl_exec($ch);

// $headerSize = curl_getinfo( $ch , CURLINFO_HEADER_SIZE );
// $headerStr = substr( $result , 0 , $headerSize );
// $result = substr( $result , $headerSize );
//       $headerSent = curl_getinfo($ch, CURLINFO_HEADER_OUT );
//       $headers = headersToArray( $headerStr );
        //dd($headers,'1');
      $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);


      $content_type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
       if ($curl_error = curl_error($ch)) {
           throw new Exception($curl_error);
       } else {
           $json_decode = json_decode($result,true);
          
           if(!$json_decode){
               
               try{
                   $Json = json_encode(simplexml_load_string($result));
                  $json_decode= json_decode($Json,true);
               }catch(\Exception $e){
                   $json_decode = $result;
               }
               
               
           }
       }
       curl_close($ch);

       //var_dump($json_decode);
       return $json_decode;
    }
}
?>