# Sms gateway plugin

Плагин для маутика https://github.com/playtini/mautic_base. Предназначен для отправки смс-сообщений через кастомных провайдеров. Данный плагин перехватывает событие отправки сообщения и перенаправляет его на `https://sms-api-gateway.n0d.dev` https://github.com/playtini/sms_api_gateway Определение провайдера происходит на стороне гейта на основании категории сообщения которая передается параметром. На текущий момент имплементированы интеграции:
- [Twilio](https://www.twilio.com/)
- [Prostor](https://prostor-sms.ru/)

[Структура плагинов и установка](https://developer.mautic.org/#plugins)

Для связи с смс-гейтом маутик должен иметь енв с его урлом:

    MAUTIC_CONFIG_SMS_API_GATE_URL=https://sms-api-gateway.n0d.dev
