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
return array('OrgHeiglDeploy' => array(
	// This denotes the security hash that has to be append to the URL to
	// trigger a deployment.
	'hash' => 'EA3459C3-8839-4F00-9423-77CDA2A386D6',
	
	// A List of IP-Addresses and/or IP-Ranges that are allowed to trigger
	// the deployment script. All calls from addresses outside the set ones
	// will simply return a 404 error
	'ip' => array(
	         '127.0.0.1',
	         '::1',
	         '192.168.1.1/24'
            ),
    
    // The location of the ZIP-file your project can be downloaded at.
    // As this module will be used on environments you can not control we do
    // not have any access to a VCS, therefore we have to download a zip-file
    // and extract that.
    'source' => 'https://nodeload.github.com/heiglandreas/OrgHeiglDeploy/zip/master',

    // The base-directory of the ZIP-File every file is located in
    // For GitHub-files this is 'RepoName-branch'
    'zipBaseDir' => 'OrgHeiglDeploy-master',

    // The location where the files shall be deployed to
    'target' => realpath(__DIR__ . '/../../'),

    // Which script shall be triggered as pre-deployment-script? This has to be
    // a php-script
    'predeployment' => 'tools/predeployment.php',
    
    // Which script shall be triggered as post-deployment-script? This has to be
    // a php-script
    'postdeployment' => 'tools/postdeployment.sh',
));
