<?php

namespace MauticPlugin\MauticSmsGatewayBundle\Services;

use Joomla\Http\Http;
use Mautic\LeadBundle\Entity\Lead;
use MauticPlugin\MauticSmsGatewayBundle\Helpers\MessageHelper;
use Psr\Log\LoggerInterface;

class MessageService
{

  const TOKEN_HEADER = 'X-CM-PRODUCTTOKEN';

  /**
   * @var Http
   */
  private $httpClient;

  /**
   * @var MessageHelper
   */
  private $helper;

  /**
   * @var LoggerInterface
   */
  private $logger;

  public function __construct(Http $connector, MessageHelper $helper, LoggerInterface $logger)
  {
    $this->httpClient = $connector;
    $this->helper = $helper;
    $this->logger = $logger;
  }

  public function sendMessage(string $contact_number_field, string $originalText, bool $change_lang_code, string $default_lang_code = '', bool $shorten_url = false, Lead $lead)
  {
    $message = $this->helper->getMessageText($lead, $originalText, $shorten_url);
    $number = $lead->$contact_number_field;
    $number = isset($number) ? $number : '';
    $number = preg_replace('/[^0-9+]+/', '', $number);
    if ($change_lang_code == true) {
      if (substr($number, 0, 2) == '06') {
        $number = $default_lang_code . substr($number, 1, strlen($number));
      }
    }


    [
      'cmSender' => $cmSender,
      'cmAccount' => $cmAccount,
      'cmGateway' => $cmGateway,
    ] = $this->helper->getKeys();

    $payload = [
      'recipients' => [['msisdn' => $number]],
      'body' => $message,
      'senders' => [$cmSender]
    ];

    $url = 'https://api.cm.com/messages/v1/accounts/' . $cmAccount . '/messages';
    $response = $this->httpClient->post($url, json_encode($payload), [self::TOKEN_HEADER => $cmGateway, 'Content-Type' => 'application/json'], 60);

    if (!in_array($response->code, [200, 201])) {
      $this->logger->error('SMS WITH CM.COM NOT SENT', array('response' => $response->body, 'body' => json_encode($payload)));
      throw new \OutOfRangeException('CM SMS service couldn\'t send message: ' . $response->code);
    }
  }
}
