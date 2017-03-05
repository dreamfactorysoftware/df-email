<?php
namespace DreamFactory\Core\Email;

use DreamFactory\Core\Components\ServiceDocBuilder;
use DreamFactory\Core\Email\Models\LocalEmailConfig;
use DreamFactory\Core\Email\Models\MailGunConfig;
use DreamFactory\Core\Email\Models\MandrillConfig;
use DreamFactory\Core\Email\Models\SmtpConfig;
use DreamFactory\Core\Email\Services\Local;
use DreamFactory\Core\Email\Services\MailGun;
use DreamFactory\Core\Email\Services\Mandrill;
use DreamFactory\Core\Email\Services\Smtp;
use DreamFactory\Core\Enums\ServiceTypeGroups;
use DreamFactory\Core\Services\ServiceManager;
use DreamFactory\Core\Services\ServiceType;

class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    use ServiceDocBuilder;

    public function register()
    {
        // Add our scripting service types.
        $this->app->resolving('df.service', function (ServiceManager $df) {
            $df->addType(
                new ServiceType(
                    [
                        'name'            => 'local_email',
                        'label'           => 'Local Email Service',
                        'description'     => 'Local email service using system configuration.',
                        'group'           => ServiceTypeGroups::EMAIL,
                        'config_handler'  => LocalEmailConfig::class,
                        'default_api_doc' => function ($service) {
                            return $this->buildServiceDoc($service->id, Local::getApiDocInfo($service));
                        },
                        'factory'         => function ($config) {
                            return new Local($config);
                        },
                    ]));
            $df->addType(
                new ServiceType(
                    [
                        'name'            => 'smtp_email',
                        'label'           => 'SMTP',
                        'description'     => 'SMTP-based email service',
                        'group'           => ServiceTypeGroups::EMAIL,
                        'config_handler'  => SmtpConfig::class,
                        'default_api_doc' => function ($service) {
                            return $this->buildServiceDoc($service->id, Smtp::getApiDocInfo($service));
                        },
                        'factory'         => function ($config) {
                            return new Smtp($config);
                        },
                    ]));
            $df->addType(
                new ServiceType(
                    [
                        'name'            => 'mailgun_email',
                        'label'           => 'Mailgun',
                        'description'     => 'Mailgun email service',
                        'group'           => ServiceTypeGroups::EMAIL,
                        'config_handler'  => MailGunConfig::class,
                        'default_api_doc' => function ($service) {
                            return $this->buildServiceDoc($service->id, MailGun::getApiDocInfo($service));
                        },
                        'factory'         => function ($config) {
                            return new MailGun($config);
                        },
                    ]));
            $df->addType(
                new ServiceType(
                    [
                        'name'            => 'mandrill_email',
                        'label'           => 'Mandrill',
                        'description'     => 'Mandrill email service',
                        'group'           => ServiceTypeGroups::EMAIL,
                        'config_handler'  => MandrillConfig::class,
                        'default_api_doc' => function ($service) {
                            return $this->buildServiceDoc($service->id, Mandrill::getApiDocInfo($service));
                        },
                        'factory'         => function ($config) {
                            return new Mandrill($config);
                        },
                    ]));
        });
    }

    public function boot()
    {
        // add migrations
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }
}
