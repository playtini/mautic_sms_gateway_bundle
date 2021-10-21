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
use Psr\Http\Client\ClientInterface;

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
     * @var ClientInterface
     */
    private $client;

    /**
     * SmsGatewayTransport constructor.
     */
    public function __construct(ClientInterface $client, Configuration $configuration, LoggerInterface $logger)
    {
        $this->client = $client;
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

        $contentBody = [
            'phone_number' => $leadPhoneNumber,
            'message' => $content,
        ];
        
        try {
            $customFields = [
                'category' => $lead->rv_category,
                'currency' => $lead->rv_currency,
            ];
        } catch (\Exception $e) {
            $customFields = [];
            
            $this->logger->error('get_custom_field_error', [
                'msg' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        try {
            $response = $this->client->post($this->configuration->getGatewayUrl(), [
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'body' => json_encode(array_merge($contentBody, $customFields)),
            ]);

            if (!in_array($response->getStatusCode(), [Response::HTTP_OK, Response::HTTP_CREATED])) {
                $this->logger->error('Sms not send', [
                    'response' => $response->getBody()->getContents(),
                    'body' => $contentBody,
                ]);

                throw new SmsGatewayException("SmsGateway couldn't send message: " . $response->getStatusCode());
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
