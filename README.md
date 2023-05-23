Sms gateway plugin
==============

Mautic plugin https://github.com/playtini/mautic_base. Designed to send SMS messages through custom providers.
This plugin intercepts the message sending event and redirects it to `https://sms-gateway.p777.org`
https://github.com/playtini/sms_api_gateway The provider is determined on the gate side based on the message category
that is passed as a parameter. Currently implemented integrations:
- [Twilio](https://www.twilio.com/)
- [Prostor](https://prostor-sms.ru/)

Run
---
[Mautic plugin's installation](https://developer.mautic.org/#plugins)

To communicate with the sms-gate, the Mautic must have an env with its URL

    MAUTIC_CONFIG_SMS_API_GATE_URL=https://sms-gateway.p777.org

Usage
-----

Params passed to sms gate example:

    'phone_number' => '+380501111111',
    'message' => 'test',
    'category' => 'Prostor',
    'currency' => 'RUB',
    'custom_sms' => false
