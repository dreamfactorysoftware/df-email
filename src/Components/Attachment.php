<?php

namespace DreamFactory\Core\Email\Components;

use DreamFactory\Core\Exceptions\InternalServerErrorException;

class Attachment
{
    /** @var string */
    protected $path;

    /** @var null|string */
    protected $name;

    /**
     * Attachment constructor.
     *
     * @param      $fullPath
     * @param null $fileName
     *
     * @throws \DreamFactory\Core\Exceptions\InternalServerErrorException
     */
    public function __construct($fullPath, $fileName = null)
    {
        if (!is_file($fullPath)) {
            throw new InternalServerErrorException('Invalid file path provided from attachment - ' . $fullPath);
        }
        $this->path = $fullPath;
        if (empty($fileName)) {
            $fileName = basename($fullPath);
        }
        $this->name = $fileName;
    }

    /**
     * @return null|string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @param bool $base64encode
     *
     * @return bool|string
     */
    public function getContent($base64encode = false)
    {
        $content = file_get_contents($this->path);

        return ($base64encode) ? base64_encode($content) : $content;
    }

    /**
     * Removes file from local storage.
     */
    public function unlink()
    {
        @unlink($this->path);
    }

    /**
     * Class destructor.
     */
    public function __destruct()
    {
        $this->unlink();
    }
}