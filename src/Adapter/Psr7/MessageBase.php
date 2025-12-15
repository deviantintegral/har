<?php

declare(strict_types=1);

namespace Deviantintegral\Har\Adapter\Psr7;

use Deviantintegral\Har\Header;
use Psr\Http\Message\MessageInterface;

abstract class MessageBase implements MessageInterface
{
    /**
     * @var \Deviantintegral\Har\MessageInterface
     */
    protected \Deviantintegral\Har\MessageInterface $message;

    public function __construct(\Deviantintegral\Har\MessageInterface $message)
    {
        $this->message = $message;
    }

    public function getHeaderLine($name): string
    {
        if ($this->hasHeader($name)) {
            return implode(', ', $this->getHeader($name));
        }

        return '';
    }

    public function hasHeader($name): bool
    {
        foreach ($this->message->getHeaders() as $header) {
            if (strtolower($header->getName()) === strtolower($name)) {
                return true;
            }
        }

        return false;
    }

    public function withHeader($name, $value): MessageInterface
    {
        $message = clone $this->message;

        if (!\is_array($value)) {
            $value = [$value];
        }

        $index = 0;
        $headers = $message->getHeaders();
        if ($this->hasHeader($name)) {
            foreach ($headers as $header_index => $header) {
                if (strtolower($header->getName()) === strtolower($name)) {
                    if (isset($value[$index])) {
                        $header->setValue($value[$index++]);
                    } else {
                        unset($headers[$header_index]);
                    }
                }
            }
        }

        for (; $index < \count($value); ++$index) {
            $header = (new Header())
              ->setName($name)
              ->setValue($value[$index]);
            $headers[] = $header;
        }

        $message->setHeaders($headers);

        return new static($message);
    }

    public function withoutHeader($name): MessageInterface
    {
        $message = clone $this->message;

        $headers = $message->getHeaders();
        foreach ($headers as $index => $header) {
            if ($header->getName() === $name) {
                unset($headers[$index]);
                break;
            }
        }

        $message->setHeaders($headers);

        return new static($message);
    }

    public function getHeaders(): array
    {
        $headers = $this->message->getHeaders();
        $return = [];
        foreach ($headers as $header) {
            $return[$header->getName()][] = $header->getValue();
        }

        return $return;
    }

    public function getHeader($name): array
    {
        if ($this->hasHeader($name)) {
            $headers = $this->getHeaders();
            foreach ($headers as $header => $value) {
                if (strtolower($header) === strtolower($name)) {
                    return $value;
                }
            }
        }

        return [];
    }

    public function withAddedHeader($name, $value): MessageInterface
    {
        $message = clone $this->message;

        if (!\is_array($value)) {
            $value = [$value];
        }

        $headers = $message->getHeaders();
        foreach ($value as $line) {
            $headers[] = (new Header())
              ->setName($name)
              ->setValue($line);
        }
        $message->setHeaders($headers);

        return new static($message);
    }

    public function withProtocolVersion($version): MessageInterface
    {
        $message = clone $this->message;
        $message->setHttpVersion('HTTP/'.$version);

        return new static($message);
    }

    public function getProtocolVersion(): string
    {
        return substr($this->message->getHttpVersion(), 5);
    }
}
