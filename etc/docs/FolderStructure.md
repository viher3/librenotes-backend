[<< Go Back](Readme.md)

## Folder Structure Standards

Code is stored in the **src/** folder. Each module has its own folder in the **/src/{{ModuleName}}** folder.

Inside a module folder, it should contain the following folders (*following the Domain Driven Design folder structure*):

#### Application
- **/src/{{module_name}}/Application/**: Application services (a.k.a use cases)
    - **Use case** (Example: Create/Update/Search ...)
        -  *UseCaseCommand.php | UseCaseQuery.php*
        -  *UseCaseHandler.php*

  ````
  # Create user use case
  /src/User/Application/Create/
  
     -  CreateUserCommand.php
     -  CreateUserHandler.php
  ````  
  ````
  # Get user details use case
  /src/User/Application/Detail/
  
     -  UserDetailQuery.php
     -  UserDetailHandler.php
     -  UserDetailResponse.php
  ````

#### Domain
- **/src/{{module_name}}/Domain/**: Domain code (entities, value objects, domain services, repository interfaces, exceptions, etc.)

    - **Event**
      ````
      UserCreated.php
      UserUpdated.php
      UserDeleted.php
      ````
    - **Model**: Domain models, entities, DTOs, ValueObjects and Collections.
      ````
      User.php          # Entity
      Users.php         # Collection (collection of entities)
      UserDetail.php    # Model
      Email.php         # ValueObject
      Password.php      # ValueObject
      ````
    - **Exception**
      ````
      UserNotFoundException.php
      ```` 
    - **Repository**: Repository interfaces
      ````
      UserRepository.php
      ```` 
    - **Services**
      ````
      UserFinder.php
      UserCreator.php
      ````
    - **Specification**
      ````
      UniqueUsernameSpecification.php 
      UniqueEmailSpecification.php
      ````

#### Infrastructure
- **/src/{{module_name}}/Infrastructure/**: Infrastructure code (repositories, services, doctrine entity definitions, etc.)
    - **DependencyInjection**
        - **Config**
            - **application.yaml**:
                - If we need to override application service dependencies, we create the `application.yaml` file and set dependency specifications.
              ````
              services:
              
               App\Api\Modules\User\Application\Detail\GetUserDetail:
                 arguments:
                    - '@App\Api\Modules\App\Repository\RedisUserRepository'
                 public: true
              ````
            - **doctrine.yaml**
                - In this file we have to define the module's Doctrine entity mapping and the ValueObject Doctrine types.
              ````
              doctrine:
                dbal:
                  types:
              
                    user_id: App\User\Infrastructure\Persistence\Doctrine\Type\UserIdType
                    user_email: App\User\Infrastructure\Persistence\Doctrine\Type\UserEmailType
              
                orm:
                  mappings:
                    User:
                      type: xml
                      dir: '%kernel.project_dir%/src/User/Infrastructure/Persistence/Doctrine/Definition'
                      prefix: App\User\Domain\Model
                      is_bundle: false
              ````
                - Doctrine types are not services: they must stay excluded from the `App\` autowiring
                  resource in `config/services.yaml` (`- '../src/*/Infrastructure/Persistence/Doctrine/Type/'`).
            - **module.yaml**
                - In this file we have to import all module config yaml files.
              ````
              imports:
                 - { resource: application.yaml }
                 - { resource: domain.yaml }
                 - { resource: infrastructure.yaml }
                 - { resource: doctrine.yaml }
              ````
                - This file has to be imported in the `{projectDir}/config/modules.yaml` file:
              ````
              imports:
                - { resource: "../src/User/Infrastructure/DependencyInjection/config/module.yaml"}
                - { resource: "../src/ExampleModule/Infrastructure/DependencyInjection/config/module.yaml"}
              ````
            - **routing.yaml**
                - In this file we have to define the module's controller routes.
              ````
              users.detail:
                 path: /users/{id}
                 methods: [ GET ]
                 defaults: { _controller: App\User\Infrastructure\Ui\Controller\UserDetailController }
              ````
                - This file has to be imported in the `{projectDir}/config/routes.yaml` file:
              ````
              controllers:
                 resource:
                    path: ../src/Controller/
                    namespace: App\Api
                    type: attribute
      
              users:
                  resource: ../src/User/Infrastructure/DependencyInjection/config/routing.yaml
              ````

    - **Persistence/Doctrine**
        - **Definition**
      ````
      User.orm.xml
      ````
        - **Repository**
      ````
        - DoctrineUserRepository.php
      ````
        - **Specification**
      ````
        - DoctrineUniqueUsernameSpecification.php
        - DoctrineUniqueEmailSpecification.php
      ````
        - **Type**: Doctrine DBAL types that convert domain ValueObjects to/from their column
          representation, one per VO registered under `dbal.types` (see `doctrine.yaml` above).
          Extend the shared abstract types in `App\Core\Infrastructure\Persistence\Doctrine\Type\`
          (`AbstractStringValueObjectType`, `AbstractIntValueObjectType`, `AbstractBoolValueObjectType`,
          `AbstractDateTimeValueObjectType`, `AbstractTextValueObjectType`, `AbstractJsonValueObjectType`).
      ````
        - UserIdType.php
        - UsernameType.php
        - UserEmailType.php
      ````

### Full folder tree example:

````
/src/User/
├── Application
│   ├── Create
│   │   ├── CreateUserCommand.php
│   │   └── CreateUserHandler.php
│   └── Detail
│       ├── UserDetailQuery.php
│       ├── UserDetailHandler.php
│       └── UserDetailResponse.php
├── Domain
│   ├── Event
│   │   ├── UserCreated.php
│   │   ├── UserUpdated.php
│   │   └── UserDeleted.php
│   ├── Exception
│   │   └── UserNotFoundException.php
│   ├── Model
│   │   └── User.php            # Entity
│   │   └── UserDetail.php      # Model
│   │   └── UserEmail.php       # ValueObject
│   │   └── Users.php           # Collection 
│   ├── Repository
│   │   └── UserRepository.php
│   ├── Services
│   │   ├── UserFinder.php
│   │   └── UserCreator.php
│   ├── Specification
│   │   ├── UniqueUsernameSpecification.php
│   │   └── UniqueEmailSpecification.php
├── Infrastructure
│   ├── DependencyInjection
│   │   └── Config
│   │       ├── application.yaml
│   │       ├── doctrine.yaml
│   │       ├── module.yaml
│   │       └── routing.yaml
│   ├── Persistence
│   │   └── Doctrine
│   │       ├── Definition
│   │       │   └── User.orm.xml
│   │       ├── Repository
│   │       │   └── DoctrineUserRepository.php
│   │       ├── Specification
│   │       │   ├── DoctrineUniqueUsernameSpecification.php
│   │       │   └── DoctrineUniqueEmailSpecification.php
│   │       └── Type
│   │           ├── UserIdType.php
│   │           ├── UsernameType.php
│   │           └── UserEmailType.php
└── Ui
    └── Controller
        └── UserDetailController.php
````

### Other and mixed code:

- **/src/Controller/**: Contains the controllers.
- **/src/EventSubscriber/**: Contains some Symfony subscribers. Mainly subscribers that handles Api responses and application exceptions.
- **/src/Shared/**: Contains shared code for all the application. Here you can find new code and some legacy code.
