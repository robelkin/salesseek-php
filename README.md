salesseek-php
=============

A simple PHP library for working with Salesseek (salesseek.net)

This is a reverse engineering of the calls their webapp makes, and so could be updated once they release a full API. This also means that it is a little difficult to do authentication etc quite right. Pull requests graciously accepted :)

### Usage

Because of the authentication method (cookie), I would recommend creating one instance of the Object and continuing to use that in multiple places if needed, to reduce the amount of authentication requests needed per session.

```
$salesseek = new SalesSeek($email, $password, $url, $client);
```

Where:
```
$email is the email address you use to log in to salesseek
$password is the password you use to log in
$url is the url for your salesseek account, with a https protocol and /api on the end e.g. https://sdtest.salesseek.net/api
$client is the first part of your salesseek url, in the example above it would be 'sdtest'
```