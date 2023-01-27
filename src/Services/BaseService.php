<?php

namespace DreamFactory\Core\Email\Services;

use App;
use DreamFactory\Core\Contracts\EmailServiceInterface;
use DreamFactory\Core\Contracts\ServiceRequestInterface;
use DreamFactory\Core\Contracts\ServiceResponseInterface;
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
use Illuminate\Mail\Mailer;
use Illuminate\Mail\Message;
use Symfony\Component\Mailer\Transport\TransportInterface as SymfonyTransport;
use Symfony\Component\Mailer\Mailer as SymfonyMailer;
use ServiceManager;
use \Illuminate\Support\Arr;

abstract class BaseService extends BaseRestService implements EmailServiceInterface
{
    use EmailUtilities;

    protected SymfonyTransport $transport;

    protected Mailer $mailer;

    protected array $parameters;

    /**
     * @throws InternalServerErrorException
     */
    public function __construct(array $settings)
    {
        parent::__construct($settings);

        $config = (Arr::get($settings, 'config', [])) ?: [];
        $this->setParameters($config);
        $this->setTransport($config);
        $this->setMailer();
    }

    abstract protected function setTransport(array $config);

    /**
     * @throws InternalServerErrorException
     */
    protected function setMailer()
    {
        if (!$this->transport instanceof SymfonyTransport) {
            throw new InternalServerErrorException('Invalid Email Transport.');
        }

        $this->mailer = new DfMailer($this->name, App::make('view'), $this->transport, App::make('events'));
    }

    protected function setParameters($config)
    {
        $this->parameters = (array)Arr::get($config, 'parameters', []);

        foreach ($this->parameters as $params) {
            $this->parameters[$params['name']] = Arr::get($params, 'value');
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function handleGET(): bool
    {
        return false;
    }

    protected function getUploadedAttachment(array|string $path = null): array
    {
        $attachment = [];
        $file = $path;
        if ($this->request instanceof ServiceRequestInterface) {
            $file = $this->request->getFile('file', $this->request->getFile('attachment', $path));
        }

        if (is_array($file)) {
            if (isset($file['tmp_name'], $file['name'])) {
                $attachment[] = new Attachment($file['tmp_name'], Arr::get($file, 'name'));
            } else {
                foreach ($file as $f) {
                    if (isset($f['tmp_name'], $f['name'])) {
                        $attachment[] = new Attachment(Arr::get($f, 'tmp_name'), Arr::get($f, 'name'));
                    }
                }
            }
        }

        return $attachment;
    }

    /**
     * @throws InternalServerErrorException
     */
    protected function getUrlAttachment( $path = null): array
    {
        $attachment = [];
        $file = $path;
        if ($this->request instanceof ServiceRequestInterface) {
            $file = $this->request->input('import_url', $this->request->input('attachment', $path));
        }

        if (!empty($file)) {
            if (!is_array($file)) {
                $files = explode(',', $file);
            } else {
                $files = $file;
            }
            try {
                foreach ($files as $f) {
                    if (is_string($f)) {
                        Session::replaceLookups($f);
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
     * @throws InternalServerErrorException
     */
    protected function getServiceAttachment(array|string $path = null): array
    {
        $attachment = [];
        $file = $path;
        if ($this->request instanceof ServiceRequestInterface) {
            $file = $this->request->input('import_url', $this->request->input('attachment', $path));
        }

        if (!empty($file) && is_array($file)) {
            if (isset($file['service'])) {
                $files = [$file];
            } else {
                $files = $file;
            }

            try {
                foreach ($files as $f) {
                    if (is_array($f)) {
                        $service = Arr::get($f, 'service');
                        $path = Arr::get($f, 'path', Arr::get($f, 'file_path'));
                        Session::replaceLookups($service);
                        Session::replaceLookups($path);

                        if (empty($service) || empty($path)) {
                            throw new BadRequestException('No service name and file path provided in request.');
                        }

                        if (Session::checkServicePermission(Verbs::GET, $service, $path, Session::getRequestor(),
                            false)) {
                            /** @var ServiceResponseInterface $result */
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
                            $content = base64_decode(Arr::get($content, 'content', ''));
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
     * @throws InternalServerErrorException
     */
    public function getAttachments($path = null): array
    {
        return array_merge(
            $this->getUploadedAttachment($path),
            $this->getUrlAttachment($path),
            $this->getServiceAttachment($path)
        );
    }

    /**
     * @throws BadRequestException
     * @throws NotFoundException
     */
    protected function handlePOST(): array
    {
        $data = $this->getPayloadData();
        if (empty($data)) {
            $data = $this->request->input();
        }
        $templateName = $this->request->input('template');
        $templateId = $this->request->input('template_id');
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

        $data = array_merge((array)Arr::get($templateData, 'defaults', []), $data);
        $data = array_merge($this->parameters, $templateData, $data);

        $text = Arr::get($data, 'body_text');
        $html = Arr::get($data, 'body_html');

        $count = $this->sendEmail($data, $text, $html);

        //Mandrill and Mailgun returns Guzzle\Message\Response object.
        if (!is_int($count)) {
            $count = 1;
        }

        return ['count' => $count];
    }

    /**
     * Sends out emails.
     */
    public function sendEmail($data, $textView = null, $htmlView = null)
    {
        Session::replaceLookups($textView);
        Session::replaceLookups($htmlView);

        $view = [
            'html' => $htmlView,
            'text' => $textView
        ];

        return $this->mailer->send(
            $view,
            $data,
            function (Message $m) use ($data) {
                $to = Arr::get($data, 'to');
                $cc = Arr::get($data, 'cc');
                $bcc = Arr::get($data, 'bcc');
                $subject = Arr::get($data, 'subject');
                $fromName = Arr::get($data, 'from_name');
                $fromEmail = Arr::get($data, 'from_email');
                $replyName = Arr::get($data, 'reply_to_name');
                $replyEmail = Arr::get($data, 'reply_to_email');
                // Look for any attachment in request data.
                $attachment = $this->getAttachments();
                // No attachment in request data. Attachment found in email template.
                if (empty($attachment) && isset($data['attachment'])) {
                    // Get the attachment data from email template.
                    $attachment = $this->getAttachments($data['attachment']);
                }

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

                if (!empty($bcc)) {
                    $m->bcc($bcc);
                }
                if (!empty($cc)) {
                    $m->cc($cc);
                }
            }
        );
    }

    /**
     * @throws NotFoundException
     */
    public static function getTemplateDataByName($name): array
    {
        // find template in system db
        $template = EmailTemplate::whereName($name)->first();
        if (empty($template)) {
            throw new NotFoundException("Email Template '$name' not found");
        }

        return $template->toArray();
    }

    /**
     * @throws NotFoundException
     */
    public static function getTemplateDataById($id): array
    {
        // find template in system db
        $template = EmailTemplate::whereId($id)->first();
        if (empty($template)) {
            throw new NotFoundException("Email Template id '$id' not found");
        }

        return $template->toArray();
    }

    protected function getApiDocPaths(): array
    {
        $capitalized = camelize($this->name);

        return [
            '/' => [
                'post' => [
                    'summary'     => 'Send an email created from posted data and/or a template.',
                    'description' =>
                        'If a template is not used with all required fields, then they must be included in the request. ' .
                        'If the \'from\' address is not provisioned in the service, then it must be included in the request.',
                    'operationId' => 'send' . $capitalized . 'Email',
                    'parameters'  => [
                        [
                            'name'        => 'template',
                            'description' => 'Optional template name to base email on.',
                            'schema'      => ['type' => 'string'],
                            'in'          => 'query',
                        ],
                        [
                            'name'        => 'template_id',
                            'description' => 'Optional template id to base email on.',
                            'schema'      => ['type' => 'integer', 'format' => 'int32'],
                            'in'          => 'query',
                        ],
                        [
                            'name'        => 'attachment',
                            'description' => 'Import file(s) from URL for attachment. This is also available in form-data post and in json payload data.',
                            'schema'      => ['type' => 'string'],
                            'in'          => 'query',
                        ],
                    ],
                    'requestBody' => [
                        '$ref' => '#/components/requestBodies/EmailRequest'
                    ],
                    'responses'   => [
                        '200' => ['$ref' => '#/components/responses/EmailResponse']
                    ],
                ],
            ],
        ];
    }

    protected function getApiDocRequests(): array
    {
        return [
            'EmailRequest' => [
                'description' => 'Email Request',
                'content'     => [
                    'application/json' => [
                        'schema' => ['$ref' => '#/components/schemas/EmailRequest']
                    ],
                    'application/xml'  => [
                        'schema' => ['$ref' => '#/components/schemas/EmailRequest']
                    ],
                ],
            ],
        ];
    }

    protected function getApiDocResponses(): array
    {
        return [
            'EmailResponse' => [
                'description' => 'Email Response',
                'content'     => [
                    'application/json' => [
                        'schema' => ['$ref' => '#/components/schemas/EmailResponse']
                    ],
                    'application/xml'  => [
                        'schema' => ['$ref' => '#/components/schemas/EmailResponse']
                    ],
                ],
            ],
        ];
    }

    protected function getApiDocSchemas(): array
    {
        return [
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
                            '$ref' => '#/components/schemas/EmailAddress',
                        ],
                    ],
                    'cc'             => [
                        'type'        => 'array',
                        'description' => 'Optional CC receiver addresses.',
                        'items'       => [
                            '$ref' => '#/components/schemas/EmailAddress',
                        ],
                    ],
                    'bcc'            => [
                        'type'        => 'array',
                        'description' => 'Optional BCC receiver addresses.',
                        'items'       => [
                            '$ref' => '#/components/schemas/EmailAddress',
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
                        'description' => 'File(s) to import from storage service or URL for attachment',
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
    }
}