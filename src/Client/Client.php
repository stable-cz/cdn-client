<?php


namespace Stable\Cdn;

/** 
 * Basic Stable.cz CDN client
 *
 * @author Evzen Eiba <evzen.eiba@stable.cz>
 * @copyright Stable.cz
 * 
 * 
 * 
 */


class Client {
    
    /** 
     * @var string URL given to contructor
     */
    protected $url;
    
    /**
     * @var string API key given to constructor
     */
    protected $apikey;
    
    /**
     * @var string Last curlcall curlinfo
     */
    public $curlLastInfo = null;
    
    /**
     * Constructor
     * @param $apikey Your CDN service api key acquired from Stable.cz
     * @param $url Endpoint url (http://domain/, rest is concatenated automatically
     * @return null
     */
    public function __construct($apikey, $url = 'http://cdn.stable.cz/')  {
        $this->apikey = $apikey;
        $this->url = $url;
    }
    
    /**
     * API request call (via cURL)
     * @param (string) $namespace       - API namespace, currently "files" namespace only
     * @param (string|null) $path       - PATH for request, eg. path for a existing file)
     * @param (array) $params           - call params, eg. metadata for uploaded file
     * @param (string) $method          - HTTP method (GET|POST|DELETE)
     * @throws Exception                - in case of wrong method
     * @return (object)                 - decoded JSON according to API definition or null in case of system fault
     */
    
    protected function call(string $namespace, string $path = null, string $method, array $params = []):? object {
        $url = rtrim($this->url, '/') . '/api/' . trim($namespace, '/') . '/' . ltrim($path, '/');

        $params['auth'] = $this->apikey;
        $options = [
              CURLOPT_URL               => $url
            , CURLOPT_RETURNTRANSFER    => true
            , CURLINFO_HEADER_OUT       => true
        ];
        switch (strtoupper($method)) {
            case 'DELETE' : 
            case 'GET' : 
                $options[CURLOPT_URL]                  .= '?' . http_build_query($params);
                $options[CURLOPT_CUSTOMREQUEST]         = strtoupper($method);
                break;
            case 'POST' :
                $options[CURLOPT_POST]          = true;
                $options[CURLOPT_POSTFIELDS]    = json_encode($params);
                // $options[CURLOPT_HTTPHEADER]    = [ 'Content-Type: application/json' ]; 
                break;
            default:
                throw new \Exception(sprintf('Unknown method %s', $method));
                break;
        }
        
        $ch = curl_init();
        curl_setopt_array($ch, $options);
        $res = curl_exec($ch);
        $this->curlLastInfo = curl_getinfo($ch);

        if ($res) {
            return json_decode($res);
        }
        
        
        return null;
    }
    
    /**
     * Uploads a file to target
     * @param (string) $file    - source file path
     * @param (string) $target  - target file path
     * @return (object)         - decoded JSON according to API definition or null in case of system fault
     */
    public function upload(string $file, string $target):? object {
        $res = $this->call('files', null, 'POST', 
            [ 'data' => [ 'filename' => $target, 'content' => base64_encode(file_get_contents($file)) ] ] );
        return $res;
    }
    
    /**
     * Delete an existing file
     * @param (string) $file    - path to existing file
     * @return (object)         - decoded JSON according to API definition or null in case of system fault
     */
     

    public function delete(string $file):? object {
        $res = $this->call('files', $file, 'DELETE');
        return $res;
    }
    
    /**
     * LS an existing file or directory
     * @param (string) $path    - path to a file or directory
     * @return (object)         - decoded JSON according to API definition or null in case of system fault
     */

    public function ls(string $path):? object {
        $res = $this->call('files', $path, 'GET');
        return $res;
        
    }
}
