<?php
/**
 * Copyright (c) 2011-2012 Andreas Heigl<andreas@heigl.org>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * @category  Deploy
 * @author    Andreas Heigl<andreas@heigl.org>
 * @copyright 2011-2012 Andreas Heigl
 * @license   http://www.opesource.org/licenses/mit-license.php MIT-License
 * @version   0.0
 * @since     18.12.2012
 * @link      http://github.com/heiglandreas/OrgHeiglDeploy
 */
namespace OrgHeiglDeploy\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use OrgHeiglDeploy\Exceptions\UnallowedAccessException;
use OrgHeiglDeploy\Exceptions\RequirementsNotMetException;
use OrgHeiglDeploy\Validator\IpRangeMatch;

/**
 * The controller to deploy your app
 *
 * @category  Deploy
 * @author    Andreas Heigl<andreas@heigl.org>
 * @copyright 2011-2012 Andreas Heigl
 * @license   http://www.opesource.org/licenses/mit-license.php MIT-License
 * @version   0.0
 * @since     18.12.2012
 * @link      http://github.com/heiglandreas/OrgHeiglDeploy
 */
class IndexController extends AbstractActionController
{   
    /**
     * The configuration for this Controller
     * 
     * @var array $config
     */
    protected $config = array();
    
    /**
     * Create an instance of this Controller.
     * 
     * This will check whether all requirements are met for the updater to
     * execute.
     * 
     * This includes check for curl and zip as well as check whether we can 
     * execute a shell-command via exec.
     * 
     * @return void
     */
    public function __construct()
    {
        if (!extension_loaded('curl')) {
            throw new RequirementsNotMetException('The "curl"-module is not available');
        }
        if (!function_exists('curl_init')) {
            throw new RequirementsNotMetException('The "curl"-module seems to be malfunctioning');
        }
        if (!extension_loaded('zip')) {
            throw new RequirementsNotMetException('The "zip"-module is not available');
        }
        if (!function_exists('exec')) {
            throw new RequirementsNotMetException('The "exec"-function is not available');            
        }
        exec('php -v', $result, $return);
        if (0 != $return) {
            throw new RequirementsNotMetException('PHP can not be called via exec');            
        }
        
        parent::__construct();
    }
    /**
     * Call the deployment action
     *
     * @return void
     */
    public function indexAction()
    {
        // Check the hash.
        $hash = $this->getEvent()->getRouteMatch()->getParam('hash');
        if (!$param['hash'] || $param['hash'] != $this->getConfig('hash')) {
            throw new UnallowedAccessException('Invalid Hash');
        }
        
        // Check for an allowed IP-Address
        $callerIp = $_SERVER['REMOTE_ADDR'];
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $callerIp = $_SERVER['HTTP_X_FORWARDED_FOR'];
        }
        $validator = new IpRangeMatch($this->getConfig('ip'));
        if (! $validator->isValid($callerIp)) {
            throw new UnallowedAccessException('Invalid IP');
        }
        
        // Download the file to deploy to a temporary location
        $tmpFile = tempnam(sys_get_temp_dir(), 'OrgHeiglDeploy');
        $fh = fopen($tmpFile, 'w');
        $ch = curl_init($this->getConfig('source'));
        
        curl_setopt($ch, CURLOPT_FILE, $fh);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        
        curl_exec($ch);
        curl_close($ch);
        fclose($fh);
        
        // run the predeployment script
        $script = $this->getConfig('predeployment');
        if ($script && file_Exists($script)) {
            exec('php ' . escapeshellarg($script));
        }
        
        // Extract the downloaded file to the target location
        $zip = new ZipArchive();
        if (true !== $zip->open($tmpFile, ZIPARCHIVE::CREATE)) {
            throw new \InvalidArgumentException('The given ZIP-File could not be opened');
        }
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            $data = $zip->getFromIndex($i);
            if (!file_exists(dirname($name))) {
                mkdir(dirname($name), 777, true);
            }
            file_put_contents($name, $data);
        }
        
        // run the composer-script
        if (file_exists('composer.phar')) {
            exec('php composer.phar selfupdate');
            exec('php composer.phar update');
        }
        // run the postdeployment-script
        $script = $this->getConfig('postdeployment');
        if ($script && file_Exists($script)) {
            exec('php ' . escapeshellarg($script));
        }
        
        // thats it ;-)
        return false;
        
        
    }
    
    /**
     * Get a config item
     * 
     * @param string $configKey
     * 
     * @return mixed
     */
    public function getConfig($configKey)
    {
        if (! $this->$config) {
            $config = $services->get('config');
            if ($config instanceof Traversable) {
                $config = ArrayUtils::iteratorToArray($config);
            }
            $this->config  = $config['OrgHeiglDeploy'];
        }
        
        if (! isset($this->config[$configKey])) {
            return '';
        }
        
        return $this->config[$configKey];
    }
}