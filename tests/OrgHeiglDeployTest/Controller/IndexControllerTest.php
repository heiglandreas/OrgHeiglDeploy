<?php
/**
 * Copyright (c)2012-2012 heiglandreas
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
 * @category
 * @author    Andreas Heigl<andreas@heigl.org>
 * @copyright ©2012-2012 Andreas Heigl
 * @license   http://www.opesource.org/licenses/mit-license.php MIT-License
 * @version   0.0
 * @since     21.12.12
 * @link      https://github.com/heiglandreas/OrgHeiglDeploy
 */
namespace OrgHeiglDeployTest\Controller;

use \PHPUnit_Framework_TestCase;
use \OrgHeiglDeploy\Controller\IndexController;
use \ReflectionClass;
use OrgHeiglDeployTest\Bootstrap;
use Zend\Mvc\Router\Http\TreeRouteStack as HttpRouter;
use Zend\Http\Request;
use Zend\Http\Response;
use Zend\Mvc\MvcEvent;
use Zend\Mvc\Router\RouteMatch;


/**
 * Test the IPRange-Match
 *
 * @category
 * @author    Andreas Heigl<andreas@heigl.org>
 * @copyright ©2012-2012 Andreas Heigl
 * @license   http://www.opesource.org/licenses/mit-license.php MIT-License
 * @version   0.0
 * @since     21.12.12
 * @link      https://github.com/heiglandreas/OrgHeiglDeploy
 */
class IndexControllerTest extends PHPUnit_Framework_TestCase
{
    /**
     * the controller instance
     *
     * @var IndexController $controller
     */
    protected $controller;
    protected $request;
    protected $response;
    protected $routeMatch;
    protected $event;

    protected function setUp()
    {
        $serviceManager = Bootstrap::getServiceManager();
        try {
            $this->controller = new IndexController();
        } catch (Exception $e) {
            $this->markSkipped('IndexController could not be instantiated');
        }
        $this->request    = new Request();
        $this->routeMatch = new RouteMatch(array('controller' => 'index'));
        $this->event      = new MvcEvent();
        $config = $serviceManager->get('Config');
        $routerConfig = isset($config['router']) ? $config['router'] : array();
        $router = HttpRouter::factory($routerConfig);

        $this->event->setRouter($router);
        $this->event->setRouteMatch($this->routeMatch);
        $this->controller->setEvent($this->event);
        $this->controller->setServiceLocator($serviceManager);
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';

    }

    public function testInstantiation()
    {
        $inst = new \OrgHeiglDeploy\Controller\IndexController();
        $this->assertInstanceof('\\OrgHeiglDeploy\\Controller\\IndexController', $inst);

        $class = new ReflectionClass('\\OrgHeiglDeploy\\Controller\\IndexController');
        $property = $class->getProperty("test");
        $property->setAccessible(true);
        $value = $property->getValue();

        $f = $value;
        $f['curl'] = 'foo';
        $property->setValue($f);
        try{
            new IndexController();
            $this->fail('Should have raised an Exception');
        }catch(\OrgHeiglDeploy\Exceptions\RequirementsNotMetException $e) {
            $this->assertTrue(true);
        }

        $f = $value;
        $f['curl_init'] = 'foo';
        $property->setValue($f);
        try{
            new IndexController();
            $this->fail('Should have raised an Exception');
        }catch(\OrgHeiglDeploy\Exceptions\RequirementsNotMetException $e) {
            $this->assertTrue(true);
        }

        $f = $value;
        $f['zip'] = 'foo';
        $property->setValue($f);
        try{
            new IndexController();
            $this->fail('Should have raised an Exception');
        }catch(\OrgHeiglDeploy\Exceptions\RequirementsNotMetException $e) {
            $this->assertTrue(true);
        }

        $f = $value;
        $f['exec'] = 'foo';
        $property->setValue($f);
        try{
            new IndexController();
            $this->fail('Should have raised an Exception');
        }catch(\OrgHeiglDeploy\Exceptions\RequirementsNotMetException $e) {
            $this->assertTrue(true);
        }

        $f = $value;
        $f['php'] = 'foo';
        $property->setValue($f);
        try{
            new IndexController();
            $this->fail('Should have raised an Exception');
        }catch(\OrgHeiglDeploy\Exceptions\RequirementsNotMetException $e) {
            $this->assertTrue(true);
        }
        $property->setValue($value);
    }

    public function testRunningComposer()
    {
        $lockFile = realpath(__DIR__ . '/../../../composer.lock');
        $lastAccessTime = filemtime($lockFile);
        $method = Bootstrap::getMethod($this->controller, 'runComposer');
        $result = $method->invoke($this->controller);
        $this->assertSame($this->controller, $result);
    //    $this->assertTrue($lastAccessTime < filemtime($lockFile));
    }

    public function testDownloadingZipFile()
    {
        $downloadZipFile = Bootstrap::getMethod($this->controller, 'downloadZipFile');
        $downloadZipFile->invoke($this->controller);
        $getTempFile = Bootstrap::getMethod($this->controller, 'getTempFile');
        $tempFile = $getTempFile->invoke($this->controller);
        $this->assertSame(file_get_contents($tempFile), file_get_contents(__DIR__ . '/../../share/test.zip'));
    }

    public function testExtractingZip()
    {
        $reflection = new \ReflectionProperty($this->controller, 'tempFile');
        $reflection->setAccessible(true);
        $reflection->setValue($this->controller, __DIR__ . '/../../share/test.zip');
        $deployZipFile = Bootstrap::getMethod($this->controller, 'deployZipFile');
        $deployZipFile->invoke($this->controller);
        $this->assertTrue(file_Exists(__DIR__ . '/../../../test1'));
    }

    public function testHashChecking()
    {
        $this->routeMatch->setParam('hash', 'EA3459C3-8839-4F00-9423-77CDA2A386D6');
        $checkHash = Bootstrap::getMethod($this->controller, 'checkHash');
        $this->assertTrue($checkHash->invoke($this->controller));
        $this->routeMatch->setParam('hash', '8839-4F00-9423-77CDA2A386D6');
        $this->assertFalse($checkHash->invoke($this->controller));
    }
    public function testIndexActionCanBeAccessed()
    {
        $this->routeMatch->setParam('action', 'index');
        $this->routeMatch->setParam('hash', 'EA3459C3-8839-4F00-9423-77CDA2A386D6');

        $result   = $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue(file_Exists(__DIR__ . '/../../../test1'));

        $this->cleanupTempFiles();
    }

    public function cleanupTempFiles()
    {
        if (file_exists(__DIR__ . '/../../../test1')) {
            unlink(__DIR__ . '/../../../test1');
        }
        if (file_exists(__DIR__ . '/../../../test2')) {
            unlink(__DIR__ . '/../../../test2');
        }
    }
}
