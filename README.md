# Yii2 Pulseem SDK

This package provides a simple way to use Pulseem for email and SMS sending.


## Installation
The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

First add this entry to the `repositories` section of your composer.json:

```
"repositories": [{
    ...
},{
    "type": "git",
    "url": "https://github.com/mipotech/yii2-pulseem.git"
},{
    ...
}],
```

then add this line:

```
"mipotech/yii2-pulseem": "dev-master",
```

to the `require` section of your `composer.json` file and perform a composer update.

## Configuration

Add the following section to the params file (@app/config/params.php):

```php
return [
    ...
    
    'pulseem' => [
        // Basic config
        'password' => '...',
        'username' => '...',
        //'endpoint' => '...', // Optionally configure a custom endpoint instead of the default
        
        // For email operations (optional)
        'fromEmail' => '...',
        'fromName' => '...'
        
        // For SMS operations (optional)
        'senderPhone' => '...',
    ],
    ...
];
```

That's it. The package is set up and ready to go.

## Usage

To create an instance of the SDK:

```php
use mipotech\pulseem\PulseemSdk;

$pulseem = new PulseemSdk();
```

### Sending a single email

Standard:

```php
$params = [
    'htmlBody' => '<p>Body here</p>',
    'subject' => 'Testing',
    'toEmail' => 'test@test.com',
];
$res = $pulseem->sendSingleEmail($params);
```

Using a Yii2 view:

```php
$params = [
    'subject' => 'Testing',
    'toEmail' => 'test@test.com',
];
$bodyTemplate = '@app/views/emails/customTeplate';
$bodyParams = [
    'model' => $model,
    'index' => $i,
]
$res = $pulseem->sendSingleEmail($params, $bodyTemplate, $bodyParams);
```

### Sending a group email with unique content for each message

This type of group email mandates that an array of `htmlBodies` be passed as part of `$params`.

```php
$params = [
    'htmlBodies' => [
        '<p>Body here 1</p>',
        '<p>Body here 2</p>',
    ],
    'subjects' => [
        'Testing 1',
        'Testing 2',
    ],
    'toEmails' => [
        'test1@test.com',
        'test2@test.com',
    ],
    'toNames' => [
        'Test User 1',
        'Test User 2',
    ],
];
$res = $pulseem->sendGroupEmail($params);
```

### Sending a group email with identical content for each messgae

This type of group emails support the same two options of either explicitly specifying the htmlBody or
using a Yii2 view.

```php
$params = [
    'htmlBody' => '<p>Body here</p>',
    'subject' => 'Testing',
    'toEmails' => [
        'test1@test.com',
        'test2@test.com',
    ],
    'toNames' => ['Test 1', 'Test 2'],
];
$res = $pulseem->sendGroupSameEmail($params);
```

### Sending a single SMS

```php
$res = $pulseem->sendSingleSms('+972541112222', 'Testing', [
    'delayDeliveryMinutes' => 1,    // optional
    'externalRef' => '111222',      // optional
]);
```
