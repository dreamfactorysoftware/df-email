<?php
namespace DreamFactory\Core\Email;

use DreamFactory\Core\Email\Models\LocalEmailConfig;
use DreamFactory\Core\Email\Models\MailGunConfig;
use DreamFactory\Core\Email\Models\MandrillConfig;
use DreamFactory\Core\Email\Models\SmtpConfig;
use DreamFactory\Core\Email\Models\SparkpostConfig;
use DreamFactory\Core\Email\Services\Local;
use DreamFactory\Core\Email\Services\MailGun;
use DreamFactory\Core\Email\Services\Mandrill;
use DreamFactory\Core\Email\Services\Smtp;
use DreamFactory\Core\Email\Services\SparkPost;
use DreamFactory\Core\Enums\ServiceTypeGroups;
use DreamFactory\Core\Services\ServiceManager;
use DreamFactory\Core\Services\ServiceType;

class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
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
                        'factory'         => function ($config) {
                            return new Mandrill($config);
                        },
                    ]));
            $df->addType(
                new ServiceType(
                    [
                        'name'            => 'sparkpost_email',
                        'label'           => 'SparkPost',
                        'description'     => 'SparkPost email service',
                        'group'           => ServiceTypeGroups::EMAIL,
                        'config_handler'  => SparkpostConfig::class,
                        'factory'         => function ($config) {
                            return new SparkPost($config);
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
