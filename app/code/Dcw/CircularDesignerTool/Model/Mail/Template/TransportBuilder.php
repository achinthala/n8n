<?php

namespace Dcw\CircularDesignerTool\Model\Mail\Template;

use Magento\Framework\Mail\Template\TransportBuilder as MagentoTransportBuilder;
use \Laminas\Mime\Mime;
use \Laminas\Mime\Part;

class TransportBuilder extends MagentoTransportBuilder
{
    private $parts = [];

    /**
     * addAttachment
     *
     * @param mixed $body
     * @param string $filename
     * @param mixed $mimeType
     * @param mixed $disposition
     * @param mixed $encoding
     * @return object
     */
    public function addAttachment(
        $body,
        $filename = null,
        $mimeType = \Laminas\Mime\Mime::TYPE_OCTETSTREAM,
        $disposition = \Laminas\Mime\Mime::DISPOSITION_ATTACHMENT,
        $encoding = \Laminas\Mime\Mime::ENCODING_BASE64
    ) {
        $attachmentPart = new \Laminas\Mime\Part();
        $attachmentPart->setContent($body)
            ->setType($mimeType)
            ->setFileName($filename)
            ->setEncoding($encoding)
            ->setDisposition($disposition);
        return $attachmentPart;
    }
}
