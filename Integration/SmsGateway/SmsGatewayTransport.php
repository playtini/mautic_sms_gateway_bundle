<?php

namespace MauticPlugin\MauticSmsGatewayBundle\Integration\SmsGateway;

use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\SmsBundle\Sms\TransportInterface;
use MauticPlugin\MauticSmsGatewayBundle\Integration\Exceptions\SmsGatewayException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;
use Joomla\Http\Http;

class SmsGatewayTransport implements TransportInterface
{
    /**
     * @var Configuration
     */
    private $configuration;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Http
     */
    private $client;

    /**
     * SmsGatewayTransport constructor.
     */
    public function __construct(Http $http, Configuration $configuration, LoggerInterface $logger)
    {
        $this->client = $http;
        $this->configuration = $configuration;
        $this->logger = $logger;
    }

    /**
     * @param string $content
     *
     * @return bool|string
     */
    public function sendSms(Lead $lead, $content)
    {
        $leadPhoneNumber = $lead->getLeadPhoneNumber();

        if (null === $leadPhoneNumber) {
            return false;
        }

        try {
            $response = $this->client->post($this->configuration->getGatewayUrl(), [
                'phone_number' => $leadPhoneNumber,
                'message' => $content,
            ], [
                'Content-Type' => 'application/json',
            ]);

            if (!in_array($response->code, [Response::HTTP_OK, Response::HTTP_CREATED])) {
                $this->logger->error('Sms not send', [
                    'response' => $response->body,
                    'body' => [
                        'phone_number' => $leadPhoneNumber,
                        'message' => $content,
                    ]
                ]);

                throw new SmsGatewayException("SmsGateway couldn't send message: " . $response->code);
            }
        } catch (SmsGatewayException $e) {
            return $e->getMessage();
        }

        return true;
    }

    /**
     * @param Lead $lead
     *
     * @return string|null
     *
     * @throws NumberParseException
     */
    private function getLeadPhoneNumber(Lead $lead): ?string
    {
        $number = $lead->getLeadPhoneNumber();
        if (!$number) {
            return null;
        }

        $util = PhoneNumberUtil::getInstance();
        $parsed = $util->parse($number, 'US');

        return $util->format($parsed, PhoneNumberFormat::E164);
    }
}
