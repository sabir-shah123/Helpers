<?php

//the function you will call in your controller
 public function details()
{
    $visitor = getVisitorDetails();
    $to = $visitor['currency_code'] ; // currency of the user's country or location
    $from = 'usd'; //the currency of your system or your main location
    $amount = 20;
    $convert = convertCurrency($amount, $from, $to);
    dd($convert);
}


// below function will be the par of your helper file
function getVisitorDetails($ip = NULL, $purpose = "location", $deep_detect = TRUE)
{
    $output = NULL;
    if (filter_var($ip, FILTER_VALIDATE_IP) === FALSE) {
        $ip = $_SERVER["REMOTE_ADDR"];
        if ($deep_detect) {
            if (filter_var(@$_SERVER['HTTP_X_FORWARDED_FOR'], FILTER_VALIDATE_IP))
                $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
            if (filter_var(@$_SERVER['HTTP_CLIENT_IP'], FILTER_VALIDATE_IP))
                $ip = $_SERVER['HTTP_CLIENT_IP'];
        }
    }

    $ip = '23.106.56.51';

    $purpose    = str_replace(array("name", "\n", "\t", " ", "-", "_"), NULL, strtolower(trim($purpose)));
    $support    = array("country", "countrycode", "state", "region", "city", "location", "address","currency_code","currency_symbol");
    $continents = array(
        "AF" => "Africa",
        "AN" => "Antarctica",
        "AS" => "Asia",
        "EU" => "Europe",
        "OC" => "Australia (Oceania)",
        "NA" => "North America",
        "SA" => "South America"
    );

    if (filter_var($ip, FILTER_VALIDATE_IP) && in_array($purpose, $support)) {
        $ipdat = @json_decode(file_get_contents("http://www.geoplugin.net/json.gp?ip=" . $ip));

        if (@strlen(trim($ipdat->geoplugin_countryCode)) == 2) {
            switch ($purpose) {
                case "location":
                    $output = array(
                        "city"           => @$ipdat->geoplugin_city,
                        "state"          => @$ipdat->geoplugin_regionName,
                        "country"        => @$ipdat->geoplugin_countryName,
                        "country_code"   => @$ipdat->geoplugin_countryCode,
                        "continent"      => @$continents[strtoupper($ipdat->geoplugin_continentCode)],
                        "continent_code" => @$ipdat->geoplugin_continentCode,
                        'currency_code'  => @$ipdat->geoplugin_currencyCode,
                        'currency_symbol'=> @$ipdat->geoplugin_currencySymbol,
                    );
                    break;
                case "address":
                    $address = array($ipdat->geoplugin_countryName);
                    if (@strlen($ipdat->geoplugin_regionName) >= 1)
                        $address[] = $ipdat->geoplugin_regionName;
                    if (@strlen($ipdat->geoplugin_city) >= 1)
                        $address[] = $ipdat->geoplugin_city;
                    $output = implode(", ", array_reverse($address));
                    break;
                case "city":
                    $output = @$ipdat->geoplugin_city;
                    break;
                case "state":
                    $output = @$ipdat->geoplugin_regionName;
                    break;
                case "region":
                    $output = @$ipdat->geoplugin_regionName;
                    break;
                case "country":
                    $output = @$ipdat->geoplugin_countryName;
                    break;
                case "countrycode":
                    $output = @$ipdat->geoplugin_countryCode;
                    break;
            }
        }
    }
    return $output;
}


function http_call($url, $method = 'GET', $data = '', $headers = [], $json = false)
{
    $client = new \GuzzleHttp\Client(['http_errors' => false]);
    $options = [];

    $isformparm = false;
    if (is_array($data)) {
        if (isset($data['form_params'])) {
            $options = $data;
            $isformparm = true;
            $headers['Content-Type'] = 'application/x-www-form-urlencoded';
        }
    }

    if ($json) {
        $headers['Content-Type'] = 'application/json';
    }

    if (!empty($headers)) {
        $options['headers'] = $headers;
    }

    if (!empty($data)) {
        if ($json) {
            $options['json'] = $data;
        } else {
            if (!$isformparm) {
                $options['body']  = $data;
            }
        }
    }

    $response = $client->request($method, $url, $options);
    $resp = $response->getBody()->getContents();
    $resp = json_decode($resp);
    if ($resp && isset($resp->total)) {
        return $resp->total;
    } else {
        return null;
    }
}

function convertCurrency($amount = 0, $from_Currency = 'usd', $to_Currency = 'pkr')
{
    
    $amount = urlencode($amount);
    $from_Currency = urlencode($from_Currency);
    $to_Currency = urlencode($to_Currency);

    $options = [
        'form_params' => [
            'from' => $from_Currency,
            'to' => $to_Currency,
            'amount' => $amount
        ]
    ];

    $res = http_call('https://fcsapi.com/converter/converter_total_val', 'POST', $options);
    return $res;
}
