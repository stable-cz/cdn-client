<?php


namespace Stable\Cdn;

/** 
 * Stable.cz CDN client
 *
 * Basic usage examples:
 * 
 * // construct
 * $client = new \Stable\Cdn\Client(YOUR_API_KEY);
 * 
 * // create some local file
 * file_put_contents('localfile', date('Y-m-d'));
 * 
 * // upload file
 * $client->upload('localfile', 'path/to/remotefile');
 * 
 * // verify file exists
 * $client->ls('path/to/remotefile');
 * 
 * // list files in directory
 * $client->ls('path/to');
 * 
 * // delete remote file
 * $client->delete('path/to/remotefile');
 * 
 * @author Evzen Eiba <evzen.eiba@stable.cz>
 * @copyright Stable.cz
 * 
 */


class Client {
    
    /** 
     * @var string URL given to contructor
     */
    protected $url = 'https://cdn.stable.cz/';
    
    /**
     * @var string API key given to constructor
     */
    protected $apikey;
    
    /**
     * @var string Last curlcall curlinfo
     */
    public $curlLastInfo = null;
    /**
     * @var string Last curlcall result
     */
    public $curlLastResult = null;
    
    /**
     * @var int Maximum size of a upload batch
     */
     public $chunkFileSize = 5 * 1024 * 1024;
    
    /**
     * Constructor
     * @param $apikey Your CDN service api key acquired from Stable.cz
     * @param $url Endpoint url (http://domain/, rest is concatenated automatically
     * @return null
     */
    public function __construct($apikey)  {
        $this->apikey = $apikey;
        // $this->url = $url;
    }
    
    /**
     * API request call (via cURL)
     * @param (string) $namespace       - API namespace, currently "files" namespace only
     * @param (string|null) $path       - PATH for request, eg. path for a existing file)
     * @param (array) $params           - call params, eg. metadata for uploaded file
     * @param (string) $method          - HTTP method (GET|POST|DELETE)
     * @throws Exception                - in case of wrong method
     * @return (stdClass)                 - decoded JSON according to API definition or null in case of system fault
     */
    
    protected function call(string $namespace, string $path = null, string $method, array $params = []):? \stdClass {
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
        $this->curlLastResult = $res;
        // var_dump($this->curlLastResult);

        if ($res) {
            return json_decode($res);
        }
        
        
        return null;
    }
    
    /**
     * Uploads a file to target
     * @param (string) $file    - source file path
     * @param (string) $target  - target file path
     * @param (callable) $callback  - callback for upload progress
     * @return (stdClass)         - decoded JSON according to API definition or null in case of system fault
     */
    public function upload(string $file, string $target, callable $callback = null):? \stdClass {
        $filesize = filesize($file);

        if ($filesize > $this->chunkFileSize) {

            $i = 0;
            $progress = 0;
            $start = microtime(true);

            $parts = $filesize / $this->chunkFileSize;

            $f = fOpen($file, 'r');
            
            if ($callback) {
                $callback((object) [
                      'chunk' => -1
                    , 'progress' => $progress
                    , 'total_filesize' => $filesize
                    , 'chunkresult' => null]);
            }

            while ($chunk = fread($f, $this->chunkFileSize)) {

                $res = $this->call('files', null, 'POST', 
                    [ 'data' => [ 
                          'filename' => $target
                        , 'chunk' => $i
                        , 'content' => base64_encode($chunk) ] ] );
                $progress += strlen($chunk);
                $eta = ( (microtime(true) - $start) / ($i+1) * $parts ) - (microtime(true) - $start);
                $d1 = new \DateTime; $d1->setTimestamp(0);
                $d2 = new \DateTime; $d2->setTimestamp((int) $eta);
                $eta_formatted = $d2->diff($d1)->format('%H:%I:%S');

                if ($callback) {
                    $callback((object) [
                                  'chunk' => $i
                                , 'progress' => $progress
                                , 'total_filesize' => $filesize
                                , 'eta' => $eta
                                , 'eta_formatted' => $eta_formatted
                                , 'chunkresult' => $res]);
                }
                $i++;
            }
            
            fClose($f);
            return $res; // return last result;
            
        } else {
            $res = $this->call('files', null, 'POST', 
                [ 'data' => [ 'filename' => $target, 'content' => base64_encode(file_get_contents($file)) ] ] );
            if ($callback) {
                $callback((object) [
                                  'chunk' => 0
                                , 'progress' => $filesize
                                , 'total_filesize' => $filesize
                                , 'eta' => 0
                                , 'eta_formatted' => 0
                                , 'chunkresult' => $res]);
            }
            return $res;
        }
    }
    
    /**
     * Delete an existing file
     * @param (string) $file    - path to existing file
     * @return (stdClass)         - decoded JSON according to API definition or null in case of system fault
     */
     

    public function delete(string $file):? \stdClass {
        $res = $this->call('files', $file, 'DELETE');
        return $res;
    }
    
    /**
     * List an existing file or directory - get a file info or a directory and its files
     * @param (string) $path    - path to a file or directory
     * @return (stdClass)         - decoded JSON according to API definition or null in case of system fault
     */

    public function ls(string $path):? \stdClass {
        $res = $this->call('files', $path, 'GET');
        return $res;
        
    }
}
