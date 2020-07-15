Odoo bundle
===========

[![Build Status](https://travis-ci.org/Ang3/odoo-bundle.svg?branch=master)](https://travis-ci.org/Ang3/odoo-bundle) 
[![Latest Stable Version](https://poser.pugx.org/ang3/odoo-bundle/v/stable)](https://packagist.org/packages/ang3/odoo-bundle) 
[![Latest Unstable Version](https://poser.pugx.org/ang3/odoo-bundle/v/unstable)](https://packagist.org/packages/ang3/odoo-bundle) 
[![Total Downloads](https://poser.pugx.org/ang3/odoo-bundle/downloads)](https://packagist.org/packages/ang3/odoo-bundle)

This bundle is a Symfony integration of packages 
[ang3/php-odoo-api-client](https://packagist.org/packages/ang3/php-odoo-api-client) and 
 [ang3/php-odoo-orm](https://packagist.org/packages/ang3/php-odoo-api-client).

**Main features:**
- Client registry
- Object relational mapping (ORM)
- Debugging commands
- Record validator

Documentation of both packages:

| Package | Documentation |
| --- | --- |
| ang3/php-odoo-api-client | [https://github.com/Ang3/php-odoo-api-client](https://github.com/Ang3/php-odoo-api-client)
| ang3/php-odoo-orm | [https://github.com/Ang3/php-odoo-orm](https://github.com/Ang3/php-odoo-orm)

Installation
============

Step 1: Download the Bundle
---------------------------

Open a command console, enter your project directory and execute the
following command to download the latest stable version of this bundle:

```console
$ composer require ang3/odoo-bundle
```

This command requires you to have Composer installed globally, as explained
in the [installation chapter](https://getcomposer.org/doc/00-intro.md)
of the Composer documentation.

Step 2: Configure your app
--------------------------

Create the file ```config/packages/ang3_odoo.yaml``` and paste the configuration below:

```yaml
# app/config/config.yml or config/packages/ang3_odoo.yaml
ang3_odoo:
  default_connection: default
  default_logger: '<logger_service_name>' # Instance of \Psr\Log\LoggerInterface (optional)
  # If set, the default logger is used if a connection hasn't one
  connections:
    default:
      url: '%env(resolve:ODOO_API_URL)%'
      database: '%env(resolve:ODOO_API_DATABASE)%'
      user: '%env(resolve:ODOO_API_USERNAME)%'
      password: '%env(resolve:ODOO_API_PASSWORD)%'
      logger: '<logger_service_name>' # Instance of \Psr\Log\LoggerInterface (optional)
  orm:
    enabled: false
```

Finally, set needed  ```.env``` vars to your project:

```.dotenv
ODOO_API_URL=
ODOO_API_DATABASE=
ODOO_API_USERNAME=
ODOO_API_PASSWORD=
```

#### Work with multiple connections (optional)

You can add more connection under section ```ang3_odoo.connections```.
Here is an example for another connection:

```yaml
# app/config/config.yml or config/packages/ang3_odoo.yaml
ang3_odoo:
  # ...
  connections:
    default:
      # ...
    other_connection_name:
      url: '...'
      database: '...'
      user: '...'
      password: '...'
      logger: '<logger_service_name>' # optional
```

The parameter ```default_connection``` is used to define the default connection to use.

Usage
=====

Registry
--------

If you want to work with all your configured clients, then you may want to get the *registry*. 
It stores all configured clients by connection name. You can get it by dependency injection:

```php
use Ang3\Bundle\OdooBundle\ClientRegistry;

class MyService
{
    private $clientRegistry;

    public function __construct(ClientRegistry $clientRegistry)
    {
        $this->clientRegistry = $clientRegistry;
    }
}
```

The registry contains three useful methods:
- ```public function set(string $connectionName, Client $client): self``` Set a client by connection name.
- ```public function get(string $connectionName): Client``` Get the client of a connection. A ```\LogicException``` is thrown if the connection was not found.
- ```public function has(string $connectionName): bool``` Check if a connection exists by name.

If you don't use autowiring, you must pass the service as argument of your service:

```yaml
# app/config/services.yml or config/services.yaml
# ...
MyClass:
    arguments:
        $clientRegistry: '@ang3_odoo.client_registry'
```

Clients
-------

It could be useful to get a client directly without working with the registry.

For example, the get the default client by autowiring, use the argument 
```Ang3\Component\Odoo\Client $client```:

```php
use Ang3\Component\Odoo\Client;

class MyService
{
    private $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }
}
```

If the connection name is ```foo_bar```, then the autowired argument is 
```Ang3\Component\Odoo\Client $fooBarClient```.

- Run the command ```php bin/console debug:autowiring Client``` to get the list of autowired clients.

Of course if you don't use autowiring, you must pass the service as argument of your service:

```yaml
# app/config/services.yml or config/services.yaml
# ...
App\MyService:
    arguments:
        $client: '@ang3_odoo.client.<connection_name>' # Or '@ang3_odoo.client' for the default connection
```

For each client, the bundle creates a public alias following this naming convention: 
```ang3_odoo.client.<connection_name>```.

ORM
---

To enable ORM features, you must edit the file ```config/packages/ang3_odoo.yaml``` to configure it:

```yaml
ang3_odoo:
  # ...
  orm:
    enabled: true # Do not forget to enable the ORM
    managers:
      default: # connection name to manage
        paths: # List of directories where your Odoo objects are stored
          - '%kernel_project_dir%/src/Odoo/Entity'
```

#### Usage

*Writing in progress*

Validator
---------

This bundle provides a validator according to the package 
[symfony/validator](https://symfony.com/doc/current/components/validator.html) 
to validate a record by ID, domains and/or connection. It resides to a basic annotation.

Here is an example of an object storing the ID of a company and invoice:

```php
use Ang3\Bundle\OdooBundle\Validator\Constraints\OdooRecord;

class MyEntity
{
    /**
     * @var int
     *
     * @OdooRecord("res.company")
     * ...
     */
    private $companyId;

    /**
     * @var int
     *
     * @OdooRecord(model="account.move", domains="expr.eq('company_id.id', this.companyId)", connection="default")
     * ...
     */
    private $invoiceId;
}
```

Here is the list of all options you can pass to the annotation:
- ```model``` (**required** string) The model name of the record.
- ```domains``` (string) An expression which evaluation must returns valid client criteria.
- ```connection``` (string) the name of the connection to use
    - By default the ```default``` connection is used.
- ```typeErrorMessage``` (string) The error message if the value is not a positive integer 
    - By default the message is: ```This value must be a positive integer.```
- ```notFoundMessage``` (string) The message if the record was not found 
    - By default the message is: ```The record of ID {{ model_id }} from "{{ model_name }}" was not found.```

As you can see, the validator uses both 
[symfony/expression-language](https://symfony.com/doc/current/components/expression_language.html) 
and the expression builder provided with the client. 
By this way, you can filter allowed records easily.
 
Here are the variable passed to the evaluated expression:
- ```expr``` the expression builder
- ```this``` the object that the property/getter belongs to
- ```user``` the user of the request ```Symfony\Component\Security\Core\User\UserInterface|null```

Upgrades & updates
==================

### v0.1.0 (beta-release)

- Client registry
- Beta ORM
    - Registry
    - Configuration
    - Cache
- Record validator