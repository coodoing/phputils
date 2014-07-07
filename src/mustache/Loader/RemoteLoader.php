<?php
class Mustache_Loader_RemoteLoader implements Mustache_Loader{
	private $remoteUrl;
    private $extension = '.mustache';
    private $templates = array();
    private $local_dir;


    public function __construct($remoteUrl, $local_dir, array $options = array())
    {
        $this->remoteUrl = $remoteUrl;
        
        if (isset($options['extension'])) {
            $this->extension = '.' . ltrim($options['extension'], '.');
        }
        
	    $this->local_dir = $local_dir;
    }

    /**
     * Load a Template by name.
     *
     *     $loader = new FilesystemLoader(dirname(__FILE__).'/views');
     *     $loader->load('admin/dashboard'); // loads "./views/admin/dashboard.mustache";
     *
     * @param string $name
     *
     * @return string Mustache Template source
     */
    public function load($name)
    {
        if (!isset($this->templates[$name])) {
            $this->templates[$name] = $this->loadFile($name);
        }

        return $this->templates[$name];
    }

    /**
     * Helper function for loading a Mustache file by name.
     *
     * @throws InvalidArgumentException if a template file is not found.
     *
     * @param string $name
     *
     * @return string Mustache Template source
     */
    protected function loadFile($name)
    {
        $fileName = $this->getFileName($name);
        if (!$this->checkRemoteFileExists($fileName)) {
            throw new InvalidArgumentException('Template '.$name.' not found.');
        }
		return file_get_contents($fileName);
    }
    
    protected function getFileName($name)
    {
        $fileName = $this->remoteUrl . '/' . $name;
        if (substr($fileName, 0 - strlen($this->extension)) !== $this->extension) {
            $fileName .= $this->extension;
        }
        return $fileName.'?dev=true';
    }
    /**
     * check the remote file existe
     * @param string $name file path
     * @return boolean
     */
    private function checkRemoteFileExists($name)
    {
        $curl = curl_init($name);
        curl_setopt($curl, CURLOPT_NOBODY, true);
        $result = curl_exec($curl);
        $found = false;
        if ($result !== false) {
            // check http respond 200
            $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            if ($statusCode == 200) {
                $found = true;
            }
        }
        curl_close($curl);   
        return $found;
    }
}