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
            "url":"https://bitbucket.org/fossil-ecommerce/lb-document-generator.git"
      }
 ]
```

Run the command:
```
composer require --dev fossil-ecommerce/lb-document-generator:dev-master
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
        "repo.magento.com": {
            "username": "***",
            "password": "***"
        },
        "composer.amasty.com": {
            "username": "***",
            "password": "***"
        },
        "github.com": {
            "username": "***",
            "password": "***"
        }
    },
    "bitbucket-oauth": {
        "bitbucket.org": {
            "consumer-key": "bBd6htKsuHTEt7J7LJ",
            "consumer-secret": "7GYp5GWKRX5CqkYANBCLX7YgdNhgwY2y",
            "access-token": "DdMZAiPklOHVKaFnB6NbszbHHCUdzmutc-_xwr02Hp6BK52y8uVuzuWPimpo58fipqhxMcaxyiNZvnji4rg=",
            "access-token-expiration": 1538114983
        }
    }
}
```

Changelog
----------

* 1.0.0 - First release