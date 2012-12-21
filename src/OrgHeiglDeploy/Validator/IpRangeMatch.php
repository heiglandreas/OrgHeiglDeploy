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
namespace OrgHeiglDeploy\Validator;

use Zend\Validator\AbstractValidator;

/**
 * Validate whether a given IP-Address is contained within a given array of IPs 
 *
 * @category  Deploy
 * @author    Andreas Heigl<andreas@heigl.org>
 * @copyright 2011-2012 Andreas Heigl
 * @license   http://www.opesource.org/licenses/mit-license.php MIT-License
 * @version   0.0
 * @since     18.12.2012
 * @link      http://github.com/heiglandreas/OrgHeiglDeploy
 */
class IpRangeMatch extends AbstractValidator
{
    const NOT_IN_RANGE = 'notInRange';
    
    private static $IP_TYPE_SINGLE   = 'single';
    private static $IP_TYPE_WILDCARD = 'wildcard';
    private static $IP_TYPE_MASK     = 'mask';
    private static $IP_TYPE_SECTION  = 'section';
    private static $IP_TYPE_V6       = 'ip_v6';
    
    
    protected $messageTemplates = array(
            self::NOT_IN_RANGE => "'%value%' is not contained in the given IP-Ranges"
    );
    
    protected $allowedAddresses = array();
    
    public function __construct(array $ranges = array())
    {
        $this->setRange($ranges);
    }
    
    public function setRange(array $range)
    {
        foreach ($range as $singleRange) {
            $this->addRange($singleRange);
        }
        return $this;
    }
    
    public function addRange($range)
    {
        $this->allowedAddresses[] = $range;
        return $this;
    }
    
    public function isValid($value)
    {
        $this->setValue($value);
    
        foreach ($this->allowedAddresses as $allowed_ip) {
            $type = $this->judge_ip_type($allowed_ip);
            $sub_rst = call_user_func(array($this,'sub_checker_' . $type), $allowed_ip, $value);

            if ($sub_rst){
                return true;
            }
        }
    
        return false;
    }
    
    private function judge_ip_type($ip)
    {
        if (strpos($ip, ':')) {
            return self::$IP_TYPE_V6;
        }
        
        if (strpos($ip, '*')) {
            return self::$IP_TYPE_WILDCARD;
        }
    
        if (strpos($ip, '/')) {
            return self::$IP_TYPE_MASK;
        }
    
        if (strpos($ip, '-')) {
            return self::$IP_TYPE_SECTION;
        }
    
        if (ip2long($ip)) {
            return self::$IP_TYPE_SINGLE;
        }
    
        return false;
    }
    
    private function sub_checker_single($allowed_ip, $ip)
    {
        return (ip2long($allowed_ip) == ip2long($ip));
    }
    
    private function sub_checker_ip_v6($allowed_ip, $ip)
    {
        return ($allowed_ip == $ip);
    }
    
    private function sub_checker_wildcard($allowed_ip, $ip)
    {
        $allowed_ip_arr = explode('.', $allowed_ip);
        $ip_arr = explode('.', $ip);
        for($i = 0;$i < count($allowed_ip_arr);$i++){
            if ($allowed_ip_arr[$i] == '*'){
                return true;
            }else{
                if (false == ($allowed_ip_arr[$i] == $ip_arr[$i])){
                    return false;
                }
            }
        }
        return false;
    }
    
    private function sub_checker_mask($allowed_ip, $ip)
    {
        list($allowed_ip_ip, $allowed_ip_mask) = explode('/', $allowed_ip);
        if(false === strpos($allowed_ip_mask,'.')){
            $allowed_ip_mask = $this->getIpMaskFromBit($allowed_ip_mask);
        }
        $ipMask   = (ip2long($allowed_ip_ip) &ip2long($allowed_ip_mask));
        $maskedIp = (ip2long($ip) & ip2long ($allowed_ip_mask));
        return ($ipMask == $maskedIp);
    }
    
    private function getIpMaskFromBit($mask)
    {
        $part=array();
        $mask=str_Repeat('1',$mask);
        $string=str_pad($mask,32,'0',STR_PAD_RIGHT);
        for($i=0;$i<4;$i++){
            $part[]=bindec(substr($string,$i*8,8));
        }
        return implode('.',$part);
    }
    
    private function sub_checker_section($allowed_ip, $ip)
    {
        list($begin, $end) = explode('-', $allowed_ip);
        $begin = ip2long($begin);
        $end = ip2long($end);
        $ip = ip2long($ip);
        return ($ip >= $begin && $ip <= $end);
    }
}