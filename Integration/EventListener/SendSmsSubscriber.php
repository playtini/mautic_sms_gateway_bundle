<?php

namespace MauticPlugin\MauticSmsGatewayBundle\Integration\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Psr7\Request;
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
    const CUSTOM_SMS_IDS = [23, 24];

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
            $this->send($lead, $sms, $event->getStatId());
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

    private function send(Lead $lead, Sms $sms, $statId = null): void
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
                'custom_sms' => false,
                'player_id' => $lead->rv_uid,
                'mautic_uid' => $statId,
                'mautic_name' => $sms->getName(),
                'project' => 'VSTK-KZ',
            ];

            if (in_array($sms->getId(), self::CUSTOM_SMS_IDS)) {
                $contentBody['custom_sms'] = true;
                $contentBody['operator_name'] = $lead->operator_name;
            }

            $request  = new Request(
                'POST',
                $this->configuration->getGatewayUrl(),
                [
                    'Content-Type' => 'application/json',
                ],
                json_encode($contentBody)
            );

            $response = $this->client->send($request);

            if (!in_array($response->getStatusCode(), [Response::HTTP_OK, Response::HTTP_CREATED])) {
                $this->logger->error('sms_gateway.send', [
                    'response' => $response->getBody()->getContents(),
                    'body' => $contentBody,
                ]);
            }
        } catch (\Throwable $e) {
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
