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
namespace OrgHeiglDeployTest\Validator;

use \PHPUnit_Framework_TestCase;
use \OrgHeiglDeploy\Validator\IpRangeMatch;

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
class IpRangeMatchTest extends PHPUnit_Framework_TestCase
{
    /**
     * the validator instance
     *
     * @var OrgHeiglDeploy\Validator\IpRangeMatch $validator
     */
    protected $validator = null;

    public function setup()
    {
        $this->validator = new IpRangeMatch();
    }

    public function testInstantiation()
    {
        $inst = new \OrgHeiglDeploy\Validator\IpRangeMatch();
        $this->assertInstanceof('\\OrgHeiglDeploy\\Validator\\IpRangeMatch', $inst);
        $this->assertAttributeEquals(array(), 'allowedAddresses', $inst);
    }

    /**
     * @dataProvider addingRangesProvider
     */
    public function testAddingRanges($ranges)
    {
        $this->assertAttributeEquals(array(),'allowedAddresses', $this->validator);
        $this->assertSame($this->validator, $this->validator->setRange($ranges));
        $this->assertAttributeEquals($ranges, 'allowedAddresses', $this->validator);
    }

    public function addingRangesProvider()
    {
        return array(
            array(array('192.168.1.1')),
            array(array('192.168.1.1/24')),
            array(array('192.168.1.*')),
            array(array('::1')),
        );
    }

    /**
     * @dataProvider validatingIpAddressProvider
     */
    public function testValidatingIpAddressProvider($range, $address, $result)
    {
        $this->validator->setRange($range);
        $this->assertEquals($result, $this->validator->isValid($address));
    }

    public function validatingIpAddressProvider()
    {
        return array(
            array(array('192.168.1.1'), '192.168.1.1', true),
            array(array('192.168.1.1'), '192.168.1.2', false),
            array(array('192.168.1.1/24'), '192.168.1.2', true),
            array(array('192.168.1.*'), '192.168.1.2', true),
            array(array('192.168.1.1-192.168.1.2'), '192.168.1.2', true),
            array(array('::1'), '::1', true),
            array(array('::f'), '::1', false),

        );
    }
}
