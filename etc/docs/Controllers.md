[<< Go Back](Code.md)

## Controllers Standard

New controllers must extend from `App\Core\Infrastructure\Ui\Controllers\ApiController` abstract class.

Parent class has to implement the following methods:

- **exceptions**: Define a mapping of exceptions to HTTP status codes. Example:
````
protected function exceptions(): array
{
    return [
        ActivityReportNotFound::class => Response::HTTP_NOT_FOUND,
        EditActivityReportDenied::class => Response::HTTP_FORBIDDEN,
        ErrorUpdatingActivityReport::class => Response::HTTP_FORBIDDEN,
    ];
}
````

- **ask**: Executes a query use case through the QueryBus and returns a QueryResponse object. Example:

````php
$userResponse = $this->ask(new GetUserQuery($userId));
````

- **dispatch**: Executes a mutation use case through the CommandBus and returns a CommandResponse object. Example:

````php
$updateUserResponse = $this->dispatch(new UpdateUserPassword(
    $userId,
    $request->request->get('password')
));
````

- **dispatchTransactional**: Executes a mutation use case through the CommandTransactionalBus and returns a CommandResponse object. If any exception is thrown, all Doctrine transactions are roll-backed. Example:

````php
$updateUserResponse = $this->dispatchTransactional(new UpdateUserPassword(
    $userId,
    $request->request->get('password')
));
````

- **buildSimpleFilters**: Extracts and returns simple filters from the request parameters.


- **requestUserId**: Retrieves the user ID from the request headers or body. Throws an Unauthorized exception if the user ID is not found.


- **response**: Constructs and returns an ApiJsonResponse with the provided data, parameters, total count, and status.


- **guardBody**: Validates the request body against a JSON schema. Throws a BadRequest exception if the validation fails.
