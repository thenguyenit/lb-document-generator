#Magento2 LB Document Generator module 
It's using to generate the Shipment/Invoice and return document,
Then It will be used to create the Shipment/Invoice and Return document on Logic Broker

Installation Instructions
--------------------------
Add these lines to the composer.json of your project

```
 "repositories":[
      ...
     "fossil-ecommerce": {
            "type": "vcs",
            "url":"git@github.com:thenguyenit/lb-document-generator.git"
      }
 ]
```

Run the command:
```
composer require --dev fossil-ecommerce/lb-document-generator
```
Or
Add these lines to the composer.json of your project and run composer install

```
"require":{
    ...
    "fossil-ecommerce/lb-document-generator": "*"
 }
```

Keys in: Bitbutket-Oauth or adding these lines to auth.json
```
{
    "http-basic": {
        ...
        "github.com": {
            "username": "***",
            "password": "***"
        }
    }
}
```

Changelog
----------

* 1.0.0 - First release
