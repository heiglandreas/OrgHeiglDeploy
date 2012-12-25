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
use ZipArchive;

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

    protected $tempFile = null;

    private static $test = array(
        'curl'      => 'curl',
        'curl_init' => 'curl_init',
        'zip'       => 'zip',
        'exec'      => 'exec',
        'php'       => 'php -v',
    );

    /**
     * Create an instance of this Controller.
     *
     * This will check whether all requirements are met for the updater to
     * execute.
     * This includes check for curl and zip as well as check whether we can
     * execute a shell-command via exec.
     *
     * @throws \OrgHeiglDeploy\Exceptions\RequirementsNotMetException
     * @return \OrgHeiglDeploy\Controller\IndexController
     */
    public function __construct()
    {
        if (!extension_loaded(self::$test['curl'])) {
            throw new RequirementsNotMetException('The "curl"-module is not available');
        }
        if (!function_exists(self::$test['curl_init'])) {
            throw new RequirementsNotMetException('The "curl"-module seems to be malfunctioning');
        }
        if (!extension_loaded(self::$test['zip'])) {
            throw new RequirementsNotMetException('The "zip"-module is not available');
        }
        if (!function_exists(self::$test['exec'])) {
            throw new RequirementsNotMetException('The "exec"-function is not available');            
        }
        @exec(self::$test['php'] . ' 2>/dev/null', $result, $return);
        if (0 != $return) {
            throw new RequirementsNotMetException('PHP can not be called via exec');
        }
    }

    /**
     * Call the deployment action
     *
     * @throws \InvalidArgumentException
     * @throws \OrgHeiglDeploy\Exceptions\UnallowedAccessException
     * @return void
     */
    public function indexAction()
    {
        // Check the hash.
        if (! $this->checkHash()) {
            throw new UnallowedAccessException('Invalid Hash');
        }
        
        // Check for an allowed IP-Address
        if (! $this->checkIpAddress()) {
            throw new UnallowedAccessException('Invalid IP');
        }
        
        // Download the file to deploy to a temporary location
        $this->downloadZipFile()
            // run the predeployment script
             ->runScript('predeployment')
            // Extract the downloaded file to the target location
             ->deployZipFile()
            // run the composer-script
             ->runComposer()
            // run the postdeployment-script
             ->runScript('postdeployment');

        // that's it ;-)
        return false;
        
        
    }

    /**
     * Check whether the provided hash matches the expected one.
     *
     * @return boolean
     */
    protected function checkHash()
    {
        $hash = $this->getEvent()->getRouteMatch()->getParam('hash');
        if (! $hash || $hash != $this->getConfig('hash')) {
            return false;
        }
        return true;
    }

    /**
     * Check whether the provided IP-Adress matches one of the expected ones
     *
     * @return boolean
     */
    protected function checkIpAddress()
    {
        $callerIp = $_SERVER['REMOTE_ADDR'];
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $callerIp = $_SERVER['HTTP_X_FORWARDED_FOR'];
        }
        $validator = new IpRangeMatch($this->getConfig('ip'));
        return $validator->isValid($callerIp);
    }

    /**
     * Download the ZIP-File
     *
     * @return IndexController
     */
    protected function downloadZipFile()
    {
        $tmpFile = $this->getTempFile();
        $fh = fopen($tmpFile, 'w');
        $ch = curl_init($this->getConfig('source'));

        curl_setopt($ch, CURLOPT_FILE, $fh);
        curl_setopt($ch, CURLOPT_HEADER, 0);

        curl_exec($ch);
        curl_close($ch);
        fclose($fh);
        return $this;
    }

    /**
     * Get a temporary filename
     *
     * @return string
     */
    protected function getTempFile()
    {
        if (null === $this->tempFile) {
            $this->tempFile = tempnam(sys_get_temp_dir(), 'OrgHeiglDeploy');
        }
        return $this->tempFile;
    }

    /**
     * Run a script
     *
     * @param string $script The kind of script to run
     *
     * @return IndexController
     */
    protected function runScript($script)
    {
        $script = $this->getConfig($script);
        if ($script && file_Exists($script)) {
            exec('php ' . escapeshellarg($script));
        }

        return $this;
    }

    /**
     * Get a config item
     * 
     * @param string $configKey
     * 
     * @return mixed
     */
    protected function getConfig($configKey)
    {
        if (! $this->config) {
            $config = $this->getServiceLocator()->get('config');
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

    /**
     * Deploy the zip file
     *
     * @throws \InvalidArgumentException
     * @return IndexController
     */
    protected function deployZipFile()
    {
        $zip = new ZipArchive();
        if (true !== $zip->open($this->getTempFile(), ZIPARCHIVE::CREATE)) {
        throw new \InvalidArgumentException('The given ZIP-File could not be opened');
        }
        $zip->extractTo($this->getConfig('target'));
//        for ($i = 0; $i < $zip->numFiles; $i++) {
//            $name = $zip->getNameIndex($i);
//            $name = $this->getConfig('target') . DIRECTORY_SEPARATOR . $name;
//            $data = $zip->getFromIndex($i);
//            if (false === $data) {
//                continue;
//            }
//            if (!file_exists(dirname($name))) {
//                mkdir(dirname($name), 777, true);
//            }
//            file_put_contents($name, $data);
//        }
        $zip->close();
        return $this;
    }

    /**
     * RUn the composer script to resolve any dependencies
     *
     * @return IndexController
     */
    protected function runComposer()
    {
        $target=$this->getConfig('target');
        chdir($target);
        if (file_exists('composer.phar')) {
            exec('php composer.phar selfupdate');
            exec('php composer.phar update', $result, $return);
        }
        return $this;
    }
}