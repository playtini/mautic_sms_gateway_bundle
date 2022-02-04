<?php

namespace MauticPlugin\MauticSmsGatewayBundle\Integration\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Helper\TokenHelper;
use Mautic\SmsBundle\Entity\Sms;
use Mautic\SmsBundle\Event\SmsSendEvent;
use Mautic\SmsBundle\SmsEvents;
use MauticPlugin\MauticSmsGatewayBundle\Integration\Exceptions\SmsGatewayException;
use MauticPlugin\MauticSmsGatewayBundle\Integration\SmsGateway\Configuration;
use Psr\Http\Client\ClientInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;

class SendSmsSubscriber implements EventSubscriberInterface
{
    const CUSTOM_SMS_ID = 21;

    private ClientInterface $client;

    private Configuration $configuration;

    private EntityManagerInterface $em;

    private LoggerInterface $logger;

    public function __construct(
        ClientInterface $client,
        Configuration $configuration,
        EntityManagerInterface $em,
        LoggerInterface $logger
    )
    {
        $this->client = $client;
        $this->configuration = $configuration;
        $this->em = $em;
        $this->logger = $logger;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            SmsEvents::SMS_ON_SEND => 'smsOnSend',
        ];
    }

    public function smsOnSend(SmsSendEvent $event): void
    {
        $lead = $event->getLead();

        $sms = $this->checkRequiredConditionsAndGetSms($event->getSmsId());

        if ($sms) {
            $this->send($lead, $sms);
        }
    }

    private function checkRequiredConditionsAndGetSms(int $smsId): ?Sms
    {
        /** @var Sms $sms */
        $sms = $this->em->getRepository(Sms::class)->findOneBy(['id' => $smsId]);
        if (!$sms) {
            $this->logger->error('sms_gateway.not_found_message', [
                'msg' => 'Message not found',
                'sms_id' => $smsId,
            ]);

            return null;
        }

        $category = $sms->getCategory();
        if (!$category) {
            $this->logger->error('sms_gateway.empty_category', [
                'msg' => 'Message category is empty',
                'sms_id' => $smsId,
            ]);

            return null;
        }

        return $sms;
    }

    private function send(Lead $lead, Sms $sms): void
    {
        $leadPhoneNumber = $lead->getLeadPhoneNumber();

        if (null === $leadPhoneNumber) {
            $this->logger->error('sms_gateway.send', [
                'msg' => 'Lead phone number not found',
                'lead_id' => $lead->getId(),
            ]);

            return;
        }

        try {
            $contentBody = [
                'phone_number' => $leadPhoneNumber,
                'message' => $this->contentTokenReplace($lead, $sms->getMessage()),
                'category' => $sms->getCategory()->getTitle(),
                'currency' => $lead->rv_currency,
                'custom_sms' => false
            ];

            if ($sms->getId() == self::CUSTOM_SMS_ID) {
                $contentBody['custom_sms'] = true;
            }

            $response = $this->client->post($this->configuration->getGatewayUrl(), [
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'body' => json_encode($contentBody),
            ]);

            if (!in_array($response->getStatusCode(), [Response::HTTP_OK, Response::HTTP_CREATED])) {
                $this->logger->error('sms_gateway.send', [
                    'response' => $response->getBody()->getContents(),
                    'body' => $contentBody,
                ]);
            }
        } catch (SmsGatewayException $e) {
            $this->logger->error('sms_gateway.send', [
                'msg' => $e->getMessage(),
                'params' => $contentBody,
            ]);
        }
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

    public function contentTokenReplace(Lead $lead, string $content)
    {
        $tokens = array_merge(
            TokenHelper::findLeadTokens($content, $lead->getProfileFields()),
        );

        return str_replace(array_keys($tokens), array_values($tokens), $content);
    }
}