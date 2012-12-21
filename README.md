# OrgHeiglDeploy

## Deployment-Module for ZF2

### Purpose

This module helps you deploying a ZendFramework2-Application in a hosting
environment where you do not have full control over the server.

You can trigger the deployment by calling a specific URL which then triggers
download of your applications ZIP-file from a defined location, extracting that 
and finaly running a composer-update.

You can also define a pre-deployment hook as well as a post-deployment hook.

The pre-deployment hook can be used to set a static maintenance-page whereas the 
post-deployment hook can be used to remove a static page or to adapt your 
database-schemes.

### Installation

#### Via composer
Add the following line to your ``composer.json`` file in the ``require``-section:

    'org_heigl/deploy' : 'dev-master'

#### Afterwork
   
To activate this module you will have to include it into your application-config
like the following example:

    return array(
        'modules'=>array(
            'Applcation',
            'OrgHeiglContact',
        ),
    );
    
### Configuration

After installing you will need to adapt some configuration-values. Therefore 
you should copy the file ``vendor/org_heigl/Deploy/config/module.org_heigl_deploy.local.php``
to ``config/autoload/module.org_heigl_deploy.local.php`` and adapt the values 
according to the comments in the file.

After doing so, you can trigger a deployment by calling 
http://your.site.example.com/deploy/<yourSecurityToken>

That's it.

### Contributing

Issues are tracked here at GitHub's issue tracker. There you can leave issues
and feature-requests

And feel free to clone and send pull-requests.

### License

 The whole stuff is licensed under the MIT-License
 
I can only emphasize, that I can not be made responsible for anything that 
happens, when you use this module!!