<?php

namespace mipotech\pulseem;

use Yii;
use yii\base\Component;
use yii\base\InvalidConfigException;

use SoapClient;

class PulseemSdk extends Component
{
    const DEFAULT_ENDPOINT = 'http://www.pulseem.co.il/Pulseem/pulseemSendservices.asmx?wsdl';

    const FROM_EMAIL = "no-replay@club50.co.il";
    const FROM_NAME = "קלאב 50";
    const PHONE_SENDER = "036939393";

    protected $endpoint;
    protected $fromEmail;
    protected $fromName;
    protected $password;
    protected $senderPhone;
    protected $username;

    /**
     * @inheritdoc
     */
    public function init()
    {
        $pulseemConfig = Yii::$app->params['pulseem'];
        if (empty($pulseemConfig) || !is_array($pulseemConfig)) {
            throw new InvalidConfigException("No Pulseem config array found in config/params.php");
        }

        if (empty($pulseemConfig['username'])) {
            throw new InvalidConfigException("No Pulseem username found in Pulseem config array in config/params.php");
        } elseif (empty($pulseemConfig['password'])) {
            throw new InvalidConfigException("No Pulseem password found in Pulseem config array in config/params.php");
        }

        $this->endpoint = $pulseemConfig['endpoint'];
        $this->fromEmail = $pulseemConfig['fromEmail'];
        $this->fromName = $pulseemConfig['fromName'];
        $this->password = $pulseemConfig['password'];
        $this->senderPhone = $pulseemConfig['senderPhone'];
        $this->username = $pulseemConfig['username'];
    }

    /**
     * Send a Pulseem group email with unique subject, body, and reference for each message
     *
     * @param array $params
     * @param string $bodyTemplate The view file to use as the email body
     * @param array $bodyParams The params to be sent to the view file for processing
     * @throws yii\base\InvalidConfigException
     * @return int The number of emails sent, or 0 in case of an error
     */
    public function sendGroupEmail(array $params): int
    {
        $res = false;

        // Basic config asserts
        if (empty($params['toEmails'])) {
            throw new InvalidConfigException("\$params[toEmails] must be set");
        } elseif (empty($params['subjects'])) {
            throw new InvalidConfigException("\$params[subjects] must be set");
        } elseif (empty($params['htmlBodies'])) {
            throw new InvalidConfigException("\$params[htmlBodies] must be set");
        }

        $fromEmail = $params['fromEmail'] ?: $this->fromEmail;
        $fromName = $params['fromName'] ?: $this->fromName;
        if (empty($fromEmail) || empty($fromName)) {
            throw new InvalidConfigException("fromEmail and fromName not set neither in config/params.php or in \$params");
        }

        // Inject empty external refs for each message if non specified
        $externalRefs = $params['externalRefs'];
        if (!empty($params['externalRefs'])) {
            $externalRefs = $params['externalRefs'];
        } else {
            $externalRefs = [];
            for ($i = 0; $i < count($params['toEmails']); $i++) {
                $externalRefs[] = '';
            }
        }

        $context = [
            'toEmails' => $params['toEmails'],
            'toNames' => $params['toNames'],
            'fromEmail' => $fromEmail,
            'fromName' => $fromName,
            'subject' => $params['subjects'],
            'HTML' => $params['htmlBodies'],
            'languageCode' => $params['languageCode'] ?: 0,
            'userID' => $this->username,
            'password' => $this->password,
            'externalRef' => $externalRefs,
        ];

        $soap = new SoapClient($this->endpointUrl, ["connection_timeout" => 1000]);
        $response = $soap->SendEmailsToGroup($context);
        $emailresponse = $response->SendEmailsToGroupResult;
        if (strtolower($emailresponse) == 'success') {
            $res = true;
        }

        return $res ? count($params['toEmails']) : 0;
    }

    /**
     *
     * @param array $params
     * @param string $bodyTemplate
     * @param string $bodyParam
     * @throws InvalidConfigException
     * @return int
     */
    public function sendGroupSameEmail(array $params, string $bodyTemplate = '', array $bodyParams = []): int
    {
        $res = false;

        if (empty($params['toEmails'])) {
            throw new InvalidConfigException("\$params[toEmails] must be set");
        } elseif (empty($params['toNames'])) {
            throw new InvalidConfigException("\$params[toNames] must be set");
        }

        if (!empty($bodyTemplate)) {
            $htmlBody = Yii::$app->controller->renderPartial($bodyTemplate, $bodyParams, true);
        } else {
            $htmlBody = $params['htmlBody'];
        }
        if (empty($params['htmlBody'])) {
            throw new InvalidConfigException("\$params[htmlBody] must be set is \$bodyTemplate is not passed");
        }

        $fromEmail = $params['fromEmail'] ?: $this->fromEmail;
        $fromName = $params['fromName'] ?: $this->fromName;
        if (empty($fromEmail) || empty($fromName)) {
            throw new InvalidConfigException("fromEmail and fromName not set neither in config/params.php or in \$params");
        }

        // Inject empty external refs for each message if non specified
        $externalRefs = $params['externalRefs'];
        if (!empty($params['externalRefs'])) {
            $externalRefs = $params['externalRefs'];
        } else {
            $externalRefs = [];
            for ($i = 0; $i < count($params['toEmails']); $i++) {
                $externalRefs[] = '';
            }
        }

        $context = [
            'toEmails' => $params['toEmails'],
            'toNames' => $params['toNames'],
            'fromEmail' => $fromEmail,
            'fromName' => $fromName,
            'subject' => $params['subject'],
            'HTML' => $htmlBody,
            'languageCode' => $params['languageCode'] ?: 0,
            'userID' => $this->username,
            'password' => $this->password,
            'externalRef' => $externalRefs,
        ];

        $soap = new SoapClient($this->endpointUrl, ["connection_timeout" => 1000]);
        $response = $soap->SendEmailToGroup($context);
        $emailResponse = $response->SendEmailToGroupResult;
        if (strtolower($emailResponse) == 'success') {
            $res = true;
        }

        return $res ? count($params['toEmails']) : 0;
    }

    /**
     * Send a single Pulseem email
     *
     * @param array $params
     * @param string $bodyTemplate
     * @param array $bodyParams
     * @throws InvalidConfigException
     * @return bool
     */
    public function sendSingleEmail(array $params, string $bodyTemplate = '', array $bodyParams = []): bool
    {
        $res = false;

        if (empty($params['toEmail'])) {
            throw new InvalidConfigException("\$params[toEmail] must be set");
        } elseif (empty($params['subject'])) {
            throw new InvalidConfigException("\$params[subject] must be set");
        }

        $toEmail = $params['toEmail'];
        $toName = $params['toName'];
        $externalRef = $params['externalRef'];
        $subject = $params['subject'];

        if (!empty($bodyTemplate)) {
            $htmlBody = Yii::$app->controller->renderPartial($bodyTemplate, $bodyParams, true);
        } else {
            $htmlBody = $params['htmlBody'];
        }
        if (empty($params['htmlBody'])) {
            throw new InvalidConfigException("\$params[htmlBody] must be set is \$bodyTemplate is not passed");
        }

        $fromEmail = $params['fromEmail'] ?: $this->fromEmail;
        $fromName = $params['fromName'] ?: $this->fromName;
        if (empty($fromEmail) || empty($fromName)) {
            throw new InvalidConfigException("fromEmail and fromName not set neither in config/params.php or in \$params");
        }

        $context = [
            'toEmail' => $toEmail,
            'toName' => $toName,
            'fromEmail' => $fromEmail,
            'fromName' => $fromName,
            'subject' => $subject,
            'HTML' => $htmlBody,
            'languageCode' => $params['languageCode'] ?: 0,
            'userID' => $this->username,
            'password' => $this->password,
            'externalRef' => $externalRef,
        ];

        $soap = new SoapClient($this->endpointUrl, ["connection_timeout" => 1000]);
        $response = $soap->SendEmail($context);
        $emailresponse = $response->SendEmailResult;
        if (strtolower($emailresponse) == 'success') {
            $res = true;
        }

        return $res;
    }

    /**
     * Send a single SMS message
     *
     * @param string $toNumber
     * @param string $textBody
     * @param array $extraParams
     * @return bool
     */
    public function sendSingleSms(string $toNumber, string $textBody, array $extraParams = []): bool
    {
        $res = false;

        $senderPhone = $extraParams['senderPhone'] ?: $this->senderPhone;
        if (empty($senderPhone)) {
            throw new InvalidConfigException("senderPhone not set in \$extraParams or in Pulseem config array in config/params.php");
        }

        $context= [
            'toNumber' => $toNumber,
            'fromNumber' => $senderPhone,
            'text' => $textBody,
            'delayDeliveryMinutes' => $extraParams['delayDeliveryMinutes'] ?: 0,
            'userID' => $this->username,
            'password' => $this->password,
            'reference' => $extraParams['externalRef'],
        ];

        $soap = new SoapClient($this->endpointUrl, ["connection_timeout" => 1000]);
        $response = $soap->SendSingleSMS($context);
        if ($response = $response->SendSingleSMSResult) {
            if (strtolower($response) == 'success') {
                $res = true;
            }
        }

        return $res;
    }

    /**
     * Determine the endpoint to use.
     * If not configured in the Pulseem config array in config/params.php,
     * it will be taken from the class default endpoint.
     *
     * @return string
     */
    protected function getEndpointUrl(): string
    {
        return $this->endpoint ?: static::DEFAULT_ENDPOINT;
    }
}
