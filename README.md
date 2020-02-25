### Stable CDN Client

* Class documentation: http://cdn.stable.cz/docs/cdn-client/html/classes/Stable_Cdn_Client.html
* Another basic examples: `php example/example.php YOUR_API_KEY`

 ##### Basic usage examples:
 
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
 



