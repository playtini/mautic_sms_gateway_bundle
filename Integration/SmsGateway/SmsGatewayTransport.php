<?php

namespace MauticPlugin\MauticSmsGatewayBundle\Integration\SmsGateway;

use Mautic\LeadBundle\Entity\Lead;
use Mautic\SmsBundle\Sms\TransportInterface;
use Psr\Log\LoggerInterface;

class SmsGatewayTransport implements TransportInterface
{
    private LoggerInterface $logger;

    /**
     * SmsGatewayTransport constructor.
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }
    
    public function sendSms(Lead $lead, $content): bool
    {
        $this->logger->info('send_sms_transport', [
            'msg' => 'Sms send via event',
            'lead' => $lead->getId(),
            'textMessage' => $content,
        ]);

        return true;
    }
}
