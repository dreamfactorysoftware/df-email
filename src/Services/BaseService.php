<?php

namespace DreamFactory\Core\Email\Services;

use App;
use DreamFactory\Core\Contracts\EmailServiceInterface;
use DreamFactory\Core\Email\Components\Attachment;
use DreamFactory\Core\Email\Components\EmailUtilities;
use DreamFactory\Core\Email\Components\Mailer as DfMailer;
use DreamFactory\Core\Enums\Verbs;
use DreamFactory\Core\Exceptions\ForbiddenException;
use DreamFactory\Core\Exceptions\NotFoundException;
use DreamFactory\Core\Exceptions\BadRequestException;
use DreamFactory\Core\Exceptions\InternalServerErrorException;
use DreamFactory\Core\Models\EmailTemplate;
use DreamFactory\Core\Services\BaseRestService;
use DreamFactory\Core\Utility\FileUtilities;
use DreamFactory\Core\Utility\Session;
use Illuminate\Mail\Message;
use Swift_Transport as SwiftTransport;
use Swift_Mailer as SwiftMailer;
use ServiceManager;

abstract class BaseService extends BaseRestService implements EmailServiceInterface
{
    use EmailUtilities;

    /**
     * @var SwiftTransport
     */
    protected $transport;

    /**
     * @var \Illuminate\Mail\Mailer;
     */
    protected $mailer;

    /**
     * @var array;
     */
    protected $parameters;

    /**
     * @param array $settings
     */
    public function __construct($settings)
    {
        parent::__construct($settings);

        $config = (array_get($settings, 'config', [])) ?: [];
        $this->setParameters($config);
        $this->setTransport($config);
        $this->setMailer();
    }

    /**
     * Sets the email transport layer based on configuration.
     *
     * @param array $config
     */
    abstract protected function setTransport($config);

    /**
     * @throws \DreamFactory\Core\Exceptions\InternalServerErrorException
     */
    protected function setMailer()
    {
        if (!$this->transport instanceof SwiftTransport) {
            throw new InternalServerErrorException('Invalid Email Transport.');
        }

        $swiftMailer = new SwiftMailer($this->transport);
        $this->mailer = new DfMailer(App::make('view'), $swiftMailer, App::make('events'));
    }

    /**
     * @param $config
     */
    protected function setParameters($config)
    {
        $this->parameters = (array)array_get($config, 'parameters', []);

        foreach ($this->parameters as $params) {
            $this->parameters[$params['name']] = array_get($params, 'value');
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function handleGET()
    {
        return false;
    }

    /**
     * Gets uploaded file(s) for attachment.
     *
     * @return array
     */
    protected function getUploadedAttachment()
    {
        $attachment = [];
        $file = $this->request->getFile('file', $this->request->getFile('attachment'));

        if (is_array($file)) {
            if (isset($file['tmp_name'])) {
                $attachment[] = new Attachment($file['tmp_name'], array_get($file, 'name'));
            } else {
                foreach ($file as $f) {
                    $attachment[] = new Attachment(array_get($f, 'tmp_name'), array_get($f, 'name'));
                }
            }
        }

        return $attachment;
    }

    /**
     * Gets URL imported file(s) for attachment.
     *
     * @return array
     * @throws \DreamFactory\Core\Exceptions\InternalServerErrorException
     */
    protected function getUrlAttachment()
    {
        $attachment = [];
        $file = $this->request->input('import_url', $this->request->input('attachment'));

        if (!empty($file)) {
            if (!is_array($file)) {
                $files = explode(',', $file);
            } else {
                $files = $file;
            }
            try {
                foreach ($files as $f) {
                    if (is_string($f)) {
                        $fileURL = urldecode($f);
                        $filePath = FileUtilities::importUrlFileToTemp($fileURL);
                        $attachment[] = new Attachment($filePath, basename($fileURL));
                    }
                }
            } catch (\Exception $e) {
                throw new InternalServerErrorException('Failed to import attachment file from url. ' .
                    $e->getMessage());
            }
        }

        return $attachment;
    }

    /**
     * Gets file(s) stored in storage service(s) for attachment.
     *
     * @return array
     * @throws \DreamFactory\Core\Exceptions\InternalServerErrorException
     */
    protected function getServiceAttachment()
    {
        $attachment = [];
        $file = $this->request->input('import_url', $this->request->input('attachment'));

        if (!empty($file) && is_array($file)) {
            if (isset($file['service'])) {
                $files = [$file];
            } else {
                $files = $file;
            }

            try {
                foreach ($files as $f) {
                    if (is_array($f)) {
                        $service = array_get($f, 'service');
                        $path = array_get($f, 'path', array_get($f, 'file_path'));

                        if (empty($service) || empty($path)) {
                            throw new BadRequestException('No service name and file path provided in request.');
                        }

                        if (Session::checkForAnyServicePermissions($service, $path)) {
                            /** @var \DreamFactory\Core\Contracts\ServiceResponseInterface $result */
                            $result = ServiceManager::handleRequest(
                                $service,
                                Verbs::GET,
                                $path,
                                ['include_properties' => true, 'content' => true, 'is_base64' => true]
                            );

                            if ($result->getStatusCode() !== 200) {
                                throw new InternalServerErrorException(
                                    'Could to retrieve attachment file: ' .
                                    $path .
                                    ' from storage service: ' .
                                    $service);
                            }

                            $content = $result->getContent();
                            $content = base64_decode(array_get($content, 'content', ''));
                            $fileName = basename($path);
                            $filePath = sys_get_temp_dir() . '/' . $fileName;
                            file_put_contents($filePath, $content);
                            $attachment[] = new Attachment($filePath, $fileName);
                        } else {
                            throw new ForbiddenException(
                                'You do not have enough privileges to access file: ' .
                                $path .
                                ' in service ' .
                                $service);
                        }
                    }
                }
            } catch (\Exception $e) {
                throw new InternalServerErrorException('Failed to get attachment file from storage service. ' .
                    $e->getMessage());
            }
        }

        return $attachment;
    }

    /**
     * @return array|mixed|string
     * @throws \DreamFactory\Core\Exceptions\BadRequestException
     */
    protected function getAttachments()
    {
        return array_merge(
            $this->getUploadedAttachment(),
            $this->getUrlAttachment(),
            $this->getServiceAttachment()
        );
    }

    /**
     * @return array
     * @throws BadRequestException
     * @throws NotFoundException
     */
    protected function handlePOST()
    {
        $data = $this->getPayloadData();
        if (empty($data)) {
            $data = $this->request->input();
        }
        $templateName = $this->request->getParameter('template', $this->request->getPayloadData('template'));
        $templateId = $this->request->getParameter('template_id', $this->request->getPayloadData('template_id'));
        $data['attachment'] = $this->getAttachments();
        $templateData = [];

        if (!empty($templateName)) {
            // find template in system db
            $template = EmailTemplate::whereName($templateName)->first();
            if (empty($template)) {
                throw new NotFoundException("Email Template '$templateName' not found");
            }

            $templateData = $template->toArray();
        } elseif (!empty($templateId)) {
            // find template in system db
            $template = EmailTemplate::whereId($templateId)->first();
            if (empty($template)) {
                throw new NotFoundException("Email Template id '$templateId' not found");
            }

            $templateData = $template->toArray();
        }

        if (empty($templateData) && empty($data)) {
            throw new BadRequestException('No valid data in request.');
        }

        $data = array_merge((array)array_get($templateData, 'defaults', []), $data);
        $data = array_merge($this->parameters, $templateData, $data);

        $text = array_get($data, 'body_text');
        $html = array_get($data, 'body_html');

        $count = $this->sendEmail($data, $text, $html);

        //Mandrill and Mailgun returns Guzzle\Message\Response object.
        if (!is_int($count)) {
            $count = 1;
        }

        return ['count' => $count];
    }

    /**
     * Sends out emails.
     *
     * @param array $data
     * @param null  $textView
     * @param null  $htmlView
     *
     * @return mixed
     */
    public function sendEmail($data, $textView = null, $htmlView = null)
    {
        Session::replaceLookups($textView);
        Session::replaceLookups($htmlView);

        $view = [
            'html' => $htmlView,
            'text' => $textView
        ];

        /** @noinspection PhpVoidFunctionResultUsedInspection */
        $count = $this->mailer->send(
            $view,
            $data,
            function (Message $m) use ($data){
                $to = array_get($data, 'to');
                $cc = array_get($data, 'cc');
                $bcc = array_get($data, 'bcc');
                $subject = array_get($data, 'subject');
                $fromName = array_get($data, 'from_name');
                $fromEmail = array_get($data, 'from_email');
                $replyName = array_get($data, 'reply_to_name');
                $replyEmail = array_get($data, 'reply_to_email');
                $attachment = array_get($data, 'attachment');

                if (empty($fromEmail)) {
                    $fromEmail = config('mail.from.address');
                    $data['from_email'] = $fromEmail;
                    if (empty($fromName)) {
                        $fromName = config('mail.from.name');
                        $data['from_name'] = $fromName;
                    }
                }

                $to = static::sanitizeAndValidateEmails($to, 'swift');
                if (!empty($cc)) {
                    $cc = static::sanitizeAndValidateEmails($cc, 'swift');
                }
                if (!empty($bcc)) {
                    $bcc = static::sanitizeAndValidateEmails($bcc, 'swift');
                }

                $fromEmail = static::sanitizeAndValidateEmails($fromEmail, 'swift');
                if (!empty($replyEmail)) {
                    $replyEmail = static::sanitizeAndValidateEmails($replyEmail, 'swift');
                }

                $m->to($to);

                if (!empty($fromEmail)) {
                    $m->from($fromEmail, $fromName);
                }
                if (!empty($replyEmail)) {
                    $m->replyTo($replyEmail, $replyName);
                }

                if (!empty($subject)) {
                    Session::replaceLookups($subject);
                    $m->subject(static::applyDataToView($subject, $data));
                }

                if (!empty($attachment)) {
                    if (!is_array($attachment)) {
                        $attachment = [$attachment];
                    }
                    foreach ($attachment as $att) {
                        if ($att instanceof Attachment) {
                            $m->attachData($att->getContent(), $att->getName());
                            $att->unlink();
                        }
                    }
                }

                if (!empty($attachment) && is_array($attachment)) {
                    if (isset($attachment['name'])) {
                        $attachments[] = $attachment;
                    } else {
                        $attachments = $attachment;
                    }
                    foreach ($attachments as $att) {
                        $fileName = array_get($att, 'name');
                        $filePath = array_get($att, 'tmp_name');
                        if (is_file($filePath)) {
                            $m->attachData(file_get_contents($filePath), $fileName);
                            // Delete from local /tmp directory.
                            @unlink($filePath);
                        }
                    }
                }

                if (!empty($bcc)) {
                    $m->bcc($bcc);
                }
                if (!empty($cc)) {
                    $m->cc($cc);
                }
            }
        );

        return $count;
    }

    /**
     * @param $name
     *
     * @throws NotFoundException
     *
     * @return array
     */
    public static function getTemplateDataByName($name)
    {
        // find template in system db
        $template = EmailTemplate::whereName($name)->first();
        if (empty($template)) {
            throw new NotFoundException("Email Template '$name' not found");
        }

        return $template->toArray();
    }

    /**
     * @param $id
     *
     * @throws NotFoundException
     *
     * @return array
     */
    public static function getTemplateDataById($id)
    {
        // find template in system db
        $template = EmailTemplate::whereId($id)->first();
        if (empty($template)) {
            throw new NotFoundException("Email Template id '$id' not found");
        }

        return $template->toArray();
    }

    public static function getApiDocInfo($service)
    {
        $name = strtolower($service->name);
        $capitalized = camelize($service->name);
        $paths = [
            '/' . $name => [
                'post' => [
                    'tags'        => [$name],
                    'summary'     => 'send' .
                        $capitalized .
                        'Email() - Send an email created from posted data and/or a template.',
                    'operationId' => 'send' . $capitalized . 'Email',
                    'parameters'  => [
                        [
                            'name'        => 'template',
                            'description' => 'Optional template name to base email on.',
                            'type'        => 'string',
                            'in'          => 'query',
                            'required'    => false,
                        ],
                        [
                            'name'        => 'template_id',
                            'description' => 'Optional template id to base email on.',
                            'type'        => 'integer',
                            'format'      => 'int32',
                            'in'          => 'query',
                            'required'    => false,
                        ],
                        [
                            'name'        => 'data',
                            'description' => 'Data containing name-value pairs used for provisioning emails.',
                            'schema'      => ['$ref' => '#/definitions/EmailRequest'],
                            'in'          => 'body',
                            'required'    => false,
                        ],
                        [
                            'name'        => 'attachment',
                            'description' => 'Import file(s) from URL for attachment. This is also available in form-data post and in json payload data.',
                            'type'        => 'string',
                            'in'          => 'query',
                            'required'    => false,
                        ],
                    ],
                    'responses'   => [
                        '200'     => [
                            'description' => 'Send Email Response',
                            'schema'      => ['$ref' => '#/definitions/EmailResponse']
                        ],
                        'default' => [
                            'description' => 'Error',
                            'schema'      => ['$ref' => '#/definitions/Error']
                        ]
                    ],
                    'description' =>
                        'If a template is not used with all required fields, then they must be included in the request. ' .
                        'If the \'from\' address is not provisioned in the service, then it must be included in the request.',
                ],
            ],
        ];

        $definitions = [
            'EmailResponse' => [
                'type'       => 'object',
                'properties' => [
                    'count' => [
                        'type'        => 'integer',
                        'format'      => 'int32',
                        'description' => 'Number of emails successfully sent.',
                    ],
                ],
            ],
            'EmailRequest'  => [
                'type'       => 'object',
                'properties' => [
                    'template'       => [
                        'type'        => 'string',
                        'description' => 'Email Template name to base email on.',
                    ],
                    'template_id'    => [
                        'type'        => 'integer',
                        'format'      => 'int32',
                        'description' => 'Email Template id to base email on.',
                    ],
                    'to'             => [
                        'type'        => 'array',
                        'description' => 'Required single or multiple receiver addresses.',
                        'items'       => [
                            '$ref' => '#/definitions/EmailAddress',
                        ],
                    ],
                    'cc'             => [
                        'type'        => 'array',
                        'description' => 'Optional CC receiver addresses.',
                        'items'       => [
                            '$ref' => '#/definitions/EmailAddress',
                        ],
                    ],
                    'bcc'            => [
                        'type'        => 'array',
                        'description' => 'Optional BCC receiver addresses.',
                        'items'       => [
                            '$ref' => '#/definitions/EmailAddress',
                        ],
                    ],
                    'subject'        => [
                        'type'        => 'string',
                        'description' => 'Text only subject line.',
                    ],
                    'body_text'      => [
                        'type'        => 'string',
                        'description' => 'Text only version of the body.',
                    ],
                    'body_html'      => [
                        'type'        => 'string',
                        'description' => 'Escaped HTML version of the body.',
                    ],
                    'from_name'      => [
                        'type'        => 'string',
                        'description' => 'Required sender name.',
                    ],
                    'from_email'     => [
                        'type'        => 'string',
                        'description' => 'Required sender email.',
                    ],
                    'reply_to_name'  => [
                        'type'        => 'string',
                        'description' => 'Optional reply to name.',
                    ],
                    'reply_to_email' => [
                        'type'        => 'string',
                        'description' => 'Optional reply to email.',
                    ],
                    'attachment'     => [
                        'type'        => 'array',
                        'description' => 'File(s) to import from storage service for attachment',
                        'items'       => [
                            'type'       => 'object',
                            'properties' => [
                                'service' => [
                                    'type'        => 'string',
                                    'description' => 'Name of the storage service to use.'
                                ],
                                'path'    => [
                                    'type'        => 'string',
                                    'description' => 'File path relative to the service.'
                                ]
                            ]
                        ]
                    ],
                ],
            ],
            'EmailAddress'  => [
                'type'       => 'object',
                'properties' => [
                    'name'  => [
                        'type'        => 'string',
                        'description' => 'Optional name displayed along with the email address.',
                    ],
                    'email' => [
                        'type'        => 'string',
                        'description' => 'Required email address.',
                    ],
                ],
            ],
        ];

        return ['paths' => $paths, 'definitions' => $definitions];
    }
}